<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'payment_number',
        'paid_at', 'total_amount', 'tax_id', 'tax_amount', 'payment_method', 'bank_account_id',
        'reference', 'notes', 'overpayment_amount', 'journal_id',
        'status', 'created_by',
    ];

    protected $casts = [
        'paid_at'            => 'date',
        'total_amount'       => 'decimal:2',
        'overpayment_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 0);
            $builder->where('company_id', $companyId);
        });
    }

    public function invoices()
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
