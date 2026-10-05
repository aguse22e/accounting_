<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KodeAkun;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // I. ASET - Aset Lancar
            ['kode_akun' => '1101', 'nama' => 'Kas dan Setara Kas', 'tipe' => 'Debit'],
            ['kode_akun' => '1102', 'nama' => 'Piutang Usaha', 'tipe' => 'Debit'],
            ['kode_akun' => '1103', 'nama' => 'Persediaan', 'tipe' => 'Debit'],
            ['kode_akun' => '1104', 'nama' => 'Biaya Dibayar Dimuka', 'tipe' => 'Debit'],
            ['kode_akun' => '1105', 'nama' => 'Aset Lancar Lainnya', 'tipe' => 'Debit'],
            
            // I. ASET - Aset Tetap
            ['kode_akun' => '1201', 'nama' => 'Tanah', 'tipe' => 'Debit'],
            ['kode_akun' => '1202', 'nama' => 'Bangunan', 'tipe' => 'Debit'],
            ['kode_akun' => '1203', 'nama' => 'Mesin dan Peralatan', 'tipe' => 'Debit'],
            ['kode_akun' => '1204', 'nama' => 'Kendaraan', 'tipe' => 'Debit'],
            ['kode_akun' => '1299', 'nama' => 'Akumulasi Penyusutan Aset Tetap', 'tipe' => 'Kredit'],
            
            // I. ASET - Aset Lainnya
            ['kode_akun' => '1301', 'nama' => 'Investasi Jangka Panjang', 'tipe' => 'Debit'],
            ['kode_akun' => '1302', 'nama' => 'Aset Tak Berwujud (Goodwill, Hak Paten)', 'tipe' => 'Debit'],
            ['kode_akun' => '1303', 'nama' => 'Aset Lainnya', 'tipe' => 'Debit'],
            
            // II. LIABILITAS
            ['kode_akun' => '2001', 'nama' => 'Liabilitas Lancar - Hutang Usaha', 'tipe' => 'Kredit'],
            ['kode_akun' => '2002', 'nama' => 'Liabilitas Lancar - Hutang Dagang', 'tipe' => 'Kredit'],
            ['kode_akun' => '2003', 'nama' => 'Liabilitas Lancar - Hutang Biaya', 'tipe' => 'Kredit'],
            ['kode_akun' => '2004', 'nama' => 'Liabilitas Lancar - Hutang Pajak', 'tipe' => 'Kredit'],
            
            ['kode_akun' => '2101', 'nama' => 'Utang Usaha', 'tipe' => 'Kredit'],
            ['kode_akun' => '2102', 'nama' => 'Utang Pajak', 'tipe' => 'Kredit'],
            ['kode_akun' => '2103', 'nama' => 'Utang Jangka Panjang', 'tipe' => 'Kredit'],
            ['kode_akun' => '2104', 'nama' => 'Beban yang Masih Harus Dibayar', 'tipe' => 'Kredit'],
            
            // III. EKUITAS
            ['kode_akun' => '3001', 'nama' => 'Ekuitas - Modal Ditempatkan', 'tipe' => 'Kredit'],
            ['kode_akun' => '3002', 'nama' => 'Ekuitas - Modal Belum Ditempatkan', 'tipe' => 'Kredit'],
            ['kode_akun' => '3003', 'nama' => 'Ekuitas - Agio Disagio Saham', 'tipe' => 'Kredit'],
            ['kode_akun' => '3004', 'nama' => 'Ekuitas - Laba Rugi Tahun Berjalan', 'tipe' => 'Kredit'],
            
            ['kode_akun' => '3101', 'nama' => 'Modal Saham', 'tipe' => 'Kredit'],
            ['kode_akun' => '3102', 'nama' => 'Laba Ditahan', 'tipe' => 'Kredit'],
            ['kode_akun' => '3103', 'nama' => 'Ekuitas Lainnya', 'tipe' => 'Kredit'],
            ['kode_akun' => '3104', 'nama' => 'Selisih Kurs', 'tipe' => 'Kredit'],
            
            // IV. PENDAPATAN (4xxx)
            ['kode_akun' => '4101', 'nama' => 'Pendapatan Penjualan', 'tipe' => 'Kredit'],
            ['kode_akun' => '4102', 'nama' => 'Pendapatan Jasa', 'tipe' => 'Kredit'],
            ['kode_akun' => '4103', 'nama' => 'Pendapatan Sewa', 'tipe' => 'Kredit'],
            ['kode_akun' => '4104', 'nama' => 'Pendapatan Bunga', 'tipe' => 'Kredit'],
            ['kode_akun' => '4105', 'nama' => 'Pendapatan Lain-lain', 'tipe' => 'Kredit'],
            ['kode_akun' => '4201', 'nama' => 'Pendapatan Operasional', 'tipe' => 'Kredit'],
            ['kode_akun' => '4202', 'nama' => 'Pendapatan Non-Operasional', 'tipe' => 'Kredit'],
            ['kode_akun' => '4301', 'nama' => 'Retur Penjualan', 'tipe' => 'Debit'],
            ['kode_akun' => '4302', 'nama' => 'Potongan Penjualan', 'tipe' => 'Debit'],
            ['kode_akun' => '4401', 'nama' => 'Pendapatan Komisi', 'tipe' => 'Kredit'],
            ['kode_akun' => '4402', 'nama' => 'Pendapatan Royalti', 'tipe' => 'Kredit'],
            ['kode_akun' => '4501', 'nama' => 'Pendapatan Dividen', 'tipe' => 'Kredit'],
            ['kode_akun' => '4502', 'nama' => 'Pendapatan Investasi', 'tipe' => 'Kredit'],
            
            // V. BEBAN (5xxx)
            ['kode_akun' => '5101', 'nama' => 'Beban Pokok Penjualan', 'tipe' => 'Debit'],
            ['kode_akun' => '5102', 'nama' => 'Beban Pembelian', 'tipe' => 'Debit'],
            ['kode_akun' => '5103', 'nama' => 'Beban Persediaan', 'tipe' => 'Debit'],
            ['kode_akun' => '5104', 'nama' => 'Retur Pembelian', 'tipe' => 'Kredit'],
            ['kode_akun' => '5105', 'nama' => 'Potongan Pembelian', 'tipe' => 'Kredit'],
            ['kode_akun' => '5201', 'nama' => 'Beban Gaji dan Upah', 'tipe' => 'Debit'],
            ['kode_akun' => '5202', 'nama' => 'Beban Tunjangan', 'tipe' => 'Debit'],
            ['kode_akun' => '5203', 'nama' => 'Beban Bonus', 'tipe' => 'Debit'],
            ['kode_akun' => '5204', 'nama' => 'Beban Komisi Penjualan', 'tipe' => 'Debit'],
            ['kode_akun' => '5301', 'nama' => 'Beban Sewa', 'tipe' => 'Debit'],
            ['kode_akun' => '5302', 'nama' => 'Beban Listrik dan Air', 'tipe' => 'Debit'],
            ['kode_akun' => '5303', 'nama' => 'Beban Telepon dan Internet', 'tipe' => 'Debit'],
            ['kode_akun' => '5304', 'nama' => 'Beban Pemeliharaan', 'tipe' => 'Debit'],
            ['kode_akun' => '5401', 'nama' => 'Beban Transportasi', 'tipe' => 'Debit'],
            ['kode_akun' => '5402', 'nama' => 'Beban Perjalanan Dinas', 'tipe' => 'Debit'],
            ['kode_akun' => '5403', 'nama' => 'Beban Bahan Bakar', 'tipe' => 'Debit'],
            ['kode_akun' => '5404', 'nama' => 'Beban Parkir dan Tol', 'tipe' => 'Debit'],
            ['kode_akun' => '5501', 'nama' => 'Beban Pajak Penghasilan', 'tipe' => 'Debit'],
            ['kode_akun' => '5502', 'nama' => 'Beban Pajak Pertambahan Nilai', 'tipe' => 'Debit'],
            ['kode_akun' => '5503', 'nama' => 'Beban Pajak Lainnya', 'tipe' => 'Debit'],
            ['kode_akun' => '5601', 'nama' => 'Beban Asuransi', 'tipe' => 'Debit'],
            ['kode_akun' => '5602', 'nama' => 'Beban Iklan dan Promosi', 'tipe' => 'Debit'],
            ['kode_akun' => '5603', 'nama' => 'Beban Konsultan', 'tipe' => 'Debit'],
            ['kode_akun' => '5604', 'nama' => 'Beban Legal dan Profesional', 'tipe' => 'Debit'],
            ['kode_akun' => '5701', 'nama' => 'Beban Penyusutan', 'tipe' => 'Debit'],
            ['kode_akun' => '5702', 'nama' => 'Beban Amortisasi', 'tipe' => 'Debit'],
            ['kode_akun' => '5801', 'nama' => 'Beban Bunga', 'tipe' => 'Debit'],
            ['kode_akun' => '5802', 'nama' => 'Beban Bank', 'tipe' => 'Debit'],
            ['kode_akun' => '5901', 'nama' => 'Beban Lain-lain', 'tipe' => 'Debit'],
            ['kode_akun' => '5902', 'nama' => 'Beban Kerugian', 'tipe' => 'Debit'],
            
            // VI. BEBAN OPERASIONAL (6xxx)
            ['kode_akun' => '6101', 'nama' => 'Beban Administrasi dan Umum', 'tipe' => 'Debit'],
            ['kode_akun' => '6102', 'nama' => 'Beban Kantor', 'tipe' => 'Debit'],
            ['kode_akun' => '6103', 'nama' => 'Beban Alat Tulis Kantor', 'tipe' => 'Debit'],
            ['kode_akun' => '6104', 'nama' => 'Beban Perlengkapan', 'tipe' => 'Debit'],
            ['kode_akun' => '6201', 'nama' => 'Beban Pemasaran', 'tipe' => 'Debit'],
            ['kode_akun' => '6202', 'nama' => 'Beban Penjualan', 'tipe' => 'Debit'],
            ['kode_akun' => '6203', 'nama' => 'Beban Distribusi', 'tipe' => 'Debit'],
            ['kode_akun' => '6301', 'nama' => 'Beban Riset dan Pengembangan', 'tipe' => 'Debit'],
            ['kode_akun' => '6302', 'nama' => 'Beban Training dan Pengembangan', 'tipe' => 'Debit'],
            ['kode_akun' => '6401', 'nama' => 'Beban Depresiasi', 'tipe' => 'Debit'],
            ['kode_akun' => '6402', 'nama' => 'Beban Amortisasi Aset Tak Berwujud', 'tipe' => 'Debit'],
            ['kode_akun' => '6501', 'nama' => 'Beban Piutang Tak Tertagih', 'tipe' => 'Debit'],
            ['kode_akun' => '6502', 'nama' => 'Beban Kerugian Piutang', 'tipe' => 'Debit'],
            ['kode_akun' => '6601', 'nama' => 'Beban Bunga Bank', 'tipe' => 'Debit'],
            ['kode_akun' => '6602', 'nama' => 'Beban Keuangan', 'tipe' => 'Debit'],
            ['kode_akun' => '6701', 'nama' => 'Beban Selisih Kurs', 'tipe' => 'Debit'],
            ['kode_akun' => '6801', 'nama' => 'Beban Operasional Lainnya', 'tipe' => 'Debit'],
        ];

        foreach ($accounts as $account) {
            KodeAkun::updateOrCreate(
                ['kode_akun' => $account['kode_akun']],
                $account
            );
        }

        $this->command->info('Chart of Accounts seeded successfully!');
    }
}
