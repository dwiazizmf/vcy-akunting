<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Incomes\Invoice;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $search = $request->input('search');
        $status = $request->input('status');
        $kapal = $request->input('kapal');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Invoice::filter($request->all());

        // Ambil data dari database lama dengan pagination
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        // Format data agar sesuai dengan props yang dibutuhkan oleh komponen Svelte
        $items = $paginator->map(function ($inv) {
            return [
                'id'              => $inv->id,
                'no'              => $inv->id,
                'invoiceText'     => $inv->invoice_text ?: $inv->invoice_number, // invoice_text → fallback invoice_number
                'number'          => $inv->invoice_number,
                'orderNumber'     => $inv->order_number,
                'coa'             => '-', // kosongkan/dummy
                'customer'        => $inv->customer_name,
                'amount_raw'      => (float) $inv->amount,
                'amount'          => 'Rp ' . number_format((float) $inv->amount, 0, ',', '.'),
                'namaKapal'       => $inv->nama_kapal,
                'tglKapBerangkat' => $inv->departure_date ? date('Y-m-d', strtotime($inv->departure_date)) : '-', // dari field departure_date
                'invoiceDate'     => $inv->invoiced_at ? date('d M Y', strtotime($inv->invoiced_at)) : '-',
                'dueDate'         => $inv->due_at ? date('d M Y', strtotime($inv->due_at)) : '-',
                'noDokumenKirim'  => '3 Dokumen', // dummy 3 dokumen
                'noTitipInternal' => 'TI-' . str_pad($inv->id % 1000, 4, '0', STR_PAD_LEFT), // dummy format TI-xxxx
                'tglDokumenKirim' => '-',
                'status'          => $inv->invoice_status_code ?? 'draft',
                'statusPayment'   => $inv->invoice_status_code ?? 'draft', // status payment dari invoice_status_code
                'notes'           => $inv->notes_text, // dari kolom notes_text
                'statusCreated'   => $inv->create_on ?: '-', // dari kolom create_on
                'faktur'          => $inv->no_faktur_pajak ?? $inv->no_faktur_int ?? '-', // dari kolom no faktur
                'bpb'             => $inv->isBpb ? 'Ya' : 'Tidak', // dari kolom isBpb (bpp)
            ];
        });

        // Siapkan info pagination
        $pagination = [
            'total'       => $paginator->total(),
            'perPage'     => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'from'        => $paginator->firstItem() ?: 0,
            'to'          => $paginator->lastItem() ?: 0,
        ];

        $totalAll = Invoice::count();

        // Stats summary sementara (nanti bisa di-query sesuai filter)
        $stats = [
            'total'         => $totalAll,
            'totalFiltered' => $paginator->total(),
            'draft'         => Invoice::where('invoice_status_code', 'draft')->count(),
            'sent'          => Invoice::where('invoice_status_code', 'sent')->count(),
            'paid'          => Invoice::where('invoice_status_code', 'paid')->count(),
            'totalAmount'   => Invoice::sum('amount'),
        ];

        // Kembalikan ke Svelte via Inertia
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
}
