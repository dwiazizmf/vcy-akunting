<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Accounting\CoaMonthlyBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoaBalanceService
{
    /**
     * Determine normal balance of an account based on its type category
     */
    public function getNormalBalance(Account $account): string
    {
        $category = $account->type->category ?? '';
        if (in_array($category, ['Asset', 'Expense'])) {
            return 'debit';
        }
        return 'credit'; // Liability, Equity, Revenue
    }

    /**
     * Apply a change in debit/credit to a specific month and cascade forwards.
     * $deltaDebit and $deltaCredit can be positive (added) or negative (removed/voided).
     */
    public function applyDelta(Account $account, string $dateString, $deltaDebit, $deltaCredit)
    {
        if ($deltaDebit == 0 && $deltaCredit == 0) {
            return;
        }

        $date = Carbon::parse($dateString);
        $startYear = $date->year;
        $startMonth = $date->month;
        $accountId = $account->id;
        $companyId = $account->company_id;

        DB::transaction(function () use ($companyId, $account, $startYear, $startMonth, $deltaDebit, $deltaCredit) {
            $normalBalance = $this->getNormalBalance($account);
            
            // Get the ending balance of the closest previous month
            $previousBalance = $this->getEndingBalanceBefore($companyId, $account->id, $startYear, $startMonth);

            // Fetch all records from the start month onwards, ordered
            $balances = CoaMonthlyBalance::where('company_id', $companyId)
                ->where('account_id', $account->id)
                ->where(function($q) use ($startYear, $startMonth) {
                    $q->where('period_year', '>', $startYear)
                      ->orWhere(function($q2) use ($startYear, $startMonth) {
                          $q2->where('period_year', $startYear)
                             ->where('period_month', '>=', $startMonth);
                      });
                })
                ->orderBy('period_year', 'asc')
                ->orderBy('period_month', 'asc')
                ->lockForUpdate()
                ->get();

            // Check if start month exists, if not create it
            $startMonthRecord = $balances->first(function ($b) use ($startYear, $startMonth) {
                return $b->period_year == $startYear && $b->period_month == $startMonth;
            });

            if (!$startMonthRecord) {
                $startMonthRecord = CoaMonthlyBalance::create([
                    'company_id' => $companyId,
                    'account_id' => $account->id,
                    'period_year' => $startYear,
                    'period_month' => $startMonth,
                    'beginning_balance' => $previousBalance,
                    'debit_mutation' => 0,
                    'credit_mutation' => 0,
                    'ending_balance' => $previousBalance,
                ]);
                
                // Re-fetch to guarantee order, or just inject and sort
                $balances->push($startMonthRecord);
                $balances = $balances->sortBy(function($b) {
                    return $b->period_year * 100 + $b->period_month;
                })->values();
            }

            // Iterate and cascade
            $currentBeginning = $previousBalance;
            
            foreach ($balances as $record) {
                $record->beginning_balance = $currentBeginning;
                
                // Only apply the delta mutation to the starting month
                if ($record->period_year == $startYear && $record->period_month == $startMonth) {
                    $record->debit_mutation += $deltaDebit;
                    $record->credit_mutation += $deltaCredit;
                }
                
                // Recalculate ending balance
                if ($normalBalance === 'debit') {
                    $record->ending_balance = $record->beginning_balance + $record->debit_mutation - $record->credit_mutation;
                } else {
                    $record->ending_balance = $record->beginning_balance + $record->credit_mutation - $record->debit_mutation;
                }
                
                $record->save();
                
                // Set the beginning balance for the next month
                $currentBeginning = $record->ending_balance;
            }
        });
    }

    private function getEndingBalanceBefore($companyId, $accountId, $year, $month)
    {
        $record = CoaMonthlyBalance::where('company_id', $companyId)
            ->where('account_id', $accountId)
            ->where(function($q) use ($year, $month) {
                $q->where('period_year', '<', $year)
                  ->orWhere(function($q2) use ($year, $month) {
                      $q2->where('period_year', $year)
                         ->where('period_month', '<', $month);
                  });
            })
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->first();
            
        return $record ? $record->ending_balance : 0;
    }
}
