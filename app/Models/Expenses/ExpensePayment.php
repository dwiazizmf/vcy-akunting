<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Settings\PaymentCategory;
use App\Models\Vendor;
use App\Models\BankAccount;
use App\Models\Account;
use App\Models\Journal;
use App\Models\User;

class ExpensePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'payment_number',
        'payment_date',
        'vendor_id',
        'payment_category_id',
        'account_id',
        'total_amount',
        'total_tax',
        'tax_details',
        'notes',
        'journal_id',
        'status',
        'created_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'tax_details' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 0);
            $builder->where('company_id', $companyId);
        });
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function paymentCategory()
    {
        return $this->belongsTo(PaymentCategory::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(ExpensePaymentLine::class);
    }
}
