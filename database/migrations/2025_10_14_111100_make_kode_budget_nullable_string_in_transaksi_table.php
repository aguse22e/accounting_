<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) { return; }
        // Drop FK if exists (ignore errors)
        try { DB::statement('ALTER TABLE transaksi DROP FOREIGN KEY transaksi_kode_budget_foreign'); } catch (\Throwable $e) {}

        Schema::table('transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi', 'kode_budget')) {
                $table->string('kode_budget', 255)->nullable()->change();
            } else {
                $table->string('kode_budget', 255)->nullable()->after('jenis');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transaksi')) { return; }
        Schema::table('transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi', 'kode_budget')) {
                // revert to decimal/bigint not strictly known; keep as nullable string to avoid data loss on down
                $table->string('kode_budget', 255)->nullable()->change();
            }
        });
    }
};


