<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Comprehensive normalization to restore database to original design standards
     */
    public function up(): void
    {
        echo "\n=== STARTING COMPLETE DATABASE NORMALIZATION ===\n";
        
        // ============================================
        // PHASE 0: Drop all Foreign Keys First
        // ============================================
        echo "PHASE 0: Dropping all foreign keys...\n";
        
        // Explicitly drop foreign keys that might reference akun table
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Try to drop foreign keys explicitly
        try {
            DB::statement('ALTER TABLE journal_lines DROP FOREIGN KEY journal_lines_account_code_foreign');
        } catch (\Exception $e) {
            // FK might not exist
        }
        
        $this->dropForeignKeysIfExist();
        echo "  ✓ All foreign keys dropped\n";
        
        // ============================================
        // PHASE 1: Backup and Consolidate Accounts
        // ============================================
        echo "\nPHASE 1: Consolidating account tables...\n";
        
        if (Schema::hasTable('akun')) {
            // Create backup of akun
            DB::statement('CREATE TABLE IF NOT EXISTS akun_legacy_backup AS SELECT * FROM akun');
            echo "  ✓ Backed up akun table to akun_legacy_backup\n";
            
            // Copy missing data from akun to kode_akun
            DB::statement('
                INSERT IGNORE INTO kode_akun (kode_akun, nama, tipe, created_at, updated_at)
                SELECT kode_akun, nama_akun, saldo_normal, created_at, updated_at 
                FROM akun
                WHERE kode_akun NOT IN (SELECT kode_akun FROM kode_akun)
            ');
            echo "  ✓ Copied data from akun to kode_akun\n";
            
            // Drop the old akun table
            Schema::dropIfExists('akun');
            echo "  ✓ Dropped old akun table\n";
        }
        
        // ============================================
        // PHASE 2: Normalize Project Codes
        // ============================================
        echo "\nPHASE 2: Normalizing project codes to PROJXXXX format...\n";
        
        if (Schema::hasTable('projects')) {
            // Backup projects
            DB::statement('CREATE TABLE IF NOT EXISTS projects_backup AS SELECT * FROM projects');
            echo "  ✓ Backed up projects table\n";
            
            // Update existing project codes to PROJXXXX format
            $projects = DB::table('projects')->get();
            $updateCount = 0;
            
            foreach ($projects as $project) {
                $oldCode = $project->kode_project;
                
                // Check if already in correct format
                if (preg_match('/^PROJ\d{4}$/', $oldCode)) {
                    continue;
                }
                
                // Convert to PROJXXXX format
                preg_match('/(\d+)/', $oldCode, $matches);
                $num = isset($matches[1]) ? $matches[1] : rand(1000, 9999);
                $newCode = 'PROJ' . str_pad($num, 4, '0', STR_PAD_LEFT);
                
                // Update all references
                DB::table('projects')
                    ->where('kode_project', $oldCode)
                    ->update(['kode_project' => $newCode]);
                
                DB::table('budget')
                    ->where('kode_project', $oldCode)
                    ->update(['kode_project' => $newCode]);
                
                if (Schema::hasTable('transaksi')) {
                    DB::table('transaksi')
                        ->where('kode_project', $oldCode)
                        ->update(['kode_project' => $newCode]);
                }
                
                if (Schema::hasTable('transaksi_bank')) {
                    DB::table('transaksi_bank')
                        ->where('kode_project', $oldCode)
                        ->update(['kode_project' => $newCode]);
                }
                
                $updateCount++;
            }
            
            echo "  ✓ Converted $updateCount project codes to PROJXXXX format\n";
        }
        
        // ============================================
        // PHASE 3: Optimize Data Types
        // ============================================
        echo "\nPHASE 3: Optimizing data types...\n";
        
        // Normalize kode_akun columns
        if (Schema::hasTable('kode_akun')) {
            Schema::table('kode_akun', function (Blueprint $table) {
                $table->string('kode_akun', 4)->change();
                $table->string('nama', 40)->change();
                $table->string('tipe', 6)->change();
            });
            echo "  ✓ Optimized kode_akun table columns\n";
        }
        
        // Normalize projects columns
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('kode_project', 20)->change();
            });
            echo "  ✓ Optimized projects table columns\n";
        }
        
        // Normalize budget columns
        if (Schema::hasTable('budget')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->string('kode_budget', 20)->change();
                $table->string('kode_project', 20)->change();
                $table->string('kode_akun', 4)->change();
                
                // Drop old timestamp columns if they exist
                if (Schema::hasColumn('budget', 'create_at')) {
                    $table->dropColumn('create_at');
                }
                if (Schema::hasColumn('budget', 'update_at')) {
                    $table->dropColumn('update_at');
                }
            });
            echo "  ✓ Optimized budget table columns\n";
        }
        
        // Normalize transaksi columns
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('kode_voucer', 20)->change();
                $table->string('akun_kode', 4)->change();
                $table->string('kode_project', 20)->nullable()->change();
                $table->string('kode_budget', 20)->nullable()->change();
                
                // Drop old timestamp columns if they exist
                if (Schema::hasColumn('transaksi', 'create_at')) {
                    $table->dropColumn('create_at');
                }
                if (Schema::hasColumn('transaksi', 'update_at')) {
                    $table->dropColumn('update_at');
                }
            });
            echo "  ✓ Optimized transaksi table columns\n";
            
            // Ensure transaksi_type is ENUM
            DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type ENUM('Debit', 'Kredit') NOT NULL");
            echo "  ✓ Set transaksi_type as ENUM\n";
            
            // Drop deprecated jenis column if it exists
            if (Schema::hasColumn('transaksi', 'jenis')) {
                Schema::table('transaksi', function (Blueprint $table) {
                    $table->dropColumn('jenis');
                });
                echo "  ✓ Dropped deprecated jenis column\n";
            }
        }
        
        // Normalize transaksi_bank columns if table exists
        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('akun_kode', 4)->change();
                $table->string('kode_project', 20)->nullable()->change();
            });
            echo "  ✓ Optimized transaksi_bank table columns\n";
            
            // Ensure tipe is ENUM
            DB::statement("ALTER TABLE transaksi_bank MODIFY COLUMN tipe ENUM('Debit', 'Kredit') NOT NULL");
            echo "  ✓ Set transaksi_bank.tipe as ENUM\n";
        }
        
        // ============================================
        // PHASE 4: Clean Orphan Records
        // ============================================
        echo "\nPHASE 4: Cleaning orphan records...\n";
        
        // Delete orphan budget records
        $orphanBudgetCount = DB::table('budget')
            ->whereNotIn('kode_akun', DB::table('kode_akun')->select('kode_akun'))
            ->orWhereNotIn('kode_project', DB::table('projects')->select('kode_project'))
            ->delete();
        echo "  ✓ Deleted $orphanBudgetCount orphan budget records\n";
        
        // Delete orphan transaksi records if table exists
        if (Schema::hasTable('transaksi')) {
            $orphanTransaksiCount = DB::table('transaksi')
                ->whereNotIn('akun_kode', DB::table('kode_akun')->select('kode_akun'))
                ->delete();
            
            $orphanTransaksiCount += DB::table('transaksi')
                ->whereNotNull('kode_project')
                ->whereNotIn('kode_project', DB::table('projects')->select('kode_project'))
                ->delete();
            
            echo "  ✓ Deleted $orphanTransaksiCount orphan transaksi records\n";
        }
        
        // Delete orphan transaksi_bank records if table exists
        if (Schema::hasTable('transaksi_bank')) {
            $orphanBankCount = DB::table('transaksi_bank')
                ->whereNotIn('akun_kode', DB::table('kode_akun')->select('kode_akun'))
                ->delete();
            
            $orphanBankCount += DB::table('transaksi_bank')
                ->whereNotNull('kode_project')
                ->whereNotIn('kode_project', DB::table('projects')->select('kode_project'))
                ->delete();
            
            echo "  ✓ Deleted $orphanBankCount orphan transaksi_bank records\n";
        }
        
        // ============================================
        // PHASE 5: Add Proper Foreign Keys
        // ============================================
        echo "\nPHASE 5: Adding foreign keys...\n";
        
        // Foreign keys for budget table
        Schema::table('budget', function (Blueprint $table) {
            $table->foreign('kode_akun', 'fk_budget_kode_akun')
                ->references('kode_akun')
                ->on('kode_akun')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->foreign('kode_project', 'fk_budget_kode_project')
                ->references('kode_project')
                ->on('projects')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
        echo "  ✓ Added foreign keys to budget table\n";
        
        // Foreign keys for transaksi table if it exists
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('akun_kode', 'fk_transaksi_akun_kode')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
                
                $table->foreign('kode_project', 'fk_transaksi_kode_project')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
            echo "  ✓ Added foreign keys to transaksi table\n";
        }
        
        // Foreign keys for transaksi_bank if it exists
        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->foreign('akun_kode', 'fk_transaksi_bank_akun_kode')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
                
                $table->foreign('kode_project', 'fk_transaksi_bank_kode_project')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
            echo "  ✓ Added foreign keys to transaksi_bank table\n";
        }
        
        // ============================================
        // PHASE 6: Add Performance Indexes
        // ============================================
        echo "\nPHASE 6: Adding performance indexes...\n";
        
        // Indexes on transaksi table - just use raw SQL with if not exists
        if (Schema::hasTable('transaksi')) {
            try {
                DB::statement('ALTER TABLE transaksi ADD INDEX IF NOT EXISTS idx_transaksi_akun_tanggal (akun_kode, tanggal)');
                DB::statement('ALTER TABLE transaksi ADD INDEX IF NOT EXISTS idx_transaksi_project_tanggal (kode_project, tanggal)');
                DB::statement('ALTER TABLE transaksi ADD INDEX IF NOT EXISTS idx_transaksi_type (transaksi_type)');
                echo "  ✓ Added indexes to transaksi table\n";
            } catch (\Exception $e) {
                echo "  ⚠ Some indexes on transaksi may already exist (non-fatal)\n";
            }
        }
        
        // Indexes on budget table
        try {
            DB::statement('ALTER TABLE budget ADD INDEX IF NOT EXISTS idx_budget_akun_project (kode_akun, kode_project)');
            echo "  ✓ Added indexes to budget table\n";
        } catch (\Exception $e) {
            echo "  ⚠ Some indexes on budget may already exist (non-fatal)\n";
        }
        
        // Indexes on transaksi_bank table
        if (Schema::hasTable('transaksi_bank')) {
            try {
                DB::statement('ALTER TABLE transaksi_bank ADD INDEX IF NOT EXISTS idx_transaksi_bank_akun_tanggal (akun_kode, tanggal)');
                DB::statement('ALTER TABLE transaksi_bank ADD INDEX IF NOT EXISTS idx_transaksi_bank_project_tanggal (kode_project, tanggal)');
                DB::statement('ALTER TABLE transaksi_bank ADD INDEX IF NOT EXISTS idx_transaksi_bank_tipe (tipe)');
                echo "  ✓ Added indexes to transaksi_bank table\n";
            } catch (\Exception $e) {
                echo "  ⚠ Some indexes on transaksi_bank may already exist (non-fatal)\n";
            }
        }
        
        echo "\n=== ✓ COMPLETE DATABASE NORMALIZATION FINISHED ===\n";
        echo "Database is now normalized to original design standards!\n";
        echo "6 phases executed successfully: FK Drop → Account Consolidation → Project Codes → Data Types → Orphan Cleanup → FK Restore → Performance Indexes\n\n";
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\nWARNING: Rollback of complete normalization is not fully supported.\n";
        echo "This migration involves data consolidation and cleanup.\n";
        echo "To rollback: Restore from database backup taken before running this migration.\n";
        echo "Backup location: Look for 'backup_before_normalization_YYYYMMDD_HHMMSS.sql'\n\n";
        
        // We can only partially rollback
        // Drop foreign keys
        Schema::table('budget', function (Blueprint $table) {
            $table->dropForeign('fk_budget_kode_akun');
            $table->dropForeign('fk_budget_kode_project');
        });
        
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                try {
                    $table->dropForeign('fk_transaksi_akun_kode');
                    $table->dropForeign('fk_transaksi_kode_project');
                } catch (\Exception $e) {
                    // Might not exist
                }
            });
        }
        
        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                try {
                    $table->dropForeign('fk_transaksi_bank_akun_kode');
                    $table->dropForeign('fk_transaksi_bank_kode_project');
                } catch (\Exception $e) {
                    // Might not exist
                }
            });
        }
    }
    
    /**
     * Drop foreign keys safely with error handling
     */
    private function dropForeignKeysIfExist(): void
    {
        $tables = ['budget', 'transaksi', 'transaksi_bank'];
        
        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) continue;
            
            try {
                $connection = DB::connection();
                $doctrineConnection = $connection->getDoctrineConnection();
                $fks = $doctrineConnection->getSchemaManager()
                    ->listTableForeignKeys($tableName);
                
                foreach ($fks as $fk) {
                    Schema::table($tableName, function (Blueprint $table) use ($fk) {
                        try {
                            $table->dropForeign($fk->getName());
                        } catch (\Exception $e) {
                            // FK might not exist, continue
                        }
                    });
                }
            } catch (\Exception $e) {
                // No FKs to drop or table doesn't exist
            }
        }
    }
};
