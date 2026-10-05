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
            $table->string('kode_projek')->nullable()->after('kode_project');
            $table->string('kode_voucher')->nullable()->after('kode_projek');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget', function (Blueprint $table) {
            $table->dropColumn(['kode_projek', 'kode_voucher']);
        });
    }
};
