<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function limits()
    {
        return $this->hasMany(PaymentLimit::class);
    }
}
