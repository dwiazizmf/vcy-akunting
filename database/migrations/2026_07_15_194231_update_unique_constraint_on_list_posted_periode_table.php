<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('list_posted_periode', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('list_posted_periode_bulan_tahun_unique');
            // Add new unique constraint including company_id
            $table->unique(['company_id', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_posted_periode', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique(['company_id', 'bulan', 'tahun']);
            // Add back the old unique constraint
            $table->unique(['bulan', 'tahun']);
        });
    }
};
