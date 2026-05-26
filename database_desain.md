# Rancangan Database (database_desain.md)

Dokumen ini berisi spesifikasi tabel database PostgreSQL untuk proyek VCY Accounting. Struktur ini dirancang untuk mendukung sistem akuntansi multi-perusahaan, pembukuan berpasangan (*double-entry*), audit trail, pelacakan umur piutang (*aging*), pelacakan fisik dokumen (*aging dokumen*), dan rekonsiliasi bank.

---

## 🏗️ 1. Entitas & Hubungan (ERD Summary)
Setiap transaksi keuangan, akun (COA), dan kontak (pelanggan/supplier) wajib terikat dengan entitas perusahaan tertentu (`company_id`).

* **Catatan Relasi Database (Foreign Keys)**: Pemetaan relasi antar tabel secara eksplisit (misalnya aturan `onDelete('cascade')` atau referensi indeks Constraint silang) akan disesuaikan lebih lanjut saat pembuatan skrip migrasi database agar integritas data akuntansi tetap terjaga.
* **Satu Akun Kas/Bank** didebit/kredit melalui entri jurnal.
* **Satu Invoice/Bill** dapat dicicil (banyak pelunasan parsial) melalui tabel perantara pelunasan (*items*).
* **Riwayat Perubahan Data** dicatat otomatis oleh tabel `activity_log` (Spatie Activitylog).

---

## 🗄️ 2. Skema Tabel Utama

### A. Tabel Utama Entitas & Master Data

#### 1. `companies`
Menampung entitas bisnis yang dicatat (Mendukung pencatatan 2 perusahaan secara terpisah).
* `id` (BigIncrements, PK)
* `name` (string) - Contoh: "PT. Logistik Maju Jaya"
* `code` (string, unique) - Contoh: "LMJ"
* `is_active` (boolean, default: true)
* `timestamps`

