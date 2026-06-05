<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');                          // "BCA Operasional"
            $table->enum('type', ['bank', 'cash'])->default('bank');
            $table->string('bank_name')->nullable();         // "BCA", "Mandiri"
            $table->string('account_number')->nullable();    // "0123-456-789"
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete(); // COA
            $table->boolean('is_default')->default(false);
            $table->boolean('enabled')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
