<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_type', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Seed default values
        DB::table('invoice_type')->insert([
            ['name' => 'Asuransi', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Buruh', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Inap', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Karantina', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lain-lain', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Iss', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Stodem', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Timbang', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trucking', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Freight', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('invoice_type_id')->nullable()->constrained('invoice_type')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['invoice_type_id']);
            $table->dropColumn('invoice_type_id');
        });

        Schema::dropIfExists('invoice_type');
    }
};
