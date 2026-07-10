# VCY Accounting — Project Overview

> Panduan arsitektur, struktur, dan konvensi kode untuk developer & AI agent.

---

## 🏢 Tentang Project

**VCY Accounting** adalah sistem akuntansi internal untuk perusahaan pelayaran VCY.  
Stack: **Laravel 11 + Inertia.js + Svelte 5 + Tailwind CSS + shadcn-svelte**.

Database utama: **MySQL** (koneksi ke database lama di `202.129.190.109:13306`, database `akunting_beta`).

---

## 🗂️ Struktur Folder

```
vcy-accounting/
├── app/
│   ├── Http/Controllers/
│   │   ├── Incomes/          # Controller untuk modul pendapatan
│   │   │   ├── InvoiceController.php
│   │   │   └── ...
│   │   └── ...
│   └── Models/
│       ├── Incomes/
│       │   ├── Invoice.php   # Model invoice dari DB lama
│       │   └── ...
│       └── ...
├── resources/js/
│   ├── Components/           # Shared components (Header, Footer, Sidebar)
│   │   ├── AppHeader.svelte
│   │   ├── AppFooter.svelte
│   │   └── AppSidebar.svelte
│   ├── components/ui/        # shadcn-svelte UI primitives (jangan edit langsung)
│   │   ├── button/
│   │   ├── card/
│   │   ├── table/
│   │   └── ...
│   ├── Layouts/
│   │   └── AppLayout.svelte  # Layout utama, support prop fullWidth={true}
│   └── Pages/                # Halaman (1 folder per modul)
│       ├── Auth/
│       ├── Customers/
│       ├── Documents/
│       ├── Invoices/         # ← Modul yang sedang dikembangkan
│       │   └── Index.svelte
│       ├── Ledger/
│       ├── ListKirimTagihan/
│       └── TandaTerima/
├── routes/
│   └── web.php               # Semua route, di-group per modul
└── .agents/
    ├── AGENTS.md             # ← File ini, dibaca otomatis
    ├── desain.md             # Panduan desain & UI
    └── project-overview.md  # File ini
```

---

## 🗃️ Grouping Route & Controller

Route di-group berdasarkan modul:
```php
// web.php
Route::prefix('invoices')->group(function() { ... });  // Incomes
Route::prefix('expenses')->group(function() { ... });  // Expenses (akan datang)
Route::prefix('journals')->group(function() { ... });  // Journals (akan datang)
Route::prefix('settings')->group(function() { ... });  // Settings (akan datang)
```

Controller dan Model **selalu di dalam subfolder modul**:
- `app/Http/Controllers/Incomes/InvoiceController.php`
- `app/Models/Incomes/Invoice.php`

---

## 🔄 Komponen Reusable — Status & Rencana

Komponen berikut **sedang inline** di halaman Invoices dan perlu di-extract menjadi komponen terpisah agar bisa dipakai ulang di halaman lain (Expenses, Journals, dsb):

### ✅ Sudah Ada (Inline, Perlu Extract)

#### 1. Pagination
**Saat ini:** Inline di `Pages/Invoices/Index.svelte` dan `Pages/TandaTerima/Index.svelte`  
**Target:** `Components/Pagination.svelte`

Props yang dibutuhkan:
```svelte
export let pagination; // { total, perPage, currentPage, lastPage, from, to }
export let onGoToPage; // function(page)
```

#### 2. Column Toggle (Show/Hide Kolom)
**Istilah resmi: Column Visibility Toggle**  
**Saat ini:** Inline di `Pages/Invoices/Index.svelte`  
**Target:** `Components/ColumnToggle.svelte`

Props yang dibutuhkan:
```svelte
export let columns;        // Array<{ key, label, visible }>
export let onToggle;       // function(key)
export let onShowAll;      // function()
export let onReset;        // function()
```

Catatan penting: Dropdown harus menggunakan `position: fixed` (bukan `absolute`) agar tidak terclip oleh parent `overflow-hidden`.

#### 3. Filter Panel (Collapsible)
**Saat ini:** Inline per halaman  
**Target:** `Components/FilterPanel.svelte`  
**Status:** Belum di-extract, menyusul setelah pattern stabil

### 🔮 Belum Ada (Rencana)

