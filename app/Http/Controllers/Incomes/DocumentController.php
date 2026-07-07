<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Models\Incomes\Document;
use App\Models\Incomes\Invoice;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DocumentController extends Controller
{
    /**
     * Show the form for creating a new document.
     */
    public function create()
    {
        $invoices = Invoice::with(['customer', 'documents'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('invoice_status_code', 'posted')
            ->orderBy('invoice_number', 'asc')
            ->get()
            ->map(function ($inv) {
                return [
                    'id'             => $inv->id,
                    'customer_id'    => $inv->customer_id,
                    'customer_name'  => $inv->customer?->name ?? 'Unknown',
                    'customer_address'=> $inv->customer?->address ?? '',
                    'invoice_number' => $inv->invoice_number,
                    'invoice_text'   => $inv->invoice_text ?? $inv->invoice_number,
                    'order_number'   => $inv->order_number ?? '',
                    'used_in_types'  => $inv->documents->pluck('type')->toArray()
                ];
            });

        return Inertia::render('Incomes/Documents/Create', [
            'invoices' => $invoices
        ]);
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'          => 'required|string|in:tanda_terima,tanda_terima_new,surat_tagihan,schedule_tukar_faktur,titip_internal',
            'send_date'     => 'nullable|date',
            'up_person'     => 'nullable|string',
            'customer_name' => 'nullable|string',
            'no_tlp'        => 'nullable|string',
            'address'       => 'nullable|string',
            'invoice_ids'   => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
        ]);

        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])->get();

        // 1. Validasi Customer Sama (Jika bukan tipe schedule_tukar_faktur)
        if ($validated['type'] !== 'schedule_tukar_faktur') {
            $customerIds = $invoices->pluck('customer_id')->unique();
            if ($customerIds->count() > 1) {
                return back()->withErrors([
                    'invoice_ids' => 'Semua invoice harus berasal dari customer yang sama untuk tipe dokumen ini.'
                ]);
            }
        }

        // 2. Validasi Invoice Duplikat (Jika bukan tanda_terima_new)
        if ($validated['type'] !== 'tanda_terima_new') {
            $alreadyUsed = DB::table('document_invoices')
                ->join('documents', 'document_invoices.document_id', '=', 'documents.id')
                ->whereNull('documents.deleted_at')
                ->whereIn('document_invoices.invoice_id', $validated['invoice_ids'])
                ->where('documents.type', $validated['type'])
                ->pluck('document_invoices.invoice_id');

            if ($alreadyUsed->isNotEmpty()) {
                $usedInvoiceNumbers = Invoice::whereIn('id', $alreadyUsed)
                    ->pluck('invoice_number')
                    ->implode(', ');
                return back()->withErrors([
                    'invoice_ids' => "Invoice berikut sudah pernah dibuatkan dokumen dengan tipe ini sebelumnya: {$usedInvoiceNumbers}"
                ]);
            }
        }

        return DB::transaction(function () use ($validated) {
            $companyId = session('company_id') ?: Company::where('enabled', 1)->first()?->id;

            $document = Document::create([
                'company_id'    => $companyId,
                'type'          => $validated['type'],
                'send_date'     => $validated['send_date'],
                'up_person'     => $validated['up_person'] ?? null,
                'customer_name' => $validated['customer_name'],
                'no_tlp'        => $validated['no_tlp'] ?? null,
                'address'       => $validated['address'] ?? null,
                'status'        => 1, // Active
            ]);

            $document->invoices()->sync($validated['invoice_ids']);

            // Redirect ke halaman index masing-masing
            $redirectPaths = [
                'tanda_terima'          => '/tanda-terima',
                'tanda_terima_new'      => '/tanda-terima/new',
                'surat_tagihan'         => '/surat-tagihan',
                'schedule_tukar_faktur' => '/schedule-tukar-faktur',
                'titip_internal'        => '/titip-internal',
            ];
            $path = $redirectPaths[$validated['type']] ?? '/dashboard';

            return redirect($path)->with('success', "Dokumen {$document->orders_text} berhasil disimpan.");
        });
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit($id)
    {
        $document = Document::with('invoices')->findOrFail($id);
        $selectedInvoiceIds = $document->invoices->pluck('id')->toArray();

        $invoices = Invoice::with(['customer', 'documents'])
            ->where(function ($q) {
                $q->whereIn('payment_status', ['unpaid', 'partial'])
                  ->where('invoice_status_code', 'posted');
            })
            ->orWhereIn('id', $selectedInvoiceIds)
            ->orderBy('invoice_number', 'asc')
            ->get()
            ->map(function ($inv) {
                return [
                    'id'             => $inv->id,
                    'customer_id'    => $inv->customer_id,
                    'customer_name'  => $inv->customer?->name ?? 'Unknown',
                    'customer_address'=> $inv->customer?->address ?? '',
                    'invoice_number' => $inv->invoice_number,
                    'invoice_text'   => $inv->invoice_text ?? $inv->invoice_number,
                    'order_number'   => $inv->order_number ?? '',
                    'used_in_types'  => $inv->documents->pluck('type')->toArray()
                ];
            });

        return Inertia::render('Incomes/Documents/Edit', [
            'document' => [
                'id'            => $document->id,
                'type'          => $document->type,
                'send_date'     => $document->send_date ? $document->send_date->format('Y-m-d') : null,
                'up_person'     => $document->up_person,
                'customer_name' => $document->customer_name,
                'no_tlp'        => $document->no_tlp,
                'address'       => $document->address,
                'selected_invoices' => $selectedInvoiceIds
            ],
            'invoices' => $invoices
        ]);
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $validated = $request->validate([
            'send_date'     => 'nullable|date',
            'up_person'     => 'nullable|string',
            'customer_name' => 'nullable|string',
            'no_tlp'        => 'nullable|string',
            'address'       => 'nullable|string',
            'invoice_ids'   => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
        ]);

        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])->get();

        // 1. Validasi Customer Sama (Jika bukan tipe schedule_tukar_faktur)
        if ($document->type !== 'schedule_tukar_faktur') {
            $customerIds = $invoices->pluck('customer_id')->unique();
            if ($customerIds->count() > 1) {
                return back()->withErrors([
                    'invoice_ids' => 'Semua invoice harus berasal dari customer yang sama untuk tipe dokumen ini.'
                ]);
            }
        }

        // 2. Validasi Invoice Duplikat (Jika bukan tanda_terima_new)
        if ($document->type !== 'tanda_terima_new') {
            $alreadyUsed = DB::table('document_invoices')
                ->join('documents', 'document_invoices.document_id', '=', 'documents.id')
                ->whereNull('documents.deleted_at')
                ->where('documents.id', '!=', $document->id)
                ->whereIn('document_invoices.invoice_id', $validated['invoice_ids'])
                ->where('documents.type', $document->type)
                ->pluck('document_invoices.invoice_id');

            if ($alreadyUsed->isNotEmpty()) {
                $usedInvoiceNumbers = Invoice::whereIn('id', $alreadyUsed)
                    ->pluck('invoice_number')
                    ->implode(', ');
                return back()->withErrors([
                    'invoice_ids' => "Invoice berikut sudah pernah dibuatkan dokumen dengan tipe ini sebelumnya: {$usedInvoiceNumbers}"
                ]);
            }
        }

        return DB::transaction(function () use ($validated, $document) {
            $document->update([
                'send_date'     => $validated['send_date'],
                'up_person'     => $validated['up_person'] ?? null,
                'customer_name' => $validated['customer_name'],
                'no_tlp'        => $validated['no_tlp'] ?? null,
                'address'       => $validated['address'] ?? null,
            ]);

            $document->invoices()->sync($validated['invoice_ids']);

            // Redirect ke halaman index masing-masing
            $redirectPaths = [
                'tanda_terima'          => '/tanda-terima',
                'tanda_terima_new'      => '/tanda-terima/new',
                'surat_tagihan'         => '/surat-tagihan',
                'schedule_tukar_faktur' => '/schedule-tukar-faktur',
                'titip_internal'        => '/titip-internal',
            ];
            $path = $redirectPaths[$document->type] ?? '/dashboard';

            return redirect($path)->with('success', "Dokumen {$document->orders_text} berhasil diperbarui.");
        });
    }

    public function tandaTerima(Request $request)
    {
        return $this->getDocumentsForIndex($request, 'tanda_terima', 'Incomes/TandaTerima/Index', 'receipts');
    }

    public function tandaTerimaNew(Request $request)
    {
        return $this->getDocumentsForIndex($request, 'tanda_terima_new', 'Incomes/TandaTerima/New', 'receipts');
    }

    public function scheduleTukarFaktur(Request $request)
    {
        return $this->getDocumentsForIndex($request, 'schedule_tukar_faktur', 'Incomes/ScheduleTukarFaktur/Index', 'schedules');
    }

    public function suratTagihan(Request $request)
    {
        return $this->getDocumentsForIndex($request, 'surat_tagihan', 'Incomes/SuratTagihan/Index', 'letters');
    }

    public function titipInternal(Request $request)
    {
        return $this->getDocumentsForIndex($request, 'titip_internal', 'Incomes/TitipInternal/Index', 'items');
    }

    private function getDocumentsForIndex(Request $request, $type, $component, $propName)
    {
        $orderNumber = $request->input('order_number', '');
        $customerName = $request->input('customer_name', '');
        $tanggalKirim = $request->input('tanggal_kirim', '');
        $bulan = $request->input('bulan', '');
        $noDokumen = $request->input('no_tt', $request->input('no_tf', $request->input('no_lt', '')));
        $perPage = (int) $request->input('per_page', 25);

        $query = Document::with(['invoices.customer', 'company'])->where('type', $type)->orderBy('id', 'desc');

        if ($noDokumen) {
            $query->where('orders_text', 'ilike', '%' . $noDokumen . '%');
        }
        if ($customerName) {
            $query->where(function($q) use ($customerName) {
                $q->where('customer_name', 'ilike', '%' . $customerName . '%')
                  ->orWhereHas('invoices.customer', function($q2) use ($customerName) {
                      $q2->where('name', 'ilike', '%' . $customerName . '%');
                  });
            });
        }
        if ($orderNumber) {
            $query->whereHas('invoices', function($q) use ($orderNumber) {
                $q->where('order_number', 'ilike', '%' . $orderNumber . '%');
            });
        }
        if ($tanggalKirim) {
            try {
                $date = \Carbon\Carbon::parse($tanggalKirim);
                $query->whereDate('send_date', $date);
            } catch (\Exception $e) {}
        }
        if ($bulan) {
            try {
                $date = \Carbon\Carbon::parse($bulan);
                $query->whereMonth('send_date', $date->month)
                      ->whereYear('send_date', $date->year);
            } catch (\Exception $e) {}
        }

        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(function ($doc) use ($type) {
            $base = [
                'id' => $doc->id,
                'tanggal_kirim' => $doc->send_date ? $doc->send_date->format('d M Y') : '',
                'tanggal' => $doc->send_date ? $doc->send_date->format('d M Y') : '',
                'no_dokumen' => $doc->orders_text,
                'no_tf' => $doc->orders_text,
                'nomor' => $doc->orders_text,
                'customer_name' => $doc->customer_name ?? '',
                'up_person' => $doc->up_person ?? '',
                'no_tlp' => $doc->no_tlp ?? '',
                'address' => $doc->address ?? '',
                'company_name' => $doc->company?->name ?? '-',
            ];

            $base['order_number'] = $doc->invoices->first()?->order_number ?? '';
            $base['invoices'] = $doc->invoices->map(function ($inv) {
                return [
                    'number' => $inv->invoice_number,
                    'status' => $inv->payment_status === 'unpaid' ? 'draft' : ($inv->payment_status === 'paid' ? 'Paid' : 'Sent'),
                    'customer_name' => $inv->customer ? $inv->customer->name : 'Unknown',
                    'order_number' => $inv->order_number,
                    'orders' => $inv->order_number ? array_map('trim', explode(',', $inv->order_number)) : [],
                    'amount' => $inv->grand_total,
                ];
            })->toArray();

            return $base;
        });

        $filters = [
            'per_page' => $perPage,
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
        ];
        if ($request->has('tanggal_kirim')) $filters['tanggal_kirim'] = $tanggalKirim;
        if ($request->has('bulan')) $filters['bulan'] = $bulan;
        if ($request->has('no_tt')) $filters['no_tt'] = $request->input('no_tt');
        if ($request->has('no_tf')) $filters['no_tf'] = $request->input('no_tf');

        return Inertia::render($component, [
            $propName => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'from' => $paginator->firstItem() ?: 0,
                'to' => $paginator->lastItem() ?: 0,
            ],
            'filters' => $filters
        ]);
    }
}
