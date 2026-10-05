<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaksi;
use App\Models\Akun;

class UpdateTransaksiAkunKode extends Command
{
    protected $signature = 'transaksi:update-akun-kode 
                            {--kode_voucer= : Kode voucer spesifik}
                            {--akun_kode= : Kode akun yang akan diisi}';
    
    protected $description = 'Update transaksi yang tidak punya akun_kode';

    public function handle()
    {
        $kodeVoucer = $this->option('kode_voucer');
        $akunKode = $this->option('akun_kode');
        
        // List all transaksi without akun_kode
        $transaksis = Transaksi::whereNull('akun_kode')
            ->orWhere('akun_kode', '')
            ->get();
        
        if ($transaksis->isEmpty()) {
            $this->info('Semua transaksi sudah punya akun_kode!');
            return 0;
        }
        
        $this->info('Ditemukan ' . $transaksis->count() . ' transaksi tanpa akun_kode:');
        $this->newLine();
        
        foreach ($transaksis as $idx => $transaksi) {
            $this->line(($idx + 1) . '. ' . $transaksi->kode_voucer . ' - ' . $transaksi->deskripsi);
        }
        
        $this->newLine();
        $this->warn('Edit transaksi secara manual melalui halaman edit untuk mengisi Kode Akun!');
        $this->info('Atau gunakan: php artisan transaksi:set-akun {kode_voucer} {akun_kode}');
        
        return 0;
    }
}
