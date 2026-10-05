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
        // MIGRASI 2: Standarisasi project code ke format PROJXXXX
        // Format standar: PROJ + 4 digit angka (PROJ0001, PROJ0002, dst)

        // 1. Backup data projects
        Schema::dropIfExists('projects_backup');
        Schema::create('projects_backup', function (Blueprint $table) {
            $table->id();
            $table->string('kode_project');
            $table->string('nama_project');
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamp('tanggal')->nullable();
            $table->string('kode_akun')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO projects_backup (kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at)
                        SELECT kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at FROM projects');

        // 2. Drop foreign key constraints yang menghalangi (jika ada)
        if (Schema::hasTable('bug')) {
            try {
                Schema::table('bug', function (Blueprint $table) {
                    $table->dropForeign('bug_kode_project_foreign');
                });
            } catch (\Exception $e) {
                // Foreign key mungkin sudah dihapus, lanjut saja
            }
        }

        // 3. Ubah tipe data kode_project ke string di semua tabel
        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project')->change();
        });

        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_project')->change();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('kode_project')->nullable()->change();
        });

        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('kode_project')->nullable()->change();
            });
        }

        // 4. Buat mapping project code lama ke baru
        // Check what codes actually exist in the database first
        $existingCodes = DB::table('projects')->pluck('kode_project')->toArray();
        $projectMapping = [];

        // Only map codes that actually exist
        if (in_array('PRJIP-033888', $existingCodes)) {
            $projectMapping['PRJIP-033888'] = 'PROJ0001';
        }
        if (in_array('prj 002', $existingCodes)) {
            $projectMapping['prj 002'] = 'PROJ0002';
        }
        if (in_array('PRJIP-0338889', $existingCodes)) {
            $projectMapping['PRJIP-0338889'] = 'PROJ0003';
        }
        if (in_array('1', $existingCodes)) {
            $projectMapping['1'] = 'PROJ0004';
        }
        if (in_array('2', $existingCodes)) {
            $projectMapping['2'] = 'PROJ0005';
        }
        if (in_array('PROJ001', $existingCodes)) {
            $projectMapping['PROJ001'] = 'PROJ0006';
        }

        // If no mapping needed (codes already in PROJXXXX format), skip mapping
        if (empty($projectMapping)) {
            echo "No project code mapping needed - codes already in PROJXXXX format." . PHP_EOL;
        }

        // 5. Update project codes di tabel projects
        foreach ($projectMapping as $oldCode => $newCode) {
            DB::table('projects')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // 6. Update project codes di tabel budget
        foreach ($projectMapping as $oldCode => $newCode) {
            DB::table('budget')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // 7. Update project codes di tabel transaksi
        foreach ($projectMapping as $oldCode => $newCode) {
            DB::table('transaksi')
                ->where('kode_project', $oldCode)
                ->update(['kode_project' => $newCode]);
        }

        // 8. Update project codes di tabel transaksi_bank (jika ada kolom)
        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            foreach ($projectMapping as $oldCode => $newCode) {
                DB::table('transaksi_bank')
                    ->where('kode_project', $oldCode)
                    ->update(['kode_project' => $newCode]);
            }
        }

        // 9. Tambah constraint untuk format standar
        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project', 8)->change();
        });

        // Add unique constraint only if it doesn't exist
        $uniqueExists = DB::select("SELECT COUNT(*) as count FROM information_schema.table_constraints
            WHERE constraint_schema = DATABASE()
            AND table_name = 'projects'
            AND constraint_name = 'projects_kode_project_unique'
            AND constraint_type = 'UNIQUE'");

        if ($uniqueExists[0]->count == 0) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unique('kode_project');
            });
        }

        echo "Standarisasi project code selesai. Format baru: PROJXXXX" . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Restore dari backup
        DB::statement('TRUNCATE TABLE projects');
        DB::statement('INSERT INTO projects (kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at)
                        SELECT kode_project, nama_project, saldo, tanggal, kode_akun, created_at, updated_at FROM projects_backup');

        // Rollback budget - use reverse mapping from actual data
        $reverseMapping = array_flip($projectMapping);
        foreach ($reverseMapping as $new => $old) {
            DB::table('budget')
                ->where('kode_project', $new)
                ->update(['kode_project' => $old]);
        }

        // Rollback transaksi - use reverse mapping from actual data
        foreach ($reverseMapping as $new => $old) {
            DB::table('transaksi')
                ->where('kode_project', $new)
                ->update(['kode_project' => $old]);
        }

        // Rollback transaksi_bank jika ada - use reverse mapping from actual data
        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            foreach ($reverseMapping as $new => $old) {
                DB::table('transaksi_bank')
                    ->where('kode_project', $new)
                    ->update(['kode_project' => $old]);
            }
        }

        // Kembalikan tipe data
        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project')->change();
        });

        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_project')->change();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('kode_project')->nullable()->change();
        });

        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('kode_project')->nullable()->change();
            });
        }

        // Restore foreign key constraint di bug table
        if (Schema::hasTable('bug')) {
            Schema::table('bug', function (Blueprint $table) {
                $table->foreign('kode_project', 'bug_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        // Hapus backup
        Schema::dropIfExists('projects_backup');

        echo "Rollback standarisasi project code selesai." . PHP_EOL;
    }
};
