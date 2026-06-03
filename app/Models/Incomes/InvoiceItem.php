<?php

namespace App\Models\Incomes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory, \App\Traits\HasHybridTaxes;

    protected $table = 'invoice_items';

    protected $guarded = [];

    protected $casts = [
        'tax_details' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
