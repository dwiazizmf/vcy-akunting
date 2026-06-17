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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('expense_number', 191);
            $table->string('base_expense_number', 191)->nullable();
            
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->string('vendor_name', 191)->nullable();
            
            $table->string('expense_status_code', 191)->default('draft');
            $table->string('payment_status', 50)->default('unpaid');
            
            $table->dateTime('expense_date');
            $table->dateTime('due_date')->nullable();
            
            $table->double('subtotal', 15, 4)->default(0);
            $table->double('total_item_tax', 15, 4)->default(0);
            $table->double('grand_total', 15, 4)->default(0);
            $table->jsonb('header_tax_details')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->boolean('is_direct_expense')->default(false);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts');
            $table->foreignId('journal_id')->nullable()->constrained('journals');
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
