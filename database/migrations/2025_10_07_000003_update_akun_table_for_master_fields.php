<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            if (!Schema::hasColumn('akun', 'tanggal')) {
                $table->timestamp('tanggal')->nullable()->after('saldo_normal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            if (Schema::hasColumn('akun', 'tanggal')) {
                $table->dropColumn('tanggal');
            }
        });
    }
};


