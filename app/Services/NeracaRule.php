<?php

namespace App\Services;

use App\Models\KodeAkun;
use Illuminate\Support\Facades\DB;

class NeracaRule
{
    /**
     * Mapping rules untuk identifikasi otomatis tipe akun berdasarkan nama
     */
    protected $accountTypePatterns = [
        'kas' => ['kas', 'cash', 'uang tunai', 'kas kecil', 'kas besar'],
        'bank' => ['bank', 'rekening', 'rekening bank', 'bca', 'mandiri', 'bni', 'bri', 'cimb', 'mega'],
        'piutang_usaha' => ['piutang usaha', 'account receivable', 'piutang dagang', 'piutang penjualan'],
        'persediaan' => ['persediaan', 'inventory', 'stock', 'barang dagang'],
        'biaya_dibayar_dimuka' => ['biaya dibayar dimuka', 'prepaid expense', 'pendapatan diterima dimuka', 'sewa dibayar dimuka'],
        'aset_tetap' => ['tanah', 'bangunan', 'mesin', 'peralatan', 'kendaraan', 'fixed asset', 'aset tetap'],
        'akumulasi_penyusutan' => ['akumulasi penyusutan', 'depreciation', 'penyusutan'],
        'investasi_jangka_panjang' => ['investasi jangka panjang', 'long term investment'],
        'aset_tak_berwujud' => ['goodwill', 'hak paten', 'aset tak berwujud', 'intangible asset'],
        'utang_usaha' => ['utang usaha', 'hutang usaha', 'account payable', 'utang dagang', 'hutang dagang'],
        'utang_pajak' => ['utang pajak', 'hutang pajak', 'pajak terutang', 'tax payable'],
        'utang_jangka_panjang' => ['utang jangka panjang', 'hutang jangka panjang', 'long term debt'],
        'beban_masih_harus_dibayar' => ['beban masih harus dibayar', 'accrued expense', 'beban terutang'],
        'modal_saham' => ['modal saham', 'share capital', 'modal dasar'],
        'laba_ditahan' => ['laba ditahan', 'retained earnings', 'laba tidak dibagi'],
        'ekuitas_lainnya' => ['ekuitas lainnya', 'other equity'],
    ];

