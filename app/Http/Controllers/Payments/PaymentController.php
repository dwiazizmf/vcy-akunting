<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentInvoice;
use App\Models\BankAccount;
use App\Models\Customer;
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

        $query = Payment::with('bankAccount')
            ->when($search, fn($q) => $q->where('payment_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
            )
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage);

        $items = $paginator->map(fn($p) => [
            'id'             => $p->id,
            'payment_number' => $p->payment_number,
            'customer_name'  => $p->customer_name,
            'paid_at'        => $p->paid_at?->format('d M Y'),
            'total_amount'   => $p->total_amount,
            'payment_method' => $p->payment_method,
            'bank_name'      => $p->bankAccount?->name ?? '-',
            'reference'      => $p->reference,
            'status'         => $p->status,
        ]);

        return Inertia::render('Payments/Index', [
            'payments'   => $items,
            'pagination' => [
                'total'       => $paginator->total(),
                'perPage'     => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage'    => $paginator->lastPage(),
            ],
            'filters' => ['search' => $search ?? '', 'per_page' => $perPage],
        ]);
    }

    public function create(Request $request)
    {
        $banks     = BankAccount::where('enabled', 1)->with('account')->get(['id', 'name', 'type', 'bank_name', 'account_id', 'is_default']);
        $customers = Customer::select('id', 'name')->get();

        // Pre-load invoices if customer_id is provided
        $selectedCustomerId = $request->input('customer_id');
        $outstandingInvoices = [];
        if ($selectedCustomerId) {
            $outstandingInvoices = $this->getOutstanding($selectedCustomerId);
        }

        return Inertia::render('Payments/Create', [
            'banks'              => $banks,
            'customers'          => $customers,
            'selectedCustomerId' => $selectedCustomerId ? (int) $selectedCustomerId : null,
            'outstandingInvoices' => $outstandingInvoices,
        ]);
    }

    /**
     * API endpoint: Get outstanding invoices for a customer.
     */
    public function outstandingInvoices(Customer $customer)
    {
        return response()->json($this->getOutstanding($customer->id));
    }

    private function getOutstanding(int $customerId): array
    {
        return Invoice::where('customer_id', $customerId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('invoice_status_code', 'posted')
            ->get()
            ->map(function ($inv) {
                $totalPaid = PaymentInvoice::where('invoice_id', $inv->id)->sum('allocated_amount');
                $outstanding = (float) $inv->amount - $totalPaid;
                return [
                    'id'             => $inv->id,
                    'invoice_text'   => $inv->invoice_text ?? $inv->invoice_number,
                    'invoice_number' => $inv->invoice_number,
                    'invoiced_at'    => $inv->invoiced_at,
                    'due_at'         => $inv->due_at,
                    'amount'         => (float) $inv->amount,
                    'total_paid'     => $totalPaid,
                    'outstanding'    => $outstanding,
                    'payment_status' => $inv->payment_status,
                ];
            })
            ->toArray();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'     => 'required|integer|exists:customers,id',
            'paid_at'         => 'required|date',
            'total_amount'    => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:cash,transfer,giro,cheque',
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'reference'       => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'allocations'     => 'required|array|min:1',
            'allocations.*.invoice_id'        => 'required|integer|exists:invoices,id',
            'allocations.*.allocated_amount'  => 'required|numeric|min:0.01',
        ]);

        $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;
        $customer  = Customer::find($validated['customer_id']);

        // Calculate total allocated
        $totalAllocated = collect($validated['allocations'])->sum('allocated_amount');
        $overpayment    = max(0, (float) $validated['total_amount'] - $totalAllocated);

        return DB::transaction(function () use ($validated, $companyId, $customer, $overpayment) {
            // 1. Create payment
            $payment = Payment::create([
                'company_id'         => $companyId,
                'payment_number'     => $this->journalService->generatePaymentNumber(),
                'customer_id'        => $validated['customer_id'],
                'customer_name'      => $customer->name,
                'paid_at'            => $validated['paid_at'],
                'total_amount'       => $validated['total_amount'],
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
        $payment->load(['bankAccount', 'customer', 'invoices.invoice', 'journal.ledgers.account']);

        $allocations = $payment->invoices->map(fn($pi) => [
            'invoice_id'       => $pi->invoice_id,
            'invoice_text'     => $pi->invoice?->invoice_text ?? $pi->invoice?->invoice_number,
            'invoiced_at'      => $pi->invoice?->invoiced_at,
            'allocated_amount' => (float) $pi->allocated_amount,
        ]);

        return Inertia::render('Payments/Show', [
            'payment'     => [
                'id'             => $payment->id,
                'payment_number' => $payment->payment_number,
                'customer_name'  => $payment->customer_name,
                'paid_at'        => $payment->paid_at?->format('d M Y'),
                'total_amount'   => (float) $payment->total_amount,
                'payment_method' => $payment->payment_method,
                'bank_name'      => $payment->bankAccount?->name,
                'reference'      => $payment->reference,
                'notes'          => $payment->notes,
                'overpayment'    => (float) $payment->overpayment_amount,
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
        // TODO: Reverse journal before deleting
        $payment->delete();
        return redirect()->route('payments.index')->with('success', 'Pembayaran dihapus.');
    }
}
