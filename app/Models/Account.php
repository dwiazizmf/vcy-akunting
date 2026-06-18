<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'type_id', 'parent_id', 'code', 'name', 'description', 'system', 'enabled'];
    protected $casts = ['system' => 'boolean', 'enabled' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope('order_by_code', function (Builder $builder) {
            $builder->orderBy('code', 'asc');
        });

        static::addGlobalScope('company_id', function (Builder $builder) {
            $companyId = session('company_id');
            if (!$companyId) {
                $companyId = \App\Models\Settings\Company::where('enabled', 1)->first()?->id;
            }
            if ($companyId) {
                $builder->where('accounts.company_id', $companyId);
            }
        });
    }
    
    public function type()
    {
        return $this->belongsTo(AccountType::class, 'type_id');
    }
    
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }
    
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}
