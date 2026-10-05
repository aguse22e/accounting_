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
        // Drop the existing table and recreate with new structure
        Schema::dropIfExists('laporan_keuangan');
        
        Schema::create('laporan_keuangan', function (Blueprint $table) {
            $table->id(); // This creates an auto-incrementing integer primary key
            $table->string('kode_laporan', 25)->unique();
            $table->string('nama_laporan', 25);
            $table->timestamp('periode_mulai');
            $table->timestamp('periode_selesai');
            $table->decimal('total_pendapatan', 15, 2)->default(0.00);
            $table->decimal('total_beban', 15, 2)->default(0.00);
            $table->decimal('laba_rugi', 15, 2)->default(0.00);
            $table->string('status', 25)->default('draft');
            $table->timestamps();
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
