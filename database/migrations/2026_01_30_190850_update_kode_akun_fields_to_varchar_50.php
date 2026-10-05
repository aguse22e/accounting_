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
        // Update transaksi_bank table: akun_kode to VARCHAR(50)
        if (Schema::hasTable('transaksi_bank') && Schema::hasColumn('transaksi_bank', 'akun_kode')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('akun_kode', 50)->change();
            });
        }

        // Update jurnal table: kode to VARCHAR(50) - only if table exists
        if (Schema::hasTable('jurnal') && Schema::hasColumn('jurnal', 'kode')) {
            Schema::table('jurnal', function (Blueprint $table) {
                $table->string('kode', 50)->change();
            });
        }

        // Update buku_besar table: Id_akun is already VARCHAR(50), no change needed
        // The buku_besar table already has Id_akun as string(50) from creation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert transaksi_bank table: akun_kode back to default VARCHAR
        if (Schema::hasTable('transaksi_bank')) {
            Schema::table('transaksi_bank', function (Blueprint $table) {
                $table->string('akun_kode')->change();
            });
        }

        // Revert jurnal table: kode back to VARCHAR(25)
        if (Schema::hasTable('jurnal')) {
            Schema::table('jurnal', function (Blueprint $table) {
                $table->string('kode', 25)->change();
            });
        }

        // buku_besar table: Id_akun remains VARCHAR(50), no change in down migration
    }
};
