<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('invoice_payments');
    }

    public function down(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->date('paid_at');
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
