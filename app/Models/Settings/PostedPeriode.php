<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class PostedPeriode extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $table = 'list_posted_periode';

    protected $fillable = [
        'company_id',
        'bulan',
        'tahun',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];
}
