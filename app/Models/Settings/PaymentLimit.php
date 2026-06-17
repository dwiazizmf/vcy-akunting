<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\Models\Settings\Company;
use Illuminate\Database\Eloquent\Builder;

class PaymentLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account_id',
        'payment_category_id',
        'limit_amount',
    ];

    /**
     * Apply Company Global Scope
     */
    protected static function booted()
    {
        static::addGlobalScope('company_id', function (Builder $builder) {
            $companyId = session('company_id');
            if (!$companyId) {
                $companyId = Company::where('enabled', 1)->first()?->id;
            }
            if ($companyId) {
                $builder->where('payment_limits.company_id', $companyId);
            }
        });
    }

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
