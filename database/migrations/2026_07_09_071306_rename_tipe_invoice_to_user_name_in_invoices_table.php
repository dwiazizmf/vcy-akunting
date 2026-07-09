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
            $table->renameColumn('tipe_invoice', 'user_name');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('user_name', 191)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('user_name', 12)->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('user_name', 'tipe_invoice');
        });
    }
};
