<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'tipe_transaksi')) {
                $table->enum('tipe_transaksi', ['debit','kredit'])->default('debit')->after('kode_voucher');
            }
            if (!Schema::hasColumn('vouchers', 'tanggal')) {
                // add the column if it does not exist
                $table->timestamp('tanggal')->nullable();
            }

            // Add master fields if they don't already exist
            if (!Schema::hasColumn('vouchers', 'Kode_Voucer')) {
                $table->string('Kode_Voucer', 25)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Kode_Budget')) {
                $table->string('Kode_Budget', 25)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Kode_Project')) {
                $table->string('Kode_Project', 25)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Id_Jurnal')) {
                $table->string('Id_Jurnal', 25)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Id_akun')) {
                $table->string('Id_akun', 25)->nullable();
            }
            // Note: AUTO_INCREMENT cannot be applied to VARCHAR in MySQL; using VARCHAR as requested
            if (!Schema::hasColumn('vouchers', 'Id_Laporan')) {
                $table->string('Id_Laporan', 20)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Id_Buku')) {
                $table->string('Id_Buku', 25)->nullable();
            }
            if (!Schema::hasColumn('vouchers', 'Nama_Akun')) {
                $table->string('Nama_Akun', 50);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'tipe_transaksi')) {
                $table->dropColumn('tipe_transaksi');
            }
            if (Schema::hasColumn('vouchers', 'tanggal')) {
                $table->dropColumn('tanggal');
            }
            if (Schema::hasColumn('vouchers', 'Kode_Voucer')) {
                $table->dropColumn('Kode_Voucer');
            }
            if (Schema::hasColumn('vouchers', 'Kode_Budget')) {
                $table->dropColumn('Kode_Budget');
            }
            if (Schema::hasColumn('vouchers', 'Kode_Project')) {
                $table->dropColumn('Kode_Project');
            }
            if (Schema::hasColumn('vouchers', 'Id_Jurnal')) {
                $table->dropColumn('Id_Jurnal');
            }
            if (Schema::hasColumn('vouchers', 'Id_akun')) {
                $table->dropColumn('Id_akun');
            }
            if (Schema::hasColumn('vouchers', 'Id_Laporan')) {
                $table->dropColumn('Id_Laporan');
            }
            if (Schema::hasColumn('vouchers', 'Id_Buku')) {
                $table->dropColumn('Id_Buku');
            }
            if (Schema::hasColumn('vouchers', 'Nama_Akun')) {
                $table->dropColumn('Nama_Akun');
            }
        });
    }
};


