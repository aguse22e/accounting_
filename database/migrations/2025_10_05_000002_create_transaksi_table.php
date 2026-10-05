<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaksiTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->bigIncrements('kode_voucer');
            $table->unsignedBigInteger('kode_budget');
            $table->unsignedBigInteger('kode_project');
            $table->bigInteger('transaksi_type');
            $table->string('deskripsi', 250);

            $table->decimal('nominal', 15, 2);
            $table->decimal('budget', 15, 2);
            $table->decimal('actual', 15, 2);
            $table->timestamp('tanggal');
            $table->timestamp('create_at')->useCurrent();
            $table->timestamp('update_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('kode_budget')->references('kode_budget')->on('budget')->onDelete('cascade');
            $table->foreign('kode_project')->references('Kode_project')->on('project')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
}
