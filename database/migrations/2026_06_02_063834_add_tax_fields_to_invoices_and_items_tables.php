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
            $table->double('total_item_subtotal', 15, 4)->default(0)->after('subtotal');
            $table->double('total_item_tax', 15, 4)->default(0)->after('total_item_subtotal');
            $table->double('grand_total', 15, 4)->default(0)->after('total_item_tax');
            $table->jsonb('header_tax_details')->nullable()->after('grand_total');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->double('tax_amount', 15, 4)->default(0)->after('tax');
            $table->jsonb('tax_details')->nullable()->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'total_item_subtotal',
                'total_item_tax',
                'grand_total',
                'header_tax_details'
            ]);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'tax_amount',
                'tax_details'
            ]);
        });
    }
};
