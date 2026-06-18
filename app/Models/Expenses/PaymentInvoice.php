<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

class PaymentInvoice extends Model
{
    use \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id', 'payment_id', 'invoice_id', 'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Incomes\Invoice::class);
    }
}
