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
        if (Schema::hasTable('vouchers')) {
            // Try to drop foreign key constraint if it exists
            try {
                DB::statement('ALTER TABLE `vouchers` DROP FOREIGN KEY `vouchers_no_project_foreign`');
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            
            // Try to drop indexes if they exist
            try {
                DB::statement('ALTER TABLE `vouchers` DROP INDEX `vouchers_no_project_index`');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                DB::statement('ALTER TABLE `vouchers` DROP INDEX `vouchers_tanggal_index`');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                DB::statement('ALTER TABLE `vouchers` DROP INDEX `vouchers_tipe_transaksi_index`');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            // Drop the table
            Schema::dropIfExists('vouchers');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the vouchers table
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('no_project', 25);
            $table->string('kode_voucher', 25);
            $table->enum('tipe_transaksi', ['debit', 'kredit'])->default('debit');
            $table->timestamp('tanggal');
            $table->string('deskripsi', 255);
            $table->decimal('jumlah', 15, 2);
            $table->timestamps();
            
            $table->index('no_project', 'vouchers_no_project_index');
            $table->index('tanggal', 'vouchers_tanggal_index');
            $table->index('tipe_transaksi', 'vouchers_tipe_transaksi_index');
            $table->foreign('no_project', 'vouchers_no_project_foreign')
                  ->references('kode_project')
                  ->on('projects')
                  ->onDelete('cascade');
        });
    }
};
