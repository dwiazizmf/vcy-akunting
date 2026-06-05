<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Ledger;
use App\Models\Payment;
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

        $last = \App\Models\Payment::withoutGlobalScopes()
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
        $customer = \App\Models\Customer::withoutGlobalScopes()->find($invoice->customer_id);

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

            $grandTotal = (float) $invoice->amount; // grand total (subtotal + tax)
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

        $totalPaid = \App\Models\PaymentInvoice::where('invoice_id', $invoiceId)->sum('allocated_amount');
        $amount    = (float) $invoice->amount;

        $status = match(true) {
            $totalPaid <= 0             => 'unpaid',
            $totalPaid < $amount        => 'partial',
            default                     => 'paid',
        };

        $invoice->update(['payment_status' => $status]);
    }
}
