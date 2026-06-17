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
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('expense_id')->constrained('expenses');
            $table->foreignId('account_id')->constrained('accounts'); // The expense COA
            
            $table->text('description');
            
            $table->double('amount', 15, 4); // Subtotal for this item before tax
            $table->double('tax_amount', 15, 4)->default(0); // Total tax amount
            $table->jsonb('tax_details')->nullable(); // JSON array of taxes applied
            
            $table->double('total', 15, 4); // amount + tax_amount
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
