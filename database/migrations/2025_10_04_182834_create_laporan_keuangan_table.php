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
        Schema::create('laporan_keuangan', function (Blueprint $table) {
            $table->bigIncrements('Laporan_Id');
            $table->unsignedBigInteger('Kode_Project');
            $table->string('Id_akun', 50)->unique();
            $table->string('Nama_Akun', 100);
            $table->string('Deskripsi', 250);

            $table->timestamp('Tanggal');
            $table->decimal('Actual', 15, 2);
            $table->decimal('Budget', 15, 2);
            $table->decimal('Saldo', 15, 2);
            $table->timestamp('Create_at')->useCurrent();
            $table->timestamp('Update_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_keuangan');
    }
};
