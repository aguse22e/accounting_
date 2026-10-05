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
        if (Schema::hasTable('transaksi_kas_detail')) {
            Schema::drop('transaksi_kas_detail');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transaksi_kas_detail')) {
            Schema::create('transaksi_kas_detail', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
};
