<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaksiBankDetailTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('transaksi_bank_detail')) {
            return;
        }
        Schema::create('transaksi_bank_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_transaksi_bank');
            $table->string('kode_lawan', 15);
            $table->decimal('subtotal', 13, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Omit FKs to simplify initial setup; ensure consistency at app-level
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaksi_bank_detail');
    }
}
