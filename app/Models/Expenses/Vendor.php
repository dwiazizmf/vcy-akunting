<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\Company;
use App\Models\Accounting\Account;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToCompany;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
