<?php

use Inertia\Inertia;
use App\Http\Controllers\Incomes\InvoiceController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\TaxController;
use App\Http\Controllers\Settings\DiscountController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\InvoiceTypeController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\InvoiceSettingController;
use App\Http\Controllers\Settings\PaymentLimitController;
use App\Http\Controllers\Settings\BankAccountController;
use App\Http\Controllers\Settings\ActivityLogController;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\LedgerController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\Expenses\VendorController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Expenses\ExpensePaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| ALUR DATA LARAVEL → INERTIA → SVELTE:
|
| 1. Route memanggil Controller
| 2. Controller menyiapkan data dan mengirim via Inertia::render()
| 3. Inertia men-serialize data sebagai JSON (mirip API response)
| 4. Svelte menerima data sebagai PROPS: export let namaProps;
| 5. Svelte bisa langsung menggunakan, memfilter, dan menampilkan data
|
*/

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'username' => 'Kombinasi username dan password tidak cocok.',
    ]);
});

Route::post('/logout', function (Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

Route::post('/set-company', function (Illuminate\Http\Request $request) {
    $request->validate(['company_id' => 'required']);
    session(['company_id' => $request->company_id]);
    
    if ($request->company_id === 'all') {
        session()->forget('company_prefix');
    } else {
        $company = \App\Models\Settings\Company::find($request->company_id);
        if ($company) {
            session(['company_prefix' => $company->prefix]);
        }
    }
    
    return back();
})->name('set-company');

// Invoice routes menggunakan Controller
Route::get('invoices/print', [InvoiceController::class, 'print'])->name('invoices.print');
Route::post('invoices/bulk-post', [InvoiceController::class, 'bulkPost'])->name('invoices.bulk-post');
Route::resource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
Route::post('invoices/{invoice}/unpost', [InvoiceController::class, 'unpost'])->name('invoices.unpost');

// Payment routes
Route::post('payments/{payment}/unpost', [PaymentController::class, 'unpost'])->name('payments.unpost');
Route::resource('payments', PaymentController::class)->except(['edit', 'update']);
Route::get('/api/payments/outstanding', [PaymentController::class, 'outstandingInvoices']);
Route::get('/api/invoices/{invoice}', function (\App\Models\Incomes\Invoice $invoice) {
    return response()->json($invoice->load('items'));
});

// Bank Accounts (Settings)
Route::resource('settings/bank-accounts', BankAccountController::class)->names('bank-accounts');

// Accounting Routes
Route::resource('accounts', AccountController::class)->except(['create', 'show', 'edit']);

Route::patch('journals/{journal}/status', [JournalController::class, 'updateStatus'])->name('journals.status');
Route::resource('journals', JournalController::class)->except(['edit', 'update', 'destroy']);

Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');

// Expenses Routes
Route::resource('vendors', VendorController::class)->except(['show']);
Route::post('expenses/bulk-post', [ExpenseController::class, 'bulkPost'])->name('expenses.bulk-post');
Route::resource('expenses', ExpenseController::class);
Route::post('expenses/{expense}/post', [ExpenseController::class, 'post'])->name('expenses.post');
Route::post('expenses/{expense}/unpost', [ExpenseController::class, 'unpost'])->name('expenses.unpost');
Route::post('expense-payments/bulk-post', [ExpensePaymentController::class, 'bulkPost'])->name('expense-payments.bulk-post');
Route::post('expense-payments/{expense_payment}/post', [ExpensePaymentController::class, 'post'])->name('expense-payments.post');
Route::post('expense-payments/{expense_payment}/unpost', [ExpensePaymentController::class, 'unpost'])->name('expense-payments.unpost');
Route::resource('expense-payments', ExpensePaymentController::class)->only(['index', 'create', 'store']);

Route::get('/customers', function (Illuminate\Http\Request $request) {
    $search = $request->input('search', '');
    $status = $request->input('status', '');
    $perPage = (int) $request->input('per_page', 25);
    
    $query = \App\Models\Incomes\Customer::with('company');

    if ($search) {
        $query->where('name', 'ilike', '%' . $search . '%');
    }

    if ($status === 'active') {
        $query->where('enabled', true);
    } elseif ($status === 'inactive') {
        $query->where('enabled', false);
    }

    $paginator = $query->paginate($perPage);

    // Calculate unpaid per customer for current page
    $customerIds = $paginator->getCollection()->pluck('id');
    $customerInvoices = \App\Models\Incomes\Invoice::whereIn('customer_id', $customerIds)
        ->whereIn('payment_status', ['unpaid', 'partial'])
        ->where('invoice_status_code', 'posted')
        ->get(['id', 'customer_id', 'grand_total']);
    $invoiceIds = $customerInvoices->pluck('id');
    $payments = \App\Models\Expenses\PaymentInvoice::whereIn('invoice_id', $invoiceIds)
        ->selectRaw('invoice_id, SUM(allocated_amount) as total_paid')
        ->groupBy('invoice_id')
        ->pluck('total_paid', 'invoice_id');
    $unpaidPerCustomer = [];
    foreach ($customerInvoices as $inv) {
        $paid = $payments->get($inv->id, 0);
        $outstanding = $inv->grand_total - $paid;
        $unpaidPerCustomer[$inv->customer_id] = ($unpaidPerCustomer[$inv->customer_id] ?? 0) + $outstanding;
    }

    $items = $paginator->getCollection()->map(function ($customer) use ($unpaidPerCustomer) {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'company_name' => $customer->company?->name,
            'email' => 'N/A',
            'phone' => 'N/A',
            'unpaid' => $unpaidPerCustomer[$customer->id] ?? 0,
            'is_active' => (bool)$customer->enabled,
        ];
    });

    // Calculate global total unpaid
    $globalInvoices = \App\Models\Incomes\Invoice::whereIn('payment_status', ['unpaid', 'partial'])
        ->where('invoice_status_code', 'posted')
        ->get(['id', 'grand_total']);
    $globalPaid = \App\Models\Expenses\PaymentInvoice::whereIn('invoice_id', $globalInvoices->pluck('id'))
        ->sum('allocated_amount');
    $globalUnpaid = $globalInvoices->sum('grand_total') - $globalPaid;

    return Inertia::render('Customers/Index', [
        'customers' => $items,
        'pagination' => [
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?: 0,
            'to' => $paginator->lastItem() ?: 0,
        ],
        'stats' => [
            'total' => \App\Models\Incomes\Customer::count(),
            'active' => \App\Models\Incomes\Customer::where('enabled', true)->count(),
            'inactive' => \App\Models\Incomes\Customer::where('enabled', false)->count(),
            'totalUnpaid' => $globalUnpaid
        ],
        'filters' => [
            'search' => $search,
            'status' => $status,
            'per_page' => $perPage
        ]
    ]);
});

Route::get('/customers/create', function () {
    return Inertia::render('Customers/Create');
});

Route::post('/customers', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:191',
        'address' => 'nullable|string',
        'tax_number' => 'nullable|string', // mapped to npwp
        'is_active' => 'boolean',          // mapped to enabled
        'reference' => 'nullable|string|max:191',
    ]);
    
    $company_id = session('company_id') ?: \App\Models\Settings\Company::where('enabled', 1)->first()?->id;

    \App\Models\Incomes\Customer::create([
        'name' => $validated['name'],
        'address' => $validated['address'] ?? null,
        'npwp' => $validated['tax_number'] ?? null,
        'enabled' => $validated['is_active'] ?? true,
        'reference' => $validated['reference'] ?? null,
        'company_id' => $company_id,
    ]);
    
    return redirect('/customers')->with('success', 'Customer created successfully.');
});

