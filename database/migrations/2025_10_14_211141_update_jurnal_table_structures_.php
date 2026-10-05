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
        if (!Schema::hasTable('jurnal')) {
            return;
        }

        Schema::table('jurnal', function (Blueprint $table) {
            // Update column sizes to match new specification
            if (Schema::hasColumn('jurnal', 'kode_transaksi')) {
                $table->string('kode_transaksi', 25)->change();
            }
            if (Schema::hasColumn('jurnal', 'keterangan')) {
                $table->text('keterangan')->change(); // text(225) is handled by Laravel as text
            }
            if (Schema::hasColumn('jurnal', 'kode')) {
                $table->string('kode', 25)->change();
            }
            if (Schema::hasColumn('jurnal', 'lawan')) {
                $table->string('lawan', 25)->change();
            }
            if (Schema::hasColumn('jurnal', 'id_detail')) {
                $table->integer('id_detail')->change(); // int(9) is handled as integer
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal', function (Blueprint $table) {
            // Revert to previous column sizes
            $table->string('kode_transaksi', 20)->change();
            $table->string('kode', 15)->change();
            $table->string('lawan', 15)->change();
        });
    }
};