| Komponen | Deskripsi |
|---|---|
| `DataTable.svelte` | Wrapper tabel lengkap dengan expand/collapse, checkbox, aksi |
| `StatsGrid.svelte` | Grid 4 kartu statistik di atas tabel |
| `EmptyState.svelte` | Tampilan saat data kosong |
| `ConfirmModal.svelte` | Modal konfirmasi delete/aksi destruktif |

---

## 🗄️ Konvensi Database

Model terhubung ke **database lama** (legacy). Kolom-kolom penting di tabel `vcy_invoices`:

| Field DB | Prop Svelte | Keterangan |
|---|---|---|
| `invoice_text` | `invoiceText` | Nomor invoice tampilan, fallback ke `invoice_number` |
| `invoice_number` | `number` | Nomor invoice internal |
| `order_number` | `orderNumber` | Nomor order (bisa panjang, wrap text) |
| `invoice_status_code` | `status` / `statusPayment` | Status invoice: `draft`, `sent`, `paid` |
| `departure_date` | `tglKapBerangkat` | Tanggal kapal berangkat |
| `created_on` | `statusCreated` | Tanggal dibuat (bukan `created_from`) |
| `notes_text` | `notes` | Catatan |
| `no_faktur_pajak` / `no_faktur_int` | `faktur` | Nomor faktur |
| `isBpb` | `bpb` | BPB (Ya/Tidak) |

Controller selalu melakukan mapping dari snake_case DB ke camelCase props Svelte.

---

## 🔧 Konvensi Kode

### PHP (Controller)
```php
// ✅ Selalu gunakan orderBy id desc untuk data terbaru di atas
$paginator = Invoice::orderBy('id', 'desc')->paginate($perPage);

// ✅ Selalu validate per_page dari whitelist
$perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

// ✅ Mapping data selalu dalam $items, pisahkan dari $pagination dan $stats
$items = $paginator->map(fn($inv) => [...]);
```

### Svelte
```js
// ✅ Reactive derived state (bukan function biasa)
$: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

// ❌ JANGAN — function biasa tidak reaktif di template Svelte
function isVisible(key) { ... }  // Template tidak akan re-render!

// ✅ Toggle array dengan reassignment (bukan push/splice)
expandedRows = [...expandedRows, id];

// ✅ Selalu gunakan preserveState dan preserveScroll untuk filter/pagination
router.get('/invoices', params, { preserveState: true, preserveScroll: true });
```

### Arsitektur UI (Contextual Actions)
Untuk menghindari *context switching* (user berpindah-pindah halaman untuk task kecil):
- ✅ **Pecah Form & Detail menjadi Komponen Mandiri**: Misalnya, daripada membuat halaman penuh untuk `CreateCustomer.svelte`, buatlah sebagai komponen yang bisa di-mount di mana saja.
- ✅ **Gunakan Modal/Slide-over**: Jika user butuh membuat data baru (misal nambah Customer di form Invoice) atau melihat detail data (misal cek Invoice di dalam menu Jurnal), gunakan komponen Svelte tersebut di dalam **Global Modal** atau **Slide-over**.
- ✅ **Data Fetching Asinkron**: Saat menampilkan detail di modal, ambil datanya melalui request API kecil (Axios/Fetch) alih-alih me-reload seluruh halaman Inertia. Saat submit form di dalam modal, gunakan `router.post` dengan `preserveState: true` dan `preserveScroll: true` agar user tidak kehilangan posisinya.

---

## 🚀 Development Workflow

```bash
# Start semua service
./vendor/bin/sail up -d

# Frontend dev (HMR)
./vendor/bin/sail npm run dev

# Build production / verifikasi kompilasi
./vendor/bin/sail npm run build

# Cari file dengan greppy
greppy "keyword" --include="*.svelte"

# Restart docker
./vendor/bin/sail restart
```

---

## 📦 Dependencies Utama

| Package | Versi | Kegunaan |
|---|---|---|
| Laravel | 11.x | Backend framework |
| Inertia.js | 2.x | SPA adapter Laravel ↔ Svelte |
| Svelte | 5.x | Frontend framework |
| shadcn-svelte | latest | UI component library |
| Tailwind CSS | 4.x | Utility CSS |
| Vite | latest | Build tool |

---

## 🔐 Koneksi Database

```
Host: 202.129.190.109
Port: 13306 (MySQL 8.0)
Database: akunting_beta
Connection name: mysql (di .env)
```

Session driver: **database** — pastikan tabel `sessions` ada, atau ganti ke `file`/`cookie`.
