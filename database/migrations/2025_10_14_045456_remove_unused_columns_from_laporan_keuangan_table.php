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
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            if (Schema::hasColumn('laporan_keuangan', 'Actual')) {
                $table->dropColumn('Actual');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_keuangan', function (Blueprint $table) {
            // Add back the removed columns
            $table->decimal('Actual', 15, 2)->after('Budget');
        });
    }
};
