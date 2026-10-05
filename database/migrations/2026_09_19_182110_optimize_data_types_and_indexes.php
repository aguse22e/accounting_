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
        // MIGRASI 5: Optimalisasi tipe data dan index

        // Drop foreign keys temporarily to allow column type changes (with error handling)
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropForeign('budget_kode_akun_foreign');
                $table->dropForeign('budget_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign keys might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropForeign('transaksi_akun_kode_foreign');
                $table->dropForeign('transaksi_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign keys might not exist, continue
        }

        if (Schema::hasTable('transaksi_bank')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->dropForeign('transaksi_bank_akun_kode_foreign');
                    $table->dropForeign('transaksi_bank_kode_project_foreign');
                });
            } catch (\Exception $e) {
                // Foreign keys might not exist, continue
            }
        }

        // 1. Tabel kode_akun - Optimize VARCHAR lengths
        Schema::table('kode_akun', function (Blueprint $table) {
            $table->string('kode_akun', 4)->change(); // Kode akun 4 digit
            $table->string('tipe', 6)->change(); // 'Debit' atau 'Kredit'
        });

        // 2. Tabel akun - Optimize VARCHAR lengths dan tipe saldo_normal
        Schema::table('akun', function (Blueprint $table) {
            $table->string('kode_akun', 4)->change();
        });

        // Use raw SQL for ENUM changes to avoid Doctrine DBAL issues
        DB::statement("ALTER TABLE akun MODIFY COLUMN saldo_normal ENUM('debit', 'kredit') DEFAULT 'debit'");

        // 3. Tabel projects - Optimize kode_project
        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project', 8)->change(); // PROJXXXX
        });

        // 4. Tabel budget - Optimize VARCHAR lengths
        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_budget', 20)->change();
            $table->string('kode_akun', 4)->change();
            $table->string('kode_project', 8)->change();
        });

        // 5. Tabel transaksi - Optimize VARCHAR lengths dan tambah constraints
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('kode_voucer', 20)->change();
            $table->string('akun_kode', 4)->change();
            $table->string('kode_project', 8)->nullable()->change();
            $table->string('kode_budget', 20)->nullable()->change();
        });

        // Use raw SQL for ENUM changes to avoid Doctrine DBAL issues
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type ENUM('Debit', 'Kredit') NULL");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN jenis ENUM('debit', 'kredit') NULL");

        // 6. Tabel transaksi_bank - Optimize jika ada
        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('akun_kode', 4)->change();
                $table->string('kode_project', 8)->nullable()->change();
            });

            // Use raw SQL for ENUM changes to avoid Doctrine DBAL issues
            DB::statement("ALTER TABLE transaksi_bank MODIFY COLUMN tipe ENUM('Debit', 'Kredit')");
        }

        // 7. Tambah NOT NULL constraint untuk kolom penting
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN akun_kode VARCHAR(4) NOT NULL");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type ENUM('Debit', 'Kredit') NOT NULL");

        // 8. Optimize index - Hapus index redundant di transaksi (with error handling)
        try {
            Schema::table('transaksi', function (Blueprint $table) {
                // Hapus index individual yang akan diganti dengan composite
                $table->dropIndex(['idx_transaksi_tanggal']);
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropIndex(['idx_transaksi_type_tanggal']);
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        // Tambah composite index yang lebih optimal (with error handling)
        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->index(['akun_kode', 'tanggal'], 'idx_transaksi_akun_tanggal');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->index(['kode_project', 'tanggal'], 'idx_transaksi_project_tanggal');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // 9. Tambah index untuk kode_akun di semua tabel yang merefer (with error handling)
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->index(['kode_akun', 'kode_project'], 'idx_budget_akun_project');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        // 10. Restore foreign keys after column type changes (with error handling)
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->foreign('kode_akun', 'budget_kode_akun_foreign')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('kode_project', 'budget_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign keys might already exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('akun_kode', 'transaksi_akun_kode_foreign')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('kode_project', 'transaksi_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign keys might already exist, continue
        }

        if (Schema::hasTable('transaksi_bank')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->foreign('akun_kode', 'transaksi_bank_akun_kode_foreign')
                        ->references('kode_akun')
                        ->on('kode_akun')
                        ->onDelete('restrict')
                        ->onUpdate('cascade');

                    $table->foreign('kode_project', 'transaksi_bank_kode_project_foreign')
                        ->references('kode_project')
                        ->on('projects')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // Foreign keys might already exist, continue
            }
        }

        echo "Optimalisasi tipe data dan index selesai." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first (with error handling)
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropForeign('budget_kode_akun_foreign');
                $table->dropForeign('budget_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign keys might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropForeign('transaksi_akun_kode_foreign');
                $table->dropForeign('transaksi_kode_project_foreign');
            });
        } catch (\Exception $e) {
            // Foreign keys might not exist, continue
        }

        if (Schema::hasTable('transaksi_bank')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->dropForeign('transaksi_bank_akun_kode_foreign');
                    $table->dropForeign('transaksi_bank_kode_project_foreign');
                });
            } catch (\Exception $e) {
                // Foreign keys might not exist, continue
            }
        }

        // Rollback tipe data
        Schema::table('kode_akun', function (Blueprint $table) {
            $table->string('kode_akun')->change();
            $table->string('tipe')->change();
        });

        Schema::table('akun', function (Blueprint $table) {
            $table->string('kode_akun')->change();
        });

        // Use raw SQL for ENUM rollback
        DB::statement("ALTER TABLE akun MODIFY COLUMN saldo_normal VARCHAR(255) DEFAULT 'debit'");

        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project')->change();
        });

        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_budget')->change();
            $table->string('kode_akun')->change();
            $table->string('kode_project')->change();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('kode_voucer')->change();
            $table->string('akun_kode')->nullable()->change();
            $table->string('kode_project')->nullable()->change();
            $table->string('kode_budget')->nullable()->change();
        });

        // Use raw SQL for ENUM rollback
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN transaksi_type VARCHAR(50) NULL");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN jenis VARCHAR(50) NULL");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN akun_kode VARCHAR(25) NULL");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN kode_project VARCHAR(255) NULL");

        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('akun_kode')->change();
                $table->string('kode_project')->nullable()->change();
            });

            // Use raw SQL for ENUM rollback
            DB::statement("ALTER TABLE transaksi_bank MODIFY COLUMN tipe VARCHAR(50)");
        }

        // Restore index (with error handling)
        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropIndex('idx_transaksi_akun_tanggal');
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropIndex('idx_transaksi_project_tanggal');
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->index('tanggal', 'idx_transaksi_tanggal');
                $table->index(['transaksi_type', 'tanggal'], 'idx_transaksi_type_tanggal');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropIndex('idx_budget_akun_project');
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        // Restore foreign keys (with error handling)
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->foreign('kode_akun', 'budget_kode_akun_foreign')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('kode_project', 'budget_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign keys might already exist, continue
        }

        try {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->foreign('akun_kode', 'transaksi_akun_kode_foreign')
                    ->references('kode_akun')
                    ->on('kode_akun')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('kode_project', 'transaksi_kode_project_foreign')
                    ->references('kode_project')
                    ->on('projects')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        } catch (\Exception $e) {
            // Foreign keys might already exist, continue
        }

        if (Schema::hasTable('transaksi_bank')) {
            try {
                Schema::table('transaksi_bank', function (Blueprint $table) {
                    $table->foreign('akun_kode', 'transaksi_bank_akun_kode_foreign')
                        ->references('kode_akun')
                        ->on('kode_akun')
                        ->onDelete('restrict')
                        ->onUpdate('cascade');

                    $table->foreign('kode_project', 'transaksi_bank_kode_project_foreign')
                        ->references('kode_project')
                        ->on('projects')
                        ->onDelete('set null')
                        ->onUpdate('cascade');
                });
            } catch (\Exception $e) {
                // Foreign keys might already exist, continue
            }
        }

        echo "Rollback optimalisasi tipe data dan index selesai." . PHP_EOL;
    }
};
