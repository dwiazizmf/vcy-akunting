<?php

namespace App\Traits;

trait HasHybridTaxes
{
    /**
     * Helper to get total amount of a specific tax by name from a JSONB field.
     * Assumes the field name is 'header_tax_details' or 'tax_details'.
     *
     * @param string $taxName
     * @param string $column (e.g. 'header_tax_details' or 'tax_details')
     * @return float
     */
    public function getTaxAmountByName(string $taxName, string $column = 'header_tax_details'): float
    {
        $taxDetails = $this->{$column} ?? [];
        
        if (!is_array($taxDetails)) {
            return 0.0;
        }

        $total = 0;
        foreach ($taxDetails as $tax) {
            if (strcasecmp($tax['name'] ?? '', $taxName) === 0) {
                $total += (float) ($tax['amount'] ?? 0);
            }
        }

        return (float) $total;
    }

    /**
     * Get list of all applied tax names on this model.
     */
    public function getAppliedTaxNames(string $column = 'header_tax_details'): array
    {
        $taxDetails = $this->{$column} ?? [];
        
        if (!is_array($taxDetails)) {
            return [];
        }

        return collect($taxDetails)->pluck('name')->unique()->values()->toArray();
    }
}
