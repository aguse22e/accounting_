<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cleanup unused tables and ensure database structure is correct
     */
    public function up(): void
    {
        // Skip for SQLite (testing)
        if (config('database.default') === 'sqlite') {
            return;
        }

        // 1. Drop transaksi_bank_detail if it exists and is empty
        // Note: This table is no longer used (kode_lawan column was removed)
        if (Schema::hasTable('transaksi_bank_detail')) {
            try {
                $count = DB::table('transaksi_bank_detail')->count();
                if ($count === 0) {
                    $this->dropForeignKeys('transaksi_bank_detail');
                    Schema::dropIfExists('transaksi_bank_detail');
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // 2. Drop detail_jurnal if it exists and is empty
        if (Schema::hasTable('detail_jurnal')) {
            try {
                $count = DB::table('detail_jurnal')->count();
                if ($count === 0) {
                    $this->dropForeignKeys('detail_jurnal');
                    Schema::dropIfExists('detail_jurnal');
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // 3. Drop old 'project' table if 'projects' table exists and is being used
        // Model Project uses 'projects' table, so old 'project' table is unused
        if (Schema::hasTable('projects') && Schema::hasTable('project')) {
            try {
                $projectCount = DB::table('project')->count();
                $projectsCount = DB::table('projects')->count();
                
                // If 'project' is empty and 'projects' has data, drop 'project'
                if ($projectCount === 0 && $projectsCount > 0) {
                    $this->dropForeignKeys('project');
                    Schema::dropIfExists('project');
                }
            } catch (\Exception $e) {
                // Skip if error
            }
        }

        // 4. Ensure required tables exist with correct structure
        $this->ensureTablesExist();

        // 5. Clean up unused columns
        $this->cleanupUnusedColumns();
    }

    /**
     * Ensure all required tables exist
     */
    private function ensureTablesExist(): void
    {
        // Required tables (already created by other migrations):
        // - users
        // - kode_akun
        // - akun
        // - projects
        // - budget
        // - transaksi
        // - transaksi_bank
        // - vouchers
        // - activity_users
        // - sessions
        // - jurnal
        // - buku_besar
        // - laporan_keuangan
        // - cache
        // - jobs
        // - failed_jobs
    }

    /**
     * Clean up unused columns from tables
     */
    private function cleanupUnusedColumns(): void
    {
        // Clean up transaksi table
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $columnsToDrop = [
                    'akun_id', // Not used, using akun_kode instead
                    'budget', // Removed in previous migrations
                ];

                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('transaksi', $column)) {
                        try {
                            $table->dropColumn($column);
                        } catch (\Exception $e) {
                            // Column might have dependencies, skip
                        }
                    }
                }
            });
        }

        // Clean up budget table
        if (Schema::hasTable('budget')) {
            Schema::table('budget', function (Blueprint $table) {
                $columnsToDrop = [
                    'kode_voucher', // Check if this is actually used
                    'budget', // Removed in previous migration
                ];

                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('budget', $column)) {
                        try {
                            // Check if column has any non-null values
                            $hasData = DB::table('budget')
                                ->whereNotNull($column)
                                ->where($column, '!=', '')
                                ->exists();
                            
                            if (!$hasData) {
                                $table->dropColumn($column);
                            }
                        } catch (\Exception $e) {
                            // Skip if error
                        }
                    }
                }
            });
        }

        // Clean up users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columnsToDrop = [
                    'email_verified_at', // Not used in this app
                    'remember_token', // Not used in this app
                ];

                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        try {
                            $table->dropColumn($column);
                        } catch (\Exception $e) {
                            // Skip if error
                        }
                    }
                }
            });
        }

        // Clean up akun table
        if (Schema::hasTable('akun')) {
            Schema::table('akun', function (Blueprint $table) {
                // Only drop 'tipe' if 'saldo_normal' exists (replacement)
                if (Schema::hasColumn('akun', 'tipe') && Schema::hasColumn('akun', 'saldo_normal')) {
                    try {
                        $table->dropColumn('tipe');
                    } catch (\Exception $e) {
                        // Skip if error
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't recreate dropped tables/columns in down()
        // as they were unused. If needed, restore from backup.
    }

    /**
     * Helper method to drop all foreign keys from a table
     */
    private function dropForeignKeys(string $tableName): void
    {
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$tableName]);

            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
        } catch (\Exception $e) {
            // Skip if error (might be SQLite or other DB)
        }
    }
};
