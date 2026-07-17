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
        Schema::create('coa_monthly_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->integer('period_year');
            $table->integer('period_month');
            $table->decimal('beginning_balance', 15, 2)->default(0);
            $table->decimal('debit_mutation', 15, 2)->default(0);
            $table->decimal('credit_mutation', 15, 2)->default(0);
            $table->decimal('ending_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'account_id', 'period_year', 'period_month'], 'coa_monthly_balance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coa_monthly_balances');
    }
};
