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
        // Only modify if table exists (some environments may not have vouchers yet)
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                // Add required columns if missing
                if (!Schema::hasColumn('vouchers', 'Kode_Budget')) {
                    $table->string('Kode_Budget', 25)->nullable()->after('kode_voucher');
                }
                if (!Schema::hasColumn('vouchers', 'Kode_Project')) {
                    $table->string('Kode_Project', 25)->nullable()->after('Kode_Budget');
                }
                if (!Schema::hasColumn('vouchers', 'Id_Jurnal')) {
                    $table->string('Id_Jurnal', 25)->nullable()->after('Kode_Project');
                }
                if (!Schema::hasColumn('vouchers', 'Id_akun')) {
                    $table->string('Id_akun', 25)->nullable()->after('Id_Jurnal');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vouchers')) {
            // Drop columns individually to support SQLite
            foreach (['Id_akun','Id_Jurnal','Kode_Project','Kode_Budget'] as $col) {
                if (Schema::hasColumn('vouchers', $col)) {
                    Schema::table('vouchers', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }
    }
};
