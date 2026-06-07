<?php

namespace App\Filters\Incomes;

use EloquentFilter\ModelFilter;

class Invoices extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relatedModel => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function search($query)
    {
        return $this->where(function($q) use ($query) {
            $q->where('invoice_number', 'like', "%{$query}%")
              ->orWhere('invoice_text', 'like', "%{$query}%")
              ->orWhere('order_number', 'like', "%{$query}%")
              ->orWhere('customer_name', 'like', "%{$query}%");
        });
    }

    public function status($status)
    {
        return $this->where('invoice_status_code', $status);
    }

    public function kapal($kapal)
    {
        return $this->where('nama_kapal', 'like', "%{$kapal}%");
    }

    public function dateFrom($date)
    {
        return $this->whereDate('invoiced_at', '>=', $date);
    }

    public function dateTo($date)
    {
        return $this->whereDate('invoiced_at', '<=', $date);
    }

    public function isPosted($isPosted)
    {
        if ($isPosted !== null && $isPosted !== '') {
            return $this->where('isPosted', $isPosted);
        }
        return $this;
    }
}
