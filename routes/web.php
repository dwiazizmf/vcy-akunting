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

// Invoice routes menggunakan Controller
Route::resource('invoices', InvoiceController::class);

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

Route::get('/surat-tagihan', function (Illuminate\Http\Request $request) {
    $orderNumber = $request->input('order_number', '');
    $customerName = $request->input('customer_name', '');
    $tanggalKirim = $request->input('tanggal_kirim', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allSuratTagihans = [
        [
            'id' => 1,
            'tanggal' => '29 May 2026',
            'nomor' => '1067/YAN-ST/ACC/V.2026',
            'customer_name' => 'PT. Khong Guan Biscuit Factory Indonesia',
            'up_person' => 'Bapak Anas',
            'no_tlp' => '021 - 5213738',
            'address' => 'Gedung Wira Usaha Kav. C5, Jl H R. Rasuna Said No.5, Kuningan Timur, Rt.3 / Rw.1, Karet, Jakarta Selatan, DKI Jakarta 12920',
            'invoices' => [
                ['number' => '0001698', 'status' => 'Paid', 'orders' => ['260400613', '260400628', '260400629', '260400631']],
                ['number' => '0001700', 'status' => 'Paid', 'orders' => ['260403160', '260403161', '260403162', '260403163']],
                ['number' => '0001704', 'status' => 'Paid', 'orders' => ['260403164', '260403165', '260403166', '260403167']],
                ['number' => '0001714', 'status' => 'Sent', 'orders' => ['260403168', '260403169', '260403170', '260403171']]
            ]
        ],
        [
            'id' => 2,
            'tanggal' => '29 May 2026',
            'nomor' => '1066/YAN-ST/ACC/V.2026',
            'customer_name' => 'PT FKS FOOD SEJAHTERA, TBK',
            'up_person' => 'Ibu Mayang / Ibu Nurul (Accounting)',
            'no_tlp' => '021 8672409 / 8670360',
            'address' => 'Jl. Pancasila IV Desa Cicadas Kec. Gunung Putri Kab. Bogor, Jawa Barat 16964',
            'invoices' => [
                ['number' => '0000567', 'status' => 'Paid', 'orders' => ['260501798']]
            ]
        ],
        [
            'id' => 3,
            'tanggal' => '29 May 2026',
            'nomor' => '1065/YAN-ST/ACC/V.2026',
            'customer_name' => 'PT. Inbisco Niagatama Semesta',
            'up_person' => 'Bp.Hasanudin/Ibu Umi /Logistic Dept /Gdg.MLC',
            'no_tlp' => '021-59400933',
            'address' => 'Jln. Raya Serang Km.12.5 - Cikupa Tangerang 15710',
            'invoices' => [
                ['number' => '0001261', 'status' => 'Sent', 'orders' => ['260302153']]
            ]
        ]
    ];

    if ($orderNumber) {
        $allSuratTagihans = array_filter($allSuratTagihans, function ($st) use ($orderNumber) {
            foreach ($st['invoices'] as $inv) {
                foreach ($inv['orders'] as $ord) {
                    if (str_contains($ord, $orderNumber)) return true;
                }
            }
            return false;
        });
    }
    if ($customerName) {
        $allSuratTagihans = array_filter($allSuratTagihans, function ($st) use ($customerName) {
            return str_contains(strtolower($st['customer_name']), strtolower($customerName));
        });
    }
    if ($tanggalKirim) {
        $allSuratTagihans = array_filter($allSuratTagihans, function ($st) use ($tanggalKirim) {
            return str_contains(strtolower($st['tanggal']), strtolower($tanggalKirim));
        });
    }

    $allSuratTagihans = array_values($allSuratTagihans);
    $total = count($allSuratTagihans);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allSuratTagihans, $offset, $perPage);

    return Inertia::render('SuratTagihan/Index', [
        'letters' => $items,
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

    return Inertia::render('ReportMayora/Index', [
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

    return Inertia::render('Kwitansi/Index', [
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

Route::get('/titip-internal', function (Illuminate\Http\Request $request) {
    $orderNumber = $request->input('order_number', '');
    $customerName = $request->input('customer_name', '');
    $tanggalKirim = $request->input('tanggal_kirim', '');
    $perPage = (int) $request->input('per_page', 25);
    $page = (int) $request->input('page', 1);

    $allTitips = [
        ['id' => 1, 'tanggal' => '29 May 2026', 'nomor' => 'TI-0237', 'customer_name' => 'DAKOU FOOD INDUSTRY', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260501243'],
        ['id' => 2, 'tanggal' => '26 May 2026', 'nomor' => 'TI-0236', 'customer_name' => 'TJUA MENG HUI', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260500559'],
        ['id' => 3, 'tanggal' => '26 May 2026', 'nomor' => 'TI-0235', 'customer_name' => 'HENDRI', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260500579'],
        ['id' => 4, 'tanggal' => '26 May 2026', 'nomor' => 'TI-0234', 'customer_name' => 'HERY', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260500288'],
        ['id' => 5, 'tanggal' => '26 May 2026', 'nomor' => 'TI-0233', 'customer_name' => 'PRIMA BINTANG SELATAN', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260500891'],
        ['id' => 6, 'tanggal' => '26 May 2026', 'nomor' => 'TI-0232', 'customer_name' => 'PT. BORNEO TWINDO GROUP', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260403888'],
        ['id' => 7, 'tanggal' => '25 May 2026', 'nomor' => 'TI-0231', 'customer_name' => 'NUSANTARA PERKASA MESINDO.PT', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260403883'],
        ['id' => 8, 'tanggal' => '22 May 2026', 'nomor' => 'TI-0230', 'customer_name' => 'GARUDA INTI OPTIMAL', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260403853'],
        ['id' => 9, 'tanggal' => '21 May 2026', 'nomor' => 'TI-0229', 'customer_name' => 'CIPTA RASA UTAMA', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260502038'],
        ['id' => 10, 'tanggal' => '21 May 2026', 'nomor' => 'TI-0228', 'customer_name' => 'CV. CIPTA RAYA DISTRIBUSINDO-YAN', 'up_person' => '', 'no_tlp' => '', 'address' => '', 'order_number' => '260502038'],
    ];

    if ($orderNumber) {
        $allTitips = array_filter($allTitips, function ($t) use ($orderNumber) {
            return str_contains(strtolower($t['order_number']), strtolower($orderNumber));
        });
    }
    if ($customerName) {
        $allTitips = array_filter($allTitips, function ($t) use ($customerName) {
            return str_contains(strtolower($t['customer_name']), strtolower($customerName));
        });
    }
    if ($tanggalKirim) {
        $allTitips = array_filter($allTitips, function ($t) use ($tanggalKirim) {
            return str_contains(strtolower($t['tanggal']), strtolower($tanggalKirim));
        });
    }

    $allTitips = array_values($allTitips);
    $total = count($allTitips);
    $offset = ($page - 1) * $perPage;
    $items = array_slice($allTitips, $offset, $perPage);

    return Inertia::render('TitipInternal/Index', [
        'items' => $items,
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

Route::get('/upload-no-faktur', function () {
    return Inertia::render('UploadNoFaktur/Index');
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