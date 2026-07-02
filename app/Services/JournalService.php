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
                'posted_at'      => now(),
                'posted_by'      => auth()->id(),
            ]);

            $grandTotal = (float) $invoice->grand_total; // grand total (subtotal + tax)
            $subtotal   = (float) $invoice->subtotal;

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

            // 3. CREDIT Revenue Account (subtotal only)
            Ledger::create([
                'company_id'       => $companyId,
                'journal_id'       => $journal->id,
                'account_id'       => $invoice->account_id,
                'contact_id'       => $customer->id,
                'ledgerable_type'  => Invoice::class,
                'ledgerable_id'    => $invoice->id,
                'debit'            => 0,
                'credit'           => $subtotal,
                'description'      => "Pendapatan - {$invoiceText}",
            ]);

            // 4. CREDIT Tax accounts (from header_tax_details)
            $headerTaxes = is_array($invoice->header_tax_details) ? $invoice->header_tax_details : [];
            foreach ($headerTaxes as $taxDetail) {
                $taxAmount = (float) ($taxDetail['amount'] ?? 0);
                if ($taxAmount <= 0) continue;

                // Find tax account_id from taxes table by name or rate
                $taxRecord = \DB::table('taxes')
                    ->where('name', $taxDetail['name'] ?? '')
                    ->orWhere('rate', $taxDetail['rate'] ?? 0)
                    ->whereNotNull('account_id')
                    ->first();

                $taxAccountId = $taxRecord?->account_id ?? $invoice->account_id; // fallback ke revenue

                Ledger::create([
                    'company_id'       => $companyId,
                    'journal_id'       => $journal->id,
                    'account_id'       => $taxAccountId,
                    'contact_id'       => $customer->id,
                    'ledgerable_type'  => Invoice::class,
                    'ledgerable_id'    => $invoice->id,
                    'debit'            => 0,
                    'credit'           => $taxAmount,
                    'description'      => ($taxDetail['name'] ?? 'Tax') . " - {$invoice->invoice_number}",
                ]);
            }

            // 5. Update invoice: mark as posted
            $invoice->update([
                'isPosted'             => true,
                'invoice_status_code'  => 'posted',
            ]);

            return $journal;
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
        $companyId = $payment->company_id;

        $payment->load(['bankAccount', 'customer', 'invoices.invoice']);

        $bankAccount = $payment->bankAccount;
        $customer    = $payment->customer;

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
                'posted_at'      => now(),
                'posted_by'      => auth()->id(),
            ]);

            $totalAmount       = (float) $payment->total_amount;
            $overpaymentAmount = (float) $payment->overpayment_amount;
            $arAmount          = $totalAmount - $overpaymentAmount;

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
            ]);

            // DEBIT Expense Accounts (from items)
            foreach ($expense->items as $item) {
                Ledger::create([
                    'company_id'  => $companyId,
                    'journal_id'  => $journal->id,
                    'account_id'  => $item->account_id,
                    'contact_id'  => $expense->vendor_id, // can be null
                    'ledgerable_type' => \App\Models\Expenses\Expense::class,
                    'ledgerable_id'   => $expense->id,
                    'debit'       => $item->total,
                    'credit'      => 0,
                    'description' => $item->description,
                ]);
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

    public function postExpensePayment(\App\Models\Expenses\ExpensePayment $payment): ?Journal
    {
        return DB::transaction(function () use ($payment) {
            $companyId = session('company_id') ?: $payment->company_id;
            
            $journal = Journal::create([
                'company_id'     => $companyId,
                'journal_number' => $this->generateJournalNumber('PAY'),
                'date'           => $payment->payment_date,
                'reference'      => $payment->payment_number,
                'description'    => "Pembayaran Bill: " . $payment->payment_number,
                'status'         => 'posted',
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

    public function updateExpensePaymentStatus(int $expenseId): void
    {
        $expense = \App\Models\Expenses\Expense::find($expenseId);
        if (!$expense) return;

        $totalPaid = \App\Models\Expenses\ExpensePaymentLine::where('expense_id', $expenseId)->sum('amount_paid');
        $amount    = (float) $expense->grand_total;

        $status = match(true) {
            $totalPaid <= 0             => 'unpaid',
            $totalPaid < $amount        => 'partial',
            default                     => 'paid',
        };

        $expense->update(['payment_status' => $status]);
    }
}
