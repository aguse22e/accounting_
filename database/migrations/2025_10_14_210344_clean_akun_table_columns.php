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
        // Drop columns individually to support SQLite
        Schema::table('akun', function (Blueprint $table) {
            if (Schema::hasColumn('akun', 'Id_Laporan')) {
                $table->dropColumn('Id_Laporan');
            }
        });
        
        Schema::table('akun', function (Blueprint $table) {
            if (Schema::hasColumn('akun', 'Id_Buku')) {
                $table->dropColumn('Id_Buku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            // Restore dropped columns
            $table->string('Id_Laporan', 20)->nullable();
            $table->string('Id_Buku', 25)->nullable();
        });
    }
};
