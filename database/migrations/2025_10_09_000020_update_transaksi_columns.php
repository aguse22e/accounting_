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
        // string and transaksi_type to enum/string, and retarget project FK
        // Safely drop FK if it exists by raw SQL to avoid name mismatch errors
        try {
            DB::statement('ALTER TABLE transaksi DROP FOREIGN KEY transaksi_kode_project_foreign');
        } catch (\Throwable $e) {}

        // Keep primary key as-is to avoid conflicts in existing data

        Schema::table('transaksi', function (Blueprint $table) {
            // Change transaksi_type to string to store values like 'Debit'/'Kredit'
            try {
                $table->string('transaksi_type', 20)->change();
            } catch (\Throwable $e) {
                // Some platforms can't change easily; fallback to rename-and-recreate if needed in manual migration
            }

            // Ensure kode_project remains string and relates to projects.kode_project
            try { $table->string('kode_project', 25)->change(); } catch (\Throwable $e) {}
            // Skip FK re-creation here to avoid cross-collation/length issues; can be added later
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
            try {
                $table->dropForeign(['kode_project']);
            } catch (\Throwable $e) {
                // ignore
            }
        });

        // revert kode_voucer back to bigint
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN kode_voucer BIGINT UNSIGNED NOT NULL");
        try {
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN kode_voucer BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        } catch (\Throwable $e) {
            // ignore if not supported
        }

        Schema::table('transaksi', function (Blueprint $table) {
            try {
                $table->bigInteger('transaksi_type')->change();
            } catch (\Throwable $e) {
                // ignore
            }

            // Legacy FK to project(Kode_project) if table exists
            if (Schema::hasTable('project')) {
                $table->foreign('kode_project')->references('Kode_project')->on('project')->onDelete('cascade');
            }
        });
    }
};


