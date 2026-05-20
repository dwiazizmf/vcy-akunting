# Panduan & Aturan Desain Proyek (desain.md)

Dokumen ini berisi standar desain, aturan validasi, dan pola koding yang wajib dibaca dan diikuti oleh developer (manusia maupun AI Agent) sebelum melakukan modifikasi atau pembuatan kode pada proyek VCY Accounting.

---

## 🎨 1. Standar Desain (Shadcn-Svelte & High-Density UI)

Karena aplikasi ini adalah sistem akuntansi, tampilan harus **padat informasi (high density)** dan **mudah difilter** tanpa mengorbankan estetika premium.

### Aturan Tipografi & Spacing
- **Font**: Gunakan font *Inter* atau *Geist Sans* (sans-serif modern).
- **Text Size**: 
  - Gunakan `text-xs` (12px) untuk data tabel, label filter, metadata, dan teks sekunder.
  - Gunakan `text-sm` (14px) untuk teks utama, nilai input, dan deskripsi sedang.
  - Gunakan `text-base` / `text-lg` hanya untuk judul section.
- **Padding Tabel**:
  - Sel tabel (`td` dan `th`) wajib menggunakan padding vertikal yang kecil: `py-1.5` atau maksimal `py-2`.
  - Hindari padding besar (`py-4`) pada baris data agar baris yang terlihat di layar lebih banyak.

### Aturan Filter & Pencarian
- **Collapsible Filter**: Form filter yang memiliki lebih dari 3 parameter wajib diletakkan di dalam kartu yang dapat dilipat (*collapsible card*).
- **Filter Active Badges**: Jika ada filter yang aktif, tampilkan indikator badge kecil di dekat tombol toggle filter agar user menyadari data sedang disaring.
- **URL State Sync**: Setiap kali filter diubah, sinkronkan nilai filter tersebut ke parameter URL (menggunakan router Inertia `preserveState: true` dan `replace: true`) agar halaman bisa di-bookmark atau di-refresh tanpa kehilangan filter.

---

## 🔒 2. Aturan Validasi Akuntansi & Eksekusi

Aturan transaksi ini wajib diperiksa secara ketat sebelum query database dieksekusi:

### Validasi Jurnal Berpasangan (Double-Entry)
Setiap kali ada pembuatan jurnal transaksi (*Journal Entry*):
1. **Balance Check**: Jumlah total debit **harus sama dengan** jumlah total kredit (`Total Debit === Total Credit`). Jika tidak seimbang, batalkan eksekusi dan kirim pesan error.
2. **Anti-Negative Check**: Saldo akun kas/bank tertentu tidak boleh bernilai negatif setelah transaksi (opsional, tergantung kebijakan akuntansi).
3. **Locking Periode**: Transaksi pada tanggal di luar periode buku yang sedang aktif (atau periode yang sudah ditutup/dikunci) tidak boleh dibuat atau diubah.

---

## 🤖 3. Daftar Skill & Pola Teknis yang Digunakan

Berikut adalah "skills" (pola teknis/arsitektur) yang diterapkan dalam proyek ini:

1. **State URL Syncing (Inertia Router)**:
   Mengirimkan filter langsung via query string agar state halaman tetap sinkron dengan URL browser.
2. **Debouncing Search**:
   Menunda pengiriman request pencarian selama ~300ms saat user sedang mengetik di input pencarian untuk menghemat beban server.
3. **Svelte Client-side Pagination**:
   Menghitung pembagian halaman secara efisien di client jika data sudah di-load secara utuh, atau menggunakan server-side pagination terpadu untuk dataset besar.
4. **Shadcn Component Composition**:
   Membangun UI yang konsisten menggunakan kombinasi komponen `Card`, `Table`, `Button`, `Input`, dan `DropdownMenu` untuk menghindari redudansi CSS.
