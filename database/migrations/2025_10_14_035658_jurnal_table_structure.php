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
        // Ensure jurnal table has all required columns
        if (!Schema::hasTable('jurnal')) {
            return;
        }
        Schema::table('jurnal', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('jurnal', 'id_detail')) {
                $table->integer('id_detail')->nullable()->after('nominal');
            }
            
            // Ensure proper data types and constraints
            $table->string('kode_transaksi', 20)->change();
            $table->string('kode', 15)->change();
            $table->string('lawan', 15)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal', 'id_detail')) {
                $table->dropColumn('id_detail');
            }
        });
    }
};

