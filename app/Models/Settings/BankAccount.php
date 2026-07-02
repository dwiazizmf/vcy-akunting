<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Accounting\Account;

class BankAccount extends Model
{
    use SoftDeletes, \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'type', 'bank_name',
        'account_number', 'account_id', 'is_default', 'enabled',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'enabled'    => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
