<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'type', 'bank_name',
        'account_number', 'account_id', 'is_default', 'enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'enabled'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 0);
            $builder->where('company_id', $companyId);
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
