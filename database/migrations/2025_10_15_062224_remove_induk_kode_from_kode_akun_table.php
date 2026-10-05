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
        if (Schema::hasColumn('kode_akun', 'induk_kode')) {
            Schema::table('kode_akun', function (Blueprint $table) {
                $table->dropColumn('induk_kode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kode_akun', function (Blueprint $table) {
            $table->string('induk_kode', 255)->nullable();
        });
    }
};
