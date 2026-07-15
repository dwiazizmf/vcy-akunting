<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Settings\User;
use App\Traits\BelongsToCompany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Journal extends Model
{
    use SoftDeletes, BelongsToCompany, LogsActivity;

    protected $fillable = ['company_id', 'journal_number', 'date', 'reference', 'description', 'status', 'posted_at', 'posted_by'];
    protected $casts = ['date' => 'date', 'posted_at' => 'datetime'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
    
    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
