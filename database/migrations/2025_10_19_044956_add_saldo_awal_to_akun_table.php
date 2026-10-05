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
        Schema::table('akun', function (Blueprint $table) {
            if (!Schema::hasColumn('akun', 'saldo_awal')) {
                if (Schema::hasColumn('akun', 'saldo_normal')) {
                    $table->decimal('saldo_awal', 15, 2)->default(0)->after('saldo_normal');
                } else {
                    $table->decimal('saldo_awal', 15, 2)->default(0);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            $table->dropColumn('saldo_awal');
        });
    }
};
