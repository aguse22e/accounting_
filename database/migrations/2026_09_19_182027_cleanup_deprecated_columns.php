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
        // MIGRASI 4: Hapus kolom deprecated

        // 1. Hapus kolom kode_projek dari tabel budget (deprecated, diganti kode_project)
        if (Schema::hasColumn('budget', 'kode_projek')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropColumn('kode_projek');
            });
        }

        // 2. Hapus kolom tipe dari tabel transaksi (deprecated, diganti transaksi_type)
        if (Schema::hasColumn('transaksi', 'tipe')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }

        // 3. Hapus kolom create_at dan update_at dari tabel transaksi (duplicate dengan created_at/updated_at)
        if (Schema::hasColumn('transaksi', 'create_at')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('create_at');
            });
        }

        if (Schema::hasColumn('transaksi', 'update_at')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('update_at');
            });
        }

        // 4. Hapus kolom create_at dan update_at dari tabel budget (duplicate dengan created_at/updated_at)
        if (Schema::hasColumn('budget', 'create_at')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropColumn('create_at');
            });
        }

        if (Schema::hasColumn('budget', 'update_at')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropColumn('update_at');
            });
        }

        echo "Kolom deprecated dihapus." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore kolom deprecated untuk rollback
        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_projek')->nullable()->after('kode_project');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('tipe')->nullable()->after('transaksi_type');
            $table->timestamp('create_at')->useCurrent()->after('tanggal');
            $table->timestamp('update_at')->useCurrent()->after('create_at');
        });

        Schema::table('budget', function (Blueprint $table) {
            $table->timestamp('create_at')->useCurrent()->after('tanggal');
            $table->timestamp('update_at')->useCurrent()->after('create_at');
        });

        echo "Kolom deprecated direstore." . PHP_EOL;
    }
};
