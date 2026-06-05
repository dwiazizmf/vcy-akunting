<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('payment_number')->unique();           // "RCP-2024-001"
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('customer_name');                      // snapshot
            $table->date('paid_at');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->enum('payment_method', ['cash', 'transfer', 'giro', 'cheque'])->default('transfer');
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->string('reference')->nullable();              // no. transfer / no. cek
            $table->text('notes')->nullable();
            $table->decimal('overpayment_amount', 20, 2)->default(0);
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
