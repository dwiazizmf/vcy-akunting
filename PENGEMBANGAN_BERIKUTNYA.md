# 🚀 Rencana Pengembangan Berikutnya: Modernisasi Audit & Activity Log (ELK Stack)

Document ini mencatat hasil diskusi dan arsitektur rencana pengalihan sistem **Activity Log (Audit Trail)** dari Database PostgreSQL ke **File-based Log (NDJSON) + Elasticsearch**.

---

## 📌 Context & Masalah Saat Ini

Aplikasi VCY Accounting menggunakan `spatie/laravel-activitylog` untuk mencatat setiap perubahan data sensitif (`CREATE`, `UPDATE`, `DELETE`), termasuk menangkap nilai data sebelum (*old values*) dan sesudah (*attributes*).

**Tantangan:**
Seiring bertambahnya transaksi harian, penyimpanan log ke tabel PostgreSQL (`activity_log`) akan membengkak sangat cepat, membebani penyimpanan database relasional, serta memperlambat performa query aplikasi.

---

## 🏗️ Arsitektur Solusi: Spatie + NDJSON File + ELK Stack

Menjaga kemudahan pengodingan menggunakan paket Spatie di Laravel (`use LogsActivity;`), namun **mengalihkan media penyimpanannya dari Database ke File Log (JSON)** yang secara otomatis di-ingest oleh **Elasticsearch**.

```
[User Action] 
     │
     ▼
[Model / Trait LogsActivity]
     │
     ▼
[Custom Activity Model (FileActivity)] ──(Override save())──► [Monolog JSON Channel]
                                                                      │
                                                                      ▼
                                                          [storage/logs/activity.log]
                                                                      │
                                                                      ▼
                                                                  [Filebeat]
                                                                      │
                                                                      ▼
                                                             [Elasticsearch & Kibana]
```

---

## 🛠️ Langkah-Langkah Implemenasi (Technical Specs)

### 1. Buat Custom Activity Model
Buat `app/Models/FileActivity.php` untuk meng-override method `save()` Eloquent agar tidak melakukan query `INSERT INTO`:

```php
namespace App\Models;

use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class FileActivity extends Activity
{
    /**
     * Override fungsi save bawaan Eloquent/Spatie
     */
    public function save(array $options = [])
    {
        $logData = [
            'timestamp'    => now()->toIso8601String(),
            'log_name'     => $this->log_name,
            'description'  => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id'   => $this->subject_id,
            'causer_type'  => $this->causer_type,
            'causer_id'    => $this->causer_id,
            'properties'   => $this->properties, // Berisi 'attributes' (baru) & 'old' (lama)
            'event'        => $this->event,
        ];

        // Tulis ke channel log khusus file JSON
        Log::channel('activity_file')->info('activity_logged', $logData);

        // Return true agar Spatie menganggap proses save sukses tanpa insert ke DB
        return true;
    }
}
```

### 2. Tambah Channel Log di `config/logging.php`
Konfigurasikan channel `activity_file` agar menghasilkan format **NDJSON (Newline Delimited JSON)**:

```php
'activity_file' => [
    'driver' => 'daily',
    'path' => storage_path('logs/activity.log'),
    'level' => 'info',
    'days' => 14,
    'formatter' => Monolog\Formatter\JsonFormatter::class,
],
```

### 3. Hubungkan ke Configuration Spatie (`config/activitylog.php`)
Arahkan model Spatie ke `FileActivity` kustom kita:

```php
'activity_model' => App\Models\FileActivity::class,
```

---

## 🔥 Keuntungan Pendekatan Ini

1. **Zero Database Overhead:** Database PostgreSQL tetap bersih dan ringan, hanya fokus melayani transaksi keuangan utama.
2. **Standard Industry Best Practice:** Format NDJSON langsung kompatibel dengan Filebeat, Logstash, Vector, maupun Fluentd.
3. **Analisis Berdaya Tinggi (Kibana):** Log dapat di-filter, di-search, dan dibuatkan *dashboard visual* dengan sangat cepat di Kibana tanpa membebani server aplikasi utama.
4. **Zero Code Refactoring:** Kode di Controller & Model yang sudah memakai `LogsActivity` tetap berfungsi 100% tanpa ada perubahan.