Route::get('/customers/{customer}', function (\App\Models\Customer $customer) {
    // Calculate customer unpaid
    $invoices = \App\Models\Incomes\Invoice::where('customer_id', $customer->id)
        ->whereIn('payment_status', ['unpaid', 'partial'])
        ->where('invoice_status_code', 'posted')
        ->get(['id', 'grand_total']);
    $paid = \App\Models\Expenses\PaymentInvoice::whereIn('invoice_id', $invoices->pluck('id'))
        ->sum('allocated_amount');
    $customerUnpaid = $invoices->sum('grand_total') - $paid;

    // For view page
    return Inertia::render('Customers/Show', [
        'customer' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'address' => $customer->address,
            'npwp' => $customer->npwp,
            'enabled' => (bool)$customer->enabled,
            'reference' => $customer->reference,
            'unpaid' => $customerUnpaid,
        ]
    ]);
});

Route::get('/customers/{customer}/edit', function (\App\Models\Customer $customer) {
    // For edit page
    return Inertia::render('Customers/Edit', [
        'customer' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'address' => $customer->address,
            'tax_number' => $customer->npwp,
            'is_active' => (bool)$customer->enabled,
            'reference' => $customer->reference,
        ]
    ]);
});

Route::put('/customers/{customer}', function (\Illuminate\Http\Request $request, \App\Models\Customer $customer) {
    $validated = $request->validate([
        'name' => 'required|string|max:191',
        'address' => 'nullable|string',
        'tax_number' => 'nullable|string',
        'is_active' => 'boolean',
        'reference' => 'nullable|string|max:191',
    ]);

    $customer->update([
        'name' => $validated['name'],
        'address' => $validated['address'] ?? null,
        'npwp' => $validated['tax_number'] ?? null,
        'enabled' => $validated['is_active'] ?? true,
        'reference' => $validated['reference'] ?? null,
    ]);

    return redirect('/customers')->with('success', 'Customer updated successfully.');
});

