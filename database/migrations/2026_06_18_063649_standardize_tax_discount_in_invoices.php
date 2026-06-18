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
            // Drop old columns safely
            $dropColumns = [
                'isDiskon', 'jumlah_diskon', 'diskon_total',
                'isTax', 'jumlah_tax', 'ppn_total', 'amount',
                'total_item_subtotal', 'total_item_tax'
            ];
            
            foreach ($dropColumns as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Modify subtotal to double if it was bigInteger
            if (Schema::hasColumn('invoices', 'subtotal')) {
                // we'll leave it or change its type. Changing to double:
                $table->double('subtotal', 15, 4)->default(0)->change();
            } else {
                $table->double('subtotal', 15, 4)->default(0);
            }

            if (!Schema::hasColumn('invoices', 'grand_total')) {
                $table->double('grand_total', 15, 4)->default(0);
            }

            // Add new tax columns
            if (!Schema::hasColumn('invoices', 'is_tax')) {
                $table->boolean('is_tax')->default(false);
            }
            if (!Schema::hasColumn('invoices', 'tax_type')) {
                $table->string('tax_type', 50)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->double('tax_amount', 15, 4)->default(0);
            }

            // Add new discount columns
            if (!Schema::hasColumn('invoices', 'is_discount')) {
                $table->boolean('is_discount')->default(false);
            }
            if (!Schema::hasColumn('invoices', 'discount_type')) {
                $table->string('discount_type', 50)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'discount_amount')) {
                $table->double('discount_amount', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'header_discount_details')) {
                $table->jsonb('header_discount_details')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'is_tax', 'tax_type', 'tax_amount', 
                'is_discount', 'discount_type', 'discount_amount', 'header_discount_details'
            ]);
        });
    }
};
