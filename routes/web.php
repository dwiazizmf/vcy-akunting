<?php

use Inertia\Inertia;
use App\Http\Controllers\InvoiceController;

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

Route::get('/ledger', function () {
    return Inertia::render('Ledger/Index');
});

// Invoice route sekarang pakai Controller
Route::get('/invoices', [InvoiceController::class, 'index']);