Route::post('/api/customers', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:191',
        'address' => 'nullable|string',
        'npwp' => 'nullable|string',
    ]);
    
    $validated['enabled'] = 1;
    $validated['company_id'] = session('company_id') ?: \App\Models\Settings\Company::where('enabled', 1)->first()?->id;

    $customer = \App\Models\Incomes\Customer::create($validated);
    
    return response()->json($customer);
});

Route::resource('documents', \App\Http\Controllers\Incomes\DocumentController::class)->only(['create', 'store', 'edit', 'update']);




Route::get('/tanda-terima', [\App\Http\Controllers\Incomes\DocumentController::class, 'tandaTerima']);

Route::get('/tanda-terima/new', [\App\Http\Controllers\Incomes\DocumentController::class, 'tandaTerimaNew']);

Route::get('/list-kirim-tagihan', function (Illuminate\Http\Request $request) {
    $noLT = $request->input('no_lt', '');
    $customerName = $request->input('customer_name', '');
    $ship = $request->input('ship', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allLts = [
        [
            'id' => 501,
            'tanggal_kirim' => '28 Apr 2026',
            'no_lt' => '0122/LT/2026',
            'invoices' => [
                ['number' => '0001216', 'customer_name' => 'PT. Hirundo Tyre Utama', 'ship' => 'MERATUS BORNEO', 'amount' => 15000000],
                ['number' => '0001217', 'customer_name' => 'PT. SELERA SWEETSINDO', 'ship' => 'SPIL RETNO', 'amount' => 8500000]
            ]
        ],
        [
            'id' => 502,
            'tanggal_kirim' => '27 Apr 2026',
            'no_lt' => '0121/LT/2026',
            'invoices' => [
                ['number' => '0001151', 'customer_name' => 'PT. Papasari', 'ship' => 'ICON LUKAS XIX', 'amount' => 22400000],
                ['number' => '0001152', 'customer_name' => 'PT. HI-COOK INDONESIA', 'ship' => 'SPIL CERIA', 'amount' => 12300000],
                ['number' => '0001153', 'customer_name' => 'PT. Papasari', 'ship' => 'MERATUS JAYAPURA', 'amount' => 5400000]
            ]
        ]
    ];

    if ($noLT) {
        $allLts = array_filter($allLts, function ($lt) use ($noLT) {
            return str_contains($lt['no_lt'], $noLT);
        });
    }
    if ($customerName) {
        $allLts = array_filter($allLts, function ($lt) use ($customerName) {
            foreach ($lt['invoices'] as $inv) {
                if (str_contains(strtolower($inv['customer_name']), strtolower($customerName))) return true;
            }
            return false;
        });
    }
    if ($ship) {
        $allLts = array_filter($allLts, function ($lt) use ($ship) {
            foreach ($lt['invoices'] as $inv) {
                if (str_contains(strtolower($inv['ship']), strtolower($ship))) return true;
            }
            return false;
        });
    }

    $allLts = array_values($allLts);
    $total = count($allLts);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allLts, $offset, $perPage);

    return Inertia::render('Incomes/ListKirimTagihan/Index', [
        'lts' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'no_lt' => $noLT,
            'customer_name' => $customerName,
            'ship' => $ship,
            'per_page' => $perPage
        ]
    ]);
});

