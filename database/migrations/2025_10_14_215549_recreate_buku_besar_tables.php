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
        // Drop existing table
        Schema::dropIfExists('buku_besar');
        
        // Create new table with updated structure
        Schema::create('buku_besar', function (Blueprint $table) {
            $table->string('Id_Buku', 25)->primary();
            $table->bigInteger('Kode_Project');
            $table->string('Id_akun', 25)->unique();
            $table->string('Nama_Akun', 50);
            $table->text('Deskripsi');
            $table->timestamp('Tanggal');
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
        // Drop the new table
        Schema::dropIfExists('buku_besar');
        
        // Recreate original table structure
        Schema::create('buku_besar', function (Blueprint $table) {
            $table->bigIncrements('Buku_Id');
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
};
