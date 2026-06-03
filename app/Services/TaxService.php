<?php

namespace App\Services;

use App\Models\Settings\Tax;
use Illuminate\Support\Facades\Log;

class TaxService
{
    /**
     * Get all currently active taxes to be used in frontend.
     */
    public function getActiveTaxes()
    {
        // Global scope 'enabled' is applied on the Tax model, so we just get all.
        return Tax::all(['id', 'name', 'rate', 'type']);
    }

    /**
     * Parse and validate header tax details from JSON payload.
     * Example expected format: [{"name": "PPN", "rate": 11, "amount": 11000}]
     */
    public function parseHeaderTaxes(?array $headerTaxes): array
    {
        if (!$headerTaxes) {
            return [];
        }

        // Basic validation or filtering can happen here
        return collect($headerTaxes)->map(function ($tax) {
            return [
                'name' => $tax['name'] ?? 'Unknown Tax',
                'rate' => (float) ($tax['rate'] ?? 0),
                'amount' => (float) ($tax['amount'] ?? 0),
            ];
        })->toArray();
    }

    /**
     * [PLACEHOLDER] General Ledger (GL) Posting Logic
     * Demonstrates Option 1: Consolidating all item taxes into a single "Hutang PPN Keluaran" account.
     */
    public function postToGL($invoice)
    {
        // 1. Calculate total base amount (Piutang)
        $piutangUsaha = $invoice->grand_total;
        $pendapatan = $invoice->total_item_subtotal; // Base price

        // 2. Calculate consolidated taxes from header_tax_details
        // Alternatively, if you want item-level accuracy, loop through $invoice->items
        $headerTaxes = $invoice->header_tax_details ?? [];
        $totalPpnAmount = 0;

        foreach ($headerTaxes as $tax) {
            if (strtoupper($tax['name']) === 'PPN') {
                $totalPpnAmount += $tax['amount'];
            }
        }

        // 3. Create Journal Entry (Placeholder)
        Log::info("GL Posting for Invoice #{$invoice->invoice_number}", [
            'Debit' => [
                'Account' => 'Piutang Usaha',
                'Amount' => $piutangUsaha
            ],
            'Credit' => [
                [
                    'Account' => 'Pendapatan Jasa',
                    'Amount' => $pendapatan
                ],
                [
                    'Account' => 'Hutang PPN Keluaran',
                    'Amount' => $totalPpnAmount
                ]
            ]
        ]);

        return true;
    }
}
