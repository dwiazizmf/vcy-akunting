# VCY Accounting — Project Rules untuk AI Agent

> File ini dibaca otomatis oleh AI agent di setiap sesi.  
> Semua rules di sini **WAJIB** diikuti tanpa pengecualian.

---

## 📋 Daftar Panduan (Wajib Baca)

Sebelum mulai bekerja, baca dan ikuti semua panduan berikut:

1. **[Desain & UI Rules](.gemini/desain.md)** — Standar tampilan, warna, komponen, dan estetika
2. **[Project Overview](.gemini/project-overview.md)** — Arsitektur, struktur folder, dan konvensi kode

---

## ⚡ Quick Rules (Ringkasan Wajib Ingat)

### Kode
- ✅ Selalu tulis kode yang **clean, rapi, dan mudah dibaca**
- ✅ Tambahkan komentar singkat untuk setiap blok logika penting
- ✅ Gunakan nama variabel dan fungsi yang **deskriptif** (bukan `x`, `tmp`, `data`)
- ✅ Pisahkan concern: UI logic di Svelte, business logic di PHP controller
- ❌ Jangan hardcode nilai yang seharusnya jadi config/prop
  - ❌ Jangan duplikasi logika — buat reusable jika dipakai lebih dari 1 halaman

### Database & Migrasi
- ❌ **JANGAN PERNAH** menjalankan `artisan migrate:refresh` atau `artisan migrate:fresh`. User menggunakan database lama yang belum disesuaikan penuh di migrasi, jalankan migrasi secara parsial/biasa saja (`artisan migrate`).

### Arsitektur UI (Contextual Actions)
- ✅ **Hindari Context Switching**: Jangan paksa user pindah halaman penuh untuk task kecil (misal: tambah entitas relasi atau lihat detail).
- ✅ **Gunakan Modal/Slide-over**: Tampilkan form create atau detail view dalam modal/slide-over dengan memanggil komponen Svelte secara dinamis.
- ✅ **Pisahkan Form & Detail**: Ekstrak form dan tampilan detail menjadi komponen Svelte mandiri agar bisa dipanggil dari dalam modal di berbagai halaman berbeda.

### Komponen Reusable (Prioritas Tinggi)
Fungsi/komponen berikut **HARUS dibuat sebagai komponen Svelte terpisah** jika sudah dipakai di ≥2 halaman:

| Komponen | Status | File Target |
|---|---|---|
| Pagination | 🔄 Inline (perlu diextract) | `Components/Pagination.svelte` |
| Column Toggle (Show/Hide) | 🔄 Inline (perlu diextract) | `Components/ColumnToggle.svelte` |
| DataTable wrapper | 🔄 Belum ada | `Components/DataTable.svelte` |
| Filter Panel | 🔄 Belum ada | `Components/FilterPanel.svelte` |

### Tampilan
- ✅ Gunakan color palette teal sebagai warna utama brand VCY
- ✅ Tampilan harus **menarik dan profesional** — bukan minimalis membosankan
- ✅ Gunakan badge, chip, dan ikon SVG untuk data yang berulang
- ✅ Semua tabel harus punya: pagination, sortable header, dan state kosong (empty state)
- ✅ Responsive: mobile-first, pastikan tampilan mobile tidak rusak

### Tools
- ✅ Gunakan `greppy` untuk mencari file sebelum editing
- ✅ Gunakan `sail npm run build` untuk verifikasi kompilasi setelah perubahan besar
- ✅ `sail npm run dev` sudah running — HMR aktif untuk development

---

## 🚨 Hal yang Harus Dicek Setiap Kali Ada Task Baru

1. Apakah logika ini sudah ada di halaman lain? → cari dulu dengan greppy
2. Apakah ini kandidat komponen reusable? → jika ya, buat terpisah
3. Apakah tampilan mobile sudah dicek? → jangan pakai `overflow-hidden` di container yang ada dropdown
4. Apakah sudah build untuk verifikasi tidak ada error?

1. Aturan Penanganan Pajak & Diskon (JSONB Hybrid)

✅ Manajemen Pajak & Diskon: Jangan pernah membuat tabel pivot/relasi untuk pajak (seperti invoice_taxes). Selalu gunakan kolom JSONB (tax_details) di tabel transaksi. Data master tarif pajak diambil dari tabel taxes, lalu dihitung secara reaktif di Svelte dan disimpan sebagai JSON saat submit.

2. Aturan Penomoran Dokumen (Race-Condition)

✅ Generate Nomor Jurnal/Invoice: Jangan menggunakan auto-increment ID untuk nomor dokumen cetak. Selalu gunakan helper service (DocumentNumberService) yang mengimplementasikan Pessimistic Locking (lockForUpdate()) untuk menghindari race condition dan reset nomor bulanan/tahunan.

3. Aturan Komunikasi Data (Inertia vs AJAX)

✅ Form vs Data Fetching: Gunakan useForm bawaan @inertiajs/svelte untuk semua operasi CRUD utama agar error validation dari Laravel otomatis ter- handle. Gunakan AJAX (axios/fetch) HANYA untuk mengambil data parsial di latar belakang (misal: dependent dropdown).