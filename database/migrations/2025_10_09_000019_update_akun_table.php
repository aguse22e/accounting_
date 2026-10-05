<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update Akun table - use safe changes based on existing schema
        Schema::table('akun', function (Blueprint $table) {
            // Ensure kode_akun length only; keep existing indexes as-is
            try { $table->string('kode_akun', 25)->change(); } catch (\Throwable $e) {}
            // Standardize name column if exists, but avoid renaming casing differences which cause issues on Windows/MySQL
            try { $table->string('nama_akun', 50)->change(); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Reverse the changes conservatively
        Schema::table('akun', function (Blueprint $table) {
            try { $table->string('kode_akun')->change(); } catch (\Throwable $e) {}
            try { $table->string('nama_akun')->change(); } catch (\Throwable $e) {}
        });
    }
};
