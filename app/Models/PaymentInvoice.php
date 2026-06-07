<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

class PaymentInvoice extends Model
{
    protected $fillable = [
        'company_id', 'payment_id', 'invoice_id', 'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: (Company::where('enabled', 1)->first()?->id ?? 0);
            $builder->where('company_id', $companyId);
        });
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Incomes\Invoice::class);
    }
}
