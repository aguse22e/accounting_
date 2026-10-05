<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan kolom 'actual' dihapus atau diberi default value
     */
    public function up(): void
    {
        if (!Schema::hasTable('budget')) {
            return;
        }
        
        // Jika kolom 'actual' masih ada, hapus kolom tersebut
        // karena sudah tidak digunakan lagi
        if (Schema::hasColumn('budget', 'actual')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropColumn('actual');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('budget')) {
            return;
        }
        
        // Jika rollback, tambahkan kembali kolom 'actual' dengan default value
        if (!Schema::hasColumn('budget', 'actual')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->decimal('actual', 15, 2)->default(0)->after('anggaran');
            });
        }
    }
};
