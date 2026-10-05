<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Akun;

class AkunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // I. ASET - Aset Lancar
            ['kode_akun' => '1101', 'nama_akun' => 'Kas dan Setara Kas', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1102', 'nama_akun' => 'Piutang Usaha', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1103', 'nama_akun' => 'Persediaan', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1104', 'nama_akun' => 'Biaya Dibayar Dimuka', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1105', 'nama_akun' => 'Aset Lancar Lainnya', 'saldo_normal' => 'debit'],
            
            // I. ASET - Aset Tetap
            ['kode_akun' => '1201', 'nama_akun' => 'Tanah', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1202', 'nama_akun' => 'Bangunan', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1203', 'nama_akun' => 'Mesin dan Peralatan', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1204', 'nama_akun' => 'Kendaraan', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1299', 'nama_akun' => 'Akumulasi Penyusutan', 'saldo_normal' => 'kredit'],
            
            // I. ASET - Aset Lainnya
            ['kode_akun' => '1301', 'nama_akun' => 'Investasi Jangka Panjang', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1302', 'nama_akun' => 'Aset Tak Berwujud', 'saldo_normal' => 'debit'],
            ['kode_akun' => '1303', 'nama_akun' => 'Aset Lainnya', 'saldo_normal' => 'debit'],
            
            // II. LIABILITAS
            ['kode_akun' => '2001', 'nama_akun' => 'Hutang Usaha', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2002', 'nama_akun' => 'Hutang Dagang', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2003', 'nama_akun' => 'Hutang Biaya', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2004', 'nama_akun' => 'Hutang Pajak', 'saldo_normal' => 'kredit'],
            
            ['kode_akun' => '2101', 'nama_akun' => 'Utang Usaha', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2102', 'nama_akun' => 'Utang Pajak', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2103', 'nama_akun' => 'Utang Jangka Panjang', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '2104', 'nama_akun' => 'Beban Dibayar Dimuka', 'saldo_normal' => 'kredit'],
            
            // III. EKUITAS
            ['kode_akun' => '3001', 'nama_akun' => 'Modal Ditempatkan', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3002', 'nama_akun' => 'Modal Belum Ditempatkan', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3003', 'nama_akun' => 'Agio Disagio', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3004', 'nama_akun' => 'Laba Rugi Berjalan', 'saldo_normal' => 'kredit'],
            
            ['kode_akun' => '3101', 'nama_akun' => 'Modal Saham', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3102', 'nama_akun' => 'Laba Ditahan', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3103', 'nama_akun' => 'Ekuitas Lainnya', 'saldo_normal' => 'kredit'],
            ['kode_akun' => '3104', 'nama_akun' => 'Selisih Kurs', 'saldo_normal' => 'kredit'],
        ];

        foreach ($accounts as $account) {
            Akun::updateOrCreate(
                ['kode_akun' => $account['kode_akun']],
                $account
            );
        }

        $this->command->info('Akun seeded successfully!');
    }
}

