<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('transaksi')) { return; }
        
        // Skip for SQLite as it doesn't support ALTER operations
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // Change primary key `kode_voucer` from integer to VARCHAR to support alphanumeric codes
        // MySQL requires dropping and re-adding the primary key when changing its type
        DB::statement('ALTER TABLE transaksi DROP PRIMARY KEY');
        DB::statement("ALTER TABLE transaksi MODIFY kode_voucer VARCHAR(50) NOT NULL");
        DB::statement('ALTER TABLE transaksi ADD PRIMARY KEY (kode_voucer)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('transaksi')) { return; }
        
        // Skip for SQLite as it doesn't support ALTER operations
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // Revert back to BIGINT auto-increment primary key if needed
        // Note: This will fail if non-numeric codes exist. Use with caution.
        DB::statement('ALTER TABLE transaksi DROP PRIMARY KEY');
        DB::statement('ALTER TABLE transaksi MODIFY kode_voucer BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE transaksi ADD PRIMARY KEY (kode_voucer)');
    }
};


