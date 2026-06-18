<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    use SoftDeletes, \App\Traits\BelongsToCompany;

    protected $fillable = ['company_id', 'type_id', 'parent_id', 'code', 'name', 'description', 'system', 'enabled'];
    protected $casts = ['system' => 'boolean', 'enabled' => 'boolean'];

    protected static function booted()
    {
        static::addGlobalScope('order_by_code', function (Builder $builder) {
            $builder->orderBy('code', 'asc');
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
