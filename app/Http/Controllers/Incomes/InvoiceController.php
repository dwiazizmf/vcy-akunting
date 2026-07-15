<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Incomes\Invoice;
use App\Models\Incomes\InvoiceItem;
use App\Models\Incomes\Customer;
use App\Models\Accounting\Account;
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
        $query = Invoice::with(['items', 'company', 'documents', 'paymentInvoices.payment'])->filter($request->all());

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
                'company_name'    => $inv->company?->name ?? '-',
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
                'statusPayment'   => $inv->payment_status ?? 'unpaid',
                'notes'           => $inv->notes_text,
                'statusCreated'   => $inv->create_on ?: '-',
                'faktur'          => $inv->no_faktur_pajak ?? $inv->no_faktur_int ?? '-',
                'bpb'             => $inv->isBpb ? 'Ya' : 'Tidak',
                'items'           => $inv->items, // Passing items array
                'documents'       => $inv->documents->map(function ($doc) {
                    return [
                        'id'      => $doc->id,
                        'nama'    => ucwords(str_replace('_', ' ', $doc->type)),
                        'tgl'     => $doc->send_date ? date('d M Y', strtotime($doc->send_date)) : '-',
                        'noTitip' => $doc->orders_text ?? '-',
                        'status'  => $doc->status == 1 ? 'Terkirim' : 'Draft',
                    ];
                }),
                'payments'        => collect($inv->paymentInvoices)->map(function ($pi) {
                    $payment = $pi->payment;
                    if (!$payment) return null;
                    return [
                        'noJurnal'   => $payment->payment_number,
                        'total'      => $pi->allocated_amount,
                        'keterangan' => $payment->notes ?: 'Pelunasan Invoice',
                        'tgl'        => $payment->paid_at ? date('d M Y', strtotime($payment->paid_at)) : '-',
                        'status'     => $payment->status === 'void' ? 'void' : 'paid',
                    ];
                })->filter()->values(),
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

        return Inertia::render('Incomes/Invoices/Index', [
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
        $customers   = Customer::select('id', 'name', 'address', 'npwp')->get();
        $revenueAccounts = \App\Helpers\AccountHelper::getFormattedAccounts($companyId, 'Revenue');
        $invoiceTypes = \App\Models\Incomes\InvoiceType::all();
        $invoices = Invoice::where('company_id', $companyId)
            ->where('invoice_status_code', '!=', 'void')
            ->select('id', 'invoice_text', 'invoice_number')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'name' => $inv->invoice_text ?: $inv->invoice_number,
                ];
            });
        
        return Inertia::render('Incomes/Invoices/Create', [
            'activeTaxes'     => $activeTaxes,
            'activeDiscounts' => $activeDiscounts,
            'customers'       => $customers,
            'revenueAccounts' => $revenueAccounts,
            'invoiceTypes'    => $invoiceTypes,
            'invoices'        => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        \App\Helpers\PeriodLockHelper::validateDate($request->invoiced_at);
        $validated = $request->validate([
            'customer_id'         => 'required|integer',
            'customer_name'       => 'required|string',
            'customer_address'    => 'nullable|string',
            'customer_npwp'       => 'nullable|string',
            'account_id'          => 'nullable|integer|exists:accounts,id',
            'invoice_type_id'     => 'nullable|integer|exists:invoice_type,id',
            'invoiced_at'         => 'required|date',
            'due_at'              => 'required|date|after_or_equal:invoiced_at',
            'order_number'        => 'nullable|string',
            'nama_kapal'          => 'nullable|string',
            'departure_date'      => 'nullable|date',
            'kode_pelabuhan_asal' => 'nullable|string',
            'pelabuhan_asal'      => 'nullable|string',
            'kode_pelabuhan_tujuan' => 'nullable|string',
            'pelabuhan_tujuan'    => 'nullable|string',
            'voy'                 => 'nullable|string',
            'notes'               => 'nullable|string',
            'no_faktur_pajak'     => 'nullable|string',
            'alamat_faktur_pajak' => 'nullable|string',
            'isFCL'               => 'nullable|boolean',
            'no_container'        => 'nullable|string|max:50',
            'isFaktur'            => 'nullable|boolean',
            'header_tax_details'  => 'nullable|array',
            'header_discount_details' => 'nullable|array',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string',
            'items.*.quantity'    => 'required|numeric',
            'items.*.price'       => 'required|numeric',
            'revised_invoice_id'  => 'nullable|integer|exists:invoices,id',
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

        $isFaktur = (bool)($validated['isFaktur'] ?? false);
        $noFakturInt = null;
        $noFakturPajak = $validated['no_faktur_pajak'] ?? null;

        if ($isFaktur) {
            $setting = \App\Models\Settings\InvoiceSetting::getSetting();
            
            $lastInvoice = Invoice::where('company_id', $companyId)
                ->where('isFaktur', true)
                ->whereNotNull('no_faktur_int')
                ->where('no_faktur_int', '!=', '')
                ->orderByRaw('CAST(no_faktur_int AS INTEGER) DESC')
                ->first();

            if ($lastInvoice) {
                $nextVal = intval($lastInvoice->no_faktur_int) + 1;
            } else {
                $nextVal = intval($setting->no_awal);
            }

            if ($nextVal < $setting->no_awal || $nextVal > $setting->no_akhir) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['no_faktur_pajak' => "Nomor urut faktur pajak otomatis ({$nextVal}) di luar rentang periode yang tersedia ({$setting->no_awal} - {$setting->no_akhir})."]);
            }

            $noFakturInt = (string)$nextVal;

            $first = $setting->first_faktur ?? '';
            $second = $setting->second_faktur ?? '';
            $third = $setting->third_faktur ?? '';
            $fourth = $setting->fourth_faktur ?? '';
            $paddedVal = str_pad($nextVal, 8, '0', STR_PAD_LEFT);

            if ($first !== '' && $second !== '' && $third !== '' && $fourth !== '') {
                $noFakturPajak = "{$first}{$second}.{$third}-{$fourth}.{$paddedVal}";
            } else {
                $prefix = collect([$first, $second, $third, $fourth])->filter(fn($v) => !is_null($v) && $v !== '')->implode('.');
                $noFakturPajak = $prefix ? "{$prefix}.{$paddedVal}" : $paddedVal;
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $companyId, $subtotal, $grandTotal, $totalTax, $headerTaxes, $totalDiscount, $headerDiscounts, $isFaktur, $noFakturInt, $noFakturPajak) {
            $revisedInvoiceId = $validated['revised_invoice_id'] ?? null;
            $invoiceData = null;
            $rInvoiceText = null;

            if ($revisedInvoiceId) {
                $oldInvoice = Invoice::findOrFail($revisedInvoiceId);
                $revData = InvoiceHelper::generateRevisionInvoiceData($oldInvoice->invoice_text, $oldInvoice->invoice_number);
                $invoiceData = [
                    'invoice_number' => $revData['invoice_number'],
                    'invoice_text' => $revData['invoice_text'],
                ];
                $rInvoiceText = $oldInvoice->invoice_text;
                
                // If it was posted, delete its journal entries
                if ($oldInvoice->isPosted) {
                    $journalIds = \App\Models\Accounting\Ledger::where('ledgerable_id', $oldInvoice->id)
                        ->where('ledgerable_type', Invoice::class)
                        ->pluck('journal_id')
                        ->unique();
                        
                    \App\Models\Accounting\Ledger::where('ledgerable_id', $oldInvoice->id)
                        ->where('ledgerable_type', Invoice::class)
                        ->delete();
                        
                    \App\Models\Accounting\Journal::whereIn('id', $journalIds)->delete();
                    
                    $oldInvoice->update(['isPosted' => false]);
                }
                
                $oldInvoice->update(['invoice_status_code' => 'void']);
            } else {
                $invoiceData = InvoiceHelper::generateInvoiceData($validated['invoiced_at']);
            }

            $invoice = Invoice::create([
                'company_id'          => $companyId,
                'customer_id'         => $validated['customer_id'],
                'customer_name'       => $validated['customer_name'],
                'customer_address'    => $validated['customer_address'] ?? null,
                'customer_npwp'       => $validated['customer_npwp'] ?? null,
                'account_id'          => $validated['account_id'] ?? null,
                'invoice_type_id'     => $validated['invoice_type_id'] ?? null,
                'invoice_number'      => $invoiceData['invoice_number'], 
                'invoice_text'        => $invoiceData['invoice_text'],
                'r_invoice_text'      => $rInvoiceText,
                'order_number'        => $validated['order_number'] ?? null,
                'nama_kapal'          => $validated['nama_kapal'] ?? null,
                'departure_date'      => $validated['departure_date'] ?? null,
                'kode_pelabuhan_asal' => $validated['kode_pelabuhan_asal'] ?? null,
                'pelabuhan_asal'      => $validated['pelabuhan_asal'] ?? null,
                'kode_pelabuhan_tujuan' => $validated['kode_pelabuhan_tujuan'] ?? null,
                'pelabuhan_tujuan'    => $validated['pelabuhan_tujuan'] ?? null,
                'voy'                 => $validated['voy'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'isFCL'               => $validated['isFCL'] ?? false,
                'no_container'        => $validated['no_container'] ?? null,
                'isFaktur'            => $isFaktur,
                'no_faktur_int'       => $noFakturInt,
                'no_faktur_pajak'     => $noFakturPajak,
                'alamat_faktur_pajak' => $validated['alamat_faktur_pajak'] ?? null,
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
                'user_name'           => auth()->user()?->name,
                'created_from'        => 'manual',
                'created_by'          => auth()->id(),
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
     * Print invoices.
     */
    public function print(Request $request)
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada invoice yang dipilih.');
        }

        $invoices = Invoice::with('items')->whereIn('id', $ids)->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Invoice tidak ditemukan.');
        }

        $data_multi = [];
        foreach ($invoices as $inv) {
            $inv->terima_dari = $inv->customer_name;
            $inv->alamat_invoice = $inv->customer_address;
            $inv->isTax = $inv->is_tax ? 1 : 0;
            $inv->amount = $inv->subtotal;

            // Paginate items based on the original logic (max 15 lines per page)
            $pages = [];
            $currentPageItems = [];
            $currentLinesForPagination = 0;
            $bb = 0;

            foreach ($inv->items as $item) {
                $nameLength = strlen($item->name);
                $ceil_total = ($nameLength > 40) ? ceil($nameLength / 40) : 1;
                $currentLinesForPagination += $ceil_total;
                
                if ($currentLinesForPagination > 14) {
                    $currentLinesForPagination = $ceil_total;
                    $bb++;
                }
                
                $pages[$bb][] = \App\Http\Controllers\Incomes\PrintInvoiceHelper::formatItemForPrint($item);
            }

            $formattedPages = array_values($pages);

            $data_multi[] = [
                'inv' => $inv, // We only need the single invoice model here now
                'pages' => $formattedPages,
            ];
        }

        $companyId = session('company_id') ?? $invoices->first()->company_id;

        $abc = [
            'data_multi' => $data_multi,
            'company' => $companyId,
        ];

        return view('incomes.invoices.print', compact('abc'));
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

    public function unpost(Invoice $invoice)
    {
        \App\Helpers\PeriodLockHelper::validateDate($invoice->invoiced_at);

        if ($invoice->payment_status !== 'unpaid') {
            return redirect()->back()->withErrors(['status' => 'Invoice ini sudah memiliki pembayaran aktif. Harap batalkan pembayaran (unpost & hapus) terlebih dahulu.']);
        }

        if (!$invoice->isPosted) {
            return back()->with('error', 'Invoice ini belum diposting.');
        }

        try {
            $this->journalService->unpostInvoiceJournal($invoice);
            return back()->with('success', 'Berhasil membatalkan posting invoice.');
        } catch (\Exception $e) {
            \Log::error('Error unposting invoice: ' . $e->getMessage());
            return back()->with('error', 'Gagal membatalkan posting: ' . $e->getMessage());
        }
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $activeTaxes = $this->taxService->getActiveTaxes();
        $activeDiscounts = Discount::all(['id', 'name', 'rate', 'type']);
        $customers = Customer::select('id', 'name', 'address', 'npwp')->get();
        $invoiceTypes = \App\Models\Incomes\InvoiceType::all();
        
        $invoices = Invoice::where('company_id', $invoice->company_id)
            ->where('id', '!=', $invoice->id)
            ->where('invoice_status_code', '!=', 'void')
            ->select('id', 'invoice_text', 'invoice_number')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'name' => $inv->invoice_text ?: $inv->invoice_number,
                ];
            });

        $revisedInvoice = null;
        if ($invoice->r_invoice_text) {
            $revisedInvoice = Invoice::where('invoice_text', $invoice->r_invoice_text)
                ->where('company_id', $invoice->company_id)
                ->first();
        }
        $invoice->revised_invoice_id = $revisedInvoice?->id;

        return Inertia::render('Incomes/Invoices/Edit', [
            'invoice' => $invoice,
            'activeTaxes' => $activeTaxes,
            'activeDiscounts' => $activeDiscounts,
            'customers' => $customers,
            'invoiceTypes' => $invoiceTypes,
            'invoices' => $invoices,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        \App\Helpers\PeriodLockHelper::validateDate($invoice->invoiced_at);
        \App\Helpers\PeriodLockHelper::validateDate($request->invoiced_at);

        if ($invoice->payment_status !== 'unpaid') {
            return redirect()->back()->withErrors(['status' => 'Invoice ini sudah memiliki pembayaran aktif. Harap hapus pembayaran terlebih dahulu jika ingin mengubah data.']);
        }

        if ($invoice->invoice_status_code !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Hanya invoice dengan status Draft yang bisa diedit.']);
        }

        $validated = $request->validate([
            'customer_id'         => 'required|integer',
            'customer_name'       => 'required|string',
            'customer_address'    => 'nullable|string',
            'customer_npwp'       => 'nullable|string',
            'invoice_type_id'     => 'nullable|integer|exists:invoice_type,id',
            'invoiced_at'         => 'required|date',
            'due_at'              => 'required|date|after_or_equal:invoiced_at',
            'order_number'        => 'nullable|string',
            'nama_kapal'          => 'nullable|string',
            'departure_date'      => 'nullable|date',
            'kode_pelabuhan_asal' => 'nullable|string',
            'pelabuhan_asal'      => 'nullable|string',
            'kode_pelabuhan_tujuan' => 'nullable|string',
            'pelabuhan_tujuan'    => 'nullable|string',
            'voy'                 => 'nullable|string',
            'notes'               => 'nullable|string',
            'no_faktur_pajak'     => 'nullable|string',
            'alamat_faktur_pajak' => 'nullable|string',
            'isFCL'               => 'nullable|boolean',
            'no_container'        => 'nullable|string|max:50',
            'isFaktur'            => 'nullable|boolean',
            'header_tax_details'  => 'nullable|array',
            'header_discount_details' => 'nullable|array',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string',
            'items.*.quantity'    => 'required|numeric',
            'items.*.price'       => 'required|numeric',
            'revised_invoice_id'  => 'nullable|integer|exists:invoices,id',
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

        $isFaktur = (bool)($validated['isFaktur'] ?? false);
        $noFakturInt = $invoice->no_faktur_int;
        $noFakturPajak = $validated['no_faktur_pajak'] ?? null;

        if ($isFaktur) {
            if (!$noFakturInt) {
                $setting = \App\Models\Settings\InvoiceSetting::getSetting();
                
                $lastInvoice = Invoice::where('company_id', $invoice->company_id)
                    ->where('isFaktur', true)
                    ->whereNotNull('no_faktur_int')
                    ->where('no_faktur_int', '!=', '')
                    ->orderByRaw('CAST(no_faktur_int AS INTEGER) DESC')
                    ->first();

                if ($lastInvoice) {
                    $nextVal = intval($lastInvoice->no_faktur_int) + 1;
                } else {
                    $nextVal = intval($setting->no_awal);
                }

                if ($nextVal < $setting->no_awal || $nextVal > $setting->no_akhir) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['no_faktur_pajak' => "Nomor urut faktur pajak otomatis ({$nextVal}) di luar rentang periode yang tersedia ({$setting->no_awal} - {$setting->no_akhir})."]);
                }

                $noFakturInt = (string)$nextVal;

                $first = $setting->first_faktur ?? '';
                $second = $setting->second_faktur ?? '';
                $third = $setting->third_faktur ?? '';
                $fourth = $setting->fourth_faktur ?? '';
                $paddedVal = str_pad($nextVal, 8, '0', STR_PAD_LEFT);

                if ($first !== '' && $second !== '' && $third !== '' && $fourth !== '') {
                    $noFakturPajak = "{$first}{$second}.{$third}-{$fourth}.{$paddedVal}";
                } else {
                    $prefix = collect([$first, $second, $third, $fourth])->filter(fn($v) => !is_null($v) && $v !== '')->implode('.');
                    $noFakturPajak = $prefix ? "{$prefix}.{$paddedVal}" : $paddedVal;
                }
            }
        } else {
            $noFakturInt = null;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $invoice, $subtotal, $grandTotal, $totalTax, $headerTaxes, $totalDiscount, $headerDiscounts, $isFaktur, $noFakturInt, $noFakturPajak) {
            $newRevisedInvoiceId = $validated['revised_invoice_id'] ?? null;
            $oldRInvoiceText = $invoice->r_invoice_text;

            $newRevisedInvoice = $newRevisedInvoiceId ? Invoice::find($newRevisedInvoiceId) : null;
            $newRInvoiceText = $newRevisedInvoice?->invoice_text;

            $invoiceNumber = $invoice->invoice_number;
            $invoiceText = $invoice->invoice_text;
            $rInvoiceText = $invoice->r_invoice_text;

            if ($oldRInvoiceText !== $newRInvoiceText) {
                // Restore old revised invoice if it was different and existed
                if ($oldRInvoiceText) {
                    $prevInvoice = Invoice::where('invoice_text', $oldRInvoiceText)
                        ->where('company_id', $invoice->company_id)
                        ->first();
                    if ($prevInvoice) {
                        $prevInvoice->update(['invoice_status_code' => 'draft']);
                    }
                }

                // Void new revised invoice
                if ($newRevisedInvoice) {
                    if ($newRevisedInvoice->isPosted) {
                        $journalIds = \App\Models\Accounting\Ledger::where('ledgerable_id', $newRevisedInvoice->id)
                            ->where('ledgerable_type', Invoice::class)
                            ->pluck('journal_id')
                            ->unique();
                            
                        \App\Models\Accounting\Ledger::where('ledgerable_id', $newRevisedInvoice->id)
                            ->where('ledgerable_type', Invoice::class)
                            ->delete();
                            
                        \App\Models\Accounting\Journal::whereIn('id', $journalIds)->delete();
                        
                        $newRevisedInvoice->update(['isPosted' => false]);
                    }
                    $newRevisedInvoice->update(['invoice_status_code' => 'void']);

                    // Generate new invoice number & text for current invoice
                    $revData = InvoiceHelper::generateRevisionInvoiceData($newRevisedInvoice->invoice_text, $newRevisedInvoice->invoice_number);
                    $invoiceNumber = $revData['invoice_number'];
                    $invoiceText = $revData['invoice_text'];
                    $rInvoiceText = $newRevisedInvoice->invoice_text;
                } else {
                    // Revert to normal invoice number
                    $invoiceData = InvoiceHelper::generateInvoiceData($validated['invoiced_at']);
                    $invoiceNumber = $invoiceData['invoice_number'];
                    $invoiceText = $invoiceData['invoice_text'];
                    $rInvoiceText = null;
                }
            }

            $invoice->update([
                'customer_id'         => $validated['customer_id'],
                'customer_name'       => $validated['customer_name'],
                'customer_address'    => $validated['customer_address'] ?? null,
                'customer_npwp'       => $validated['customer_npwp'] ?? null,
                'invoice_type_id'     => $validated['invoice_type_id'] ?? null,
                'invoice_number'      => $invoiceNumber,
                'invoice_text'        => $invoiceText,
                'r_invoice_text'      => $rInvoiceText,
                'order_number'        => $validated['order_number'] ?? null,
                'nama_kapal'          => $validated['nama_kapal'] ?? null,
                'departure_date'      => $validated['departure_date'] ?? null,
                'kode_pelabuhan_asal' => $validated['kode_pelabuhan_asal'] ?? null,
                'pelabuhan_asal'      => $validated['pelabuhan_asal'] ?? null,
                'kode_pelabuhan_tujuan' => $validated['kode_pelabuhan_tujuan'] ?? null,
                'pelabuhan_tujuan'    => $validated['pelabuhan_tujuan'] ?? null,
                'voy'                 => $validated['voy'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'isFCL'               => $validated['isFCL'] ?? false,
                'no_container'        => $validated['no_container'] ?? null,
                'isFaktur'            => $isFaktur,
                'no_faktur_int'       => $noFakturInt,
                'no_faktur_pajak'     => $noFakturPajak,
                'alamat_faktur_pajak' => $validated['alamat_faktur_pajak'] ?? null,
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
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }


    public function destroy(Invoice $invoice)
    {
        \App\Helpers\PeriodLockHelper::validateDate($invoice->invoiced_at);

        if ($invoice->payment_status !== 'unpaid') {
            return redirect()->back()->withErrors(['status' => 'Invoice ini sudah memiliki pembayaran aktif. Harap batalkan pembayaran (unpost & hapus) terlebih dahulu.']);
        }

        if ($invoice->invoice_status_code === 'posted') {
            return redirect()->back()->withErrors(['status' => 'Invoice sudah di-posting. Harap Unpost terlebih dahulu sebelum membatalkan.']);
        }

        $invoice->update([
            'invoice_status_code' => 'void'
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice has been voided.');
    }
}
