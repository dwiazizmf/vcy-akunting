<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ledger;
use App\Models\Account;
use App\Models\Journal;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('company_id') ?: 1;
        
        $accounts = Account::with('type')
            ->where('company_id', $companyId)
            ->where('enabled', true)
            ->orderBy('code')
            ->get();

        $accountId = $request->input('account_id');
        
        // Default to first day of current month and today
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $ledgers = [];
        $openingBalance = 0;
        $runningBalance = 0;
        $account = null;
        $totalDebit = 0;
        $totalCredit = 0;

        if ($accountId) {
            $account = Account::with('type')->find($accountId);

            if ($account && $account->company_id == $companyId) {
                // Determine normal balance (Debit or Credit)
                // Harta (Asset) & Beban (Expense) -> Debit
                // Kewajiban (Liability), Modal (Equity), Pendapatan (Revenue) -> Credit
                $normalBalance = in_array(strtolower($account->type->category), ['asset', 'expense']) ? 'debit' : 'credit';

                // Query for Opening Balance (before dateFrom)
                $openingLedgers = Ledger::whereHas('journal', function($q) use ($dateFrom) {
                        $q->where('date', '<', $dateFrom)
                          ->where('status', 'posted');
                    })
                    ->where('account_id', $accountId)
                    ->where('company_id', $companyId)
                    ->select(
                        DB::raw('SUM(debit) as total_debit'),
                        DB::raw('SUM(credit) as total_credit')
                    )
                    ->first();

                if ($openingLedgers) {
                    if ($normalBalance === 'debit') {
                        $openingBalance = $openingLedgers->total_debit - $openingLedgers->total_credit;
                    } else {
                        $openingBalance = $openingLedgers->total_credit - $openingLedgers->total_debit;
                    }
                }

                // Query for Transactions (dateFrom to dateTo)
                $transactions = Ledger::with(['journal' => function($q) {
                        $q->select('id', 'journal_number', 'date', 'reference');
                    }, 'contact'])
                    ->whereHas('journal', function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('date', [$dateFrom, $dateTo])
                          ->where('status', 'posted');
                    })
                    ->where('account_id', $accountId)
                    ->where('company_id', $companyId)
                    ->orderBy(Journal::select('date')
                        ->whereColumn('journals.id', 'ledgers.journal_id')
                        ->limit(1)
                    )
                    ->orderBy('created_at')
                    ->get();

                $runningBalance = $openingBalance;
                
                foreach ($transactions as $tx) {
                    if ($normalBalance === 'debit') {
                        $runningBalance += ($tx->debit - $tx->credit);
                    } else {
                        $runningBalance += ($tx->credit - $tx->debit);
                    }
                    
                    $tx->balance = $runningBalance;
                    $ledgers[] = $tx;
                    
                    $totalDebit += $tx->debit;
                    $totalCredit += $tx->credit;
                }
            }
        }

        return Inertia::render('Ledger/Index', [
            'accounts' => $accounts,
            'selectedAccount' => $account,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'ledgers' => $ledgers,
            'filters' => [
                'account_id' => $accountId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        ]);
    }
}
