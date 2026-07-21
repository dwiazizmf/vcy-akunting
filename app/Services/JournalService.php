<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Accounting\Ledger;
use App\Models\Expenses\Payment;
use App\Models\Settings\Company;
use App\Models\Incomes\Invoice;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Generate unique journal number: JRN-YYYY-XXXX
     */
    public function generateJournalNumber(string $prefix = 'JRN'): string
    {
        $year = now()->year;
        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        $last = Journal::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('journal_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('journal_number', 'desc')
            ->value('journal_number');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) end($parts) + 1;
        }

        return "{$prefix}-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment receipt number: RCP-YYYY-XXXX
     */
    public function generatePaymentNumber(): string
    {
        $year = now()->year;
        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        $last = \App\Models\Expenses\Payment::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('payment_number', 'like', "RCP-{$year}-%")
            ->orderBy('payment_number', 'desc')
            ->value('payment_number');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) end($parts) + 1;
        }

        return "RCP-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create journal when Invoice is posted.
     *
     * Entry:
     *   DEBIT  customer.account_id     = invoice.grand_total
     *   CREDIT invoice.account_id      = invoice.subtotal  (revenue)
     *   CREDIT tax.account_id          = tax_amount (per tax in header_tax_details)
     */
    public function createInvoiceJournal(Invoice $invoice): Journal
    {
        \App\Helpers\PeriodLockHelper::validateDate($invoice->invoiced_at);
        $companyId = $invoice->company_id;

        // Load customer with account
        $customer = \App\Models\Incomes\Customer::withoutGlobalScopes()->find($invoice->customer_id);

        if (!$customer?->account_id) {
            throw new \RuntimeException("Customer [{$invoice->customer_name}] belum memiliki COA. Jalankan CustomerAccountSeeder terlebih dahulu.");
        }

        if (!$invoice->account_id) {
            throw new \RuntimeException("Invoice [{$invoice->invoice_number}] belum memiliki akun pendapatan. Pilih akun pendapatan terlebih dahulu.");
        }

        return DB::transaction(function () use ($invoice, $customer, $companyId) {
            // 1. Create Journal header
            $invoiceText = $invoice->invoice_text ?? $invoice->invoice_number;
            $journal = Journal::create([
                'company_id'     => $companyId,
                'journal_number' => $this->generateJournalNumber('INV'),
                'date'           => $invoice->invoiced_at,
                'reference'      => $invoice->invoice_number,
                'description'    => "Invoice: {$invoiceText} - {$invoice->customer_name}",
                'status'         => 'posted',
                'is_manual'      => false,
                'posted_at'      => now(),
                'posted_by'      => auth()->id(),
            ]);

            $grandTotal = (float) $invoice->grand_total; // grand total (subtotal - discount + tax)
            $subtotal   = (float) $invoice->subtotal;     // raw subtotal before discount/tax
            $discountTotal = (float) ($invoice->discount_amount ?? 0);

            // 2. DEBIT Customer COA (full grand total)
            Ledger::create([
                'company_id'       => $companyId,
                'journal_id'       => $journal->id,
                'account_id'       => $customer->account_id,
                'contact_id'       => $customer->id,
                'ledgerable_type'  => Invoice::class,
                'ledgerable_id'    => $invoice->id,
                'debit'            => $grandTotal,
                'credit'           => 0,
                'description'      => "Piutang - {$invoice->customer_name}",
            ]);

            // 3. Process discounts — if discount COA is set, debit to discount account,
            //    otherwise reduce revenue credit directly.
            $revenueCredit = $subtotal; // start full, will be reduced if no dedicated discount account
            $headerDiscounts = is_array($invoice->header_discount_details) ? $invoice->header_discount_details : [];

            foreach ($headerDiscounts as $discountDetail) {
                $discountAmt = (float) ($discountDetail['amount'] ?? 0);
                if ($discountAmt <= 0) continue;

                $discountId = $discountDetail['discount_id'] ?? $discountDetail['id'] ?? null;
                $discountRecord = null;

                if ($discountId) {
                    $discountRecord = \DB::table('discounts')
                        ->where('id', $discountId)
                        ->whereNotNull('account_id')
                        ->first();
                }
                if (!$discountRecord) {
                    $discountRecord = \DB::table('discounts')
                        ->where('name', $discountDetail['name'] ?? '')
                        ->whereNotNull('account_id')
                        ->first();
                }

                if ($discountRecord?->account_id) {
                    // Debit the dedicated discount/contra-revenue account
                    Ledger::create([
                        'company_id'       => $companyId,
                        'journal_id'       => $journal->id,
                        'account_id'       => $discountRecord->account_id,
                        'contact_id'       => $customer->id,
                        'ledgerable_type'  => Invoice::class,
                        'ledgerable_id'    => $invoice->id,
                        'debit'            => $discountAmt,
                        'credit'           => 0,
                        'description'      => ($discountDetail['name'] ?? 'Diskon') . " - {$invoiceText}",
                    ]);
                    // Revenue stays full because discount is separately accounted for
                } else {
                    // No COA for this discount → net it from revenue credit
                    $revenueCredit -= $discountAmt;
                }
            }

            // 4. CREDIT Revenue Account (net after discounts that have no dedicated account)
            Ledger::create([
                'company_id'       => $companyId,
                'journal_id'       => $journal->id,
                'account_id'       => $invoice->account_id,
                'contact_id'       => $customer->id,
                'ledgerable_type'  => Invoice::class,
                'ledgerable_id'    => $invoice->id,
                'debit'            => 0,
                'credit'           => $revenueCredit,
                'description'      => "Pendapatan - {$invoiceText}",
            ]);


            // 5. CREDIT Tax accounts (from header_tax_details)
            $headerTaxes = is_array($invoice->header_tax_details) ? $invoice->header_tax_details : [];
            foreach ($headerTaxes as $taxDetail) {
                $taxAmount = (float) ($taxDetail['amount'] ?? 0);
                if ($taxAmount <= 0) continue;

                // Lookup tax COA: prefer by id, fallback by name
                $taxId = $taxDetail['tax_id'] ?? $taxDetail['id'] ?? null;
                $taxRecord = null;

                if ($taxId) {
                    $taxRecord = \DB::table('taxes')
                        ->where('id', $taxId)
                        ->whereNotNull('account_id')
                        ->first();
                }

                if (!$taxRecord) {
                    // Fallback: search by exact name, no orWhere to avoid ambiguity
                    $taxRecord = \DB::table('taxes')
                        ->where('name', $taxDetail['name'] ?? '')
                        ->whereNotNull('account_id')
                        ->first();
                }

                if (!$taxRecord?->account_id) {
                    throw new \RuntimeException(
                        "Pajak [{$taxDetail['name']}] belum memiliki Akun COA. "
                        . "Silakan atur COA pajak di menu Settings > Pajak sebelum posting."
                    );
                }

                Ledger::create([
                    'company_id'       => $companyId,
                    'journal_id'       => $journal->id,
                    'account_id'       => $taxRecord->account_id,
                    'contact_id'       => $customer->id,
                    'ledgerable_type'  => Invoice::class,
                    'ledgerable_id'    => $invoice->id,
                    'debit'            => 0,
                    'credit'           => $taxAmount,
                    'description'      => ($taxDetail['name'] ?? 'Tax') . " - {$invoice->invoice_number}",
                ]);
            }

            // 6. Update invoice: mark as posted
            $invoice->update([
                'isPosted'             => true,
                'invoice_status_code'  => 'posted',
            ]);

            return $journal;
        });
    }

    /**
     * Unpost invoice journal.
     */
    public function unpostInvoiceJournal(Invoice $invoice): void
    {
        \App\Helpers\PeriodLockHelper::validateDate($invoice->invoiced_at);

        DB::transaction(function () use ($invoice) {
            $journalId = \App\Models\Accounting\Ledger::where('ledgerable_type', Invoice::class)
                ->where('ledgerable_id', $invoice->id)
                ->value('journal_id');
                
            if ($journalId) {
                // Delete ledgers first to be safe (softDeletes or hard deletes)
                \App\Models\Accounting\Ledger::where('journal_id', $journalId)->get()->each->forceDelete();
                Journal::where('id', $journalId)->forceDelete();
            }

            $invoice->update([
                'isPosted'             => false,
                'invoice_status_code'  => 'draft',
            ]);
        });
    }

    /**
     * Create journal when Payment is received.
     *
     * Entry:
     *   DEBIT  bank_account.account_id  = payment.total_amount
     *   CREDIT customer.account_id      = total_amount - overpayment
     *   CREDIT titipan_account          = overpayment_amount (if any)
     */
    public function createPaymentJournal(Payment $payment): Journal
    {
        \App\Helpers\PeriodLockHelper::validateDate($payment->paid_at);
        $companyId = $payment->company_id;

        $payment->load(['bankAccount', 'invoices.invoice.customer']);

        $bankAccount = $payment->bankAccount;
        $customer    = $payment->invoices->first()?->invoice?->customer;

        if (!$bankAccount?->account_id) {
            throw new \RuntimeException("Bank account belum memiliki COA.");
        }
        if (!$customer?->account_id) {
            throw new \RuntimeException("Customer [{$payment->customer_name}] belum memiliki COA.");
        }

        return DB::transaction(function () use ($payment, $bankAccount, $customer, $companyId) {
            $journal = Journal::create([
                'company_id'     => $companyId,
                'journal_number' => $this->generateJournalNumber('PAY'),
                'date'           => $payment->paid_at,
                'reference'      => $payment->payment_number,
                'description'    => "Pembayaran: {$payment->payment_number} - {$payment->customer_name}",
                'status'         => 'posted',
                'is_manual'      => false,
                'posted_at'      => now(),
                'posted_by'      => auth()->id(),
            ]);

            $totalAmount       = (float) $payment->total_amount;
            $overpaymentAmount = (float) $payment->overpayment_amount;
            $taxAmount         = (float) ($payment->tax_amount ?? 0);
            $arAmount          = $totalAmount - $overpaymentAmount - $taxAmount;

            if (!empty($payment->adjustments)) {
                foreach ($payment->adjustments as $adj) {
                    if ($adj['type'] === 'addition') {
                        $arAmount -= $adj['amount'];
                    } else {
                        $arAmount += $adj['amount'];
                    }
                }
            }

            // DEBIT Bank/Cash (full amount received)
            Ledger::create([
                'company_id'  => $companyId,
                'journal_id'  => $journal->id,
                'account_id'  => $bankAccount->account_id,
                'contact_id'  => $customer->id,
                'ledgerable_type' => Payment::class,
                'ledgerable_id'   => $payment->id,
                'debit'       => $totalAmount,
                'credit'      => 0,
                'description' => "{$bankAccount->name} - {$payment->payment_number}",
            ]);

            // CREDIT Customer COA (AR amount)
            Ledger::create([
                'company_id'  => $companyId,
                'journal_id'  => $journal->id,
                'account_id'  => $customer->account_id,
                'contact_id'  => $customer->id,
                'ledgerable_type' => Payment::class,
                'ledgerable_id'   => $payment->id,
                'debit'       => 0,
                'credit'      => $arAmount,
                'description' => "Pelunasan piutang - {$payment->customer_name}",
            ]);

            // CREDIT Tax Payable (legacy if taxAmount > 0)
            if ($taxAmount > 0 && $payment->tax_id) {
                $taxRecord = \DB::table('taxes')->where('id', $payment->tax_id)->first();
                $taxAccountId = $taxRecord?->account_id;
                
                // Fallback to "Pajak" or "Hutang" if not set in taxes table
                if (!$taxAccountId) {
                    $taxAccountId = Account::where('company_id', $companyId)->where('name', 'like', '%Pajak%')->first()?->id;
                }
                if (!$taxAccountId) {
                    $taxAccountId = Account::where('company_id', $companyId)->where('name', 'like', '%Hutang%')->first()?->id;
                }
                
                if ($taxAccountId) {
                    Ledger::create([
                        'company_id'  => $companyId,
                        'journal_id'  => $journal->id,
                        'account_id'  => $taxAccountId,
                        'contact_id'  => $customer->id,
                        'ledgerable_type' => Payment::class,
                        'ledgerable_id'   => $payment->id,
                        'debit'       => 0,
                        'credit'      => $taxAmount,
                        'description' => "Pajak " . ($taxRecord->name ?? '') . " - {$payment->payment_number}",
                    ]);
                }
            }

            // NEW: Adjustments
            if (!empty($payment->adjustments)) {
                foreach ($payment->adjustments as $adj) {
                    $amt = (float)$adj['amount'];
                    $accId = $adj['account_id'];
                    $desc = !empty($adj['description']) ? $adj['description'] : "Penyesuaian - {$payment->payment_number}";
                    
                    if ($adj['type'] === 'addition') {
                        Ledger::create([
                            'company_id'  => $companyId,
                            'journal_id'  => $journal->id,
                            'account_id'  => $accId,
                            'contact_id'  => $customer->id,
                            'ledgerable_type' => Payment::class,
                            'ledgerable_id'   => $payment->id,
                            'debit'       => 0,
                            'credit'      => $amt,
                            'description' => $desc,
                        ]);
                    } else {
                        Ledger::create([
                            'company_id'  => $companyId,
                            'journal_id'  => $journal->id,
                            'account_id'  => $accId,
                            'contact_id'  => $customer->id,
                            'ledgerable_type' => Payment::class,
                            'ledgerable_id'   => $payment->id,
                            'debit'       => $amt,
                            'credit'      => 0,
                            'description' => $desc,
                        ]);
                    }
                }
            }

            // CREDIT Titipan Customer (if overpayment)
            if ($overpaymentAmount > 0) {
                // Find "Titipan Customer" account or fallback to any liability
                $titipanAccount = Account::where('company_id', $companyId)
                    ->where('name', 'like', '%Titipan%')
                    ->first();

                if ($titipanAccount) {
                    Ledger::create([
                        'company_id'  => $companyId,
                        'journal_id'  => $journal->id,
                        'account_id'  => $titipanAccount->id,
                        'contact_id'  => $customer->id,
                        'ledgerable_type' => Payment::class,
                        'ledgerable_id'   => $payment->id,
                        'debit'       => 0,
                        'credit'      => $overpaymentAmount,
                        'description' => "Titipan lebih bayar - {$payment->customer_name}",
                    ]);
                }
            }

            // Update payment with journal_id
            $payment->update(['journal_id' => $journal->id]);

            return $journal;
        });
    }

    /**
     * Unpost payment journal.
     */
    public function unpostPaymentJournal(Payment $payment): void
    {
        \App\Helpers\PeriodLockHelper::validateDate($payment->paid_at);

        DB::transaction(function () use ($payment) {
            if ($payment->journal_id) {
                \App\Models\Accounting\Ledger::where('journal_id', $payment->journal_id)->get()->each->forceDelete();
                Journal::where('id', $payment->journal_id)->forceDelete();
            }

            $payment->update(['journal_id' => null]);
        });
    }

    /**
     * Update payment_status for a given invoice based on total paid.
     */
    public function updateInvoicePaymentStatus(int $invoiceId): void
    {
        $invoice   = Invoice::withoutGlobalScopes()->find($invoiceId);
        if (!$invoice) return;

        $totalPaid = \App\Models\Expenses\PaymentInvoice::where('invoice_id', $invoiceId)->sum('allocated_amount');
        $amount    = (float) $invoice->grand_total;

        $status = match(true) {
            $totalPaid <= 0             => 'unpaid',
            $totalPaid < $amount        => 'partial',
            default                     => 'paid',
        };

        $invoice->update(['payment_status' => $status]);
    }

    /**
     * Post an expense to the ledger (Vendor Bill or Direct Expense)
     */
    public function postExpense(\App\Models\Expenses\Expense $expense): ?Journal
    {
        \App\Helpers\PeriodLockHelper::validateDate($expense->date);

        return DB::transaction(function () use ($expense) {
            $companyId = session('company_id') ?: $expense->company_id;
            
            // Create Journal Header
            $journal = Journal::create([
                'company_id'     => $companyId,
                'journal_number' => $this->generateJournalNumber('JRN'),
                'date'           => $expense->expense_date,
                'reference'      => $expense->expense_number,
                'description'    => "Posting Expense: " . $expense->expense_number,
                'total_debit'    => $expense->grand_total,
                'total_credit'   => $expense->grand_total,
                'status'         => 'posted',
                'is_manual'      => false,
            ]);

            // DEBIT Expense Accounts (from items) — amount only, tax is debited separately
            foreach ($expense->items as $item) {
                $itemAmount = (float) $item->amount;
                $itemTaxAmount = (float) $item->tax_amount;

                // Debit the expense/cost account with the net amount (before tax)
                Ledger::create([
                    'company_id'  => $companyId,
                    'journal_id'  => $journal->id,
                    'account_id'  => $item->account_id,
                    'contact_id'  => $expense->vendor_id,
                    'ledgerable_type' => \App\Models\Expenses\Expense::class,
                    'ledgerable_id'   => $expense->id,
                    'debit'       => $itemAmount,
                    'credit'      => 0,
                    'description' => $item->description,
                ]);

                // Debit the tax account if item has tax (PPN Masukan / Input Tax)
                if ($itemTaxAmount > 0 && is_array($item->tax_details)) {
                    foreach ($item->tax_details as $td) {
                        $tdAmount = (float) ($td['amount'] ?? 0);
                        if ($tdAmount <= 0) continue;

                        $tdId = $td['id'] ?? $td['tax_id'] ?? null;
                        $taxRecord = null;

                        if ($tdId) {
                            $taxRecord = \DB::table('taxes')
                                ->where('id', $tdId)
                                ->whereNotNull('account_id')
                                ->first();
                        }
                        if (!$taxRecord) {
                            $taxRecord = \DB::table('taxes')
                                ->where('name', $td['name'] ?? '')
                                ->whereNotNull('account_id')
                                ->first();
                        }

                        if (!$taxRecord?->account_id) {
                            throw new \RuntimeException(
                                "Pajak [{$td['name']}] belum memiliki Akun COA. "
                                . "Silakan atur COA pajak di menu Settings > Pajak sebelum posting."
                            );
                        }

                        Ledger::create([
                            'company_id'  => $companyId,
                            'journal_id'  => $journal->id,
                            'account_id'  => $taxRecord->account_id,
                            'contact_id'  => $expense->vendor_id,
                            'ledgerable_type' => \App\Models\Expenses\Expense::class,
                            'ledgerable_id'   => $expense->id,
                            'debit'       => $tdAmount,
                            'credit'      => 0,
                            'description' => ($td['name'] ?? 'Tax') . ' Masukan - ' . $expense->expense_number,
                        ]);
                    }
                }
            }

            // CREDIT
            if ($expense->is_direct_expense) {
                // CREDIT Bank Account
                $bankAccount = \App\Models\Settings\BankAccount::find($expense->bank_account_id);
                if ($bankAccount) {
                    Ledger::create([
                        'company_id'  => $companyId,
                        'journal_id'  => $journal->id,
                        'account_id'  => $bankAccount->account_id,
                        'contact_id'  => $expense->vendor_id,
                        'ledgerable_type' => \App\Models\Expenses\Expense::class,
                        'ledgerable_id'   => $expense->id,
                        'debit'       => 0,
                        'credit'      => $expense->grand_total,
                        'description' => "Pengeluaran Kas/Bank untuk " . $expense->expense_number,
                    ]);
                }
            } else {
                // CREDIT Vendor AP Account
                $vendor = \App\Models\Expenses\Vendor::find($expense->vendor_id);
                // Default to Accounts Payable if vendor doesn't have a specific account
                $apAccountId = $vendor?->account_id;
                
                if (!$apAccountId) {
                    // Fallback to finding an AP account
                    $apAccount = Account::where('company_id', $companyId)
                        ->where('name', 'like', '%Hutang%')
                        ->first();
                    $apAccountId = $apAccount?->id;
                }

                if ($apAccountId) {
                    Ledger::create([
                        'company_id'  => $companyId,
                        'journal_id'  => $journal->id,
                        'account_id'  => $apAccountId,
                        'contact_id'  => $expense->vendor_id,
                        'ledgerable_type' => \App\Models\Expenses\Expense::class,
                        'ledgerable_id'   => $expense->id,
                        'debit'       => 0,
                        'credit'      => $expense->grand_total,
                        'description' => "Hutang Vendor untuk " . $expense->expense_number,
                    ]);
                }
            }

            // Update expense
            $expense->update([
                'journal_id' => $journal->id,
                'expense_status_code' => 'posted'
            ]);

            return $journal;
        });
    }

    /**
     * Unpost expense journal.
     */
    public function unpostExpense(\App\Models\Expenses\Expense $expense): void
    {
        \App\Helpers\PeriodLockHelper::validateDate($expense->date);

        DB::transaction(function () use ($expense) {
            if ($expense->journal_id) {
                \App\Models\Accounting\Ledger::where('journal_id', $expense->journal_id)->get()->each->forceDelete();
                Journal::where('id', $expense->journal_id)->forceDelete();
            }

            $expense->update([
                'journal_id' => null,
                'expense_status_code' => 'draft'
            ]);
        });
    }

    public function postExpensePayment(\App\Models\Expenses\ExpensePayment $payment): ?Journal
    {
        \App\Helpers\PeriodLockHelper::validateDate($payment->payment_date);

        return DB::transaction(function () use ($payment) {
            $companyId = session('company_id') ?: $payment->company_id;
            
            $journal = Journal::create([
                'company_id'     => $companyId,
                'journal_number' => $this->generateJournalNumber('PAY'),
                'date'           => $payment->payment_date,
                'reference'      => $payment->payment_number,
                'description'    => "Pembayaran Bill: " . $payment->payment_number,
                'status'         => 'posted',
                'is_manual'      => false,
            ]);

            $vendor = \App\Models\Expenses\Vendor::find($payment->vendor_id);
            $apAccountId = $vendor?->account_id;
            if (!$apAccountId) {
                $apAccount = Account::where('company_id', $companyId)
                    ->where('name', 'like', '%Hutang%')
                    ->first();
                $apAccountId = $apAccount?->id;
            }

            // Debit AP (Total Payment + Total Tax)
            $grossAmount = $payment->total_amount + $payment->total_tax;
            if ($apAccountId && $grossAmount > 0) {
                Ledger::create([
                    'company_id'  => $companyId,
                    'journal_id'  => $journal->id,
                    'account_id'  => $apAccountId,
                    'contact_id'  => $payment->vendor_id,
                    'ledgerable_type' => \App\Models\Expenses\ExpensePayment::class,
                    'ledgerable_id'   => $payment->id,
                    'debit'       => $grossAmount,
                    'credit'      => 0,
                    'description' => "Pelunasan Hutang - " . $payment->payment_number,
                ]);
            }

            // Credit Bank
            if ($payment->total_amount > 0) {
                Ledger::create([
                    'company_id'  => $companyId,
                    'journal_id'  => $journal->id,
                    'account_id'  => $payment->account_id,
                    'contact_id'  => $payment->vendor_id,
                    'ledgerable_type' => \App\Models\Expenses\ExpensePayment::class,
                    'ledgerable_id'   => $payment->id,
                    'debit'       => 0,
                    'credit'      => $payment->total_amount,
                    'description' => "Pengeluaran Kas/Bank - " . $payment->payment_number,
                ]);
            }

            // Credit Taxes
            if (is_array($payment->tax_details)) {
                foreach ($payment->tax_details as $taxDetail) {
                    $taxRecord = \DB::table('taxes')->where('id', $taxDetail['id'])->first();
                    $taxAccountId = $taxRecord?->account_id;
                    if ($taxAccountId && $taxDetail['amount'] > 0) {
                        Ledger::create([
                            'company_id'  => $companyId,
                            'journal_id'  => $journal->id,
                            'account_id'  => $taxAccountId,
                            'contact_id'  => $payment->vendor_id,
                            'ledgerable_type' => \App\Models\Expenses\ExpensePayment::class,
                            'ledgerable_id'   => $payment->id,
                            'debit'       => 0,
                            'credit'      => $taxDetail['amount'],
                            'description' => "Hutang Pajak (" . $taxDetail['name'] . ")",
                        ]);
                    }
                }
            }

            $payment->update(['journal_id' => $journal->id]);

            // Update Expense Payment Statuses
            foreach ($payment->lines as $line) {
                $this->updateExpensePaymentStatus($line->expense_id);
            }

            return $journal;
        });
    }

    /**
     * Unpost expense payment journal.
     */
    public function unpostExpensePayment(\App\Models\Expenses\ExpensePayment $payment): void
    {
        \App\Helpers\PeriodLockHelper::validateDate($payment->payment_date);

        DB::transaction(function () use ($payment) {
            if ($payment->journal_id) {
                \App\Models\Accounting\Ledger::where('journal_id', $payment->journal_id)->get()->each->forceDelete();
                Journal::where('id', $payment->journal_id)->forceDelete();
            }

            $payment->update(['journal_id' => null]);

            foreach ($payment->lines as $line) {
                $this->updateExpensePaymentStatus($line->expense_id);
            }
        });
    }

    public function updateExpensePaymentStatus(int $expenseId): void
    {
        $expense = \App\Models\Expenses\Expense::find($expenseId);
        if (!$expense) return;

        $totalPaid = \App\Models\Expenses\ExpensePaymentLine::where('expense_id', $expenseId)
            ->whereHas('expensePayment', function($q) {
                $q->where('status', '!=', 'void');
            })
            ->sum('amount_paid');
        $amount    = (float) $expense->grand_total;

        $status = match(true) {
            $totalPaid <= 0             => 'unpaid',
            $totalPaid < $amount        => 'partial',
            default                     => 'paid',
        };

        $expense->update(['payment_status' => $status]);
    }
}
