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
        if (Schema::hasTable('akun') && !Schema::hasColumn('akun', 'tanggal')) {
            Schema::table('akun', function (Blueprint $table) {
                $table->timestamp('tanggal')->nullable()->after('saldo_normal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('akun') && Schema::hasColumn('akun', 'tanggal')) {
            Schema::table('akun', function (Blueprint $table) {
                $table->dropColumn('tanggal');
            });
        }
    }
};


