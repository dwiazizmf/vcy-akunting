<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Accounting\Account;
use App\Services\ReportService;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function trialBalance(Request $request)
    {
        // Default to current month if not provided
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Only get child accounts (postable)
        $accounts = Account::with('type')
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $beginningBalances = $this->reportService->getBeginningBalances($startDate);
        $mutations = $this->reportService->getMutations($startDate, $endDate);

        $reportData = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $baseBal = $beginningBalances[$account->id] ?? 0;
            $mut = $mutations[$account->id] ?? null;
            
            $debitMut = $mut ? (float) $mut['total_debit'] : 0;
            $creditMut = $mut ? (float) $mut['total_credit'] : 0;
            
            $category = $account->type->category;
            
            if (in_array($category, ['Asset', 'Expense'])) {
                $endBal = $baseBal + $debitMut - $creditMut;
            } else {
                $endBal = $baseBal + $creditMut - $debitMut;
            }

            // Only show accounts that have balance or mutation
            if ($baseBal != 0 || $debitMut != 0 || $creditMut != 0) {
                $reportData[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'category' => $category,
                    'beginning_balance' => $baseBal,
                    'debit' => $debitMut,
                    'credit' => $creditMut,
                    'ending_balance' => $endBal,
                ];

                // For Trial Balance totals, we usually sum the ending balances in their respective normal columns
                if (in_array($category, ['Asset', 'Expense'])) {
                    if ($endBal >= 0) $totalDebit += $endBal;
                    else $totalCredit += abs($endBal);
                } else {
                    if ($endBal >= 0) $totalCredit += $endBal;
                    else $totalDebit += abs($endBal);
                }
            }
        }

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'data' => $reportData,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit
            ]
        ]);
    }

    public function profitAndLoss(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // PnL only uses Revenue and Expense accounts
        $accounts = Account::with('type')
            ->whereHas('type', function($q) {
                $q->whereIn('category', ['Revenue', 'Expense']);
            })
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $mutations = $this->reportService->getMutations($startDate, $endDate);

        $revenues = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($accounts as $account) {
            $mut = $mutations[$account->id] ?? null;
            if (!$mut) continue;

            $debitMut = (float) $mut['total_debit'];
            $creditMut = (float) $mut['total_credit'];
            $category = $account->type->category;
            
            // For PnL, we just care about the net change in the period
            if ($category === 'Revenue') {
                $net = $creditMut - $debitMut; // Revenue normal is credit
                if ($net != 0) {
                    $revenues[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'type_name' => $account->type->name,
                        'amount' => $net
                    ];
                    $totalRevenue += $net;
                }
            } else if ($category === 'Expense') {
                $net = $debitMut - $creditMut; // Expense normal is debit
                if ($net != 0) {
                    $expenses[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'type_name' => $account->type->name,
                        'amount' => $net
                    ];
                    $totalExpense += $net;
                }
            }
        }

        return Inertia::render('Accounting/Reports/ProfitAndLoss', [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit' => $totalRevenue - $totalExpense,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function balanceSheet(Request $request)
    {
        // Balance sheet is "As of" a specific date. We can use endDate.
        // For beginning balance, we'll start from start of year to calculate YTD Retained Earnings properly.
        // Wait, Balance Sheet shows cumulative balances since the beginning of time.
        // Our getBeginningBalances() handles this automatically!
        
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        // We use $endDate as the date to get balances *up to*.
        // So we just need getBeginningBalances for ($endDate + 1 day), which effectively includes $endDate.
        $dateAfterEnd = Carbon::parse($endDate)->addDay()->format('Y-m-d');
        
        $balances = $this->reportService->getBeginningBalances($dateAfterEnd);

        $accounts = Account::with('type')
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $assets = [];
        $liabilities = [];
        $equities = [];
        
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquities = 0;
        $currentYearEarnings = 0;

        foreach ($accounts as $account) {
            $bal = $balances[$account->id] ?? 0;
            $category = $account->type->category;

            if ($category === 'Revenue') {
                $currentYearEarnings += $bal; // Revenue is positive Credit
            } else if ($category === 'Expense') {
                $currentYearEarnings -= $bal; // Expense is positive Debit
            } else {
                if ($bal != 0) {
                    $item = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'type_name' => $account->type->name,
                        'amount' => $bal
                    ];
                    
                    if ($category === 'Asset') {
                        $assets[] = $item;
                        $totalAssets += $bal;
                    } else if ($category === 'Liability') {
                        $liabilities[] = $item;
                        $totalLiabilities += $bal;
                    } else if ($category === 'Equity') {
                        $equities[] = $item;
                        $totalEquities += $bal;
                    }
                }
            }
        }

        // Add Current Year Earnings to Equity
        if ($currentYearEarnings != 0) {
            $equities[] = [
                'code' => '319999', // Virtual code
                'name' => 'Laba Berjalan (Current Earnings)',
                'type_name' => 'Ekuitas',
                'amount' => $currentYearEarnings,
                'is_virtual' => true
            ];
            $totalEquities += $currentYearEarnings;
        }

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equities' => $totalEquities,
            'total_liabilities_equities' => $totalLiabilities + $totalEquities,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }
}
