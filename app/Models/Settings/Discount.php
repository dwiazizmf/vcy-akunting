<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Discount extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'type',
        'description',
        'enabled',
    ];
}
