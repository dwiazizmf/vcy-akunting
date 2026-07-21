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
        'account_id',
        'description',
        'enabled',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class);
    }
}
