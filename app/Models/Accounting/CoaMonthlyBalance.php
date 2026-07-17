<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\Company;
use App\Models\Accounting\Account;

class CoaMonthlyBalance extends Model
{
    protected $fillable = [
        'company_id',
        'account_id',
        'period_year',
        'period_month',
        'beginning_balance',
        'debit_mutation',
        'credit_mutation',
        'ending_balance',
    ];

    protected $casts = [
        'beginning_balance' => 'decimal:2',
        'debit_mutation' => 'decimal:2',
        'credit_mutation' => 'decimal:2',
        'ending_balance' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
