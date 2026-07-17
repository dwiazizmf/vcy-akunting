<?php

namespace App\Helpers;

use App\Models\Settings\PostedPeriode;
use Carbon\Carbon;
use Exception;

class PeriodLockHelper
{
    protected static $cache = [];

    /**
     * Check if a given date falls within a locked period without throwing an exception.
     * Returns true if locked, false otherwise.
     *
     * @param string|\Carbon\Carbon $date
     * @return bool
     */
    public static function isLocked($date)
    {
        if (!$date) return false;
        
        $carbonDate = Carbon::parse($date);
        $month = $carbonDate->month;
        $year = $carbonDate->year;
        
        $key = "{$year}-{$month}";
        
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $periode = PostedPeriode::where('bulan', $month)
            ->where('tahun', $year)
            ->first();
            
        $isLocked = $periode && $periode->status;
        self::$cache[$key] = $isLocked;
        
        return $isLocked;
    }

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
        
        if (self::isLocked($date)) {
            $monthName = Carbon::parse($date)->translatedFormat('F Y');
            throw new Exception("Transaksi pada periode {$monthName} sudah dikunci dan tidak dapat diubah.");
        }
    }
}
