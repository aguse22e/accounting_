<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transaksi')) { return; }
        Schema::table('transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi', 'akun_kode')) {
                $table->dropColumn('akun_kode');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transaksi')) { return; }
        Schema::table('transaksi', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi', 'akun_kode')) {
                $table->string('akun_kode', 255)->nullable()->after('kode_voucer');
            }
        });
    }
};


