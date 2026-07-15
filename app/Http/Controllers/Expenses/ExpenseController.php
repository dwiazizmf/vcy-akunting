<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseItem;
use App\Models\Expenses\Vendor;
use App\Models\Accounting\Account;
use App\Models\Settings\Tax;
use App\Models\Settings\BankAccount;
use Illuminate\Support\Facades\DB;
use App\Services\JournalService;

class ExpenseController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Expense::with(['vendor', 'items', 'company'])
            ->when($search, function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%");
            })
            ->when($status, function ($q) use ($status) {
                $q->where('expense_status_code', $status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('expense_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('expense_date', '<=', $dateTo);
            });

        // Hide 'void' status from main view unless specifically filtered
        if (empty($status)) {
            $query->where('expense_status_code', '!=', 'void');
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $paginator->getCollection()->transform(function ($expense) {
            $expense->company_name = $expense->company?->name ?? '-';
            return $expense;
        });

        $pagination = [
            'total'       => $paginator->total(),
            'perPage'     => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'from'        => $paginator->firstItem() ?: 0,
            'to'          => $paginator->lastItem() ?: 0,
        ];

        $totalAll = Expense::where('expense_status_code', '!=', 'void')->count();

        $stats = [
            'total'         => $totalAll,
            'totalFiltered' => $paginator->total(),
            'draft'         => Expense::where('expense_status_code', 'draft')->count(),
            'posted'        => Expense::where('expense_status_code', 'posted')->count(),
            'totalAmount'   => Expense::where('expense_status_code', '!=', 'void')->sum('grand_total'),
        ];

        return Inertia::render('Expenses/Bills/Index', [
            'expenses'   => $paginator->items(),
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => [
                'search'    => $search ?? '',
                'status'    => $status ?? '',
                'date_from' => $dateFrom ?? '',
                'date_to'   => $dateTo ?? '',
                'per_page'  => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $vendors = Vendor::all();
        $accounts = Account::where('enabled', true)->get(); // Should filter by Expense/Cost of Sales
        $taxes = Tax::all();
        $bankAccounts = BankAccount::with('account')->get();

        return Inertia::render('Expenses/Bills/Form', [
            'expense' => new Expense(),
            'vendors' => $vendors,
            'accounts' => $accounts,
            'taxes' => $taxes,
            'bankAccounts' => $bankAccounts,
            'isEdit' => false
        ]);
    }

    public function store(Request $request)
    {
        \App\Helpers\PeriodLockHelper::validateDate($request->date);
        // Validation logic
        $validated = $request->validate([
            'expense_number' => 'required|string|max:191',
            'vendor_id' => 'nullable|exists:vendors,id',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_direct_expense' => 'required|boolean',
            'bank_account_id' => 'nullable|required_if:is_direct_expense,true|exists:bank_accounts,id',
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric',
            'items.*.tax_id' => 'nullable|exists:taxes,id',
        ]);

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            $tax_total = 0;

            foreach ($request->items as $item) {
                $itemAmount = floatval($item['amount']);
                $itemTax = 0;
                if (!empty($item['tax_id'])) {
                    $tax = \App\Models\Settings\Tax::find($item['tax_id']);
                    if ($tax) {
                        $itemTax = $tax->type === 'fixed' ? floatval($tax->rate) : $itemAmount * ($tax->rate / 100);
                    }
                }
                $subtotal += $itemAmount;
                $tax_total += $itemTax;
            }

            $vendor = Vendor::find($request->vendor_id);
            $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 1);

            $expense = Expense::create([
                'company_id' => $companyId,
                'expense_number' => $request->expense_number,
                'vendor_id' => $request->vendor_id,
                'vendor_name' => $vendor ? $vendor->name : null,
                'expense_date' => $request->expense_date,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'is_direct_expense' => $request->is_direct_expense,
                'bank_account_id' => $request->bank_account_id,
                'subtotal' => $subtotal,
                'total_item_tax' => $tax_total,
                'grand_total' => $subtotal + $tax_total,
                'expense_status_code' => 'draft',
                'payment_status' => $request->is_direct_expense ? 'paid' : 'unpaid',
            ]);

            foreach ($request->items as $item) {
                $itemAmount = floatval($item['amount']);
                $itemTax = 0;
                $taxDetails = null;

                if (!empty($item['tax_id'])) {
                    $tax = \App\Models\Settings\Tax::find($item['tax_id']);
                    if ($tax) {
                        $itemTax = $tax->type === 'fixed' ? floatval($tax->rate) : $itemAmount * ($tax->rate / 100);
                        $taxDetails = [
                            ['id' => $tax->id, 'name' => $tax->name, 'rate' => $tax->rate, 'amount' => $itemTax]
                        ];
                    }
                }

                ExpenseItem::create([
                    'company_id' => $companyId,
                    'expense_id' => $expense->id,
                    'account_id' => $item['account_id'],
                    'description' => $item['description'],
                    'amount' => $itemAmount,
                    'tax_amount' => $itemTax,
                    'tax_details' => $taxDetails,
                    'total' => $itemAmount + $itemTax,
                ]);
            }

            DB::commit();
            return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(Expense $expense)
    {
        $expense->load('items');
        $vendors = Vendor::all();
        $accounts = Account::where('enabled', true)->get();
        $taxes = Tax::all();
        $bankAccounts = BankAccount::with('account')->get();

        return Inertia::render('Expenses/Bills/Form', [
            'expense' => $expense,
            'vendors' => $vendors,
            'accounts' => $accounts,
            'taxes' => $taxes,
            'bankAccounts' => $bankAccounts,
            'isEdit' => true
        ]);
    }

    // update, post, unpost, destroy to be implemented...

    public function post(Expense $expense)
    {
        if ($expense->expense_status_code === 'posted') {
            return back()->withErrors(['error' => 'Expense is already posted.']);
        }

        try {
            $this->journalService->postExpense($expense);
            return back()->with('success', 'Expense posted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to post expense: ' . $e->getMessage()]);
        }
    }

    public function unpost(Expense $expense)
    {
        if ($expense->expense_status_code !== 'posted') {
            return back()->withErrors(['error' => 'Expense is not posted.']);
        }

        try {
            $this->journalService->unpostExpense($expense);
            return back()->with('success', 'Expense unposted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to unpost expense: ' . $e->getMessage()]);
        }
    }

    public function bulkPost(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:expenses,id'
        ]);

        $expenses = Expense::whereIn('id', $request->ids)->where('expense_status_code', 'draft')->get();
        $count = 0;
        
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($expenses as $expense) {
                $this->journalService->postExpense($expense);
                $count++;
            }
            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', "{$count} expenses posted successfully.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->withErrors(['error' => 'Failed to bulk post expenses: ' . $e->getMessage()]);
        }
    }
}
