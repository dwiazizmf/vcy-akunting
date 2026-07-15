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
}
