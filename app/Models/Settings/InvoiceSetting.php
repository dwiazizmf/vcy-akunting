<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $table = 'faktur_periode';

    public $timestamps = true;

    protected $fillable = [
        'company_id',
        'first_faktur',
        'second_faktur',
        'third_faktur',
        'fourth_faktur',
        'no_awal',
        'no_akhir',
    ];

    protected $casts = [
        'no_awal'  => 'integer',
        'no_akhir' => 'integer',
    ];

    /**
     * Ambil setting aktif (baris pertama), buat jika belum ada.
     */
    public static function getSetting(): static
    {
        $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 0);
        return static::firstOrCreate(['company_id' => $companyId], [
            'first_faktur'  => 'INV',
            'second_faktur' => null,
            'third_faktur'  => null,
            'fourth_faktur' => null,
            'no_awal'       => 1,
            'no_akhir'      => 9999,
        ]);
    }
}