#### 2. `users`
Pengguna sistem.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`, nullable) -> *Null jika Superadmin, terisi jika staf khusus perusahaan tertentu*
* `name` (string)
* `email` (string, unique)
* `password` (string)
* `role` (enum: `'admin'`, `'accounting'`, `'invoicing'`)
* `timestamps`

#### 3. `contacts`
Pihak ketiga (Pelanggan / Customer & Supplier / Vendor).
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `type` (enum: `'customer'`, `'vendor'`, `'both'`)
* `name` (string)
* `phone` (string, nullable)
* `email` (string, nullable)
* `address` (text, nullable)
* `is_active` (boolean, default: true)
* `timestamps`

#### 4. `accounts` (Chart of Accounts / COA)
Daftar rekening akuntansi.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `code` (string) -> *Contoh: '11101' (Kas Besar). Unik per company_id*
* `name` (string) -> *Contoh: 'Kas Besar'*
* `type` (enum: `'Asset'`, `'Liability'`, `'Equity'`, `'Revenue'`, `'Expense'`)
* `parent_id` (foreignId ke self `accounts`, nullable)
* `is_active` (boolean, default: true)
* `timestamps`

---

### B. Tabel Buku Besar (General Ledger & Double-Entry)

#### 5. `journal_entries`
Header transaksi jurnal umum maupun jurnal otomatis dari modul lain.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `reference_number` (string) -> *Nomor bukti jurnal, unik per company_id (Contoh: JV-202605001)*
* `date` (date) -> *Tanggal transaksi*
* `description` (text, nullable) -> *Keterangan transaksi*
* `status` (enum: `'draft'`, `'posted'`) -> *Mendukung posting/unposting*
* `created_by` (foreignId ke `users`)
* `timestamps`

#### 6. `journal_items`
Detail baris debit dan kredit jurnal (Wajib seimbang per `journal_entry_id`).
* `id` (BigIncrements, PK)
* `journal_entry_id` (foreignId ke `journal_entries` dengan `onDelete('cascade')`)
* `account_id` (foreignId ke `accounts`)
* `debit` (decimal, 15,2, default: 0.00)
* `credit` (decimal, 15,2, default: 0.00)
* `timestamps`

---

### C. Modul Pendapatan & Piutang (Incomes & Accounts Receivable)

#### 7. `invoices`
Tagihan penjualan ke customer.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `invoice_number` (string) -> *Nomor invoice, unik per company_id (Bisa mengandung tag .R1, .R2)*
* `base_invoice_number` (string, nullable) -> *Nomor invoice asli yang dikirim cpanel. Digunakan untuk mengelompokkan riwayat revisi yang di-void ke dalam satu kesatuan UI.*
* `revision_number` (integer, default: 0) -> *Menyimpan urutan revisi (0 = asli, 1 = R1, 2 = R2)*
* `contact_id` (foreignId ke `contacts`) -> *Hanya untuk contact bertipe 'customer' atau 'both'*
* `invoice_date` (date)
* `due_date` (date) -> *Jatuh tempo untuk perhitungan AR Aging*
* `status` (enum: `'draft'`, `'posted'`, `'void'`) -> *Status dokumen & pembukuan*
* `payment_status` (enum: `'unpaid'`, `'partial'`, `'paid'`) -> *Status pelunasan piutang*
* `subtotal` (decimal, 15,2)
* `tax_amount` (decimal, 15,2, default: 0.00)
* `total_amount` (decimal, 15,2) -> *Total tagihan bersih*
* `journal_entry_id` (foreignId ke `journal_entries`, nullable) -> *Terisi jika sudah di-posting*
* `timestamps`

#### 8. `invoice_items`
Rincian item jasa/freight di dalam invoice.
* `id` (BigIncrements, PK)
* `invoice_id` (foreignId ke `invoices` dengan `onDelete('cascade')`)
* `description` (text) -> *Contoh: "Freight Jakarta - Surabaya Container 40ft"*
* `amount` (decimal, 15,2)
* `timestamps`

#### 9. `payments`
Pencatatan uang masuk pelunasan piutang customer.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `payment_number` (string) -> *Nomor bukti penerimaan kas/bank, unik per company_id*
* `contact_id` (foreignId ke `contacts`)
* `payment_date` (date)
* `account_id` (foreignId ke `accounts`) -> *Akun Kas/Bank penampung uang masuk*
* `amount` (decimal, 15,2) -> *Jumlah uang yang diterima fisik*
* `reference` (string, nullable) -> *Nomor transfer bank, info tambahan, dsb*
* `journal_entry_id` (foreignId ke `journal_entries`, nullable) -> *Terisi jika sudah di-posting*
* `timestamps`

#### 10. `payment_items`
Pencocokan nominal bayar ke invoice (Mendukung pelunasan parsial/cicil).
* `id` (BigIncrements, PK)
* `payment_id` (foreignId ke `payments` dengan `onDelete('cascade')`)
* `invoice_id` (foreignId ke `invoices`)
* `amount_applied` (decimal, 15,2) -> *Nominal yang dialokasikan ke invoice terkait*
* `timestamps`

#### 11. `kwitansis`
Pencatatan kwitansi manual dengan nomor seri tersendiri yang diterbitkan staf keuangan.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `kwitansi_number` (string) -> *Nomor seri kwitansi manual, unik per company_id*
* `payment_id` (foreignId ke `payments`, nullable) -> *Terkoneksi ke transaksi pembayaran*
* `date` (date)
* `received_from` (string) -> *Nama penyetor*
* `amount` (decimal, 15,2)
* `description` (text) -> *Keterangan kwitansi*
* `created_by` (foreignId ke `users`)
* `timestamps`

---

### D. Modul Pengeluaran & Hutang (Expenses & Accounts Payable)

#### 12. `bills`
Tagihan dari vendor/supplier.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `bill_number` (string) -> *Nomor tagihan vendor, unik per company_id*
* `contact_id` (foreignId ke `contacts`) -> *Hanya untuk contact bertipe 'vendor' atau 'both'*
* `bill_date` (date)
* `due_date` (date)
* `status` (enum: `'draft'`, `'posted'`, `'void'`) -> *Status dokumen & pembukuan*
* `payment_status` (enum: `'unpaid'`, `'partial'`, `'paid'`) -> *Status pelunasan hutang*
* `total_amount` (decimal, 15,2)
* `journal_entry_id` (foreignId ke `journal_entries`, nullable) -> *Terisi jika sudah di-posting*
* `timestamps`

#### 13. `bill_items`
Rincian biaya pengeluaran di dalam tagihan vendor.
* `id` (BigIncrements, PK)
* `bill_id` (foreignId ke `bills` dengan `onDelete('cascade')`)
* `account_id` (foreignId ke `accounts`) -> *Akun beban / biaya (contoh: Beban Listrik/Beban Kapal)*
* `description` (text)
* `amount` (decimal, 15,2)
* `timestamps`

#### 14. `bill_payments`
Pencatatan pelunasan hutang ke vendor.
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `bill_payment_number` (string) -> *Nomor bukti pengeluaran kas/bank, unik per company_id*
* `contact_id` (foreignId ke `contacts`)
* `payment_date` (date)
* `account_id` (foreignId ke `accounts`) -> *Akun Kas/Bank asal uang keluar*
* `amount` (decimal, 15,2)
* `reference` (string, nullable)
* `journal_entry_id` (foreignId ke `journal_entries`, nullable) -> *Terisi jika sudah di-posting*
* `timestamps`

#### 15. `bill_payment_items`
Pencocokan nominal bayar ke tagihan vendor (Mendukung pelunasan parsial).
* `id` (BigIncrements, PK)
* `bill_payment_id` (foreignId ke `bill_payments` dengan `onDelete('cascade')`)
* `bill_id` (foreignId ke `bills`)
* `amount_applied` (decimal, 15,2)
* `timestamps`

---

### E. Modul Pelacakan Dokumen Fisik & Audit Trail

#### 16. `document_trackings`
Melacak umur fisik dokumen (untuk menu "Aging Dokumen").
* `id` (BigIncrements, PK)
* `company_id` (foreignId ke `companies`)
* `invoice_id` (foreignId ke `invoices`, nullable) -> *Dihubungkan ke invoice terkait*
* `document_type` (enum: `'surat_tanda_terima'`, `'list_kirim'`, `'surat_tagihan'`, `'tukar_faktur'`)
* `document_number` (string) -> *Nomor fisik dokumen*
* `created_date` (date) -> *Tanggal pembuatan dokumen*
* `sent_date` (date, nullable) -> *Tanggal kirim fisik*
* `received_date` (date, nullable) -> *Tanggal tanda terima / diterima oleh customer*
* `status` (enum: `'created'`, `'sent'`, `'received'`)
* `timestamps`

#### 17. `activity_log` (Spatie Activitylog Schema)
Tabel pelacak riwayat perubahan data (Audit Trail).
* `id` (BigIncrements, PK)
* `log_name` (string, nullable)
* `description` (text)
* `subject_type` (string, nullable)
* `subject_id` (bigint, nullable)
* `causer_type` (string, nullable)
* `causer_id` (bigint, nullable) -> *User yang melakukan tindakan*
* `properties` (json, nullable) -> *Menyimpan old & new values*
* `created_at` (timestamp, nullable)
