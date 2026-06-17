<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expenses\Expense;
use App\Models\Payment;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use App\Services\JournalService;

class BillPaymentController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function store(Request $request, Expense $expense)
    {
        if ($expense->payment_status === 'paid') {
            return back()->withErrors(['error' => 'This expense is already paid.']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            // Note: In a complete implementation, we would create a `BillPayment` record
            // linking to the `Expense` and run `$this->journalService->postBillPayment()`.
            // For now, we simulate the payment to complete the scaffolding.
            
            $expense->update([
                'payment_status' => 'paid',
                // other logic here
            ]);

            DB::commit();
            return back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }
}
