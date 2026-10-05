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
        // Add indexes for transaksi table
        Schema::table('transaksi', function (Blueprint $table) {
            $table->index(['tanggal', 'transaksi_type'], 'idx_transaksi_tanggal_type');
            $table->index(['transaksi_type', 'tanggal'], 'idx_transaksi_type_tanggal');
            $table->index(['kode_project', 'tanggal'], 'idx_transaksi_project_tanggal');
            $table->index('created_at', 'idx_transaksi_created');
            $table->index('tanggal', 'idx_transaksi_tanggal');
        });

        // Add indexes for akun table
        Schema::table('akun', function (Blueprint $table) {
            $table->index('kode_akun', 'idx_akun_kode');
        });

        // Add indexes for projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->index('kode_project', 'idx_projects_kode');
            $table->index('nama_project', 'idx_projects_nama');
        });

        // Add indexes for vouchers table if it exists
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->index('kode_voucher', 'idx_vouchers_kode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for transaksi table
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex('idx_transaksi_tanggal_type');
            $table->dropIndex('idx_transaksi_type_tanggal');
            $table->dropIndex('idx_transaksi_project_tanggal');
            $table->dropIndex('idx_transaksi_created');
            $table->dropIndex('idx_transaksi_tanggal');
        });

        // Drop indexes for akun table
        Schema::table('akun', function (Blueprint $table) {
            $table->dropIndex('idx_akun_kode');
        });

        // Drop indexes for projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_kode');
            $table->dropIndex('idx_projects_nama');
        });

        // Drop indexes for vouchers table if it exists
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropIndex('idx_vouchers_kode');
            });
        }
    }
};
