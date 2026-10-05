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
        // MIGRASI 1: Konsolidasi tabel akun ke kode_akun (single source of truth)
        // Langkah-langkah untuk migrasi yang aman:

        // 1. Backup data dari tabel akun
        Schema::create('akun_backup', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun')->unique();
            $table->string('nama_akun');
            $table->string('saldo_normal')->default('debit');
            $table->timestamp('tanggal')->nullable();
            $table->string('deskripsi')->nullable();
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->timestamps();
        });

        DB::statement('INSERT INTO akun_backup (kode_akun, nama_akun, saldo_normal, tanggal, deskripsi, saldo_awal, created_at, updated_at)
                        SELECT kode_akun, nama_akun, saldo_normal, tanggal, deskripsi, saldo_awal, created_at, updated_at FROM akun');

        // 2. Migrasi data unik dari akun ke kode_akun
        $akunData = DB::table('akun_backup')->get();

        foreach ($akunData as $akun) {
            // Cek apakah kode sudah ada di kode_akun
            $existing = DB::table('kode_akun')->where('kode_akun', $akun->kode_akun)->first();

            if (!$existing) {
                // Insert baru jika belum ada
                DB::table('kode_akun')->insert([
                    'kode_akun' => $akun->kode_akun,
                    'nama' => $akun->nama_akun,
                    'tipe' => $akun->saldo_normal,
                    'created_at' => $akun->created_at,
                    'updated_at' => $akun->updated_at,
                ]);
            } else {
                // Update jika sudah ada (ambil data yang lebih lengkap)
                DB::table('kode_akun')
                    ->where('kode_akun', $akun->kode_akun)
                    ->update([
                        'nama' => $akun->nama_akun,
                        'tipe' => $akun->saldo_normal,
                        'updated_at' => now(),
                    ]);
            }
        }

        // 3. Update referensi di tabel budget
        DB::statement('UPDATE budget SET kode_akun = SUBSTRING(kode_akun, 1, 4) WHERE LENGTH(kode_akun) > 4');

        // 4. Update referensi di tabel transaksi (jika perlu)
        // DB::statement('UPDATE transaksi SET akun_kode = SUBSTRING(akun_kode, 1, 4) WHERE LENGTH(akun_kode) > 4');

        echo "Migration konsolidasi akun selesai. Backup disimpan di tabel akun_backup." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Restore data dari backup dan hapus kode_akun yang baru ditambah
        // Hapus data kode_akun yang ditambah selama migrasi ini
        $backupCodes = DB::table('akun_backup')->pluck('kode_akun')->toArray();
        DB::table('kode_akun')->whereIn('kode_akun', $backupCodes)->delete();

        // Restore data dari backup ke tabel akun asli (gunakan ON DUPLICATE KEY UPDATE)
        DB::statement('INSERT INTO akun (kode_akun, nama_akun, saldo_normal, tanggal, deskripsi, saldo_awal, created_at, updated_at)
                        SELECT kode_akun, nama_akun, saldo_normal, tanggal, deskripsi, saldo_awal, created_at, updated_at FROM akun_backup
                        ON DUPLICATE KEY UPDATE nama_akun = VALUES(nama_akun), saldo_normal = VALUES(saldo_normal), tanggal = VALUES(tanggal), deskripsi = VALUES(deskripsi), saldo_awal = VALUES(saldo_awal), updated_at = VALUES(updated_at)');

        // Hapus tabel backup
        Schema::dropIfExists('akun_backup');

        echo "Rollback konsolidasi akun selesai." . PHP_EOL;
    }
};
