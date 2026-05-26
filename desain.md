# Panduan & Aturan Tampilan Frontend (desain.md)

Dokumen ini berisi standar desain antarmuka pengguna (UI/UX), komponen, dan pola interaksi frontend yang wajib diikuti oleh developer (manusia maupun AI Agent) saat memodifikasi atau membuat tampilan pada proyek VCY Accounting.

---

## 🎨 1. Standar Desain (Shadcn-Svelte & High-Density UI)

Karena aplikasi ini adalah sistem akuntansi, tampilan harus **padat informasi (high density)** dan **mudah difilter** tanpa mengorbankan estetika premium.

### Modul UI & Tema Visual (Design System)
- **Framework UI**: Menggunakan komponen **Shadcn-Svelte** (berbasis arsitektur *Radix Svelte* / *Bits UI*) yang dikombinasikan dengan kemudahan kustomisasi **Tailwind CSS v4**.
- **Logo Aplikasi**: Logo resmi disimpan di `/public/images/logo.png`. Pada header atau sidebar, logo harus dimuat dengan ukuran `w-8 h-8 rounded-lg object-contain bg-white p-0.5 shadow-md border border-teal-500/30` agar memiliki kontras tinggi dengan warna latar belakang gelap korporasi.
- **Sistem Ikon**: Wajib menggunakan **Lucide Icons** (`lucide-svelte`). Ikon ini memiliki gaya garis (*line art*) yang minimalis, tajam, dan sangat sesuai dengan standar estetika dashboard profesional.
- **Palet Warna Utama**:
  - **Primary (Utama)**: Warna dominan korporat (disarankan warna *Slate*, *Zinc*, atau *Navy Blue* yang elegan) untuk elemen aksi utama seperti tombol *Save/Submit* dan *Sidebar aktif*.
  - **Background**: Mengutamakan warna latar belakang yang sejuk dan netral (misal: bg-slate-50) pada mode terang. Sistem juga wajib dirancang untuk mendukung **Mode Gelap (Dark Mode)** secara natif menggunakan variabel Tailwind CSS.
  - **Semantic Colors (Warna Status)**: 
    - 🟩 **Success** (Hijau): Digunakan untuk status transaksi *"Paid"*, *"Posted"*, atau indikator nilai saldo Laba Positif.
    - 🟥 **Destructive/Error** (Merah): Digunakan untuk tombol *Delete*, status *"Overdue"*, *"Unposted"*, notifikasi error, atau indikator nilai Rugi/Minus.
    - 🟨 **Warning** (Oranye/Kuning): Digunakan untuk status *"Draft"*, *"Partial"*, atau tindakan peringatan sementara.

### Aturan Tipografi & Spacing
- **Font**: Gunakan font *Inter* atau *Geist Sans* (sans-serif modern).
- **Text Size**: 
  - Gunakan `text-xs` (12px) untuk data tabel, label filter, metadata, dan teks sekunder.
  - Gunakan `text-sm` (14px) untuk teks utama, nilai input, dan deskripsi sedang.
  - Gunakan `text-base` / `text-lg` hanya untuk judul section.
- **Padding Tabel**:
  - Sel tabel (`td` dan `th`) wajib menggunakan padding vertikal yang kecil: `py-1.5` atau maksimal `py-2`.
  - Hindari padding besar (`py-4`) pada baris data agar baris yang terlihat di layar lebih banyak.

### Aturan Komponen UI (Shadcn-Svelte)
- Gunakan komposisi komponen Shadcn (`Card`, `Table`, `Button`, `Input`, `Select`, `DropdownMenu`) yang konsisten.
- Gunakan transisi halus (`flyAndScale` dari `resources/js/utils.js`) untuk dropdown menu, modal dialog, atau popover untuk mempertahankan sentuhan premium.

---

## 🔍 2. Pola Interaksi & Filter Frontend

Untuk memudahkan navigasi data akuntansi dalam jumlah besar, ikuti pola interaksi berikut:

### Pencarian & Filter Data
- **Collapsible Filter**: Form filter yang memiliki lebih dari 3 parameter wajib diletakkan di dalam kartu yang dapat dilipat (*collapsible card*).
- **Filter Active Badges**: Jika ada filter yang aktif, tampilkan indikator badge kecil di dekat tombol toggle filter agar user menyadari data sedang disaring.
- **Debouncing Search**: Terapkan penundaan (delay) pencarian (~300ms) saat user mengetik di kotak pencarian untuk mencegah pengiriman request berlebih ke server.
- **URL State Sync**: Setiap kali filter atau pencarian diubah di frontend, sinkronkan nilai tersebut ke parameter URL browser (menggunakan router Inertia `preserveState: true` dan `replace: true`) agar halaman dapat di-refresh tanpa kehilangan filter aktif.
- **Pagination**: Tampilkan navigasi halaman yang ringkas di bagian bawah tabel data dengan indikator jumlah data (misal: "Showing 1 to 10 of 120 entries").

