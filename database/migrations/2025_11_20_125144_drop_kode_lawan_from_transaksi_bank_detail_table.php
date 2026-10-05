<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists before dropping
        if (Schema::hasColumn('transaksi_bank_detail', 'kode_lawan')) {
            // Check if foreign key exists and drop it
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transaksi_bank_detail' 
                AND COLUMN_NAME = 'kode_lawan' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE `transaksi_bank_detail` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
            
            // Check if index exists and drop it
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transaksi_bank_detail' 
                AND COLUMN_NAME = 'kode_lawan'
                AND INDEX_NAME != 'PRIMARY'
            ");
            
            foreach ($indexes as $idx) {
                try {
                    DB::statement("ALTER TABLE `transaksi_bank_detail` DROP INDEX `{$idx->INDEX_NAME}`");
                } catch (\Exception $e) {
                    // Index doesn't exist, continue
                }
            }
            
            Schema::table('transaksi_bank_detail', function (Blueprint $table) {
                // Drop column
                $table->dropColumn('kode_lawan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_bank_detail', function (Blueprint $table) {
            $table->string('kode_lawan', 15)->after('kode_transaksi_bank');
            $table->index('kode_lawan');
            $table->foreign('kode_lawan')->references('kode_akun')->on('kode_akun');
        });
    }
};
