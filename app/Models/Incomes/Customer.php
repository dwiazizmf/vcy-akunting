<?php

namespace App\Models\Incomes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
