<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vcy_companies';

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'npwp',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'vcy_user_companies',
            'company_id',
            'user_id'
        );
    }
}
