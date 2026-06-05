<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'journal_id', 'account_id', 'contact_id', 'ledgerable_type', 'ledgerable_id', 'debit', 'credit', 'description'];
    protected $casts = ['debit' => 'decimal:4', 'credit' => 'decimal:4'];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    public function contact()
    {
        return $this->belongsTo(Customer::class, 'contact_id');
    }
    
    public function ledgerable()
    {
        return $this->morphTo();
    }
}
