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
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('invoice_status_code');
            $table->index('customer_name');
            $table->index('nama_kapal');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('type');
            $table->index('send_date');
            $table->index('customer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_status_code']);
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['nama_kapal']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['send_date']);
            $table->dropIndex(['customer_name']);
        });
    }
};
