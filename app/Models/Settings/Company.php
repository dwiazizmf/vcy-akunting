<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'prefix',
        'code',
        'address',
        'phone',
        'npwp',
        'logo_path',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Global scope: hanya tampilkan company yang aktif secara default.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('enabled', function (Builder $query) {
            $query->where('enabled', true);
        });
    }

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\Settings\User::class,
            'user_companies',
            'company_id',
            'user_id'
        );
    }
}
