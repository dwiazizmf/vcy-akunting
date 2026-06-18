<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    protected $fillable = ['name', 'category'];
    
    public function accounts()
    {
        return $this->hasMany(Account::class, 'type_id');
    }
}
