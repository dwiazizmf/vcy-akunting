# Proyek VCY Accounting - Project Overview

Dokumen ini berfungsi sebagai peta jalan dan spesifikasi fitur utama untuk VCY Accounting. Dokumentasi proyek dibagi menjadi tiga bagian terpisah:
1. **Peta Jalan & Aturan Bisnis**: [project_overview.md](file:///home/dwiazizmf/work/vcy-accounting/project_overview.md) (File ini)
2. **Desain Tampilan & Interaksi Frontend**: [desain.md](file:///home/dwiazizmf/work/vcy-accounting/desain.md)
3. **Desain Struktur Database PostgreSQL**: [database_desain.md](file:///home/dwiazizmf/work/vcy-accounting/database_desain.md)

---

## 🎯 1. Informasi Umum
* **Nama Aplikasi**: VCY Accounting
* **Tujuan Utama**: Aplikasi akuntansi berbasis web untuk mencatat transaksi keuangan dua perusahaan jasa logistik yang berbeda secara terpisah (multi-perusahaan).
* **Target Pengguna**: Tim akunting dan tim invoicing.

---

## 💻 2. Spesifikasi Teknologi
* **Backend**: Laravel 11 / PHP 8.2+
* **Frontend**: Svelte 4 / Inertia.js (SPA feel, SSR compatible)
* **CSS Framework**: Tailwind CSS v4
* **UI Component**: Shadcn-Svelte (Radix Svelte / Bits-UI)
* **Database**: PostgreSQL
* **Docker Environment**: Laravel Sail (Nginx, PGSQL, Redis)

---

## 📦 3. Modul & Fitur Utama

### A. Modul Buku Besar (General Ledger & Double-Entry)
* **Chart of Accounts (COA)**: Pengelolaan nomor rekening akuntansi (Aktiva, Pasiva, Modal, Pendapatan, Beban) yang terpisah untuk masing-masing perusahaan.
* **Jurnal Umum (Journal Entries)**: Input transaksi manual berpasangan (Debit & Kredit wajib seimbang).
* **Buku Besar (Ledger)**: Filter per perincian akun dan per tanggal transaksi.
* **Jurnal Penyesuaian (Adjustment Entries)**: Pembuatan jurnal koreksi akhir bulan/periode.

### B. Modul Pendapatan (Incomes & Accounts Receivable)
* **Daftar Pelanggan (Customers)**: Database lengkap klien/customer logistik.
* **Invoices**: Pembuatan invoice tagihan penjualan/jasa dengan term pembayaran.
* **Kwitansi**: Pencatatan bukti bayar dengan nomor seri tersendiri yang dibuat dan dicetak secara manual oleh karyawan (terpisah dari nomor seri invoice).
* **Penerimaan Pembayaran (Payments)**: Pencatatan pelunasan invoice baik secara parsial maupun lunas.

### C. Modul Pengeluaran (Expenses & Accounts Payable)
* **Daftar Supplier / Vendor**: Database penyedia barang/jasa logistik.
* **Tagihan Pembelian (Vendor Bills)**: Pencatatan hutang atas pembelian jasa vendor/operasional.
* **Pembayaran Hutang (Bill Payments)**: Pencatatan pelunasan tagihan supplier.
* **Pengeluaran Kas/Bank (Direct Expenses)**: Pencatatan biaya operasional langsung tanpa tagihan terlebih dahulu.

### D. Manajemen Kas & Bank
* **Mutasi Kas & Bank**: Rekam jejak transfer antar-rekening kas internal.
* **Rekonsiliasi Bank**: Proses pengunggahan mutasi rekening koran format Excel, pencocokan otomatis jumlah oleh sistem, dan persetujuan (approval) manual oleh user berwenang.

### E. Modul Pelaporan Keuangan (Financial Reports)
* **Laba Rugi (Profit & Loss Statement)**: Analisis pendapatan dikurangi beban pengeluaran per perusahaan.
* **Neraca Keuangan (Balance Sheet)**: Laporan Aset vs (Kewajiban + Modal) per perusahaan.
* **Arus Kas (Cash Flow Statement)**: Laporan arus kas masuk/keluar.
* **Neraca Saldo (Trial Balance)**: Laporan neraca saldo dengan opsi **8 Kolom** (Saldo Awal D/K, Mutasi D/K, Penyesuaian D/K, Saldo Akhir D/K).
* **Mutasi Piutang**: Laporan mutasi piutang customer dengan rumus `Saldo Awal + Penambahan (Invoice Baru) - Pengurangan (Payment) = Saldo Akhir`.
* **Aging Report Piutang & Summary**: Laporan umur piutang berdasarkan **tanggal jatuh tempo** invoice.
* **Aging Dokumen & Summary**: Laporan untuk melacak umur fisik dokumen (seperti surat tanda terima, list kirim, surat tagihan) guna memantau efisiensi pengiriman dokumen hingga penagihan.

---

## 🔒 4. Aturan Bisnis & Validasi Utama

1. **Multi-Perusahaan**: Seluruh data transaksi, COA, customer/vendor, dan laporan keuangan diisolasi secara ketat per masing-masing perusahaan (tidak saling mencampuri).
2. **Aturan Keseimbangan Jurnal**: `SUM(journal_items.debit) === SUM(journal_items.credit)` pada setiap jurnal. Jika tidak seimbang, database transaksi di-rollback.
3. **Kunci Periode Buku**: Transaksi pada tanggal di luar periode aktif atau periode yang sudah dikunci tidak boleh diubah atau dihapus.
4. **Pemisahan Status Dokumen & Status Pembayaran**:
   * Dalam dokumen transaksi (seperti *Invoice* dan *Bill*), terdapat dua kolom status terpisah untuk menciptakan matriks data yang sangat jelas (*clean architecture*):
   * **Status Dokumen (`status`)**: Menandakan tahapan dokumen itu sendiri. Nilainya: `draft` (baru dibuat/belum diproses), `posted` (sudah tervalidasi dan dijurnal ke Buku Besar), dan `void` (dibatalkan).
   * **Status Pembayaran (`payment_status`)**: Menandakan status pelunasan. Nilainya: `unpaid` (belum dibayar), `partial` (dibayar sebagian), dan `paid` (lunas).
   * Dengan pemisahan ini, Anda bisa memiliki invoice yang berstatus `posted` dan `unpaid` secara bersamaan (sudah diakui sebagai piutang, tapi pelanggan belum membayar).
5. **Alur Posting, Unposting, dan Kunci Periode (Accounting Period Lock)**:
   * **Kunci Periode (`list_posted_periode`)**: Semua dokumen dilindungi oleh pengunci periode (berdasarkan bulan dan tahun). Jika manajer keuangan menetapkan suatu periode sebagai *closed* (Tutup Buku), maka semua dokumen dalam periode tersebut tidak bisa diedit, dihapus, atau di-unpost.
   * Setiap transaksi (Invoice, Bill, Jurnal Manual) berstatus **Draft** saat pertama kali dibuat.
   * Ketika di-**Posting**, sistem otomatis membuat Jurnal Entry ke Buku Besar, mengubah `isPosted` menjadi `true`, dan mengubah `invoice_status_code` menjadi `posted`.
   * **Revisi & Unposting Tingkat Dokumen (Document-level Unposting)**: 
     * Jika terjadi kesalahan pada dokumen yang sudah diposting, staf tidak bisa langsung mengubah nominal.
     * Manajer Keuangan harus **membuka akses periode** di tabel `list_posted_periode` (jika sedang ditutup). Proses buka periode ini **tidak menghapus jurnal masal apapun**.
     * Setelah periode terbuka, staf melakukan **Unposting khusus pada dokumen yang salah tersebut**. Sistem akan mengubah `isPosted` menjadi `false`, status kembali menjadi **Draft**, dan **hanya jurnal milik dokumen tersebut yang dihapus**.
     * Setelah berstatus **Draft**, dokumen bisa direvisi atau dihapus sepenuhnya.
     * Setelah revisi selesai, dokumen wajib di-**Posting** kembali untuk menghitung ulang dan membentuk jurnal baru.
   * **Cegat Hapus (Intercept Delete) & Revisi Cpanel**: Saat klien membatalkan/menghapus tagihan lewat aplikasi *cpanel* via API, **Sistem Akunting akan MENOLAK Hard Delete**. Sebagai gantinya, sistem akan mengubah status dokumen tersebut menjadi `void`. Saat Cpanel mengirimkan data perbaikan, sistem Akunting mendeteksi kecocokan `base_invoice_number` yang sama lalu menyimpannya sebagai *draft* baru dengan menambahkan *Revision Tag* di belakangnya (`INV-001.R1`, `INV-001.R2`, dst).
   * **Manajemen UI (Hidden Void)**: Agar UI tabel utama tetap bersih (*clean*), *Frontend* harus secara otomatis mem-filter tabel dengan aturan `WHERE status != 'void'`. Namun saat *User* membuka detail tagihan (misal: `INV-001.R2`), *Frontend* dapat menampilkan seluruh riwayat perjalanan dokumen ini (menarik data `INV-001` asli dan `R1` yang berstatus *void* dari tabel yang sama).
6. **Integrasi API Eksternal & Penjurnalan**:
   * Data transaksi (seperti Invoice) yang masuk dari aplikasi eksternal (misal: *cpanel*) via API, maupun yang dibuat manual oleh user, wajib tersimpan dengan status awal **Draft**.
   * **Sistem dilarang keras membuat entri Jurnal** pada saat dokumen masih berstatus Draft. Jurnal Buku Besar hanya diciptakan secara otomatis ketika staf akunting sudah melakukan *review* dan menekan tombol **Posting**. Aturan ini dibuat untuk menjaga kebersihan tabel jurnal dari data transaksi yang batal atau mengandung *error* input.

---

## 🛡️ 5. Sistem Logging & Keamanan (Audit Trail)

Mengingat integritas data sangat krusial dalam akuntansi, sistem diwajibkan memiliki dua jenis pelacakan:
1. **Audit Trail Transaksi**: Menggunakan **Spatie Laravel Activitylog** untuk mencatat jejak rekam setiap perubahan data keuangan (Invoice, Bill, Jurnal, Mutasi). Log ini mencakup:
   * Siapa user yang melakukan tindakan (`causer_id`).
   * Tindakan yang dilakukan (misal: "Created", "Updated", "Posted", "Unposted", "Deleted").
   * Kapan tindakan dilakukan (`created_at`).
   * Detail perubahan data (Nilai sebelum / *old values* dan nilai sesudah / *new values*, serta rekam jejak utuh jika terjadi *Hard Delete*).
2. **Log Sistem / Error Log**: Menggunakan sistem log bawaan Laravel (Monolog) dengan konfigurasi log harian (`daily`) untuk mendeteksi *error bug* atau masalah performa server.

---

## 📅 6. Rencana Pengembangan (Roadmap)
- **Fase 1: Setup & Core Ledger** (Inisialisasi database multi-company, COA, Jurnal Manual, Buku Besar per Akun). -> *[Sedang Berjalan]*
- **Fase 2: Invoices & Payments** (Manajemen Invoices, Kwitansi manual, Penerimaan Pembayaran, serta pelacakan Aging Dokumen).
- **Fase 3: Expenses & Bills** (Pembelian operasional, Vendor Bills, dan Bill Payments).
- **Fase 4: Rekonsiliasi Bank** (Fitur upload Excel rekening koran, auto-matching, dan manual approval).
- **Fase 5: Laporan Keuangan Lengkap** (Neraca 8-Kolom, Laba Rugi, Mutasi Piutang, Aging Report Piutang).

🧠 Brainstorm: Audit & Grand Plan VCY Accounting
📋 Context
Aplikasi ini sudah memiliki pondasi yang kuat (modul Incomes matang, dan modul Expenses seperti Bills & BillsPayments sudah berfungsi baik). Namun, kita perlu menyusun skala prioritas pengembangan selanjutnya yang seimbang antara menambah fitur, merapikan kode (technical debt), dan menjaga keandalan sistem berskala besar.

Berikut adalah 4 opsi arah fokus pengembangan yang bisa kita ambil saat ini:

Option A: Fokus pada Keamanan & Audit Trail (Prioritas Sistem)
Meningkatkan pertahanan aplikasi dari manipulasi internal dan mencatat setiap perubahan data keuangan yang sensitif.

✅ Pros:

Audit Logging: Menggunakan (misal: spatie/laravel-activitylog) untuk merekam "Siapa, melakukan apa, kapan, dan mengubah apa" (data before-after). Jika tagihan berubah, kita punya buktinya.
Pessimistic Locking: Memastikan pencegahan race-condition saat men-generate nomor dokumen atau menyimpan jurnal secara bersamaan.
RBAC (Role-Based Access): Mencegah user biasa membatalkan transaksi yang sudah divalidasi/dilunasi.
❌ Cons:

Fokus murni di backend, tidak ada perubahan visual atau fitur bisnis baru bagi user.
📊 Effort: Medium

Option B: Fokus pada Performa & Beban Server (Scalability)
Mengoptimalkan aplikasi agar tidak lambat atau crash saat data mencapai ratusan ribu baris, terutama karena kita menggunakan database legacy.

✅ Pros:

Resolusi N+1 Query: Mencegah server mati kehabisan memori dengan memastikan implementasi Eager Loading (with()) di semua Controller tabel.
Database Indexing: Mempercepat pencarian data dengan menambahkan index pada kolom krusial (nomor invoice, tanggal, status).
Optimasi Report: Menggunakan chunking atau paginasi berbasis cursor untuk penarikan laporan skala besar.
❌ Cons:

Membutuhkan pengetesan ekstensif (load testing) untuk memastikan perubahan query tidak merusak logika bisnis.
📊 Effort: Medium to High

Option C: Fokus Standarisasi UI/UX & Reusability (Technical Debt)
Menyelesaikan ekstraksi komponen UI yang berulang sesuai dengan panduan .agents/project-overview.md.

✅ Pros:

Mengekstrak DataTable.svelte dan EmptyState.svelte sebagai komponen utuh.
Menghilangkan ribuan baris kode yang duplikat di halaman Index.svelte pada modul Invoices, Payments, Bills, dll.
Membuat pembuatan halaman modul baru di masa depan menjadi sangat instan.
❌ Cons:

Memaksa kita memodifikasi ulang halaman yang saat ini "sudah jalan dan aman-aman saja" (seperti Bills dan Invoices).
📊 Effort: Medium

Option D: Fokus Penyelesaian Modul Bisnis (Fitur Baru)
Melanjutkan pengembangan fungsionalitas bisnis yang belum tersentuh atau belum selesai seratus persen.

✅ Pros:

Menyelesaikan modul Settings (Pengaturan Pajak, Chart of Accounts, User Management).
Menyelesaikan modul Accounting/Journals agar terintegrasi penuh dengan seluruh transaksi (Invoices & Bills otomatis masuk ke Jurnal).
Menjadikan aplikasi mencapai status Minimum Viable Product (MVP) lebih cepat.
❌ Cons:

Membangun fitur baru tanpa membereskan opsi A, B, atau C terlebih dahulu berarti kita menumpuk "utang" performa dan UI yang harus dibayar lebih mahal di akhir proyek.
📊 Effort: High