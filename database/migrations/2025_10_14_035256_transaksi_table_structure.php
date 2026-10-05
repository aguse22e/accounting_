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
        if (!Schema::hasTable('transaksi')) {
            return;
        }
        // Add only the missing columns; do not drop existing ones to avoid DBAL requirements
        Schema::table('transaksi', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi', 'no_akun')) {
                $table->string('no_akun', 255)->nullable()->after('kode_voucer');
            }
            if (!Schema::hasColumn('transaksi', 'akun_kode')) {
                $table->string('akun_kode', 255)->nullable()->after(
                    Schema::hasColumn('transaksi', 'no_akun') ? 'no_akun' : 'kode_voucer'
                );
            }
            if (!Schema::hasColumn('transaksi', 'kode_project')) {
                $table->string('kode_project', 255)->nullable()->after(
                    Schema::hasColumn('transaksi', 'akun_kode') ? 'akun_kode' : 'kode_voucer'
                );
            }
            if (!Schema::hasColumn('transaksi', 'transaksi_type')) {
                $table->string('transaksi_type', 50)->nullable()->after(
                    Schema::hasColumn('transaksi', 'kode_project') ? 'kode_project' : 'kode_voucer'
                );
            }
            if (!Schema::hasColumn('transaksi', 'saldo')) {
                $table->decimal('saldo', 15, 2)->default(0)->after(
                    Schema::hasColumn('transaksi', 'nominal') ? 'nominal' : 'tanggal'
                );
            }
            if (!Schema::hasColumn('transaksi', 'jumlah')) {
                $table->decimal('jumlah', 15, 2)->default(0)->after('saldo');
            }
            if (!Schema::hasColumn('transaksi', 'debit')) {
                $table->decimal('debit', 15, 2)->default(0)->after('jumlah');
            }
            if (!Schema::hasColumn('transaksi', 'kredit')) {
                $table->decimal('kredit', 15, 2)->default(0)->after('debit');
            }
            if (!Schema::hasColumn('transaksi', 'jenis')) {
                $table->string('jenis', 50)->nullable()->after('kredit');
            }
            if (!Schema::hasColumn('transaksi', 'kode_budget')) {
                $table->string('kode_budget', 255)->nullable()->after('jenis');
            }
            if (!Schema::hasColumn('transaksi', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('transaksi', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        // Fix jurnal table structure
        if (Schema::hasTable('jurnal')) {
            Schema::table('jurnal', function (Blueprint $table) {
                // Add missing columns if they don't exist
                if (!Schema::hasColumn('jurnal', 'id_detail')) {
                    $table->integer('id_detail')->nullable()->after('nominal');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best-effort down: drop columns we added if they exist
        // Drop columns individually to support SQLite
        $columns = [
            'no_akun','akun_kode','kode_project','transaksi_type','saldo','jumlah','debit','kredit','jenis','kode_budget','created_at','updated_at'
        ];
        foreach ($columns as $col) {
            if (Schema::hasColumn('transaksi', $col)) {
                Schema::table('transaksi', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
