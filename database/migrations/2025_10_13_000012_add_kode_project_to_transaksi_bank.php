<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_bank', 'kode_project')) {
                $table->string('kode_project')->nullable()->after('akun_kode');
                try { $table->foreign('kode_project')->references('kode_project')->on('projects')->cascadeOnDelete(); } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('transaksi_bank', function (Blueprint $table) {
            try { $table->dropForeign(['kode_project']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
                $table->dropColumn('kode_project');
            }
        });
    }
};


