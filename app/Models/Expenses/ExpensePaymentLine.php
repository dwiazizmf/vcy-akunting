<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;

class ExpensePaymentLine extends Model
{
    protected $fillable = [
        'expense_payment_id',
        'expense_id',
        'amount_paid'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function expensePayment()
    {
        return $this->belongsTo(ExpensePayment::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
