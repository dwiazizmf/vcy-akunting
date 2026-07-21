<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Settings\User;
use App\Models\Settings\BankAccount;
use App\Models\Accounting\Journal;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payment extends Model
{
    use SoftDeletes, \App\Traits\BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id', 'payment_number',
        'paid_at', 'total_amount', 'payment_method', 'bank_account_id',
        'reference', 'notes', 'overpayment_amount', 'adjustments', 'journal_id',
        'status', 'created_by',
    ];

    protected $casts = [
        'paid_at'            => 'date',
        'total_amount'       => 'decimal:2',
        'overpayment_amount' => 'decimal:2',
        'adjustments'        => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
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

    public function tax()
    {
        return $this->belongsTo(\App\Models\Settings\Tax::class, 'tax_id');
    }
}