Route::get('/schedule-tukar-faktur', [\App\Http\Controllers\Incomes\DocumentController::class, 'scheduleTukarFaktur']);

Route::get('/surat-tagihan', [\App\Http\Controllers\Incomes\DocumentController::class, 'suratTagihan']);

Route::get('/report-mayora', function (Illuminate\Http\Request $request) {
    $noKode = $request->input('no_kode', '');
    $tanggalKirim = $request->input('tanggal_kirim', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allReports = [
        ['id' => 1, 'tanggal_kirim' => '2021-09-22', 'no_dokumen' => '0001/YAN-IX/2021', 'invoice_number' => '0001/YAN-IX/2021', 'order_number' => '0001/YAN-IX/2021'],
        ['id' => 2, 'tanggal_kirim' => '2021-09-29', 'no_dokumen' => '0002/YAN-IX/2021', 'invoice_number' => '0002/YAN-IX/2021', 'order_number' => '0002/YAN-IX/2021'],
        ['id' => 3, 'tanggal_kirim' => '2021-10-13', 'no_dokumen' => '0003/YAN-X/2021', 'invoice_number' => '0003/YAN-X/2021', 'order_number' => '0003/YAN-X/2021'],
        ['id' => 4, 'tanggal_kirim' => '2021-10-21', 'no_dokumen' => '0004/YAN-X/2021', 'invoice_number' => '0004/YAN-X/2021', 'order_number' => '0004/YAN-X/2021'],
        ['id' => 5, 'tanggal_kirim' => '2021-10-27', 'no_dokumen' => '0005/YAN-X/2021', 'invoice_number' => '0005/YAN-X/2021', 'order_number' => '0005/YAN-X/2021'],
        ['id' => 6, 'tanggal_kirim' => '2021-11-03', 'no_dokumen' => '0006/YAN-XI/2021', 'invoice_number' => '0006/YAN-XI/2021', 'order_number' => '0006/YAN-XI/2021'],
        ['id' => 7, 'tanggal_kirim' => '2021-11-03', 'no_dokumen' => '0007/YAN-XI/2021', 'invoice_number' => '0007/YAN-XI/2021', 'order_number' => '0007/YAN-XI/2021'],
        ['id' => 8, 'tanggal_kirim' => '2021-11-10', 'no_dokumen' => '0008/YAN-XI/2021', 'invoice_number' => '0008/YAN-XI/2021', 'order_number' => '0008/YAN-XI/2021'],
        ['id' => 9, 'tanggal_kirim' => '2021-11-17', 'no_dokumen' => '0009/YAN-XI/2021', 'invoice_number' => '0009/YAN-XI/2021', 'order_number' => '0009/YAN-XI/2021'],
        ['id' => 10, 'tanggal_kirim' => '2021-11-24', 'no_dokumen' => '0010/YAN-XI/2021', 'invoice_number' => '0010/YAN-XI/2021', 'order_number' => '0010/YAN-XI/2021'],
        ['id' => 11, 'tanggal_kirim' => '2021-12-01', 'no_dokumen' => '0011/YAN-XI/2021', 'invoice_number' => '0011/YAN-XI/2021', 'order_number' => '0011/YAN-XI/2021'],
        ['id' => 12, 'tanggal_kirim' => '2021-12-01', 'no_dokumen' => '0012/YAN-XI/2021', 'invoice_number' => '0012/YAN-XI/2021', 'order_number' => '0012/YAN-XI/2021'],
        ['id' => 13, 'tanggal_kirim' => '2021-12-08', 'no_dokumen' => '0013/YAN-XII/2021', 'invoice_number' => '0013/YAN-XII/2021', 'order_number' => '0013/YAN-XII/2021'],
        ['id' => 14, 'tanggal_kirim' => '2021-12-15', 'no_dokumen' => '0014/YAN-XII/2021', 'invoice_number' => '0014/YAN-XII/2021', 'order_number' => '0014/YAN-XII/2021'],
        ['id' => 15, 'tanggal_kirim' => '2021-12-22', 'no_dokumen' => '0015/YAN-XII/2021', 'invoice_number' => '0015/YAN-XII/2021', 'order_number' => '0015/YAN-XII/2021'],
    ];

    if ($noKode) {
        $allReports = array_filter($allReports, function ($r) use ($noKode) {
            return str_contains(strtolower($r['no_dokumen']), strtolower($noKode)) ||
                   str_contains(strtolower($r['invoice_number']), strtolower($noKode)) ||
                   str_contains(strtolower($r['order_number']), strtolower($noKode));
        });
    }
    if ($tanggalKirim) {
        $allReports = array_filter($allReports, function ($r) use ($tanggalKirim) {
            return str_contains(strtolower($r['tanggal_kirim']), strtolower($tanggalKirim));
        });
    }

    $allReports = array_values($allReports);
    $total = count($allReports);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allReports, $offset, $perPage);

    return Inertia::render('Incomes/ReportMayora/Index', [
        'reports' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'no_kode' => $noKode,
            'tanggal_kirim' => $tanggalKirim,
            'per_page' => $perPage
        ]
    ]);
});

