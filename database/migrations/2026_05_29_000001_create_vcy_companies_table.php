<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom-kolom yang dibutuhkan ke tabel companies (vcy_companies)
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Ubah nama tabel jika diperlukan (tapi tetap dengan nama 'companies')
            // Model sudah set $table = 'vcy_companies', jadi kita buat tabel vcy_companies
            if (!Schema::hasColumn('companies', 'code')) {
                $table->string('code', 20)->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'address')) {
                $table->text('address')->nullable()->after('code');
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone', 30)->nullable()->after('address');
            }
            if (!Schema::hasColumn('companies', 'npwp')) {
                $table->string('npwp', 50)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path', 500)->nullable()->after('npwp');
            }
        });

        // Jika tabel vcy_companies belum ada (karena model pointnya ke sana), buat alias
        if (!Schema::hasTable('vcy_companies')) {
            Schema::create('vcy_companies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('code', 20)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('npwp', 50)->nullable();
                $table->string('logo_path', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vcy_companies');
    }
};
