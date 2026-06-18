<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'journal_number', 'date', 'reference', 'description', 'status', 'posted_at', 'posted_by'];
    protected $casts = ['date' => 'date', 'posted_at' => 'datetime'];

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
    
    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
