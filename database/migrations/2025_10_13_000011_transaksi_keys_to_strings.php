<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        // Safely drop FK by raw SQL; ignore if doesn't exist
        try { DB::statement('ALTER TABLE transaksi DROP FOREIGN KEY transaksi_kode_project_foreign'); } catch (\Throwable $e) {}

        // Change PK to VARCHAR if not already
        try {
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN kode_voucer VARCHAR(30) NOT NULL");
        } catch (\Throwable $e) {
            // ignore if already string
        }
        try {
            DB::statement("ALTER TABLE transaksi ADD PRIMARY KEY (kode_voucer)");
        } catch (\Throwable $e) {
            // ignore if already primary
        }

        // Align project key to string; skip FK recreation to avoid incompatibility
        Schema::table('transaksi', function (Blueprint $table) {
            try { $table->string('kode_project', 25)->change(); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        Schema::table('transaksi', function (Blueprint $table) {
            try { $table->dropForeign(['kode_project']); } catch (\Throwable $e) {}
        });
        try {
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN kode_voucer BIGINT UNSIGNED NOT NULL");
        } catch (\Throwable $e) {}
        Schema::table('transaksi', function (Blueprint $table) {
            try { $table->unsignedBigInteger('kode_project')->change(); } catch (\Throwable $e) {}
        });
    }
};


