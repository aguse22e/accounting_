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
        Schema::table('budget', function (Blueprint $table) {
            if (!Schema::hasColumn('budget', 'kode_akun')) {
                $table->string('kode_akun')->nullable()->after('kode_project');
                $table->foreign('kode_akun')->references('kode_akun')->on('akun')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget', function (Blueprint $table) {
            try {
                $table->dropForeign(['kode_akun']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            if (Schema::hasColumn('budget', 'kode_akun')) {
                $table->dropColumn('kode_akun');
            }
        });
    }
};
