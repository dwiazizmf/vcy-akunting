<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom yang dibutuhkan ke tabel companies.
     * Kolom 'enabled' sudah ada sejak migration awal.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['code', 'address', 'phone', 'npwp', 'logo_path']);
        });
    }
};
