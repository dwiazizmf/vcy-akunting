<?php

namespace App\Models\Incomes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;

class Invoice extends Model
{
    use HasFactory, Filterable, \App\Traits\HasHybridTaxes;

    protected $table = 'invoices';

    protected $guarded = [];

    protected $casts = [
        'header_tax_details' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;
            if ($companyId) {
                $builder->where('company_id', $companyId);
            }
        });
    }

    public function modelFilter()
    {
        return $this->provideFilter(\App\Filters\Incomes\Invoices::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