Route::get('/kwitansi', function (Illuminate\Http\Request $request) {
    $customerName = $request->input('customer_name', '');
    $tanggalReport = $request->input('tanggal_report', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allKwitansis = [
        [
            'id' => 1,
            'tanggal_report' => '2026-05-28',
            'no_faktur' => '05002600194846225',
            'alamat' => 'JL TIPAR CAKUNG KAV F 5-7 Blok - No.- RT:000 RW:000 Kel.CAKUNG BARAT Kec.CAKUNG Kota/Kab.JAKARTA TIMUR DKI JAKARTA 13910',
            'keterangan' => 'PEMBAYARAN ONGKOS ANGKUTAN BARANG',
            'no_kwitansi' => '043/KWT-FP/V/2026',
            'terima_dari' => 'PT. Sayap Mas Utama'
        ],
        [
            'id' => 2,
            'tanggal_report' => '2026-05-18',
            'no_faktur' => '05002600183665577',
            'alamat' => 'JL TIPAR CAKUNG KAV F 5-7 Blok - No.- RT:000 RW:000 Kel.CAKUNG BARAT Kec.CAKUNG Kota/Kab.JAKARTA TIMUR DKI JAKARTA 13910',
            'keterangan' => 'PEMBAYARAN ONGKOS ANGKUTAN BARANG',
            'no_kwitansi' => '042/KWT-FP/V/2026',
            'terima_dari' => 'PT. Sayap Mas Utama'
        ],
        [
            'id' => 3,
            'tanggal_report' => '2026-05-08',
            'no_faktur' => '05002600164228030',
            'alamat' => 'HARAPAN RAYA LOT LL-1 & 2 KAWASAN INDUSTRI KIIC Blok - No.- RT:000 RW:000 Kel.SIRNABAYA Kec.TELUK JAMBE Kota/Kab.KARAWANG JAWA BARAT 41361',
            'keterangan' => 'PEMBAYARAN ONGKOS ANGKUTAN BARANG',
            'no_kwitansi' => '041/KWT-FP/V/2026',
            'terima_dari' => 'PT. Sharp Electronics Indonesia'
        ],
        [
            'id' => 4,
            'tanggal_report' => '2026-05-08',
            'no_faktur' => '05002600164228028',
            'alamat' => 'HARAPAN RAYA LOT LL-1 & 2 KAWASAN INDUSTRI KIIC Blok - No.- RT:000 RW:000 Kel.SIRNABAYA Kec.TELUK JAMBE Kota/Kab.KARAWANG JAWA BARAT 41361',
            'keterangan' => 'PEMBAYARAN ONGKOS ANGKUTAN BARANG',
            'no_kwitansi' => '040/KWT-FP/V/2026',
            'terima_dari' => 'PT. Sharp Electronics Indonesia'
        ],
        [
            'id' => 5,
            'tanggal_report' => '2026-05-02',
            'no_faktur' => '05002600164228020',
            'alamat' => 'HARAPAN RAYA LOT LL-1 & 2 KAWASAN INDUSTRI KIIC Blok - No.- RT:000 RW:000 Kel.SIRNABAYA Kec.TELUK JAMBE Kota/Kab.KARAWANG JAWA BARAT 41361',
            'keterangan' => 'PEMBAYARAN ONGKOS ANGKUTAN BARANG',
            'no_kwitansi' => '039/KWT-FP/V/2026',
            'terima_dari' => 'PT. Sharp Electronics Indonesia'
        ]
    ];

    if ($customerName) {
        $allKwitansis = array_filter($allKwitansis, function ($k) use ($customerName) {
            return str_contains(strtolower($k['terima_dari']), strtolower($customerName));
        });
    }

    if ($tanggalReport) {
        $allKwitansis = array_filter($allKwitansis, function ($k) use ($tanggalReport) {
            return str_contains(strtolower($k['tanggal_report']), strtolower($tanggalReport));
        });
    }

    $allKwitansis = array_values($allKwitansis);
    $total = count($allKwitansis);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allKwitansis, $offset, $perPage);

    return Inertia::render('Incomes/Kwitansi/Index', [
        'kwitansis' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'customer_name' => $customerName,
            'tanggal_report' => $tanggalReport,
            'per_page' => $perPage
        ]
    ]);
});

