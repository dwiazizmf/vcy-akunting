<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expenses\ExpensePayment;
use App\Models\Expenses\ExpensePaymentLine;
use App\Models\Expenses\Expense;
use App\Models\Expenses\Vendor;
use App\Models\Settings\PaymentCategory;
use App\Models\Settings\PaymentLimit;
use App\Models\Settings\Tax;
use App\Models\Accounting\Account;
use App\Services\JournalService;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ExpensePaymentController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $paymentsQuery = ExpensePayment::with(['vendor', 'account', 'paymentCategory', 'company'])
            ->when($search, function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            })
            ->orderBy('id', 'desc');

        $paginator = $paymentsQuery->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function ($payment) {
            $payment->company_name = $payment->company?->name ?? '-';
            return $payment;
        });

        $pagination = [
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?: 0,
            'to' => $paginator->lastItem() ?: 0,
        ];

        return Inertia::render('Expenses/BillsPayments/Index', [
            'payments' => $paginator->items(),
            'pagination' => $pagination,
            'filters' => ['search' => $search, 'per_page' => $perPage]
        ]);
    }

    public function create(Request $request)
    {
        $vendorId = $request->input('vendor_id');
        $vendors = Vendor::all();
        $categories = PaymentCategory::all();
        $accounts = Account::where('enabled', true)->get();
        $taxes = Tax::all();
        
        $unpaidExpenses = [];
        if ($vendorId) {
            $unpaidExpenses = Expense::where('vendor_id', $vendorId)
                ->where('expense_status_code', 'posted')
                ->where('is_direct_expense', false)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->get();
        }

        return Inertia::render('Expenses/BillsPayments/Create', [
            'vendors' => $vendors,
            'categories' => $categories,
            'accounts' => $accounts,
            'taxes' => $taxes,
            'unpaidExpenses' => $unpaidExpenses,
            'selectedVendorId' => $vendorId
        ]);
    }

    public function store(Request $request)
    {
        \App\Helpers\PeriodLockHelper::validateDate($request->payment_date);
        $request->validate([
            'payment_date' => 'required|date',
            'vendor_id' => 'required|exists:vendors,id',
            'payment_category_id' => 'required|exists:payment_categories,id',
            'account_id' => 'required|exists:accounts,id',
            'lines' => 'required|array|min:1',
            'lines.*.expense_id' => 'required|exists:expenses,id',
            'lines.*.amount_paid' => 'required|numeric|min:0.01',
            'taxes' => 'nullable|array',
            'taxes.*.tax_id' => 'required|exists:taxes,id',
        ]);

        $companyId = session('company_id') ?: (\App\Models\Settings\Company::where('enabled', 1)->first()?->id ?? 0);

        // 1. Calculate Gross Payment Amount
        $grossAmount = 0;
        foreach ($request->lines as $line) {
            $grossAmount += floatval($line['amount_paid']);
        }

        // 2. Validate Payment Limit
        $limit = PaymentLimit::where('payment_category_id', $request->payment_category_id)
            ->where('account_id', $request->account_id)
            ->first();

        if ($limit) {
            if ($grossAmount > floatval($limit->limit_amount)) {
                return back()->withErrors(['error' => "Total pembayaran (Rp " . number_format($grossAmount, 2, ',', '.') . ") melebihi Limit COA (Rp " . number_format($limit->limit_amount, 2, ',', '.') . "). Silakan sesuaikan."]);
            }
        }

        // 3. Calculate Taxes
        $totalTax = 0;
        $taxDetails = [];
        if (!empty($request->taxes)) {
            foreach ($request->taxes as $taxReq) {
                $tax = Tax::find($taxReq['tax_id']);
                if ($tax) {
                    $taxVal = $tax->type === 'fixed' ? floatval($tax->rate) : $grossAmount * ($tax->rate / 100);
                    $totalTax += $taxVal;
                    $taxDetails[] = [
                        'id' => $tax->id,
                        'name' => $tax->name,
                        'rate' => $tax->rate,
                        'amount' => $taxVal
                    ];
                }
            }
        }

        $netAmount = $grossAmount - $totalTax; // the amount effectively withdrawn from bank

        try {
            DB::beginTransaction();

            $paymentNumber = app(\App\Services\DocumentNumberService::class)->generateNext(
                \App\Models\Expenses\ExpensePayment::class,
                'payment_number',
                function($maxNumber) {
                    $prefix = 'PAY-EXP-' . date('Ym') . '-';
                    if ($maxNumber && str_starts_with($maxNumber, $prefix)) {
                        $lastNumber = intval(substr($maxNumber, -4));
                        $newNumber = $lastNumber + 1;
                    } else {
                        $newNumber = 1;
                    }
                    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                },
                function($query) use ($companyId) {
                    $prefix = 'PAY-EXP-' . date('Ym') . '-';
                    return $query->where('company_id', $companyId)
                                 ->where('payment_number', 'like', $prefix . '%')
                                 ->orderBy('payment_number', 'desc');
                }
            );

            $payment = ExpensePayment::create([
                'company_id' => $companyId,
                'payment_number' => $paymentNumber,
                'payment_date' => $request->payment_date,
                'vendor_id' => $request->vendor_id,
                'payment_category_id' => $request->payment_category_id,
                'account_id' => $request->account_id,
                'total_amount' => $netAmount,
                'total_tax' => $totalTax,
                'tax_details' => $taxDetails,
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => auth()->id()
            ]);

            foreach ($request->lines as $line) {
                ExpensePaymentLine::create([
                    'expense_payment_id' => $payment->id,
                    'expense_id' => $line['expense_id'],
                    'amount_paid' => $line['amount_paid'],
                ]);
            }

            DB::commit();

            return redirect()->route('expense-payments.index')->with('success', 'Pembayaran Bill berhasil disimpan (Draft).');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan pembayaran: ' . $e->getMessage()]);
        }
    }

    public function post(ExpensePayment $expense_payment)
    {
        if ($expense_payment->status === 'posted') {
            return back()->withErrors(['error' => 'Payment is already posted.']);
        }

        try {
            DB::beginTransaction();
            $this->journalService->postExpensePayment($expense_payment);
            $expense_payment->update(['status' => 'posted']);
            DB::commit();
            return back()->with('success', 'Payment posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to post payment: ' . $e->getMessage()]);
        }
    }

    public function unpost(ExpensePayment $expense_payment)
    {
        if ($expense_payment->status !== 'posted') {
            return back()->withErrors(['error' => 'Payment is not posted.']);
        }

        try {
            DB::beginTransaction();
            $this->journalService->unpostExpensePayment($expense_payment);
            $expense_payment->update(['status' => 'draft']);
            DB::commit();
            return back()->with('success', 'Payment unposted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to unpost payment: ' . $e->getMessage()]);
        }
    }

    public function bulkPost(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:expense_payments,id'
        ]);

        $payments = ExpensePayment::whereIn('id', $request->ids)->where('status', 'draft')->get();
        $count = 0;
        
        DB::beginTransaction();
        try {
            foreach ($payments as $payment) {
                $this->journalService->postExpensePayment($payment);
                $payment->update(['status' => 'posted']);
                $count++;
            }
            DB::commit();
            return back()->with('success', "{$count} payments posted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to bulk post payments: ' . $e->getMessage()]);
        }
    }
}
