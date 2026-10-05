<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaksiBankTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('transaksi_bank')) {
            return;
        }
        Schema::create('transaksi_bank', function (Blueprint $table) {
            $table->string('kode_transaksi_bank', 20)->primary();
            $table->timestamp('tanggal');
            $table->string('akun_kode');
            $table->enum('tipe', ['Debit', 'Kredit']);
            $table->decimal('total', 13, 2);
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            if (Schema::hasTable('users')) {
                $table->foreign('deleted_by')->references('id')->on('users');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaksi_bank');
    }
}
