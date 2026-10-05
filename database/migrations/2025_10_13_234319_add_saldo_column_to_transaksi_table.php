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
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                if (!Schema::hasColumn('transaksi', 'saldo')) {
                    $table->decimal('saldo', 15, 2)->nullable()->after('nominal');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transaksi') && Schema::hasColumn('transaksi', 'saldo')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('saldo');
            });
        }
    }
};
