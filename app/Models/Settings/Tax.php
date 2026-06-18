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
        'description',
        'enabled',
    ];

    protected $casts = [
        'rate'    => 'decimal:2',
        'enabled' => 'boolean',
    ];

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
