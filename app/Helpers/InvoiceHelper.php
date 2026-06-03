<?php

namespace App\Helpers;

use App\Models\Incomes\Invoice;
use Carbon\Carbon;

class InvoiceHelper
{
    /**
     * Generate next invoice number and text based on date
     * Returns array: ['invoice_number' => '00001', 'invoice_text' => '00001/VI/2026']
     */
    public static function generateInvoiceData($date)
    {
        $parsedDate = Carbon::parse($date);
        $month = $parsedDate->month;
        $year = $parsedDate->year;

        // Get max invoice number for this month and year that is exactly 5 digits
        $lastInvoiceNumber = Invoice::whereYear('invoiced_at', $year)
            ->whereMonth('invoiced_at', $month)
            ->whereRaw('LENGTH(invoice_number) = 5')
            ->max('invoice_number');

        if ($lastInvoiceNumber) {
            $nextNumber = intval($lastInvoiceNumber) + 1;
        } else {
            $nextNumber = 1;
        }

        $paddedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[$month];

        $invoiceText = "{$paddedNumber}/{$romanMonth}/{$year}";

        return [
            'invoice_number' => $paddedNumber,
            'invoice_text' => $invoiceText
        ];
    }
}
