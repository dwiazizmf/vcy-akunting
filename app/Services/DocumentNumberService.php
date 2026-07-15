<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class DocumentNumberService
{
    /**
     * Generate the next document number with Pessimistic Locking.
     * HATI-HATI: Fungsi ini WAJIB dipanggil di dalam blok DB::transaction()
     * agar lockForUpdate() benar-benar menahan proses concurrent (mencegah race condition).
     *
     * @param string $modelClass The Eloquent Model class (e.g., Invoice::class)
     * @param string $column The column name storing the number (e.g., 'invoice_number')
     * @param callable $formatter A callback function that takes the previous max number and returns the new one.
     * @param callable|null $queryModifier A callback to apply additional filters before fetching the record.
     * @return mixed
     * @throws Exception
     */
    public function generateNext(string $modelClass, string $column, callable $formatter, ?callable $queryModifier = null)
    {
        if (DB::transactionLevel() === 0) {
            throw new Exception("DocumentNumberService::generateNext() wajib dipanggil di dalam DB::transaction() untuk mencegah race condition.");
        }

        // Kita menggunakan orderBy id desc dan first() agar query melock row terakhir.
        // Lock ini akan dilepas otomatis saat DB::transaction() selesai (commit/rollback).
        $query = $modelClass::lockForUpdate()->orderBy('id', 'desc');
        
        if ($queryModifier) {
            $query = $queryModifier($query);
        }

        $latestRecord = $query->first();
        
        $maxNumber = $latestRecord ? $latestRecord->{$column} : null;

        // Callback formatter memberikan fleksibilitas penuh kepada controller 
        // untuk menentukan format nomor berikutnya (reset bulanan, tahunan, dll)
        return $formatter($maxNumber);
    }
}
