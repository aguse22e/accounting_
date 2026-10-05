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
        // Note: kode_project was already changed to string in a previous migration (2026_01_30_190442)
        // This migration is redundant but kept for consistency
        Schema::table('projects', function (Blueprint $table) {
            // Ensure kode_project is string (already done)
            $table->string('kode_project', 25)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_project')->change();
        });
    }
};
