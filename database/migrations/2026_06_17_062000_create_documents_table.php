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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type'); // e.g., 'tanda_terima', using string as requested
            $table->string('document_number')->nullable();
            $table->dateTime('send_date')->nullable();
            
            // Kolom baru sesuai request
            $table->string('up_person')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('no_tlp')->nullable();
            $table->text('address')->nullable();
            
            // Kolom dari tangkapan layar
            $table->string('order_number')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->integer('orders')->nullable();
            $table->text('orders_text')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
