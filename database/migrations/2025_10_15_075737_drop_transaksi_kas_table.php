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
        if (Schema::hasTable('transaksi_kas')) {
            Schema::drop('transaksi_kas');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transaksi_kas')) {
            Schema::create('transaksi_kas', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
};
