<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $table = 'no_faktur_periode';

    public $timestamps = false;

    protected $fillable = [
        'first_faktur',
        'second_faktur',
        'third_faktur',
        'fourth_faktur',
        'no_awal',
        'no_akhir',
        'company',
    ];

    protected $casts = [
        'no_awal'  => 'integer',
        'no_akhir' => 'integer',
        'company'  => 'integer',
    ];

    /**
     * Get the single active setting (first row)
     */
    public static function getSetting(): static
    {
        return static::firstOrCreate([], [
            'first_faktur'  => 'INV',
            'second_faktur' => null,
            'third_faktur'  => null,
            'fourth_faktur' => null,
            'no_awal'       => 1,
            'no_akhir'      => 9999,
        ]);
    }
}