Route::get('/titip-internal', [\App\Http\Controllers\Incomes\DocumentController::class, 'titipInternal']);

Route::get('/upload-no-faktur', function () {
    return Inertia::render('Incomes/UploadNoFaktur/Index');
});

// =============================================
// SETTINGS ROUTES
// =============================================

// Main Settings page (Inertia)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::get('/settings/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');

// Payment Limits & Categories
Route::get('/settings/payment-limits', [PaymentLimitController::class, 'index'])->name('payment-limits.index');
Route::post('/settings/payment-categories', [PaymentLimitController::class, 'storeCategory']);
Route::put('/settings/payment-categories/{category}', [PaymentLimitController::class, 'updateCategory']);
Route::delete('/settings/payment-categories/{category}', [PaymentLimitController::class, 'destroyCategory']);

Route::post('/settings/payment-limits', [PaymentLimitController::class, 'storeLimit']);
Route::put('/settings/payment-limits/{limit}', [PaymentLimitController::class, 'updateLimit']);
Route::delete('/settings/payment-limits/{limit}', [PaymentLimitController::class, 'destroyLimit']);

// Companies API
Route::prefix('api/settings')->group(function () {
    // Companies
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::post('/companies/{id}', [CompanyController::class, 'update']); // POST with _method=PUT for file uploads
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

    // Taxes
    Route::get('/taxes', [TaxController::class, 'index']);
    Route::post('/taxes', [TaxController::class, 'store']);
    Route::put('/taxes/{id}', [TaxController::class, 'update']);
    Route::delete('/taxes/{id}', [TaxController::class, 'destroy']);

    Route::get('/discounts', [DiscountController::class, 'index']);
    Route::post('/discounts', [DiscountController::class, 'store']);
    Route::put('/discounts/{id}', [DiscountController::class, 'update']);
    Route::delete('/discounts/{id}', [DiscountController::class, 'destroy']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Roles & Permissions
    Route::get('/api/roles', [RoleController::class, 'getRoles']);
    Route::post('/users/{user}/roles', [RoleController::class, 'assignRole']);
    Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'removeRole']);

    Route::get('/posted-periode', [App\Http\Controllers\Settings\PostedPeriodeController::class, 'index']);
    Route::post('/posted-periode/{periode}/toggle', [App\Http\Controllers\Settings\PostedPeriodeController::class, 'toggle']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/permissions', [RoleController::class, 'storePermission']);
    Route::delete('/permissions/{id}', [RoleController::class, 'destroyPermission']);

    // Invoice Settings
    Route::get('/invoice-setting', [InvoiceSettingController::class, 'index']);
    Route::post('/invoice-setting', [InvoiceSettingController::class, 'save']);

    // Invoice Types
    Route::get('/invoice-types', [InvoiceTypeController::class, 'index']);
    Route::post('/invoice-types', [InvoiceTypeController::class, 'store']);
    Route::put('/invoice-types/{id}', [InvoiceTypeController::class, 'update']);
    Route::delete('/invoice-types/{id}', [InvoiceTypeController::class, 'destroy']);
});