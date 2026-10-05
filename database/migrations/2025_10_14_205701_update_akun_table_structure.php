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
            // Modify existing columns
            $table->integer('id', 9)->change();
            $table->string('kode_akun', 25)->change();
            $table->string('nama_akun', 25)->change();
            $table->text('deskripsi', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            // Restore original column types
            $table->bigIncrements('id')->change();
            $table->string('kode_akun')->change();
            $table->string('nama_akun')->change();
            $table->text('deskripsi')->change();
        });
    }
};
