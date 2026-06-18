<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Incomes\Invoice;
use App\Models\Incomes\InvoiceItem;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Settings\Discount;
use App\Helpers\InvoiceHelper;
use Inertia\Inertia;
use App\Services\TaxService;
use App\Services\JournalService;

class InvoiceController extends Controller
{
    protected TaxService $taxService;
    protected JournalService $journalService;

    public function __construct(TaxService $taxService, JournalService $journalService)
    {
        $this->taxService     = $taxService;
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $search = $request->input('search');
        $status = $request->input('status');
        $kapal = $request->input('kapal');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Eager load items to pass to frontend for expandable rows
        $query = Invoice::with('items')->filter($request->all());

        // Hide 'void' status from main view unless specifically filtered
        if (empty($status)) {
            $query->where('invoice_status_code', '!=', 'void');
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        $items = $paginator->map(function ($inv) {
            return [
                'id'              => $inv->id,
                'no'              => $inv->id,
                'invoiceText'     => $inv->invoice_text ?: $inv->invoice_number,
                'number'          => $inv->invoice_number,
                'orderNumber'     => $inv->order_number,
                'coa'             => '-',
                'customer'        => $inv->customer_name,
                'amount_raw'      => (float) $inv->grand_total,
                'amount'          => 'Rp ' . number_format((float) $inv->grand_total, 0, ',', '.'),
                'namaKapal'       => $inv->nama_kapal,
                'tglKapBerangkat' => $inv->departure_date ? date('Y-m-d', strtotime($inv->departure_date)) : '-',
                'invoiceDate'     => $inv->invoiced_at ? date('d M Y', strtotime($inv->invoiced_at)) : '-',
                'dueDate'         => $inv->due_at ? date('d M Y', strtotime($inv->due_at)) : '-',
                'noDokumenKirim'  => '3 Dokumen',
                'noTitipInternal' => 'TI-' . str_pad($inv->id % 1000, 4, '0', STR_PAD_LEFT),
                'tglDokumenKirim' => '-',
                'status'          => $inv->invoice_status_code ?? 'draft',
                'statusPayment'   => $inv->invoice_status_code ?? 'draft',
                'notes'           => $inv->notes_text,
                'statusCreated'   => $inv->create_on ?: '-',
                'faktur'          => $inv->no_faktur_pajak ?? $inv->no_faktur_int ?? '-',
                'bpb'             => $inv->isBpb ? 'Ya' : 'Tidak',
                'items'           => $inv->items, // Passing items array
            ];
        });

        $pagination = [
            'total'       => $paginator->total(),
            'perPage'     => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'from'        => $paginator->firstItem() ?: 0,
            'to'          => $paginator->lastItem() ?: 0,
        ];

        $totalAll = Invoice::where('invoice_status_code', '!=', 'void')->count();

        $stats = [
            'total'         => $totalAll,
            'totalFiltered' => $paginator->total(),
            'draft'         => Invoice::where('invoice_status_code', 'draft')->count(),
            'sent'          => Invoice::where('invoice_status_code', 'sent')->count(),
            'paid'          => Invoice::where('invoice_status_code', 'paid')->count(),
            'totalAmount'   => Invoice::where('invoice_status_code', '!=', 'void')->sum('grand_total'),
        ];

        return Inertia::render('Invoices/Index', [
            'invoices'   => $items,
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => [
                'search'    => $search ?? '',
                'status'    => $status ?? '',
                'kapal'     => $kapal ?? '',
                'date_from' => $dateFrom ?? '',
                'date_to'   => $dateTo ?? '',
                'per_page'  => $perPage,
            ],
        ]);
    }

    public function create()
    {
        $companyId = session('company_id') ?: 1;
        $activeTaxes = $this->taxService->getActiveTaxes();
        $activeDiscounts = Discount::all(['id', 'name', 'rate', 'type']);
        $customers   = Customer::select('id', 'name')->get();
        $revenueAccounts = \App\Helpers\AccountHelper::getFormattedAccounts($companyId, 'Revenue');
        $invoiceTypes = \App\Models\Incomes\InvoiceType::all();
        
        return Inertia::render('Invoices/Create', [
            'activeTaxes'     => $activeTaxes,
            'activeDiscounts' => $activeDiscounts,
            'customers'       => $customers,
            'revenueAccounts' => $revenueAccounts,
            'invoiceTypes'    => $invoiceTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|integer',
            'customer_name'       => 'required|string',
            'account_id'          => 'nullable|integer|exists:accounts,id',
            'invoice_type_id'     => 'nullable|integer|exists:invoice_type,id',
            'invoiced_at'         => 'required|date',
            'due_at'              => 'required|date|after_or_equal:invoiced_at',
            'order_number'        => 'nullable|string',
            'nama_kapal'          => 'nullable|string',
            'departure_date'      => 'nullable|date',
            'pelabuhan_asal'      => 'nullable|string',
            'pelabuhan_tujuan'    => 'nullable|string',
            'voy'                 => 'nullable|string',
            'notes'               => 'nullable|string',
            'no_faktur_pajak'     => 'nullable|string',
            'header_tax_details'  => 'nullable|array',
            'header_discount_details' => 'nullable|array',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string',
            'items.*.quantity'    => 'required|numeric',
            'items.*.price'       => 'required|numeric',
        ]);

        // Server-side calculation
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['price']);
        }

