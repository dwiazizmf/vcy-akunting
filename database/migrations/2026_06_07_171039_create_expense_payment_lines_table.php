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
        Schema::create('expense_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_payment_id')->constrained('expense_payments')->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained('expenses')->restrictOnDelete();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_payment_lines');
    }
};
