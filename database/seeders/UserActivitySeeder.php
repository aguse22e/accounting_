<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserActivitySeeder extends Seeder
{
    public function run()
    {
        DB::table('user_activity')->insert([
            [
                'id_user' => 1,
                'jenis_transaksi' => 'Kas',
                'tipe' => 'Insert',
                'keterangan' => 'Berhasil menambahkan Transaksi Kas dengan kode BKM-25-05-1110-001 dengan total 15000000.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 1,
                'jenis_transaksi' => 'Kas',
                'tipe' => 'Insert',
                'keterangan' => 'Berhasil menambahkan Transaksi Kas dengan kode BKK-25-05-1110-001 dengan total 1500000.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 1,
                'jenis_transaksi' => 'Kas',
                'tipe' => 'Insert',
                'keterangan' => 'Berhasil menambahkan Transaksi Kas dengan kode BKK-25-05-1110-002 dengan total 1250000.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 1,
                'jenis_transaksi' => 'Kas',
                'tipe' => 'Insert',
                'keterangan' => 'Berhasil menambahkan Transaksi Kas dengan kode BKM-25-06-1000-001 dengan total 100000.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 1,
                'jenis_transaksi' => 'Kas',
                'tipe' => 'Update',
                'keterangan' => 'Berhasil memperbarui/menambahkan data baru Transaksi Kas dengan kode BKM-25-06-1000-001 dengan total 150000.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
