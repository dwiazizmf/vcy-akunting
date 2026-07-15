<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class PostedPeriode extends Model
{
    protected $table = 'list_posted_periode';

    protected $fillable = [
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
