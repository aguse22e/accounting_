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
        // Drop foreign keys dulu
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropForeign('budget_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropForeign('transaksi_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'kode_project')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->dropForeign('transaksi_bank_kode_project_foreign');
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }

        // Backup current projects
        DB::statement('CREATE TABLE projects_backup_v3 AS SELECT * FROM projects');

        // Generate mapping of old codes to new codes
        $projects = DB::table('projects')->get();
        $mapping = [];

        foreach ($projects as $project) {
            $oldCode = $project->kode_project;
            $newCode = $this->generateVariableCode();

            // Ensure uniqueness
            while (DB::table('projects')->where('kode_project', $newCode)->exists()) {
                $newCode = $this->generateVariableCode();
            }

            $mapping[$oldCode] = $newCode;

            // Update projects table
            DB::table('projects')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // Update budget table
        foreach ($mapping as $oldCode => $newCode) {
            DB::table('budget')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // Update transaksi table
        foreach ($mapping as $oldCode => $newCode) {
            DB::table('transaksi')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // Update transaksi_bank table if exists
        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'kode_project')) {
            foreach ($mapping as $oldCode => $newCode) {
                DB::table('transaksi_bank')
                    ->where('kode_project', $oldCode)
                    ->update(['kode_project' => $newCode]);
            }
        }

        // Restore foreign keys
        Schema::table('budget', function (Blueprint $table) {
            $table->foreign('kode_project', 'budget_kode_project_foreign')
                ->references('kode_project')
                ->on('projects')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreign('kode_project', 'transaksi_kode_project_foreign')
                ->references('kode_project')
                ->on('projects')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'kode_project')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->foreign('kode_project', 'transaksi_bank_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        echo "Project codes changed to variable format (2-4 letters + 2-6 digits)." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys dulu
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropForeign('budget_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropForeign('transaksi_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'kode_project')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->dropForeign('transaksi_bank_kode_project_foreign');
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }

        // Restore dari backup
        DB::statement('TRUNCATE TABLE projects');
        DB::statement('INSERT INTO projects (kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at)
                        SELECT kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at FROM projects_backup_v3');

        // Rollback budget dan transaksi perlu manual mapping
        echo "Rollback hanya untuk tabel projects. Budget dan transaksi perlu manual mapping." . PHP_EOL;

        // Restore foreign keys
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->foreign('kode_project', 'budget_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('kode_project', 'transaksi_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, continue
        }

        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'kode_project')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->foreign('kode_project', 'transaksi_bank_kode_project_foreign')
                        ->references('kode_project')
                        ->on('projects')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist, continue
            }
        }

        // Hapus backup
        Schema::dropIfExists('projects_backup_v3');
    }

    /**
     * Generate a random code with 2-4 uppercase letters and 2-6 digits
     */
    private function generateVariableCode(): string
    {
        $letterCount = rand(2, 4);
        $digitCount = rand(2, 6);

        $letters = '';
        for ($i = 0; $i < $letterCount; $i++) {
            $letters .= chr(rand(65, 90)); // A-Z
        }

        $digits = '';
        for ($i = 0; $i < $digitCount; $i++) {
            $digits .= rand(0, 9);
        }

        return $letters . $digits;
    }
};
