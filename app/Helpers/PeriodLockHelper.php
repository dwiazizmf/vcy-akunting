<?php

namespace App\Helpers;

use App\Models\Settings\PostedPeriode;
use Carbon\Carbon;
use Exception;

class PeriodLockHelper
{
    /**
     * Check if a given date falls within a locked period.
     * Throws an Exception if locked.
     * 
     * @param string|\Carbon\Carbon $date
     * @throws \Exception
     */
    public static function validateDate($date)
    {
        if (!$date) return;
        
        $carbonDate = Carbon::parse($date);
        $month = $carbonDate->month;
        $year = $carbonDate->year;

        $periode = PostedPeriode::where('bulan', $month)
            ->where('tahun', $year)
            ->first();

        if ($periode && $periode->status) {
            $monthName = $carbonDate->translatedFormat('F Y');
            throw new Exception("Transaksi pada periode {$monthName} sudah dikunci dan tidak dapat diubah.");
        }
    }
}
