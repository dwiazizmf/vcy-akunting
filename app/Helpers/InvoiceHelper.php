<?php

namespace App\Helpers;

use App\Models\Incomes\Invoice;
use Carbon\Carbon;
use App\Services\DocumentNumberService;

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

        return app(DocumentNumberService::class)->generateNext(
            Invoice::class,
            'invoice_number',
            function ($maxNumber) use ($month, $year) {
                if ($maxNumber) {
                    $nextNumber = intval($maxNumber) + 1;
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
            },
            function ($query) use ($month, $year) {
                return $query->whereYear('invoiced_at', $year)
                             ->whereMonth('invoiced_at', $month)
                             ->whereRaw('LENGTH(invoice_number) = 5');
            }
        );
    }

    /**
     * Generate next revision invoice number and text based on parent invoice
     * Returns array: ['invoice_number' => '00001.R1', 'invoice_text' => '00001/VI/2026.R1']
     */
    public static function generateRevisionInvoiceData($oldInvoiceText, $oldInvoiceNumber)
    {
        // Strip any existing revision suffix like .R1, .R2 from the end of the number or text
        $baseInvoiceNumber = preg_replace('/\.R\d+$/', '', $oldInvoiceNumber);
        $baseInvoiceText = preg_replace('/\.R\d+$/', '', $oldInvoiceText);

        // Find the highest revision tag in the database for this base invoice
        $maxRevision = 0;
        $existingInvoices = Invoice::lockForUpdate()->where(function($q) use ($baseInvoiceNumber, $baseInvoiceText) {
            $q->where('invoice_number', 'like', $baseInvoiceNumber . '.R%')
              ->orWhere('invoice_text', 'like', $baseInvoiceText . '.R%')
              ->orWhere('invoice_number', $baseInvoiceNumber)
              ->orWhere('invoice_text', $baseInvoiceText);
        })->get();

        foreach ($existingInvoices as $inv) {
            if (preg_match('/\.R(\d+)$/', $inv->invoice_number, $matches)) {
                $maxRevision = max($maxRevision, intval($matches[1]));
            }
            if (preg_match('/\.R(\d+)$/', $inv->invoice_text, $matches)) {
                $maxRevision = max($maxRevision, intval($matches[1]));
            }
        }

        $nextRevision = $maxRevision + 1;

        return [
            'invoice_number' => $baseInvoiceNumber . '.R' . $nextRevision,
            'invoice_text' => $baseInvoiceText . '.R' . $nextRevision,
        ];
    }
}
