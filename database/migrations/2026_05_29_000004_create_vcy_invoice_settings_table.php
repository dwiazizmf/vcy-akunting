<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vcy_invoice_settings')) { return; }
        Schema::create('vcy_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('first_faktur', 50)->nullable()->comment('Bagian pertama no faktur');
            $table->string('second_faktur', 50)->nullable()->comment('Bagian kedua no faktur');
            $table->string('third_faktur', 50)->nullable()->comment('Bagian ketiga no faktur');
            $table->string('fourth_faktur', 50)->nullable()->comment('Bagian keempat no faktur');
            $table->integer('no_awal')->default(1)->comment('Nomor urut awal');
            $table->integer('no_akhir')->default(9999)->comment('Nomor urut akhir (batas)');
            $table->timestamps();
        });

        // Seed data awal
        DB::table('vcy_invoice_settings')->insert([
            'first_faktur'  => 'INV',
            'second_faktur' => null,
            'third_faktur'  => null,
            'fourth_faktur' => null,
            'no_awal'       => 1,
            'no_akhir'      => 9999,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vcy_invoice_settings');
    }
};
