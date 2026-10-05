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
        Schema::table('transaksi', function (Blueprint $table) {
            // Only rename if both columns exist/not-exist accordingly
            if (Schema::hasColumn('transaksi', 'akun_kode') && !Schema::hasColumn('transaksi', 'no_akun')) {
                try { $table->renameColumn('akun_kode', 'no_akun'); } catch (\Throwable $e) {}
            }
            // Remove columns only if present
            foreach (['actual', 'budget'] as $col) {
                if (Schema::hasColumn('transaksi', $col)) {
                    $table->dropColumn([$col]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Add back the removed columns
            $table->decimal('budget', 15, 2)->after('nominal');
            $table->decimal('actual', 15, 2)->after('budget');

            // Rename no_akun back to akun_kode
            $table->renameColumn('no_akun', 'akun_kode');
        });
    }
};
