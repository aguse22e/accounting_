<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KodeAkun;

class KodeAkunSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            // Assets
            ['1101', 'Kas dan Setara Kas', 'Debit'],
            ['1102', 'Piutang Usaha', 'Debit'],
            ['1103', 'Persediaan', 'Debit'],
            ['1104', 'Biaya Dibayar Dimuka', 'Debit'],
            ['1105', 'Aset Lancar Lainnya', 'Debit'],
            ['1201', 'Tanah', 'Debit'],
            ['1202', 'Bangunan', 'Debit'],
            ['1203', 'Mesin dan Peralatan', 'Debit'],
            ['1204', 'Kendaraan', 'Debit'],
            ['1299', 'Akumulasi Penyusutan Aset Tetap', 'Kredit'],
            ['1301', 'Investasi Jangka Panjang', 'Debit'],
            ['1302', 'Aset Tak Berwujud (Goodwill, Hak Paten)', 'Debit'],
            ['1303', 'Aset Lainnya', 'Debit'],
            // Liabilities
            ['2101', 'Utang Usaha', 'Kredit'],
            ['2102', 'Utang Pajak', 'Kredit'],
            ['2103', 'Utang Jangka Panjang', 'Kredit'],
            ['2104', 'Beban yang Masih Harus Dibayar', 'Kredit'],
            // Equity
            ['3101', 'Modal Saham', 'Kredit'],
            ['3102', 'Laba Ditahan', 'Kredit'],
            ['3103', 'Ekuitas Lainnya', 'Kredit'],
        ];

        foreach ($accounts as $account) {
            KodeAkun::create([
                'kode_akun' => $account[0],
                'nama' => $account[1],
                'tipe' => $account[2],
            ]);
        }
    }
}