        $headerTaxes = $this->taxService->parseHeaderTaxes($validated['header_tax_details'] ?? []);
        $totalTax = 0;
        foreach ($headerTaxes as $tax) {
            $totalTax += $tax['amount']; // Use the amount sent from frontend (handles both fixed and percentage correctly)
        }

        $headerDiscounts = collect($validated['header_discount_details'] ?? [])->map(function ($discount) {
            return [
                'name' => $discount['name'] ?? 'Unknown Discount',
                'rate' => (float) ($discount['rate'] ?? 0),
                'amount' => (float) ($discount['amount'] ?? 0),
            ];
        })->toArray();
        $totalDiscount = 0;
        foreach ($headerDiscounts as $discount) {
            $totalDiscount += $discount['amount'];
        }

        $grandTotal = $subtotal - $totalDiscount + $totalTax;
        
        $companyId = session('company_id') ?: 1;
        $invoiceData = InvoiceHelper::generateInvoiceData($validated['invoiced_at']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $companyId, $invoiceData, $subtotal, $grandTotal, $totalTax, $headerTaxes, $totalDiscount, $headerDiscounts) {
            $invoice = Invoice::create([
                'company_id'          => $companyId,
                'customer_id'         => $validated['customer_id'],
                'customer_name'       => $validated['customer_name'],
                'account_id'          => $validated['account_id'] ?? null,
                'invoice_type_id'     => $validated['invoice_type_id'] ?? null,
                'invoice_number'      => $invoiceData['invoice_number'], 
                'invoice_text'        => $invoiceData['invoice_text'],
                'order_number'        => $validated['order_number'] ?? null,
                'nama_kapal'          => $validated['nama_kapal'] ?? null,
                'departure_date'      => $validated['departure_date'] ?? null,
                'pelabuhan_asal'      => $validated['pelabuhan_asal'] ?? null,
                'pelabuhan_tujuan'    => $validated['pelabuhan_tujuan'] ?? null,
                'voy'                 => $validated['voy'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'no_faktur_pajak'     => $validated['no_faktur_pajak'] ?? null,
                'invoiced_at'         => $validated['invoiced_at'],
                'due_at'              => $validated['due_at'],
                'subtotal'            => $subtotal,
                'tax_amount'          => $totalTax,
                'header_tax_details'  => $headerTaxes,
                'discount_amount'     => $totalDiscount,
                'header_discount_details' => $headerDiscounts,
                'is_tax'              => $totalTax > 0,
                'is_discount'         => $totalDiscount > 0,
                'grand_total'         => $grandTotal,
                'invoice_status_code' => 'draft',
                'payment_status'      => 'unpaid',
            ]);

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'company_id'   => $companyId,
                    'invoice_id'   => $invoice->id,
                    'name'         => $item['name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total'        => ($item['quantity'] * $item['price']),
                    'tax_amount'   => 0,
                    'tax_details'  => [],
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    /**
     * Post an invoice: generate journal entries.
     */
    public function post(Invoice $invoice)
    {
        if ($invoice->isPosted) {
            return back()->with('error', 'Invoice sudah diposting sebelumnya.');
        }

        if (!$invoice->account_id) {
            return back()->with('error', 'Pilih Akun Pendapatan terlebih dahulu sebelum posting.');
        }

        try {
            $this->journalService->createInvoiceJournal($invoice);
            return back()->with('success', "Invoice {$invoice->invoice_text} berhasil diposting ke jurnal.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal posting: ' . $e->getMessage());
        }
    }

    /**
     * Bulk post invoices.
     */
    public function bulkPost(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada invoice yang dipilih.');
        }

        $invoices = Invoice::whereIn('id', $ids)->get();
        $successCount = 0;
        $errorMessages = [];

        foreach ($invoices as $invoice) {
            if ($invoice->isPosted) {
                $errorMessages[] = "Invoice {$invoice->invoice_number} sudah diposting sebelumnya.";
                continue;
            }

            if (!$invoice->account_id) {
                $errorMessages[] = "Invoice {$invoice->invoice_number} tidak memiliki Akun Pendapatan.";
                continue;
            }

            try {
                $this->journalService->createInvoiceJournal($invoice);
                $successCount++;
            } catch (\Exception $e) {
                $errorMessages[] = "Invoice {$invoice->invoice_number} gagal diposting: " . $e->getMessage();
            }
        }

        $message = "Berhasil memposting {$successCount} invoice.";
        if (count($errorMessages) > 0) {
            return back()->with('warning', $message . ' Terdapat beberapa error: ' . implode(' ', $errorMessages));
        }

        return back()->with('success', $message);
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $activeTaxes = $this->taxService->getActiveTaxes();
        $activeDiscounts = Discount::all(['id', 'name', 'rate', 'type']);
        $customers = Customer::select('id', 'name')->get();
        $invoiceTypes = \App\Models\Incomes\InvoiceType::all();
        
        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'activeTaxes' => $activeTaxes,
            'activeDiscounts' => $activeDiscounts,
            'customers' => $customers,
            'invoiceTypes' => $invoiceTypes,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->invoice_status_code !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Hanya invoice dengan status Draft yang bisa diedit.']);
        }

        $validated = $request->validate([
            'customer_id'         => 'required|integer',
            'customer_name'       => 'required|string',
            'invoice_type_id'     => 'nullable|integer|exists:invoice_type,id',
            'invoiced_at'         => 'required|date',
            'due_at'              => 'required|date|after_or_equal:invoiced_at',
            'order_number'        => 'nullable|string',
            'nama_kapal'          => 'nullable|string',
            'departure_date'      => 'nullable|date',
            'notes'               => 'nullable|string',
            'no_faktur_pajak'     => 'nullable|string',
            'header_tax_details'  => 'nullable|array',
            'header_discount_details' => 'nullable|array',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string',
            'items.*.quantity'    => 'required|numeric',
            'items.*.price'       => 'required|numeric',
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ($item['quantity'] * $item['price']);
        }

        $headerTaxes = $this->taxService->parseHeaderTaxes($validated['header_tax_details'] ?? []);
        $totalTax = 0;
        foreach ($headerTaxes as $tax) {
            $totalTax += $tax['amount']; // Use the amount sent from frontend
        }

        $headerDiscounts = collect($validated['header_discount_details'] ?? [])->map(function ($discount) {
            return [
                'name' => $discount['name'] ?? 'Unknown Discount',
                'rate' => (float) ($discount['rate'] ?? 0),
                'amount' => (float) ($discount['amount'] ?? 0),
            ];
        })->toArray();
        $totalDiscount = 0;
        foreach ($headerDiscounts as $discount) {
            $totalDiscount += $discount['amount'];
        }

        $grandTotal = $subtotal - $totalDiscount + $totalTax;

        $invoice->update([
            'customer_id'         => $validated['customer_id'],
            'customer_name'       => $validated['customer_name'],
            'invoice_type_id'     => $validated['invoice_type_id'] ?? null,
            'order_number'        => $validated['order_number'] ?? null,
            'nama_kapal'          => $validated['nama_kapal'] ?? null,
            'departure_date'      => $validated['departure_date'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'no_faktur_pajak'     => $validated['no_faktur_pajak'] ?? null,
            'invoiced_at'         => $validated['invoiced_at'],
            'due_at'              => $validated['due_at'],
            'subtotal'            => $subtotal,
            'tax_amount'          => $totalTax,
            'header_tax_details'  => $headerTaxes,
            'discount_amount'     => $totalDiscount,
            'header_discount_details' => $headerDiscounts,
            'is_tax'              => $totalTax > 0,
            'is_discount'         => $totalDiscount > 0,
            'grand_total'         => $grandTotal,
        ]);

        // Sync Items
        $invoice->items()->delete();
        
        foreach ($validated['items'] as $item) {
            InvoiceItem::create([
                'company_id'   => $invoice->company_id,
                'invoice_id'   => $invoice->id,
                'name'         => $item['name'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
                'total'        => ($item['quantity'] * $item['price']),
                'tax_amount'   => 0, 
                'tax_details'  => [],
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->invoice_status_code === 'posted') {
            return redirect()->back()->withErrors(['status' => 'Invoice sudah di-posting. Harap Unpost terlebih dahulu sebelum membatalkan.']);
        }

        $invoice->update([
            'invoice_status_code' => 'void'
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice has been voided.');
    }
}
