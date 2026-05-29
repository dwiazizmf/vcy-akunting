<?php

use Inertia\Inertia;
use App\Http\Controllers\Incomes\InvoiceController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\TaxController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\InvoiceSettingController;

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
    $email = $request->input('email');
    $password = $request->input('password');

    if ($email === 'admin@vcy.com' && $password === 'password') {
        return redirect('/dashboard');
    }

    return back()->withErrors([
        'error' => 'Kombinasi email dan password tidak cocok.',
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

Route::post('/set-company', function (Illuminate\Http\Request $request) {
    $request->validate(['company_id' => 'required|integer']);
    session(['company_id' => $request->company_id]);
    return back();
})->name('set-company');

Route::get('/ledger', function () {
    return Inertia::render('Ledger/Index');
});

// Invoice route sekarang pakai Controller
Route::get('/invoices', [InvoiceController::class, 'index']);

Route::get('/invoices/create', function () {
    return Inertia::render('Invoices/Create');
});

Route::get('/customers', function (Illuminate\Http\Request $request) {
    // Generate dummy customer data
    $search = $request->input('search', '');
    $status = $request->input('status', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allCustomers = [
        ['id' => 1, 'name' => 'SOOPLAI', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 2, 'name' => 'PT. BARUNA', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 3, 'name' => 'AB SHOP', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 4, 'name' => 'Abadi Cargo / Kiki Express', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 5, 'name' => 'ABC PRESIDENT INDONESIA', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 6, 'name' => 'ACHAN FARM', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 7, 'name' => 'ACHOI - SOSOK', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 8, 'name' => 'achoi - sosok', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 9, 'name' => 'ACI / BP.EVENTIUS', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 10, 'name' => 'ACU.', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => false],
        ['id' => 11, 'name' => 'AGRO ABADI', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 12, 'name' => 'AGRO ABADI / BP. ERWIN', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 13, 'name' => 'AGRO PALEM', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 14, 'name' => 'AHMAD SANUSI, BP.', 'email' => 'N/A', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 15, 'name' => 'AHUI / HOWE AUTO', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
        ['id' => 16, 'name' => 'AKHO', 'email' => '', 'phone' => '', 'unpaid' => 0, 'is_active' => true],
    ];

    // Filter
    if ($search) {
        $allCustomers = array_filter($allCustomers, function ($c) use ($search) {
            return str_contains(strtolower($c['name']), strtolower($search));
        });
    }
    if ($status) {
        $isActiveVal = $status === 'active';
        $allCustomers = array_filter($allCustomers, function ($c) use ($isActiveVal) {
            return $c['is_active'] === $isActiveVal;
        });
    }

    $allCustomers = array_values($allCustomers);
    $total = count($allCustomers);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allCustomers, $offset, $perPage);

    return Inertia::render('Customers/Index', [
        'customers' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'stats' => [
            'total' => 16,
            'active' => 15,
            'inactive' => 1,
            'totalUnpaid' => 0
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

Route::post('/customers', function () {
    return redirect('/customers');
});

Route::get('/documents/create', function () {
    return Inertia::render('Documents/Create');
});

Route::post('/documents', function () {
    return redirect('/customers'); // Redirect somewhere for now
});

Route::get('/tanda-terima', function (Illuminate\Http\Request $request) {
    $orderNumber = $request->input('order_number', '');
    $customerName = $request->input('customer_name', '');
    $tanggalKirim = $request->input('tanggal_kirim', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allReceipts = [
        [
            'id' => 4463,
            'tanggal_kirim' => '30 Apr 2026',
            'no_dokumen' => '0206/IV/2026',
            'invoices' => [
                ['number' => '0001179', 'status' => 'Paid', 'customer_name' => 'PT. Hirundo Tyre Utama', 'orders' => ['260203505']],
                ['number' => '0001223', 'status' => 'Sent', 'customer_name' => 'PT. Hirundo Tyre Utama', 'orders' => ['260203505']]
            ]
        ],
        [
            'id' => 4462,
            'tanggal_kirim' => '30 Apr 2026',
            'no_dokumen' => '0205/IV/2026',
            'invoices' => [
                ['number' => '0001166', 'status' => 'Paid', 'customer_name' => 'PT. SELERA SWEETSINDO', 'orders' => ['260400849']],
                ['number' => '0001168', 'status' => 'Sent', 'customer_name' => 'PT. SELERA SWEETSINDO', 'orders' => ['260203525']]
            ]
        ],
        [
            'id' => 4461,
            'tanggal_kirim' => '30 Apr 2026',
            'no_dokumen' => '0204/IV/2026',
            'invoices' => [
                ['number' => '0001165', 'status' => 'Paid', 'customer_name' => 'PT. HI-COOK INDONESIA', 'orders' => ['260400357']]
            ]
        ],
        [
            'id' => 4460,
            'tanggal_kirim' => '30 Apr 2026',
            'no_dokumen' => '0203/IV/2026',
            'invoices' => [
                ['number' => '0001169', 'status' => 'draft', 'customer_name' => 'PT. MITRA KENCANA NUSANTARA', 'orders' => ['260300423']]
            ]
        ],
        [
            'id' => 4459,
            'tanggal_kirim' => '30 Apr 2026',
            'no_dokumen' => '0202/IV/2026',
            'invoices' => [
                ['number' => '0001170', 'status' => 'draft', 'customer_name' => 'PT.CRIETA', 'orders' => ['260400393']]
            ]
        ],
        [
            'id' => 4458,
            'tanggal_kirim' => '29 Apr 2026',
            'no_dokumen' => '0201/IV/2026',
            'invoices' => [
                ['number' => '0001054', 'status' => 'Paid', 'customer_name' => 'PT. BAHTERA CIPTA RAGA PRIMA', 'orders' => ['260400003']]
            ]
        ],
        [
            'id' => 4457,
            'tanggal_kirim' => '29 Apr 2026',
            'no_dokumen' => '0200/IV/2026',
            'invoices' => [
                ['number' => '0001080', 'status' => 'Paid', 'customer_name' => 'PT. DYNAVOLT TEKNOLOGI INDONESIA', 'orders' => ['260203633']]
            ]
        ],
        [
            'id' => 4456,
            'tanggal_kirim' => '29 Apr 2026',
            'no_dokumen' => '0199/IV/2026',
            'invoices' => [
                ['number' => '0000075', 'status' => 'Paid', 'customer_name' => 'MEGUSDYAN SUSANTO', 'orders' => ['260102822']],
                ['number' => '0000906', 'status' => 'Sent', 'customer_name' => 'MEGUSDYAN SUSANTO', 'orders' => ['260201508']]
            ]
        ]
    ];

    if ($orderNumber) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($orderNumber) {
            foreach ($r['invoices'] as $inv) {
                foreach ($inv['orders'] as $o) {
                    if (str_contains($o, $orderNumber)) return true;
                }
            }
            return false;
        });
    }
    if ($customerName) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($customerName) {
            foreach ($r['invoices'] as $inv) {
                if (str_contains(strtolower($inv['customer_name']), strtolower($customerName))) return true;
            }
            return false;
        });
    }
    if ($tanggalKirim) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($tanggalKirim) {
            return str_contains(strtolower($r['tanggal_kirim']), strtolower($tanggalKirim));
        });
    }

    $allReceipts = array_values($allReceipts);
    $total = count($allReceipts);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allReceipts, $offset, $perPage);

    return Inertia::render('TandaTerima/Index', [
        'receipts' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
            'tanggal_kirim' => $tanggalKirim,
            'per_page' => $perPage
        ]
    ]);
});

Route::get('/tanda-terima/new', function (Illuminate\Http\Request $request) {
    $noTT = $request->input('no_tt', '');
    $orderNumber = $request->input('order_number', '');
    $customerName = $request->input('customer_name', '');
    $bulan = $request->input('bulan', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allReceipts = [
        [
            'id' => 4463,
            'tanggal_kirim' => '29 Apr 2026',
            'no_dokumen' => '0099/IV/2026',
            'customer_name' => 'PT. Papasari',
            'invoices' => [
                ['number' => '0001216', 'status' => 'draft', 'orders' => ['260300319', '260300567']],
                ['number' => '0001217', 'status' => 'draft', 'orders' => ['260300817', '260300860']],
                ['number' => '0001218', 'status' => 'draft', 'orders' => ['260300862', '260300873']],
                ['number' => '0001219', 'status' => 'draft', 'orders' => ['260300791', '260300946']],
                ['number' => '0001220', 'status' => 'draft', 'orders' => ['260300996', '260301209']],
                ['number' => '0001221', 'status' => 'draft', 'orders' => ['260301354', '260301543']],
                ['number' => '0001222', 'status' => 'draft', 'orders' => ['260301106', '260301195']]
            ]
        ],
        [
            'id' => 4462,
            'tanggal_kirim' => '29 Apr 2026',
            'no_dokumen' => '0098/IV/2026',
            'customer_name' => 'PT. Papasari',
            'invoices' => [
                ['number' => '0001151', 'status' => 'draft', 'orders' => ['260300020', '260300211']],
                ['number' => '0001152', 'status' => 'draft', 'orders' => ['260300911', '260300570']],
                ['number' => '0001153', 'status' => 'draft', 'orders' => ['260300641', '260300645']],
                ['number' => '0001154', 'status' => 'draft', 'orders' => ['260300646', '260203438']],
                ['number' => '0001155', 'status' => 'draft', 'orders' => ['260301074']],
                ['number' => '0001156', 'status' => 'draft', 'orders' => ['260301076']]
            ]
        ],
        [
            'id' => 4461,
            'tanggal_kirim' => '28 Apr 2026',
            'no_dokumen' => '0097/IV/2026',
            'customer_name' => 'PT. Papasari',
            'invoices' => [
                ['number' => '0001140', 'status' => 'Paid', 'orders' => ['260202923']]
            ]
        ]
    ];

    if ($noTT) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($noTT) {
            return str_contains($r['no_dokumen'], $noTT);
        });
    }
    if ($orderNumber) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($orderNumber) {
            foreach ($r['invoices'] as $inv) {
                foreach ($inv['orders'] as $o) {
                    if (str_contains($o, $orderNumber)) return true;
                }
            }
            return false;
        });
    }
    if ($customerName) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($customerName) {
            return str_contains(strtolower($r['customer_name']), strtolower($customerName));
        });
    }
    if ($bulan) {
        $allReceipts = array_filter($allReceipts, function ($r) use ($bulan) {
            return str_contains(strtolower($r['tanggal_kirim']), strtolower($bulan));
        });
    }

    $allReceipts = array_values($allReceipts);
    $total = count($allReceipts);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allReceipts, $offset, $perPage);

    return Inertia::render('TandaTerima/New', [
        'receipts' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'no_tt' => $noTT,
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
            'bulan' => $bulan,
            'per_page' => $perPage
        ]
    ]);
});

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

    return Inertia::render('ListKirimTagihan/Index', [
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

Route::get('/schedule-tukar-faktur', function (Illuminate\Http\Request $request) {
    $noTF = $request->input('no_tf', '');
    $customerName = $request->input('customer_name', '');
    $orderNumber = $request->input('order_number', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allSchedules = [
        [
            'id' => 2676,
            'tanggal_kirim' => '29 May 2026',
            'no_tf' => 'TF-0000218',
            'invoices' => [
                ['number' => '0001698', 'customer_name' => 'PT. Ajinomoto Sales Indonesia', 'order_number' => '260202887', 'amount' => 15000000],
                ['number' => '0001700', 'customer_name' => 'PT. Ajinomoto Sales Indonesia', 'order_number' => '260202983', 'amount' => 8500005],
                ['number' => '0001704', 'customer_name' => 'PT. Ajinomoto Sales Indonesia', 'order_number' => '260203181', 'amount' => 12500000]
            ]
        ],
        [
            'id' => 2675,
            'tanggal_kirim' => '29 May 2026',
            'no_tf' => 'TF-0000217',
            'invoices' => [
                ['number' => '0001261', 'customer_name' => 'PT. Inbisco Niagatama Semesta', 'order_number' => '260103747', 'amount' => 22400000],
                ['number' => '0001386', 'customer_name' => 'PT. Inbisco Niagatama Semesta', 'order_number' => '260403135', 'amount' => 12300000]
            ]
        ]
    ];

    if ($noTF) {
        $allSchedules = array_filter($allSchedules, function ($s) use ($noTF) {
            return str_contains($s['no_tf'], $noTF);
        });
    }
    if ($customerName) {
        $allSchedules = array_filter($allSchedules, function ($s) use ($customerName) {
            foreach ($s['invoices'] as $inv) {
                if (str_contains(strtolower($inv['customer_name']), strtolower($customerName))) return true;
            }
            return false;
        });
    }
    if ($orderNumber) {
        $allSchedules = array_filter($allSchedules, function ($s) use ($orderNumber) {
            foreach ($s['invoices'] as $inv) {
                if (str_contains(strtolower($inv['order_number']), strtolower($orderNumber))) return true;
            }
            return false;
        });
    }

    $allSchedules = array_values($allSchedules);
    $total = count($allSchedules);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allSchedules, $offset, $perPage);

    return Inertia::render('ScheduleTukarFaktur/Index', [
        'schedules' => $items,
        'pagination' => [
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => (int) ceil($total / $perPage),
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ],
        'filters' => [
            'no_tf' => $noTF,
            'customer_name' => $customerName,
            'order_number' => $orderNumber,
            'per_page' => $perPage
        ]
    ]);
});

// =============================================
// SETTINGS ROUTES
// =============================================

// Main Settings page (Inertia)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

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

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Roles & Permissions
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
});