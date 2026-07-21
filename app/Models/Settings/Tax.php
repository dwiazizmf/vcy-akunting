<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Tax extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToCompany;

    protected $table = 'taxes';

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'type',
        'account_id',
        'description',
        'enabled',
    ];

    protected $casts = [
        'rate'    => 'decimal:2',
        'enabled' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class);
    }

    /**
     * Global scope: hanya tampilkan pajak yang aktif secara default.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('enabled', function (Builder $query) {
            $query->where('enabled', true);
        });
    }
}
