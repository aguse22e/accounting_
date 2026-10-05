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
        // MIGRASI 3: Tambah foreign key constraints untuk referential integrity

        // 1. Cleanup orphan records di budget (akun yang tidak ada di kode_akun)
        DB::statement('DELETE FROM budget WHERE kode_akun NOT IN (SELECT kode_akun FROM kode_akun)');

        // 2. Cleanup orphan records di budget (project yang tidak ada di projects)
        DB::statement('DELETE FROM budget WHERE kode_project NOT IN (SELECT kode_project FROM projects)');

        // 3. Cleanup orphan records di transaksi (akun yang tidak ada di kode_akun)
        DB::statement('DELETE FROM transaksi WHERE akun_kode NOT IN (SELECT kode_akun FROM kode_akun)');

        // 4. Cleanup orphan records di transaksi (project yang tidak ada di projects)
        DB::statement('DELETE FROM transaksi WHERE kode_project IS NOT NULL AND kode_project NOT IN (SELECT kode_project FROM projects)');

        // 5. Tambah foreign key ke tabel budget
        Schema::table('budget', function (Blueprint $table) {
            // Hapus foreign key lama jika ada
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('budget');
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getLocalColumns()[0] === 'kode_akun' || $foreignKey->getLocalColumns()[0] === 'kode_project') {
                    $table->dropForeign($foreignKey->getName());
                }
            }

            // Foreign key ke kode_akun
            $table->foreign('kode_akun', 'budget_kode_akun_foreign')
                ->references('kode_akun')
                ->on('kode_akun')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Foreign key ke projects
            $table->foreign('kode_project', 'budget_kode_project_foreign')
                ->references('kode_project')
                ->on('projects')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // 6. Tambah foreign key ke tabel transaksi
        Schema::table('transaksi', function (Blueprint $table) {
            // Hapus foreign key lama jika ada
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('transaksi');
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->getLocalColumns()[0] === 'akun_kode' || $foreignKey->getLocalColumns()[0] === 'kode_project') {
                    $table->dropForeign($foreignKey->getName());
                }
            }

            // Foreign key ke kode_akun
            $table->foreign('akun_kode', 'transaksi_akun_kode_foreign')
                ->references('kode_akun')
                ->on('kode_akun')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Foreign key ke projects (nullable)
            $table->foreign('kode_project', 'transaksi_kode_project_foreign')
                ->references('kode_project')
                ->on('projects')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // 7. Tambah foreign key ke tabel transaksi_bank jika ada kolom kode_project
        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                // Hapus foreign key lama jika ada
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('transaksi_bank');
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->getLocalColumns()[0] === 'akun_kode' || $foreignKey->getLocalColumns()[0] === 'kode_project') {
                        $table->dropForeign($foreignKey->getName());
                    }
                }

                // Foreign key ke kode_akun
                $table->foreign('akun_kode', 'transaksi_bank_akun_kode_foreign')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                // Foreign key ke projects (nullable)
                $table->foreign('kode_project', 'transaksi_bank_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }

        echo "Foreign key constraints ditambahkan." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('budget', function (Blueprint $table) {
            $table->dropForeign('budget_kode_akun_foreign');
            $table->dropForeign('budget_kode_project_foreign');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign('transaksi_akun_kode_foreign');
            $table->dropForeign('transaksi_kode_project_foreign');
        });

        if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->dropForeign('transaksi_bank_akun_kode_foreign');
                $table->dropForeign('transaksi_bank_kode_project_foreign');
            });
        }

        echo "Foreign key constraints dihapus." . PHP_EOL;
    }
};
