# Arsitektur & Optimasi Database

> Dokumen ini berisi catatan keputusan arsitektur (Architecture Decision Record) terkait penanganan data dalam skala besar (ratusan ribu hingga jutaan baris) di aplikasi VCY Accounting.

---

## 🚀 Masalah yang Dihadapi

Pada tabel dengan ratusan ribu baris data (seperti tabel `vcy_invoices`), fitur standar dari framework seringkali memicu performa lambat (*bottleneck*):
1. **Pencarian Teks Bebas (`LIKE "%query%"`):** Memicu *Full Table Scan* (FTS) di MySQL karena index standar tidak bisa digunakan untuk mencari teks dengan pola wildcard di awal kata.
2. **Standard Pagination (`paginate()`):** Laravel otomatis menjalankan query `COUNT(*)` untuk menghitung total baris dan menentukan jumlah halaman (halaman 1, 2, 3, dst.). Query `COUNT(*)` dengan filter pencarian sangat memberatkan database raksasa.
3. **Penghitungan Statistik (*Dashboard Stats*):** Query agregasi seperti `Invoice::where('status', 'draft')->count()` dan `sum('amount')` yang dipanggil berulang kali di setiap *page load* akan membebani I/O database.

---

## 🛠️ Keputusan Arsitektur & Solusi

Mengingat **client tetap membutuhkan fitur navigasi halaman berangka (1, 2, 3, dst.)**, maka kita tidak bisa semata-mata menggunakan `simplePaginate()`. Berikut adalah strategi arsitektur yang disepakati untuk diaplikasikan saat melakukan optimasi:

### 1. Pencarian Teks & Pagination (Laravel Scout + Meilisearch)
**Status:** Rekomendasi Utama

*   **Pemisahan Beban:** Memindahkan tugas pencarian teks dari MySQL ke mesin pencari khusus berbasis RAM (*Search Engine*), seperti **Meilisearch** (atau Typesense).
*   **Alur Kerja:**
    1. Laravel Scout mensinkronisasikan perubahan data (Create, Update, Delete) ke Meilisearch secara otomatis (*event-driven*).
    2. Saat *user* mencari data via UI, aplikasi bertanya ke Meilisearch. Meilisearch mengembalikan informasi Total Hit (untuk *count* halaman) dan *Array of ID* secara instan (milidetik).
    3. Laravel tinggal mengambil sisa detail datanya ke MySQL dengan query `WHERE id IN (...)` yang sangat efisien lewat Primary Key Index.
*   **Keuntungan:** UI tetap bisa menampilkan *paging* berangka (1, 2, 3...) tanpa perlu menjalankan query `COUNT(*)` lambat di MySQL, dan pencarian mendukung toleransi *typo* (*fuzzy matching*).

### 2. Caching Statistik Global (Redis)
**Status:** Rekomendasi Utama

*   **Menghilangkan Agregasi Berulang:** Menghitung total nominal tagihan (`SUM`) atau jumlah *draft* (`COUNT`) adalah komputasi berat. Hasil komputasi ini harus disimpan sebagai *Cache* di dalam **Redis** (in-memory data store).
*   **Alur Kerja Invalidation:**
    1. Gunakan Laravel *Model Observers* atau sistem *Events/Listeners*.
    2. Saat ada invoice baru dibuat, diedit, atau dihapus, *Observer* berjalan di *background* untuk memperbarui nilai total di Redis (misalnya via `INCRBY`/`DECRBY` atau hitung ulang asinkron melalui *Queue*).
    3. Saat *user* meload halaman tabel, angka statistik diambil dari Redis secara instan (O(1)).

### 3. Opsi Alternatif: MySQL Full-Text Search
**Status:** Cadangan (Jika tidak memungkinkan tambah container Docker baru)

*   **Alur Kerja:** Membuat index `FULLTEXT` di tabel MySQL pada kolom-kolom string pencarian. Pencarian dilakukan melalui query khusus: `MATCH(kolom) AGAINST(? IN BOOLEAN MODE)`.
*   **Kekurangan Utama:** Lebih cepat dari `LIKE`, namun tetap memaksa MySQL menjalankan `COUNT(*)` jika aplikasi masih membutuhkan kalkulasi navigasi halaman (halaman 1, 2, 3, dst.), sehingga tetap bisa terasa lambat pada *dataset* ekstrem.

---

## 📝 Kesimpulan
Saat aplikasi memasuki fase optimasi data besar, infrastruktur standar harus ditingkatkan dengan memadukan **Laravel Scout (Meilisearch)** untuk pencarian dan **Redis** untuk statistik/agregasi *cache*.

---

## 🔐 Akses Login Lokal (Development)

> Kredensial berikut hanya untuk environment **lokal/development**. Jangan gunakan di production.

### Aplikasi Web
| Info | Detail |
|---|---|
| URL | `http://localhost` |
| Email | `admin@vcy.test` |
| Password | `password123` |
| Role | `super-admin` (akses penuh semua fitur) |

### Database PostgreSQL (Sail)
| Info | Detail |
|---|---|
| Host | `localhost` (dari luar container) / `pgsql` (dari dalam container) |
| Port | `5432` |
| Database | `vcy-akunting` |
| Username | `sail` |
| Password | `password` |

### Roles yang Tersedia
| Role | Keterangan |
|---|---|
| `super-admin` | Semua permission (18 permission) |
| `admin` | Semua fitur kecuali kelola roles |
| `staff` | View + create/edit invoice & customer, view report |

> ⚠️ **Ganti password** `password123` segera setelah login pertama di production!

> Tambah prefix no invoice di companies. contoh jika dia MBK, bisa di tambahkan /MBK di invoice text jika tidak ada prefix jangan di tambahkan