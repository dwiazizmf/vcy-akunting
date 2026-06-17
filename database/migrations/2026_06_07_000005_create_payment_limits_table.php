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
        Schema::create('payment_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('payment_category_id')->constrained('payment_categories')->cascadeOnDelete();
            $table->decimal('limit_amount', 20, 2)->default(0);
            $table->timestamps();

            // Prevent duplicate limits for the same account and category in the same company
            $table->unique(['company_id', 'account_id', 'payment_category_id'], 'unique_payment_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_limits');
    }
};
