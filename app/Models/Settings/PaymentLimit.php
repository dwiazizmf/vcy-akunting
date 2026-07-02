<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\Account;
use App\Models\Settings\Company;
use Illuminate\Database\Eloquent\Builder;

class PaymentLimit extends Model
{
    use HasFactory, \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_id',
        'payment_category_id',
        'limit_amount',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
