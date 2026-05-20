<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * ARSITEKTUR SERVER-SIDE PAGINATION
     *
     * Masalah: Jika ada 1 juta invoice, tidak mungkin kirim semua ke browser.
     * Solusi:
     *   1. Svelte mengirim parameter filter+page via Inertia router.get()
     *   2. Laravel menerima params ini via Request
     *   3. Laravel filter + paginate di DATABASE (hanya ambil 1 halaman)
     *   4. Kirim hasilnya ke Svelte — hanya ~10-50 baris, bukan 1 juta!
     *
     * Ini sama konsepnya dengan query:
     *   SELECT * FROM invoices WHERE customer LIKE '%foo%' LIMIT 10 OFFSET 0
     */
    public function index(Request $request)
    {
        // Ambil parameter filter dari query string
        // Svelte akan mengirim ini via: router.get('/invoices', { search: '...', page: 1 })
        $search   = $request->input('search', '');
        $status   = $request->input('status', '');
        $kapal    = $request->input('kapal', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $perPage  = (int) $request->input('per_page', 10);
        $page     = (int) $request->input('page', 1);

        // ============================================
        // Di production, ini adalah query Eloquent:
        // $query = Invoice::query()
        //     ->when($search, fn($q) => $q->where('customer', 'like', "%{$search}%")
        //         ->orWhere('number', 'like', "%{$search}%"))
        //     ->when($status, fn($q) => $q->where('status', $status))
        //     ->when($kapal,  fn($q) => $q->where('nama_kapal', 'like', "%{$kapal}%"))
        //     ->paginate($perPage);
        //
        // Sekarang kita simulasikan dengan data dummy yang banyak:
        // ============================================

        $allData = $this->generateDummyInvoices(500); // Simulasi 500 record

        // Filter di PHP (simulasi query WHERE di database)
        $filtered = array_filter($allData, function ($inv) use ($search, $status, $kapal) {
            $matchSearch = !$search ||
                str_contains(strtolower($inv['customer']), strtolower($search)) ||
                str_contains($inv['number'], $search) ||
                str_contains($inv['orderNumber'], $search) ||
                str_contains(strtolower($inv['namaKapal']), strtolower($search));

            $matchStatus = !$status || $inv['status'] === $status;
            $matchKapal  = !$kapal  || str_contains(strtolower($inv['namaKapal']), strtolower($kapal));

            return $matchSearch && $matchStatus && $matchKapal;
        });

        $filtered = array_values($filtered);
        $total    = count($filtered);

        // Paginate manual (simulasi LIMIT/OFFSET SQL)
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $offset  = ($page - 1) * $perPage;
        $items   = array_slice($filtered, $offset, $perPage);

        // Stats dihitung dari semua data (bukan hanya halaman ini)
        $allFiltered = $filtered; // sudah difilter
        $stats = [
            'total'        => count($this->generateDummyInvoices(500)),
            'totalFiltered'=> $total,
            'draft'        => count(array_filter($allFiltered, fn($i) => $i['status'] === 'Draft')),
            'sent'         => count(array_filter($allFiltered, fn($i) => $i['status'] === 'Sent')),
            'paid'         => count(array_filter($allFiltered, fn($i) => $i['status'] === 'Paid')),
            'totalAmount'  => array_sum(array_column($allFiltered, 'amount_raw')),
        ];

        return Inertia::render('Invoices/Index', [
            // Hanya 10-50 item (1 halaman), bukan semua!
            'invoices' => $items,

            // Info pagination untuk ditampilkan di Svelte
            'pagination' => [
                'total'        => $total,
                'perPage'      => $perPage,
                'currentPage'  => $page,
                'lastPage'     => (int) ceil($total / $perPage),
                'from'         => $total > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $total),
            ],

            // Stats summary
            'stats' => $stats,

            // Kembalikan filter aktif ke Svelte (untuk isi ulang form)
            'filters' => [
                'search'    => $search,
                'status'    => $status,
                'kapal'     => $kapal,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'per_page'  => $perPage,
            ],
        ]);
    }

    /**
     * Generate N data dummy invoice (simulasi tabel database besar)
     */
    private function generateDummyInvoices(int $count = 500): array
    {
        $customers = [
            ['name' => 'PT. Inbisco Niagatama Semesta',  'coa' => '11300703'],
            ['name' => 'PT. Primarasa Abadi Sejahtera',  'coa' => '11302292'],
            ['name' => 'PT. Maju Jaya Logistics',        'coa' => '11300890'],
            ['name' => 'PT. Sumber Makmur Sentosa',      'coa' => '11301100'],
            ['name' => 'PT. Indo Global Cargo',          'coa' => '11301250'],
            ['name' => 'PT. Pelayaran Nusantara',        'coa' => '11301400'],
            ['name' => 'PT. Armada Samudera',            'coa' => '11301550'],
            ['name' => 'PT. Bahari Trans Laut',          'coa' => '11301700'],
            ['name' => 'CV. Mitra Ekspres Logistics',    'coa' => '11301850'],
            ['name' => 'PT. Karya Bumi Nusantara',       'coa' => '11302000'],
        ];
        $kapalList   = ['MERATUS BORNEO','SPIL RETNO','ICON LUKAS XIX','ICON KOLOSE III','SPIL RUMI','ICON JAMES II','MERATUS KALIMANTAN','SPIL CERIA','MERATUS JAYAPURA','TANTO FAJAR','ARMADA PERMATA','SINAR BALI'];
        $statuses    = ['Draft','Draft','Draft','Sent','Sent','Paid'];
        $fakturTypes = ['cpanel','manual','manual','cpanel'];

        $invoices = [];
        for ($i = 1; $i <= $count; $i++) {
            $customer  = $customers[($i - 1) % count($customers)];
            $status    = $statuses[($i - 1) % count($statuses)];
            $amountRaw = match ($i % 5) {
                0       => rand(500000, 2000000),
                1       => rand(2000000, 15000000),
                2       => rand(15000000, 50000000),
                3       => rand(50000, 500000),
                default => rand(100000, 800000),
            };

            $invoices[] = [
                'id'              => $i,
                'no'              => 187100 - $i,
                'number'          => str_pad($i, 7, '0', STR_PAD_LEFT),
                'orderNumber'     => (string)(260301500 + $i),
                'coa'             => $customer['coa'],
                'customer'        => $customer['name'],
                'amount_raw'      => $amountRaw,
                'amount'          => 'Rp' . number_format($amountRaw, 0, ',', '.'),
                'namaKapal'       => $kapalList[($i - 1) % count($kapalList)],
                'tglKapBerangkat' => date('Y-m-d', strtotime("-{$i} days -5 days")),
                'invoiceDate'     => date('d M Y', strtotime("-{$i} days")),
                'dueDate'         => date('d M Y', strtotime("+30 days -{$i} days")),
                'noDokumenKirim'  => $status !== 'Draft' ? 'DOC-' . str_pad($i, 5, '0', STR_PAD_LEFT) : null,
                'tglDokumenKirim' => $status !== 'Draft' ? date('d M Y', strtotime("-{$i} days +2 days")) : null,
                'status'          => $status,
                'notes'           => $i % 7 === 0 ? 'Perlu konfirmasi ulang' : null,
                'statusCreated'   => 'create',
                'faktur'          => $fakturTypes[($i - 1) % count($fakturTypes)],
            ];
        }
        return $invoices;
    }
}
