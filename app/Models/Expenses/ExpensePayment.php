<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Settings\PaymentCategory;
use App\Models\Expenses\Vendor;
use App\Models\Settings\BankAccount;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Settings\User;

class ExpensePayment extends Model
{
    use SoftDeletes, \App\Traits\BelongsToCompany;

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
