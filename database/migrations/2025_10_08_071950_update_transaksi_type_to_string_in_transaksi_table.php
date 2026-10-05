<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        // First, set default value for NULL entries
        DB::statement("UPDATE transaksi SET transaksi_type = 'Debit' WHERE transaksi_type IS NULL");

        // Normalize existing int values to strings
        try {
            DB::statement("UPDATE transaksi SET transaksi_type = CASE
                WHEN transaksi_type = 1 THEN 'Debit'
                WHEN transaksi_type = 2 THEN 'Kredit'
                WHEN transaksi_type = '1' THEN 'Debit'
                WHEN transaksi_type = '2' THEN 'Kredit'
                ELSE 'Debit'
            END");
        } catch (\Throwable $e) {
            // ignore if column already string
        }

        // Change column type to VARCHAR(10) for MySQL/MariaDB
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type VARCHAR(10) NOT NULL DEFAULT 'Debit'");
        } else if ($driver === 'sqlite') {
            // On SQLite, altering column types is limited; skip if already compatible
        } else if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE transaksi ALTER COLUMN transaksi_type TYPE VARCHAR(10)");
            DB::statement("ALTER TABLE transaksi ALTER COLUMN transaksi_type SET NOT NULL");
            DB::statement("ALTER TABLE transaksi ALTER COLUMN transaksi_type SET DEFAULT 'Debit'");
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
        try {
            DB::statement("UPDATE transaksi SET transaksi_type = CASE 
                WHEN transaksi_type = 'Debit' THEN 1
                WHEN transaksi_type = 'Kredit' THEN 2
                ELSE 1
            END");
        } catch (\Throwable $e) {
            // ignore
        }
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type BIGINT NOT NULL");
        } else if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE transaksi ALTER COLUMN transaksi_type TYPE BIGINT");
        }
    }
};
