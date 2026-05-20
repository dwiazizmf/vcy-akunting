# Proyek VCY Accounting - Project Overview

Dokumen ini berfungsi sebagai peta jalan dan spesifikasi fitur untuk aplikasi web akuntansi VCY Accounting. Anda dapat mengisi detail, menyesuaikan fitur, atau mengubah aturan bisnis di bawah ini sesuai kebutuhan bisnis Anda.

---

## 🎯 1. Informasi Umum
* **Nama Aplikasi**: VCY Accounting
* **Tujuan Utama**: [Tulis deskripsi singkat, contoh: Pencatatan keuangan harian, rekonsiliasi bank, dan pelaporan keuangan otomatis untuk operasional kapal dan logistik.]
* **Target Pengguna**: [Contoh: Tim Keuangan, Auditor, Manajemen Perusahaan.]

---

## 💻 2. Spesifikasi Teknologi
* **Backend**: Laravel 11 / PHP 8.2+
* **Frontend**: Svelte 4 / Inertia.js (SPA feel, SSR compatible)
* **CSS Framework**: Tailwind CSS v4
* **UI Component**: Shadcn-Svelte (Radix Svelte / Bits-UI)
* **Database**: PostgreSQL (Development & Production)
* **Docker Environment**: Laravel Sail (Nginx, PGSQL, Redis)

---

## 📦 3. Modul & Fitur Utama

### A. Modul Buku Besar (General Ledger & Double-Entry)
* [ ] **Daftar Akun / Chart of Accounts (COA)**: Pengelolaan nomor rekening akuntansi (Aktiva, Pasiva, Modal, Pendapatan, Beban).
* [ ] **Jurnal Umum (Journal Entries)**: Input transaksi manual berpasangan (Debit & Kredit wajib seimbang).
* [ ] **Buku Besar (Ledger)**: Filter per perincian akun dan per tanggal transaksi.
* [ ] **Jurnal Penyesuaian (Adjustment Entries)**: Pembuatan jurnal koreksi akhir bulan.

### B. Modul Pendapatan (Incomes & Accounts Receivable)
* [ ] **Daftar Pelanggan (Customers)**: Database lengkap klien/customer.
* [ ] **Invoices**: Pembuatan invoice tagihan penjualan/jasa lengkap dengan detail term pembayaran.
* [ ] **Penerimaan Pembayaran (Payments)**: Pencatatan pelunasan invoice baik secara parsial maupun lunas.
* [ ] **Tanda Terima Dokumen / Faktur**: Pengelolaan dokumen tukar faktur atau tanda terima fisik.

### C. Modul Pengeluaran (Expenses & Accounts Payable)
* [ ] **Daftar Supplier / Vendor**: Database penyedia barang/jasa.
* [ ] **Tagihan Pembelian (Vendor Bills)**: Pencatatan hutang atas pembelian barang/operasional.
* [ ] **Pembayaran Hutang (Bill Payments)**: Pencatatan pelunasan tagihan supplier.
* [ ] **Pengeluaran Kas/Bank (Direct Expenses)**: Pencatatan biaya operasional langsung tanpa tagihan terlebih dahulu (misal: bayar listrik, ATK).

### D. Manajemen Kas & Bank
* [ ] **Mutasi Kas & Bank**: Rekam jejak transfer antar-rekening kas internal (Kas Besar ke Bank, dsb).
* [ ] **Rekonsiliasi Bank**: Pencocokan otomatis/manual antara rekening koran bank dengan pencatatan sistem.

### E. Modul Pelaporan Keuangan (Financial Reports)
* [ ] **Laba Rugi (Profit & Loss Statement)**: Analisis pendapatan dikurangi beban pengeluaran.
* [ ] **Neraca Keuangan (Balance Sheet)**: Laporan Aset vs (Kewajiban + Modal) yang wajib seimbang.
* [ ] **Arus Kas (Cash Flow Statement)**: Laporan arus kas masuk/keluar metode langsung/tidak langsung.
* [ ] **Neraca Saldo (Trial Balance)**: Ringkasan saldo debit dan kredit semua akun COA.

