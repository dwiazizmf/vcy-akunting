<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Vendor;
use App\Models\BankAccount;
use App\Models\Journal;

class Expense extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\HasHybridTaxes;

    protected $guarded = ['id'];

    protected $casts = [
        'expense_date' => 'datetime',
        'due_date' => 'datetime',
        'header_tax_details' => 'array',
        'is_direct_expense' => 'boolean',
    ];

    protected static function booted()
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

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function expensePayments()
    {
        return $this->hasMany(ExpensePaymentLine::class);
    }
}
