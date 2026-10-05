<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transaksi', 'no_akun')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('no_akun');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('transaksi', 'no_akun')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('no_akun', 255)->nullable()->after('kode_voucer');
            });
        }
    }
};


