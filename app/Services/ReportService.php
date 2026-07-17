<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\CoaMonthlyBalance;
use App\Models\Accounting\Ledger;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get beginning balances for all accounts just before the given start_date.
     * Uses coa_monthly_balances of the previous month + partial ledgers for the current month.
     * 
     * @param string $startDate (Y-m-d)
     * @return array [account_id => beginning_balance_amount]
     */
    public function getBeginningBalances($startDate)
    {
        $date = Carbon::parse($startDate);
        
        // Find the previous month
        $prevMonthDate = $date->copy()->subMonth();
        $prevMonth = $prevMonthDate->month;
        $prevYear = $prevMonthDate->year;

        // 1. Get ending balances from previous month
        $monthlyBalances = CoaMonthlyBalance::where('period_month', $prevMonth)
            ->where('period_year', $prevYear)
            ->pluck('ending_balance', 'account_id')
            ->toArray();

        // 2. If startDate is not the 1st of the month, we must add partial mutations from the 1st up to startDate - 1
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d');
        $dateBeforeStart = $date->copy()->subDay()->format('Y-m-d');

        if ($startDate > $startOfMonth) {
            $partialLedgers = Ledger::select('ledgers.account_id', DB::raw('SUM(ledgers.debit) as total_debit'), DB::raw('SUM(ledgers.credit) as total_credit'))
                ->join('journals', 'journals.id', '=', 'ledgers.journal_id')
                ->whereBetween('journals.date', [$startOfMonth, $dateBeforeStart])
                ->whereNull('journals.deleted_at')
                ->groupBy('ledgers.account_id')
                ->get();

            // We need to know the account category to know if Debit adds or subtracts.
            $accounts = Account::with('type')->get()->keyBy('id');

            foreach ($partialLedgers as $ledger) {
                $accountId = $ledger->account_id;
                $account = $accounts->get($accountId);
                if (!$account) continue;
                
                $category = $account->type->category; // Asset, Expense, Liability, Equity, Revenue
                $baseBal = $monthlyBalances[$accountId] ?? 0;
                
                $debit = (float) $ledger->total_debit;
                $credit = (float) $ledger->total_credit;

                // Adjust balance based on normal balance
                if (in_array($category, ['Asset', 'Expense'])) {
                    $monthlyBalances[$accountId] = $baseBal + $debit - $credit;
                } else {
                    $monthlyBalances[$accountId] = $baseBal + $credit - $debit;
                }
            }
        }

        return $monthlyBalances;
    }

    /**
     * Get exact mutations (Debit & Credit sum) in the given date range for all accounts.
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array [account_id => ['debit' => amount, 'credit' => amount]]
     */
    public function getMutations($startDate, $endDate)
    {
        $mutations = Ledger::select('ledgers.account_id', DB::raw('SUM(ledgers.debit) as total_debit'), DB::raw('SUM(ledgers.credit) as total_credit'))
            ->join('journals', 'journals.id', '=', 'ledgers.journal_id')
            ->whereBetween('journals.date', [$startDate, $endDate])
            ->whereNull('journals.deleted_at')
            ->groupBy('ledgers.account_id')
            ->get()
            ->keyBy('account_id')
            ->toArray();
            
        return $mutations;
    }
}
