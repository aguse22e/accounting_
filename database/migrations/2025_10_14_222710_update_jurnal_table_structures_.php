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
        if (!Schema::hasTable('jurnal')) { return; }
        Schema::table('jurnal', function (Blueprint $table) {
            // Update existing columns to match new specification
            $table->string('kode_transaksi', 25)->change();
            $table->text('keterangan')->change();
            $table->string('kode', 25)->change();
            $table->string('lawan', 25)->change();
            $table->decimal('nominal', 15, 2)->change();
            
            // Add new columns for enhanced functionality (guard existence)
            if (!Schema::hasColumn('jurnal','no_referensi')) {
                $table->string('no_referensi', 50)->nullable()->after('kode_transaksi');
            }
            if (!Schema::hasColumn('jurnal','status')) {
                $table->enum('status', ['Draft', 'Posted', 'Reversed'])->default('Draft')->after('nominal');
            }
            if (!Schema::hasColumn('jurnal','catatan')) {
                $table->text('catatan')->nullable()->after('status');
            }
            if (!Schema::hasColumn('jurnal','created_by')) { $table->unsignedBigInteger('created_by')->nullable()->after('catatan'); }
            if (!Schema::hasColumn('jurnal','updated_by')) { $table->unsignedBigInteger('updated_by')->nullable()->after('created_by'); }
            if (!Schema::hasColumn('jurnal','posted_at')) { $table->timestamp('posted_at')->nullable()->after('updated_by'); }
            if (!Schema::hasColumn('jurnal','reversed_at')) { $table->timestamp('reversed_at')->nullable()->after('posted_at'); }
            
            // Add foreign key constraints (users PK may vary)
            if (Schema::hasTable('users')) {
                $userPk = Schema::hasColumn('users','Id_User') ? 'Id_User' : (Schema::hasColumn('users','id') ? 'id' : null);
                if ($userPk) {
                    try { $table->foreign('created_by')->references($userPk)->on('users')->onDelete('set null'); } catch (\Throwable $e) {}
                    try { $table->foreign('updated_by')->references($userPk)->on('users')->onDelete('set null'); } catch (\Throwable $e) {}
                }
            }
            
            // Add indexes for better performance
            $table->index(['tanggal', 'jenis_transaksi']);
            $table->index('status');
            $table->index('no_referensi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite as it doesn't support dropping foreign keys
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('jurnal', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Drop indexes
            $table->dropIndex(['tanggal', 'jenis_transaksi']);
            $table->dropIndex(['status']);
            $table->dropIndex(['no_referensi']);
            
            // Drop new columns individually to support SQLite
            $columns = ['no_referensi', 'status', 'catatan', 'created_by', 'updated_by', 'posted_at', 'reversed_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('jurnal', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Revert column changes to original sizes
            $table->string('kode_transaksi', 20)->change();
            $table->string('kode', 15)->change();
            $table->string('lawan', 15)->change();
            $table->decimal('nominal', 13, 2)->change();
        });
    }
};
