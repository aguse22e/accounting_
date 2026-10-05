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
        try { DB::statement('ALTER TABLE transaksi DROP FOREIGN KEY transaksi_kode_project_foreign'); } catch (\Throwable $e) {}

        Schema::table('transaksi', function (Blueprint $table) {
            //
            if (Schema::hasColumn('transaksi', 'kode_project')) {
                $table->string('kode_project', 25)->nullable()->change();
            } else {
                $table->string('kode_project', 25)->nullable()->after('akun_kode');
            }
        });


    }

    public function down(): void
    {
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        if (!Schema::hasTable('transaksi')) { return; }
        Schema::table('transaksi', function (Blueprint $table) {
            try { $table->dropForeign(['kode_project']); } catch (\Throwable $e) {}
            // Revert to unsignedBigInteger for backward compatibility
            try { $table->unsignedBigInteger('kode_project')->nullable()->change(); } catch (\Throwable $e) {}
        });
    }
};


