<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

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
     * Global scope: hanya tampilkan pajak yang aktif secara default & sesuai company.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('enabled', function (Builder $query) {
            $query->where('enabled', true);
        });

        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 0);
            $builder->where('company_id', $companyId);
        });
    }
}
