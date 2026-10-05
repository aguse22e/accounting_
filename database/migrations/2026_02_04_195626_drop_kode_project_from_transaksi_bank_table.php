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
        Schema::table('transaksi_bank', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
                $table->dropForeign(['kode_project']);
                $table->dropColumn('kode_project');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_bank', 'kode_project')) {
                $table->string('kode_project')->nullable()->after('akun_kode');
                $table->foreign('kode_project')->references('kode_project')->on('projects')->cascadeOnDelete();
            }
        });
    }
};
