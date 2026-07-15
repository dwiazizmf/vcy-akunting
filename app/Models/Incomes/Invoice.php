<?php

namespace App\Models\Incomes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, Filterable, \App\Traits\HasHybridTaxes, \App\Traits\BelongsToCompany, LogsActivity;

    protected $table = 'invoices';

    protected $guarded = [];

    protected $casts = [
        'header_tax_details' => 'array',
        'header_discount_details' => 'array',
        'isFCL' => 'boolean',
        'isFaktur' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function modelFilter()
    {
        return $this->provideFilter(\App\Filters\Incomes\Invoices::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function invoiceType()
    {
        return $this->belongsTo(InvoiceType::class, 'invoice_type_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Incomes\Customer::class, 'customer_id');
    }
    public function documents()
    {
        return $this->belongsToMany(\App\Models\Incomes\Document::class, 'document_invoices')
                    ->withTimestamps();
    }

    public function paymentInvoices()
    {
        return $this->hasMany(\App\Models\Expenses\PaymentInvoice::class, 'invoice_id');
    }
}
