<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Incomes\Invoice;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'document_number',
        'send_date',
        'up_person',
        'customer_name',
        'no_tlp',
        'address',
        'order_number',
        'status',
        'orders',
        'orders_text',
    ];

    protected $casts = [
        'send_date' => 'datetime',
        'status'    => 'integer',
        'orders'    => 'integer',
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
        return $this->belongsToMany(Invoice::class, 'document_invoices')
                    ->withTimestamps();
    }
}
