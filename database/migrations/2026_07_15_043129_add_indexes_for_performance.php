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
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_number');
            $table->index('expense_date');
            $table->index('expense_status_code');
            $table->index('vendor_name');
            $table->index('company_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->index('date');
            $table->index('status');
            $table->index('company_id');
            $table->index('reference');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_number');
            $table->index('company_id');
            $table->index('status');
            $table->index('paid_at');
        });

        Schema::table('expense_payments', function (Blueprint $table) {
            $table->index('payment_number');
            $table->index('company_id');
            $table->index('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['expense_number']);
            $table->dropIndex(['expense_date']);
            $table->dropIndex(['expense_status_code']);
            $table->dropIndex(['vendor_name']);
            $table->dropIndex(['company_id']);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['reference']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_number']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['paid_at']);
        });

        Schema::table('expense_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_number']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['vendor_id']);
        });
    }
};
