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
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'tax')) {
                $table->dropColumn('tax');
            }

            if (!Schema::hasColumn('invoice_items', 'discount_amount')) {
                $table->double('discount_amount', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('invoice_items', 'discount_details')) {
                $table->jsonb('discount_details')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_details']);
            $table->double('tax', 15, 4)->default(0);
        });
    }
};
