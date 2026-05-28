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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->text('name');
            $table->string('sku', 255)->nullable();
            $table->double('quantity', 15, 4);
            $table->string('satuan', 25)->nullable();
            $table->double('price', 15, 4);
            $table->double('tax', 15, 4)->default(0.0000);
            $table->double('total', 15, 4);
            $table->double('cubication', 15, 3)->nullable();
            $table->double('total_volume', 15, 3)->default(0.000);
            $table->double('tariff_per_volume', 15, 3)->nullable();
            $table->string('spe', 13)->nullable();
            $table->double('kuli', 15, 4)->nullable()->default(0.0000);
            $table->string('type_container', 13)->nullable();
            $table->text('dokumen_ttft')->nullable();
            $table->tinyInteger('is_tax')->nullable()->default(0);
            $table->string('vas', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
