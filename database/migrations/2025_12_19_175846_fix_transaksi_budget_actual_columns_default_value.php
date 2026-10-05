<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan kolom 'budget' dan 'actual' di tabel transaksi memiliki default value
     */
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        
        // Skip for SQLite as it doesn't support ALTER operations well
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // Update kolom budget dan actual untuk memiliki default value 0.00
        try {
            DB::statement('ALTER TABLE transaksi MODIFY COLUMN budget DECIMAL(15,2) DEFAULT 0.00');
            DB::statement('ALTER TABLE transaksi MODIFY COLUMN actual DECIMAL(15,2) DEFAULT 0.00');
        } catch (\Throwable $e) {
            // Jika gagal, coba dengan nullable
            try {
                DB::statement('ALTER TABLE transaksi MODIFY COLUMN budget DECIMAL(15,2) DEFAULT 0.00 NULL');
                DB::statement('ALTER TABLE transaksi MODIFY COLUMN actual DECIMAL(15,2) DEFAULT 0.00 NULL');
            } catch (\Throwable $e2) {
                // Ignore error jika kolom sudah diubah sebelumnya
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // Revert back to NOT NULL without default (original state)
        try {
            DB::statement('ALTER TABLE transaksi MODIFY COLUMN budget DECIMAL(15,2) NOT NULL');
            DB::statement('ALTER TABLE transaksi MODIFY COLUMN actual DECIMAL(15,2) NOT NULL');
        } catch (\Throwable $e) {
            // Ignore error
        }
    }
};
