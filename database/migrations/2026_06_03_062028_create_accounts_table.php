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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('account_types')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('system')->default(false)->comment('Cannot be deleted if true');
            $table->boolean('enabled')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
