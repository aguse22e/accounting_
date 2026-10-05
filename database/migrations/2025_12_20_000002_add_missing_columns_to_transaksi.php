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
        if (!Schema::hasTable('transaksi')) {
            return;
        }

        Schema::table('transaksi', function (Blueprint $table) {
            // Add debit column if missing
            if (!Schema::hasColumn('transaksi', 'debit')) {
                $table->decimal('debit', 15, 2)->default(0)->nullable()->after('nominal');
            }
            
            // Add kredit column if missing
            if (!Schema::hasColumn('transaksi', 'kredit')) {
                $table->decimal('kredit', 15, 2)->default(0)->nullable()->after('debit');
            }
            
            // Add transaksi_type column if missing (check if it exists as different type)
            if (!Schema::hasColumn('transaksi', 'transaksi_type')) {
                $table->string('transaksi_type', 50)->nullable()->after('akun_kode');
            }
            
            // Add saldo column if missing
            if (!Schema::hasColumn('transaksi', 'saldo')) {
                $table->decimal('saldo', 15, 2)->default(0)->nullable()->after('kredit');
            }
            
            // Add jumlah column if missing
            if (!Schema::hasColumn('transaksi', 'jumlah')) {
                $table->decimal('jumlah', 15, 2)->default(0)->nullable()->after('saldo');
            }
            
            // Add jenis column if missing
            if (!Schema::hasColumn('transaksi', 'jenis')) {
                $table->string('jenis', 50)->nullable()->after('kredit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $columns = ['debit', 'kredit', 'transaksi_type', 'saldo', 'jumlah', 'jenis'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('transaksi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