---

## 🗄️ 4. Struktur Database (Rencana Tabel Utama)

### 1. Tabel `accounts` (COA)
Menyimpan daftar nomor rekening/akun keuangan.
* `id` (Primary Key)
* `code` (string, unik - contoh: '11101')
* `name` (string - contoh: 'Kas Besar')
* `type` (enum: 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense')
* `parent_id` (foreign key ke self, untuk sub-akun)
* `is_active` (boolean)

### 2. Tabel `journal_entries`
Menyimpan kepala/header dari setiap jurnal transaksi.
* `id` (Primary Key)
* `reference_number` (string, unik - contoh: 'JV-202605001')
* `date` (date)
* `description` (text)
* `created_by` (foreign key ke users)

### 3. Tabel `journal_items`
Menyimpan baris detail Debit/Kredit dari setiap jurnal (satu header `journal_entries` memiliki banyak `journal_items`).
* `id` (Primary Key)
* `journal_entry_id` (foreign key ke `journal_entries`)
* `account_id` (foreign key ke `accounts`)
* `debit` (decimal/numeric, default: 0)
* `credit` (decimal/numeric, default: 0)

### 4. Tabel `invoices`
Menyimpan data penagihan ke customer.
* `id` (Primary Key)
* `invoice_number` (string, unik)
* `customer_name` (string)
* `amount` (decimal)
* `status` (enum: 'Draft', 'Sent', 'Paid', 'Overdue')
* `invoice_date` (date)
* `due_date` (date)
* `journal_entry_id` (foreign key ke `journal_entries`, dibuat otomatis saat invoice status 'Sent' atau 'Paid')

---

## 🔢 5. Standar Akun (Daftar Awal COA)
[Isi atau sesuaikan daftar akun default perusahaan Anda di sini]
* **10000 - Aset / Aktiva**
  * 11000 - Aset Lancar
    * 11101 - Kas Besar
    * 11201 - Bank BCA
    * 11301 - Piutang Usaha
* **20000 - Kewajiban / Hutang / Liabilitas**
  * 21101 - Hutang Dagang / Usaha
* **30000 - Ekuitas / Modal**
  * 31101 - Modal Disetor
* **40000 - Pendapatan / Penjualan**
  * 41101 - Pendapatan Jasa Penjualan
* **50000 - Beban / Pengeluaran**
  * 51101 - Beban Gaji Karyawan
  * 51102 - Beban Listrik, Air & Internet
  * 51103 - Beban Operasional Kapal

---

## 🔒 6. Aturan Bisnis & Validasi Utama
1. **Aturan Keseimbangan Jurnal**: `SUM(journal_items.debit) === SUM(journal_items.credit)` pada setiap transaksi jurnal. Jika tidak sama, database transaksi harus di-rollback.
2. **Kunci Periode Buku**: Transaksi dengan tanggal di masa lalu yang periode bulannya sudah "ditutup" (locked) tidak boleh ditambah, diubah, atau dihapus.
3. **Sinkronisasi Otomatis**: Setiap pembuatan Invoice (Incomes) atau Pembayaran harus memicu (*trigger*) pembuatan Jurnal Entry secara otomatis agar buku besar langsung ter-update tanpa input manual ulang.

---

## 📅 7. Rencana Pengembangan (Roadmap)
- **Fase 1: Setup & Core Ledger** (Inisialisasi database, COA, Jurnal Manual, Buku Besar per Akun). -> *[Sedang Berjalan]*
- **Fase 2: Invoice & Pelanggan** (Pembuatan Invoice penjualan, pencatatan pembayaran customer).
- **Fase 3: Pengeluaran & Supplier** (Pencatatan biaya operasional, pembelian inventaris/jasa vendor).
- **Fase 4: Laporan Keuangan** (Laporan Neraca Saldo, Laba Rugi, Neraca Keuangan).
- **Fase 5: Rekonsiliasi & Pajak** (Import rekening koran bank, penghitungan PPN/PPh).
