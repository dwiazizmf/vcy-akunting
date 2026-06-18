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
        Schema::table('taxes', function (Blueprint $table) {
            $table->decimal('rate', 15, 2)->default(0.00)->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->decimal('rate', 15, 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->decimal('rate', 5, 2)->default(0.00)->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->decimal('rate', 5, 2)->default(0.00)->change();
        });
    }
};
