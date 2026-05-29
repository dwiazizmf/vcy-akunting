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
        if (Schema::hasTable('invoices')) {
            // Table exists, skip creation
            return;
        }
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('invoice_number', 191);
            $table->string('order_number', 500)->nullable();
            $table->text('invoice_text')->nullable();
            $table->text('r_invoice_text')->nullable();
            $table->string('invoice_status_code', 191)->default('draft');
            $table->string('payment_status', 50)->default('unpaid');
            $table->dateTime('invoiced_at');
            $table->dateTime('due_at');
            $table->bigInteger('subtotal')->default(0);
            $table->boolean('isDiskon')->default(false);
            $table->integer('jumlah_diskon')->default(0);
            $table->bigInteger('diskon_total')->default(0);
            $table->integer('isTax')->default(1);
            $table->integer('jumlah_tax')->default(0);
            $table->bigInteger('ppn_total')->default(0);
            $table->double('amount', 15, 4);
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('customer_name', 191);
            $table->string('customer_npwp', 191)->nullable();
            $table->text('customer_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('pelabuhan_asal')->nullable();
            $table->text('pelabuhan_tujuan')->nullable();
            $table->boolean('isFaktur')->default(false);
            $table->string('no_faktur_int', 250)->nullable();
            $table->string('no_faktur_pajak', 250)->nullable();
            $table->text('alamat_faktur_pajak')->nullable();
            $table->tinyInteger('isFCL')->nullable();
            $table->string('no_container', 50)->nullable();
            $table->string('nama_kapal', 50)->nullable();
            $table->string('kode_pelabuhan_asal', 50)->nullable();
            $table->string('kode_pelabuhan_tujuan', 50)->nullable();
            $table->string('voy', 225)->nullable();
            $table->dateTime('departure_date')->nullable();
            $table->text('notes_text')->nullable();
            $table->string('tipe_invoice', 12)->nullable();
            $table->boolean('isBpb')->default(false);
            $table->boolean('isPosted')->default(false);
            $table->text('invoice_type')->nullable(); // originally varchar(1000)
            $table->string('user_id', 100)->nullable();
            $table->string('created_from', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            // Note: order_number is varchar(500) so it may need prefix index in MySQL
            // $table->index('order_number');  // removed: too long for index
            // $table->index('invoice_text'); // removed: TEXT column cannot be indexed directly
            $table->index('invoiced_at');
            $table->index('invoice_number');
            $table->index('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
