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
            // Add required columns
            $table->string('Id_Laporan', 20)->nullable()->after('id');
            $table->string('Id_Buku', 25)->nullable()->after('Id_Laporan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            $table->dropColumn(['Id_Laporan', 'Id_Buku']);
        });
    }
};
