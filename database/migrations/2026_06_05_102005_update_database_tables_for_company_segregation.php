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
        // 1. Rename table invoice_settings to faktur_periode, and add company_id
        Schema::rename('invoice_settings', 'faktur_periode');

        Schema::table('faktur_periode', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->cascadeOnDelete();
        });

        // 2. Add company_id in list_posted_periode
        Schema::table('list_posted_periode', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->cascadeOnDelete();
        });

        // 3. Add company_id in payment_invoices
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->cascadeOnDelete();
        });

        // 4. Add company_id in taxes
        Schema::table('taxes', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->nullable()->constrained('companies')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove company_id from taxes
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        // Remove company_id from payment_invoices
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        // Remove company_id from list_posted_periode
        Schema::table('list_posted_periode', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        // Remove company_id from faktur_periode and rename back
        Schema::table('faktur_periode', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::rename('faktur_periode', 'invoice_settings');
    }
};
