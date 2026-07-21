<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Expenses\Payment;
use App\Models\Expenses\PaymentInvoice;
use App\Models\Settings\BankAccount;
use App\Models\Incomes\Customer;
use App\Models\Incomes\Invoice;
use App\Models\Settings\Company;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected JournalService $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $search  = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $query = Payment::with(['bankAccount', 'invoices.invoice.customer', 'company'])
            ->when($search, fn($q) => $q->where('payment_number', 'like', "%{$search}%")
                ->orWhereHas('invoices.invoice.customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
            )
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage);

        // Fetch all unique account_ids from adjustments to prevent N+1
        $allAccountIds = collect($paginator->items())
            ->flatMap(fn($p) => $p->adjustments ?? [])
            ->pluck('account_id')
            ->filter()
            ->unique();

        $accounts = $allAccountIds->isEmpty() 
            ? collect() 
            : \App\Models\Accounting\Account::whereIn('id', $allAccountIds)->get()->keyBy('id');

        $items = $paginator->map(function($p) use ($accounts) {
            $adjs = $p->adjustments ?? [];
            foreach ($adjs as &$adj) {
                $acc = $accounts->get($adj['account_id'] ?? null);
                $adj['account_name'] = $acc ? "{$acc->code} - {$acc->name}" : 'Unknown Account';
            }

            return [
                'id'             => $p->id,
                'payment_number' => $p->payment_number,
                'company_name'   => $p->company?->name ?? '-',
                'paid_at'        => $p->paid_at?->format('d M Y'),
                'is_locked'      => \App\Helpers\PeriodLockHelper::isLocked($p->paid_at),
                'total_amount'   => $p->total_amount,
                'payment_method' => $p->payment_method,
                'bank_name'      => $p->bankAccount?->name ?? '-',
                'reference'      => $p->reference,
                'status'         => $p->status,
                'paid_invoices'  => $p->invoices->map(fn($pi) => [
                    'id'             => $pi->id,
                    'invoice_id'     => $pi->invoice_id,
                    'invoice_number' => $pi->invoice?->invoice_number ?? '-',
                    'invoice_text'   => $pi->invoice?->invoice_text ?: ($pi->invoice?->invoice_number ?? '-'),
                    'customer_name'  => $pi->invoice?->customer_name ?? '-',
                    'invoice_date'   => $pi->invoice?->invoiced_at ? \Carbon\Carbon::parse($pi->invoice->invoiced_at)->format('d M Y') : '-',
                    'amount'         => $pi->allocated_amount,
                ]),
                'adjustments'    => $adjs,
            ];
        });

        return Inertia::render('Expenses/Payments/Index', [
            'payments'   => $items,
            'pagination' => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
                'from'        => $paginator->firstItem() ?? 0,
                'to'          => $paginator->lastItem() ?? 0,
            ],
            'filters' => ['search' => $search ?? '', 'per_page' => $perPage],
        ]);
    }

    public function create(Request $request)
    {
        $banks = BankAccount::where('enabled', 1)->with('account')->get(['id', 'name', 'type', 'bank_name', 'account_id', 'is_default']);
        $customers = Customer::select('id', 'name')->orderBy('name')->get();
        
        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        $accounts = \App\Models\Accounting\Account::where('company_id', $companyId)
            ->where('enabled', true)
            ->get(['id', 'name', 'code']);

        return Inertia::render('Expenses/Payments/Create', [
            'banks'     => $banks,
            'customers' => $customers,
            'accounts'  => $accounts,
        ]);
    }

    /**
     * API endpoint: Get outstanding invoices (globally searchable).
     */
    public function outstandingInvoices(Request $request)
    {
        $customerId = $request->query('customer_id');

        $query = Invoice::with('customer')
            ->where('invoice_status_code', 'posted')
            ->whereIn('payment_status', ['unpaid', 'partial']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } else {
            // Return empty if no customer selected
            return response()->json([]);
        }

        $invoices = $query->get()->map(function ($inv) {
            $totalPaid = PaymentInvoice::where('invoice_id', $inv->id)->sum('allocated_amount');
            $outstanding = (float) $inv->grand_total - $totalPaid;
            return [
                'id'             => $inv->id,
                'customer_name'  => $inv->customer?->name ?? 'Unknown',
                'invoice_text'   => $inv->invoice_text ?? $inv->invoice_number,
                'invoice_number' => $inv->invoice_number,
                'invoiced_at'    => $inv->invoiced_at,
                'due_at'         => $inv->due_at,
                'amount'         => (float) $inv->grand_total,
                'total_paid'     => $totalPaid,
                'outstanding'    => $outstanding,
                'payment_status' => $inv->payment_status,
            ];
        });

        return response()->json($invoices->filter(fn($i) => $i['outstanding'] > 0)->values());
    }

    public function store(Request $request)
    {
        \App\Helpers\PeriodLockHelper::validateDate($request->paid_at);
        $validated = $request->validate([
            'paid_at'         => 'required|date',
            'payment_method'  => 'required|string',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference'       => 'nullable|string',
            'notes'           => 'nullable|string',
            'allocations'     => 'required|array|min:1',
            'allocations.*.invoice_id'        => 'required|integer|exists:invoices,id',
            'allocations.*.allocated_amount'  => 'required|numeric|min:0.01',
            'adjustments'     => 'nullable|array',
            'adjustments.*.account_id' => 'required|integer|exists:accounts,id',
            'adjustments.*.amount'     => 'required|numeric|min:0',
            'adjustments.*.type'       => 'required|in:addition,deduction',
            'adjustments.*.description'=> 'nullable|string',
        ]);

        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

        // Calculate total allocated
        $totalAllocated = collect($validated['allocations'])->sum('allocated_amount');
        
        $totalAdditions = 0;
        $totalDeductions = 0;
        $adjustments = [];

        if (!empty($validated['adjustments'])) {
            foreach ($validated['adjustments'] as $adj) {
                if ($adj['amount'] > 0) {
                    $adjustments[] = [
                        'account_id' => $adj['account_id'],
                        'amount'     => (float)$adj['amount'],
                        'type'       => $adj['type'],
                        'description'=> $adj['description'] ?? '',
                    ];
                    if ($adj['type'] === 'addition') {
                        $totalAdditions += $adj['amount'];
                    } else {
                        $totalDeductions += $adj['amount'];
                    }
                }
            }
        }
        
        $totalAmount = $totalAllocated + $totalAdditions - $totalDeductions;
        $overpayment = 0;

        return DB::transaction(function () use ($validated, $companyId, $totalAmount, $adjustments, $overpayment) {
            // 1. Create payment
            $payment = Payment::create([
                'company_id'         => $companyId,
                'payment_number'     => $this->journalService->generatePaymentNumber(),
                'paid_at'            => $validated['paid_at'],
                'total_amount'       => $totalAmount,
                'adjustments'        => $adjustments,
                'payment_method'     => $validated['payment_method'],
                'bank_account_id'    => $validated['bank_account_id'],
                'reference'          => $validated['reference'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'overpayment_amount' => $overpayment,
                'status'             => 'posted',
                'created_by'         => auth()->id(),
            ]);

            // 2. Save allocations
            foreach ($validated['allocations'] as $alloc) {
                PaymentInvoice::create([
                    'company_id'       => $companyId,
                    'payment_id'       => $payment->id,
                    'invoice_id'       => $alloc['invoice_id'],
                    'allocated_amount' => $alloc['allocated_amount'],
                ]);

                // 3. Update invoice payment_status
                $this->journalService->updateInvoicePaymentStatus($alloc['invoice_id']);
            }

            // 4. Generate journal
            $this->journalService->createPaymentJournal($payment);

            return redirect()->route('payments.show', $payment->id)
                ->with('success', "Pembayaran {$payment->payment_number} berhasil disimpan.");
        });
    }

    public function show(Payment $payment)
    {
        $payment->load(['bankAccount', 'invoices.invoice.customer', 'journal.ledgers.account']);

        $allocations = $payment->invoices->map(fn($pi) => [
            'invoice_id'       => $pi->invoice_id,
            'invoice_text'     => $pi->invoice?->invoice_text ?? $pi->invoice?->invoice_number,
            'customer_name'    => $pi->invoice?->customer?->name,
            'invoiced_at'      => $pi->invoice?->invoiced_at,
            'allocated_amount' => (float) $pi->allocated_amount,
        ]);

        $customerNames = $payment->invoices->map(fn($pi) => $pi->invoice?->customer?->name)->filter()->unique()->implode(', ');

        $adjs = $payment->adjustments ?? [];
        if (!empty($adjs)) {
            $accountIds = collect($adjs)->pluck('account_id')->unique();
            $accounts = \App\Models\Accounting\Account::whereIn('id', $accountIds)->get()->keyBy('id');
            foreach ($adjs as &$adj) {
                $acc = $accounts->get($adj['account_id']);
                $adj['account_name'] = $acc ? "{$acc->code} - {$acc->name}" : 'Unknown Account';
            }
        }

        return Inertia::render('Expenses/Payments/Show', [
            'payment'     => [
                'id'             => $payment->id,
                'payment_number' => $payment->payment_number,
                'customer_name'  => $customerNames,
                'paid_at'        => $payment->paid_at?->format('d M Y'),
                'total_amount'   => (float) $payment->total_amount,
                'payment_method' => $payment->payment_method,
                'bank_name'      => $payment->bankAccount?->name,
                'reference'      => $payment->reference,
                'notes'          => $payment->notes,
                'overpayment'    => (float) $payment->overpayment_amount,
                'adjustments'    => $adjs,
                'status'         => $payment->status,
            ],
            'allocations' => $allocations,
            'journal'     => $payment->journal ? [
                'journal_number' => $payment->journal->journal_number,
                'date'           => $payment->journal->date?->format('d M Y'),
                'ledgers'        => $payment->journal->ledgers->map(fn($l) => [
                    'account' => "{$l->account?->code} - {$l->account?->name}",
                    'debit'   => (float) $l->debit,
                    'credit'  => (float) $l->credit,
                    'description' => $l->description,
                ]),
            ] : null,
        ]);
    }

    public function destroy(Payment $payment)
    {
        \App\Helpers\PeriodLockHelper::validateDate($payment->paid_at);

        // If it's still posted, unpost it first to clean up journals
        if ($payment->status === 'posted') {
            $this->journalService->unpostPaymentJournal($payment);
        }

        // Grab invoice IDs to recalculate their status after deletion
        $invoiceIds = $payment->invoices()->pluck('invoice_id')->unique();

        // Hard delete the allocations and the payment itself to avoid junk data
        $payment->invoices()->delete();
        $payment->forceDelete();

        // Recalculate invoice payment_status
        foreach ($invoiceIds as $invoiceId) {
            $this->journalService->updateInvoicePaymentStatus($invoiceId);
        }

        return redirect()->route('payments.index')->with('success', 'Pembayaran berhasil dihapus permanen.');
    }

    public function unpost(Payment $payment)
    {
        if ($payment->status !== 'posted') {
            return back()->with('error', 'Payment ini belum diposting.');
        }

        try {
            \App\Helpers\PeriodLockHelper::validateDate($payment->paid_at);
            
            $this->journalService->unpostPaymentJournal($payment);
            $payment->update(['status' => 'draft']);
            return back()->with('success', 'Berhasil membatalkan posting payment.');
        } catch (\Exception $e) {
            \Log::error('Error unposting payment: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan posting: ' . $e->getMessage());
        }
    }
}
