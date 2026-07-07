<?php

namespace App\Models\Incomes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Incomes\Invoice;
use Carbon\Carbon;

class Document extends Model
{
    use SoftDeletes, \App\Traits\BelongsToCompany;

    protected $fillable = [
        'company_id',
        'type',
        'document_number',
        'send_date',
        'up_person',
        'customer_name',
        'no_tlp',
        'address',
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

        static::creating(function ($model) {
            $date = $model->send_date ? Carbon::parse($model->send_date) : now();
            
            // Cari order max pada bulan & tahun dari send_date ini untuk tipe dokumen yang sama
            $maxOrder = static::where('type', $model->type)
                ->whereYear('send_date', $date->year)
                ->whereMonth('send_date', $date->month)
                ->max('orders');

            $model->orders = $maxOrder ? $maxOrder + 1 : 1;

            // Tentukan singkatan nomor
            $abbreviations = [
                'tanda_terima' => 'TT',
                'tanda_terima_new' => 'TTN',
                'surat_tagihan' => 'ST',
                'schedule_tukar_faktur' => 'SCH',
                'titip_internal' => 'TI',
            ];
            $abbr = $abbreviations[$model->type] ?? 'DOC';
            
            $model->orders_text = sprintf(
                '%s/%s%s/%03d',
                $abbr,
                $date->format('Y'),
                $date->format('m'),
                $model->orders
            );
        });
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'document_invoices')
                    ->withTimestamps();
    }
}