    /**
     * Mendapatkan kategori akun berdasarkan nama atau pola
     */
    public function getAccountCategory($accountName)
    {
        $nama = strtolower(trim((string)$accountName));

        // Cek pattern untuk setiap kategori
        foreach ($this->accountTypePatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos((string)$nama, (string)$pattern) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Mendapatkan semua akun dengan kategori aset lancar untuk periode tertentu
     */
    public function getAsetLancarAccounts($tanggal_sampai)
    {
        // Identifikasi akun aset lancar berdasarkan nama atau kode
        $asetLancarCodes = ['1101', '1102', '1103', '1104', '1105'];
        
        return KodeAkun::whereIn('kode_akun', $asetLancarCodes)
            ->orWhere(function($query) {
                $query->where('nama', 'like', '%kas%')
                      ->orWhere('nama', 'like', '%bank%')
                      ->orWhere('nama', 'like', '%piutang%')
                      ->orWhere('nama', 'like', '%persediaan%')
                      ->orWhere('nama', 'like', '%biaya dibayar dimuka%')
                      ->orWhere('nama', 'like', '%prepaid%');
            })
            ->where('tipe', 'Debit')
            ->get();
    }

    /**
     * Menghitung Kas dan Setara Kas
     * Rule: Jumlahkan nilai akun-akun dengan tipe 'Kas' atau 'Bank' pada kategori aset lancar
     */
    public function calculateKasDanSetaraKas($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik dulu (1101 jika ada)
        $kodeAkunSpesifik = KodeAkun::where('kode_akun', '1101')
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->first();
        
        if ($kodeAkunSpesifik) {
            $balance = $kodeAkunSpesifik->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Tambahkan akun lain dengan nama mengandung "kas" atau "bank" pada aset lancar
        // Exclude kode yang sudah dihitung di atas
        $accounts = KodeAkun::where(function($query) {
                $query->where('nama', 'like', '%kas%')
                      ->orWhere('nama', 'like', '%cash%')
                      ->orWhere('nama', 'like', '%bank%')
                      ->orWhere('nama', 'like', '%rekening%');
            })
            ->where('kode_akun', '!=', '1101') // Exclude kode spesifik
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            // Hanya ambil yang masuk kategori aset lancar (biasanya kode 11xx)
            $kode = (int) substr($account->kode_akun, 0, 2);
            if ($kode >= 11 && $kode < 12) {
                $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                $total += max(0, $balance);
            }
        }
        
        return $total;
    }

    /**
     * Menghitung Piutang Usaha
     * Rule: Jumlahkan nilai akun dengan tipe 'Piutang Usaha' dari transaksi aset kategori lancar
     */
    public function calculatePiutangUsaha($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik (1102 )
        $kodeAkunSpesifik = KodeAkun::where('kode_akun', '1102')
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->first();
        
        if ($kodeAkunSpesifik) {
            $balance = $kodeAkunSpesifik->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Tambahkan akun lain dengan nama mengandung "piutang usaha"
        $accounts = KodeAkun::where(function($query) {
                $query->where('nama', 'like', '%piutang usaha%')
                      ->orWhere('nama', 'like', '%piutang dagang%')
                      ->orWhere('nama', 'like', '%account receivable%');
            })
            ->where('kode_akun', '!=', '1102') // Exclude kode spesifik
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        return $total;
    }

    /**
     * Menghitung Persediaan
     * Rule: Jumlahkan nilai akun 'Persediaan' pada aset lancar
     */
    public function calculatePersediaan($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik  (1103)
        $kodeAkunSpesifik = KodeAkun::where('kode_akun', '1103')
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->first();
        
        if ($kodeAkunSpesifik) {
            $balance = $kodeAkunSpesifik->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Tambahkan akun lain dengan nama mengandung "persediaan"
        $accounts = KodeAkun::where(function($query) {
                $query->where('nama', 'like', '%persediaan%')
                      ->orWhere('nama', 'like', '%inventory%')
                      ->orWhere('nama', 'like', '%stock%')
                      ->orWhere('nama', 'like', '%barang dagang%');
            })
            ->where('kode_akun', '!=', '1103') // Exclude kode spesifik
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        return $total;
    }

    /**
     * Menghitung Biaya Dibayar Dimuka
     * Rule: Jumlahkan nilai transaksi dengan tipe 'Biaya Dibayar Dimuka' (prepaid expense)
     */
    public function calculateBiayaDibayarDimuka($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik (1104 )
        $kodeAkunSpesifik = KodeAkun::where('kode_akun', '1104')
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->first();
        
        if ($kodeAkunSpesifik) {
            $balance = $kodeAkunSpesifik->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Tambahkan akun lain dengan nama mengandung "biaya dibayar dimuka"
        $accounts = KodeAkun::where(function($query) {
                $query->where('nama', 'like', '%biaya dibayar dimuka%')
                      ->orWhere('nama', 'like', '%prepaid expense%')
                      ->orWhere('nama', 'like', '%sewa dibayar dimuka%')
                      ->orWhere('nama', 'like', '%pendapatan diterima dimuka%');
            })
            ->where('kode_akun', '!=', '1104') // Exclude kode spesifik
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        return $total;
    }

    /**
     * Menghitung Aset Lancar Lainnya
     * Rule: Jumlahkan nilai semua aset lancar yang tidak masuk kategori kas, piutang, persediaan, biaya dibayar dimuka
     */
    public function calculateAsetLancarLainnya($tanggal_dari, $tanggal_sampai, $kasDanSetaraKas, $piutangUsaha, $persediaan, $biayaDibayarDimuka)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik dulu (1105 jika ada)
        $kodeAkunSpesifik = KodeAkun::where('kode_akun', '1105')
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->first();
        
        if ($kodeAkunSpesifik) {
            $balance = $kodeAkunSpesifik->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Ambil semua aset lancar lainnya (kode 11xx tapi bukan yang sudah dihitung)
        // Exclude kode yang sudah masuk kategori spesifik
        $excludedCodes = ['1101', '1102', '1103', '1104'];
        
        // Ambil semua kode 11xx yang belum masuk kategori spesifik
        $allAsetLancar = KodeAkun::where('kode_akun', 'like', '11%')
            ->whereNotIn('kode_akun', $excludedCodes)
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($allAsetLancar as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        return $total;
    }

    /**
     * Menghitung Total Aset Lancar
     * Rule: Agregasi SUM dari seluruh nilai di atas
     */
    public function calculateTotalAsetLancar($tanggal_dari, $tanggal_sampai)
    {
        $kasDanSetaraKas = $this->calculateKasDanSetaraKas($tanggal_dari, $tanggal_sampai);
        $piutangUsaha = $this->calculatePiutangUsaha($tanggal_dari, $tanggal_sampai);
        $persediaan = $this->calculatePersediaan($tanggal_dari, $tanggal_sampai);
        $biayaDibayarDimuka = $this->calculateBiayaDibayarDimuka($tanggal_dari, $tanggal_sampai);
        $asetLancarLainnya = $this->calculateAsetLancarLainnya($tanggal_dari, $tanggal_sampai, $kasDanSetaraKas, $piutangUsaha, $persediaan, $biayaDibayarDimuka);
        
        return [
            'kas_dan_setara_kas' => $kasDanSetaraKas,
            'piutang_usaha' => $piutangUsaha,
            'persediaan' => $persediaan,
            'biaya_dibayar_dimuka' => $biayaDibayarDimuka,
            'aset_lancar_lainnya' => $asetLancarLainnya,
            'total' => $kasDanSetaraKas + $piutangUsaha + $persediaan + $biayaDibayarDimuka + $asetLancarLainnya
        ];
    }

    /**
     * Menghitung Aset Tetap/Lainnya
     * Rule: Lakukan pemetaan serupa pada kategori aset tetap/lainnya
     */
    public function calculateAsetTetap($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        $akumulasiPenyusutan = 0;
        
        // Prioritas: Gunakan kode akun spesifik dulu (1201, 1202, 1203, 1204)
        $asetTetapCodes = ['1201', '1202', '1203', '1204'];
        
        foreach ($asetTetapCodes as $kode) {
            $account = KodeAkun::where('kode_akun', $kode)
                ->where('tipe', 'Debit')
                ->where('deleted_at', null)
                ->first();
            
            if ($account) {
                $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                $total += max(0, $balance);
            }
        }
        
        // Tambahkan akun lain dengan nama mengandung aset tetap (jika tidak ada di kode spesifik)
        $accounts = KodeAkun::whereNotIn('kode_akun', $asetTetapCodes)
            ->where(function($query) {
                $query->where('nama', 'like', '%tanah%')
                      ->orWhere('nama', 'like', '%bangunan%')
                      ->orWhere('nama', 'like', '%mesin%')
                      ->orWhere('nama', 'like', '%peralatan%')
                      ->orWhere('nama', 'like', '%kendaraan%')
                      ->orWhere('nama', 'like', '%fixed asset%')
                      ->orWhere('nama', 'like', '%aset tetap%');
            })
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        // Kurangi dengan akumulasi penyusutan - prioritas kode 1299
        $penyusutanAccount = KodeAkun::where('kode_akun', '1299')
            ->where('deleted_at', null)
            ->first();
        
        if ($penyusutanAccount) {
            $balance = $penyusutanAccount->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            // PERBAIKAN: Akumulasi penyusutan adalah contra-asset dengan saldo kredit
            // Jika hasil negatif (karena logic), gunakan nilai absolut untuk pengurangan
            $akumulasiPenyusutan += abs($balance);
        }

        // Tambahkan akun lain dengan nama mengandung akumulasi penyusutan (jika tidak 1299)
        $penyusutanAccounts = KodeAkun::where('kode_akun', '!=', '1299')
            ->where(function($query) {
                $query->where('nama', 'like', '%akumulasi penyusutan%')
                      ->orWhere('nama', 'like', '%depreciation%');
            })
            ->where(function($query) {
                $query->where('tipe', 'Kredit');
            })
            ->where('deleted_at', null)
            ->get();

        foreach ($penyusutanAccounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            // PERBAIKAN: Akumulasi penyusutan adalah contra-asset
            $akumulasiPenyusutan += abs($balance);
        }
        
        return [
            'total' => max(0, $total - $akumulasiPenyusutan),
            'bruto' => $total,
            'akumulasi_penyusutan' => $akumulasiPenyusutan
        ];
    }

    /**
     * Menghitung Aset Lainnya
     */
    public function calculateAsetLainnya($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik dulu (1301, 1302, 1303)
        $asetLainnyaCodes = ['1301', '1302', '1303'];
        
        foreach ($asetLainnyaCodes as $kode) {
            $account = KodeAkun::where('kode_akun', $kode)
                ->where('tipe', 'Debit')
                ->where('deleted_at', null)
                ->first();
            
            if ($account) {
                $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                $total += max(0, $balance);
            }
        }
        
        // Tambahkan akun lain dengan nama mengandung aset lainnya (jika tidak ada di kode spesifik)
        $accounts = KodeAkun::whereNotIn('kode_akun', $asetLainnyaCodes)
            ->where(function($query) {
                $query->where('nama', 'like', '%investasi jangka panjang%')
                      ->orWhere('nama', 'like', '%long term investment%')
                      ->orWhere('nama', 'like', '%goodwill%')
                      ->orWhere('nama', 'like', '%hak paten%')
                      ->orWhere('nama', 'like', '%aset tak berwujud%')
                      ->orWhere('nama', 'like', '%intangible asset%');
            })
            ->where('tipe', 'Debit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $total += max(0, $balance);
        }
        
        return $total;
    }

    /**
     * Menghitung Total Aset
     * Rule: Total Aset = Total Lancar + Tetap + Lainnya
     */
    public function calculateTotalAset($tanggal_dari, $tanggal_sampai)
    {
        $asetLancar = $this->calculateTotalAsetLancar($tanggal_dari, $tanggal_sampai);
        $asetTetap = $this->calculateAsetTetap($tanggal_dari, $tanggal_sampai);
        $asetLainnya = $this->calculateAsetLainnya($tanggal_dari, $tanggal_sampai);
        
        return [
            'aset_lancar' => $asetLancar['total'],
            'aset_tetap' => $asetTetap['total'],
            'aset_lainnya' => $asetLainnya,
            'total' => $asetLancar['total'] + $asetTetap['total'] + $asetLainnya
        ];
    }

    /**
     * Menghitung Total Liabilitas
     * Rule: SUM seluruh transaksi liabilitas selama periode pada kategori kewajiban/hutang
     */
    public function calculateTotalLiabilitas($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik dulu (2101, 2102, 2103, 2104)
        $liabilitasCodes = ['2101', '2102', '2103', '2104'];
        
        foreach ($liabilitasCodes as $kode) {
            $account = KodeAkun::where('kode_akun', $kode)
                ->where('tipe', 'Kredit')
                ->where('deleted_at', null)
                ->first();
            
            if ($account) {
                $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                // PERBAIKAN: Jangan gunakan abs() di sini
                // Logika saldo normal sudah ditangani di getFreshBalance
                // Untuk liabilitas (kredit account), nilai bisa positif atau negatif tergantung perhitungan
                $total += $balance;
            }
        }
        
        // Tambahkan akun lain dengan nama mengandung liabilitas (jika tidak ada di kode spesifik)
        $accounts = KodeAkun::whereNotIn('kode_akun', $liabilitasCodes)
            ->where(function($query) {
                $query->where('nama', 'like', '%utang%')
                      ->orWhere('nama', 'like', '%hutang%')
                      ->orWhere('nama', 'like', '%account payable%')
                      ->orWhere('nama', 'like', '%pajak terutang%')
                      ->orWhere('nama', 'like', '%tax payable%');
            })
            ->where('tipe', 'Kredit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            // PERBAIKAN: Jangan gunakan abs() di sini
            $total += $balance;
        }
        
        return $total;
    }

    /**
     * Menghitung Total Ekuitas
     * Rule: SUM seluruh transaksi ekuitas (modal dan saldo laba) pada periode
     */
    public function calculateTotalEkuitas($tanggal_dari, $tanggal_sampai)
    {
        $total = 0;
        
        // Prioritas: Gunakan kode akun spesifik (3101, 3102, 3103)
        $ekuitasCodes = ['3101', '3102', '3103'];
        
        foreach ($ekuitasCodes as $kode) {
            $account = KodeAkun::where('kode_akun', $kode)
                ->where('tipe', 'Kredit')
                ->where('deleted_at', null)
                ->first();
            
            if ($account) {
                $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                // PERBAIKAN: Jangan gunakan abs() di sini
                $total += $balance;
            }
        }
        
        // Tambahkan akun lain dengan nama mengandung ekuitas (jika tidak ada di kode spesifik)
        $accounts = KodeAkun::whereNotIn('kode_akun', $ekuitasCodes)
            ->where(function($query) {
                $query->where('nama', 'like', '%modal%')
                      ->orWhere('nama', 'like', '%laba ditahan%')
                      ->orWhere('nama', 'like', '%retained earnings%')
                      ->orWhere('nama', 'like', '%ekuitas%')
                      ->orWhere('nama', 'like', '%equity%');
            })
            ->where('tipe', 'Kredit')
            ->where('deleted_at', null)
            ->get();
        
        foreach ($accounts as $account) {
            $balance = $account->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            // PERBAIKAN: Jangan gunakan abs() di sini
            $total += $balance;
        }
        
        return $total;
    }

    /**
     * Menghitung Total Liabilitas & Ekuitas
     * Rule: Total Liabilitas + Total Ekuitas
     * Validasi: hasil harus = Total Aset (keseimbangan posisi keuangan)
     */
    public function calculateTotalLiabilitasDanEkuitas($tanggal_dari, $tanggal_sampai)
    {
        $liabilitas = $this->calculateTotalLiabilitas($tanggal_dari, $tanggal_sampai);
        $ekuitas = $this->calculateTotalEkuitas($tanggal_dari, $tanggal_sampai);
        
        return [
            'liabilitas' => $liabilitas,
            'ekuitas' => $ekuitas,
            'total' => $liabilitas + $ekuitas
        ];
    }

    /**
     * Generate complete neraca keuangan berdasarkan rules
     */
    public function generateNeracaKeuangan($tanggal_dari, $tanggal_sampai)
    {
        $asetLancar = $this->calculateTotalAsetLancar($tanggal_dari, $tanggal_sampai);
        $asetTetap = $this->calculateAsetTetap($tanggal_dari, $tanggal_sampai);
        $asetLainnya = $this->calculateAsetLainnya($tanggal_dari, $tanggal_sampai);
        $totalAset = $this->calculateTotalAset($tanggal_dari, $tanggal_sampai);
        $liabilitasDanEkuitas = $this->calculateTotalLiabilitasDanEkuitas($tanggal_dari, $tanggal_sampai);

        $selisih = $totalAset['total'] - $liabilitasDanEkuitas['total'];
        $isBalanced = abs($selisih) < 0.01; // Toleransi 0.01 untuk floating point

        return [
            'aset_lancar' => $asetLancar,
            'aset_tetap' => $asetTetap,
            'aset_lainnya' => $asetLainnya,
            'total_aset' => $totalAset,
            'liabilitas' => $liabilitasDanEkuitas['liabilitas'],
            'ekuitas' => $liabilitasDanEkuitas['ekuitas'],
            'total_liabilitas_dan_ekuitas' => $liabilitasDanEkuitas['total'],
            'selisih' => $selisih,
            'is_balanced' => $isBalanced,
            'balance_warning' => !$isBalanced ? 'Neraca tidak seimbang. Total Aset (' . number_format($totalAset['total'], 2) . ') tidak sama dengan Total Liabilitas & Ekuitas (' . number_format($liabilitasDanEkuitas['total'], 2) . '). Selisih: ' . number_format($selisih, 2) : null
        ];
    }
    
    /**
     * Manilai berdasarkan rules ke collection accounts untuk digunakan di view
     */
    public function mapRuleValuesToAccounts($accounts, $accountMapping, $ruleValues)
    {
        // Mapping untuk aset lancar
        $mapping = [
            '1101' => 'kas_dan_setara_kas',
            '1102' => 'piutang_usaha',
            '1103' => 'persediaan',
            '1104' => 'biaya_dibayar_dimuka',
            '1105' => 'aset_lancar_lainnya',
        ];
        
        foreach ($mapping as $kode => $key) {
            if ($accounts->has($kode) && isset($ruleValues[$key])) {
                $account = $accounts->get($kode);
                $account->rule_value = $ruleValues[$key];
            }
        }
    }
}

