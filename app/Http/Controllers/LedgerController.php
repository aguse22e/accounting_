<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\KodeAkun;
use App\Exports\LedgerExport;
use App\Exports\PosisiKeuanganExport;
use App\Exports\NeracaExport;
use App\Exports\TrialBalanceExport;
use App\Exports\GlDetailExport;
use App\Exports\CashFlowsExport;
use App\Exports\FixedAssetsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Services\NeracaRule;
use App\Models\Transaksi;
use App\Imports\LedgerImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        // Default to current month (first day to today)
        $default_from = date('Y-m-01');
        $default_to = date('Y-m-d');
        $tanggal_dari = $request->input('tanggal_dari', $default_from);
        $tanggal_sampai = $request->input('tanggal_sampai', $default_to);
        $filter_akun = $request->input('akun_id', 'semua');
        
        // Get data from database for real-time updates
        $akun_list = Akun::select(['kode_akun', 'nama', 'tipe'])
            ->orderBy('kode_akun')
            ->get();

        $query = Akun::select(['kode_akun', 'nama', 'tipe']);
        
        if ($filter_akun !== 'semua') {
            $query->where('id', $filter_akun);
        }
        
        $akun_data = $query->orderBy('kode_akun')->get();
        
        return view('ledger.index', [
            'akun_data' => $akun_data,
            'akun_list' => $akun_list,
            'tanggal_dari' => $tanggal_dari,
            'tanggal_sampai' => $tanggal_sampai,
            'filter_akun' => $filter_akun
        ]);
    }
    
    public function exportPdf(Request $request): BinaryFileResponse|Response|RedirectResponse
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));
        $filter_akun_id = $request->input('akun_id', 'semua');
        $menu = $request->input('menu', 'General Ledger Detail');
        $periodStr = $this->formatDatePeriod($from, $to);

        switch ($menu) {
            case 'Neraca Saldo':
                return $this->handleNeracaSaldoPdf($from, $to, $periodStr);
            case 'General Ledger Detail':
            case 'GL Detail':
                return $this->handleGeneralLedgerDetailPdf($from, $to, $periodStr, $menu);
            case 'Arus Kas':
            case 'Cash Flows':
                return $this->handleCashFlowsPdf($from, $to, $periodStr);
            case 'Aset Tetap':
            case 'Fixed Assets':
                return $this->handleFixedAssetsPdf($from, $to, $periodStr);
            case 'Laporan Keuangan':
            case 'Neraca':
                return $this->handleLaporanKeuanganPdf($from, $to, $periodStr, $filter_akun_id, $menu);
            default:
                return $this->handleDefaultPdf($from, $to, $periodStr, $menu);
        }
    }
    
    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $from = $request->input('from', date('Y-m-01'));
            $to = $request->input('to', date('Y-m-t'));
            $filter_akun_id = $request->input('akun_id', 'semua');

            // Implementasi export Excel dengan library maatwebsite/excel
            $menu = $request->input('menu', 'General Ledger Detail');
            $filename = str_replace(' ', '-', strtolower($menu)) . '-' . date('Ymd-His') . '.xlsx';

            // Pilih export class berdasarkan menu
            if ($menu === 'Laporan Keuangan' || $menu === 'Neraca') {
                return Excel::download(
                    new NeracaExport($from, $to, $filter_akun_id),
                    $filename
                );
            } elseif ($menu === 'Neraca Saldo') {
                return Excel::download(
                    new TrialBalanceExport($from, $to),
                    $filename
                );
            } elseif ($menu === 'General Ledger Detail' || $menu === 'GL Detail') {
                return Excel::download(
                    new GlDetailExport($from, $to),
                    $filename
                );
            } elseif ($menu === 'Arus Kas' || $menu === 'Cash Flows') {
                return Excel::download(
                    new CashFlowsExport($from, $to),
                    $filename
                );
            } elseif ($menu === 'Aset Tetap' || $menu === 'Fixed Assets') {
                return Excel::download(
                    new FixedAssetsExport($from, $to),
                    $filename
                );
            } else {
                return Excel::download(
                    new LedgerExport($from, $to, $filter_akun_id, $menu),
                    $filename
                );
            }
        } catch (\Exception $e) {
            Log::error('Excel Export Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat file Excel.');
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv']
        ]);

        // Simpan file lalu parsing dan rekam transaksi 
        $storedPath = $request->file('file')->store('imports');

        $collection = Excel::toCollection(new LedgerImport, storage_path('app/' . $storedPath));
        if ($collection->isEmpty() || ($collection->first() && $collection->first()->isEmpty())) {
            return back()->with('error', 'File kosong atau tidak terbaca.');
        }

        $rows = $collection->first();

        // Baca header secara fleksibel (mendukung beberapa penamaan umum)
        $firstRow = $rows->first();
        $header = $firstRow ? $firstRow->map(function($v){ return strtolower(trim((string)$v)); }) : collect();
        $map = function(string $key) use ($header) {
            $aliases = [
                'tanggal' => ['tanggal','date','tgl'],
                'kode_akun' => ['kode akun','kode_akun','akun','account code','kode'],
                'nama_akun' => ['nama akun','nama_akun','account name','nama'],
                'deskripsi' => ['deskripsi','keterangan','memo','description'],
                'jenis' => ['jenis','dk','type'],
                'jumlah' => ['jumlah','nominal','amount'],
                'referensi' => ['referensi','ref','no bukti','no_bukti'],
            ];
            $candidates = $aliases[$key] ?? [$key];
            foreach ($candidates as $candidate) {
                $idx = $header->search($candidate);
                if ($idx !== false) return $idx;
            }
            return null;
        };

        $indexes = [
            'tanggal' => $map('tanggal'),
            'kode_akun' => $map('kode_akun'),
            'nama_akun' => $map('nama_akun'),
            'deskripsi' => $map('deskripsi'),
            'jenis' => $map('jenis'),
            'jumlah' => $map('jumlah'),
            'referensi' => $map('referensi'),
        ];

        // Hilangkan baris header
        $dataRows = $rows ? $rows->slice(1) : collect();

        $created = 0; $skipped = 0;
        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                // Ambil nilai dengan aman
                $get = function($key) use ($indexes, $row) {
                    $i = $indexes[$key];
                    return $i === null ? null : (isset($row[$i]) ? trim((string)$row[$i]) : null);
                };

                $tanggal = $get('tanggal');
                $jumlah = $get('jumlah');
                $jenis = strtolower($get('jenis') ?? '');
                $deskripsi = $get('deskripsi');
                $referensi = $get('referensi');

                if (!$tanggal || !$jumlah || ($jenis !== 'debit' && $jenis !== 'kredit')) { $skipped++; continue; }

                // Temukan akun berdasarkan kode_akun atau nama_akun
                $akun = null;
                $kode = $get('kode_akun');
                $nama = $get('nama_akun');
                if ($kode) {
                    $akun = Akun::where('kode_akun', $kode)->first();
                }
                if (!$akun && $nama) {
                    $akun = Akun::where('nama', 'like', $nama)->first();
                }
                if (!$akun) { $skipped++; continue; }

                $nominal = (float)str_replace([','], [''], $jumlah);
                
                // Prepare data for insertion
                $transaksiData = [
                    'akun_kode' => $akun->kode_akun,
                    'kode_project' => (string)$request->get('kode_project') ?: null,
                    'deskripsi' => $deskripsi ?? '',
                    'transaksi_type' => $jenis === 'debit' ? 'Debit' : 'Kredit',
                    'jenis' => $jenis,
                    'tanggal' => date('Y-m-d', strtotime($tanggal)),
                    'nominal' => $nominal,
                ];

                // Set debit or kredit column based on type
                if ($jenis === 'debit') {
                    $transaksiData['debit'] = $nominal;
                    $transaksiData['kredit'] = 0;
                } else {
                    $transaksiData['debit'] = 0;
                    $transaksiData['kredit'] = $nominal;
                }

                // Also set jumlah field
                $transaksiData['jumlah'] = $nominal;
                
                // Calculate saldo
                $transaksiData['saldo'] = $jenis === 'debit' ? $nominal : -$nominal;

                Transaksi::create($transaksiData);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengimpor file.');
        }

        return back()->with('success', "Impor selesai. Tersimpan: {$created}, dilewati: {$skipped}. File: " . basename($storedPath))
            ->with('imported_data', $dataRows->map(function($row) use ($indexes) {
                $mappedRow = [];
                foreach ($indexes as $key => $idx) {
                    $mappedRow[$key] = $idx !== null && isset($row[$idx]) ? $row[$idx] : null;
                }
                return $mappedRow;
            })->toArray());
    }

    public function commitSerial(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => ['required','string']
        ]);
        $key = $request->input('key');
        return back()->with('success', 'Fitur nomor seri telah dihapus.');
    }

    /**
     * Laporan Posisi Keuangan (Neraca)
     * 
     * Rule untuk Penentuan Nilai:
     * 
     * 1. ASET LANCAR (kategori: 'Lancar')
     *    - Rule: Ambil semua data aset dengan kategori 'Lancar' pada periode yang dipilih
     *    - Kode Akun: 1101-1105
     *    - Perhitungan: SUM(nilai) dari semua transaksi akun kategori lancar
     *    - Sumber: transaksi, jurnal, transaksi_bank, transaksi_bank_detail
     *    - Filter Periode: tanggal_dari sampai tanggal_sampai
     * 
     * 2. ASET TETAP (kategori: 'Tetap')
     *    - Rule: Ambil semua data aset dengan kategori 'Tetap' pada periode yang dipilih
     *    - Kode Akun: 1201-1204, 1299 (Akumulasi Penyusutan)
     *    - Perhitungan: SUM(nilai) dari semua transaksi akun kategori tetap
     *    - Sumber: transaksi, jurnal, transaksi_bank, transaksi_bank_detail
     *    - Filter Periode: tanggal_dari sampai tanggal_sampai
     * 
     * 3. ASET LAINNYA (kategori: 'Lainnya')
     *    - Rule: Ambil semua data aset dengan kategori 'Lainnya' pada periode yang dipilih
     *    - Kode Akun: 1301-1303
     *    - Perhitungan: SUM(nilai) dari semua transaksi akun kategori lainnya
     *    - Sumber: transaksi, jurnal, transaksi_bank, transaksi_bank_detail
     *    - Filter Periode: tanggal_dari sampai tanggal_sampai
     * 
     * 4. TOTAL ASET
     *    - Rule: Penjumlahan total nilai aset lancar, tetap, dan lainnya
     *    - Formula: Total Aset = Total Aset Lancar + Total Aset Tetap + Total Aset Lainnya
     * 
     * 5. LIABILITAS
     *    - Rule: Ambil dan jumlahkan nilai semua liabilitas pada periode yang ditentukan
     *    - Kode Akun: 2101-2104
     *    - Perhitungan: SUM(nilai) dari semua transaksi akun liabilitas (kredit account)
     *    - Sumber: transaksi, jurnal, transaksi_bank, transaksi_bank_detail
     *    - Filter Periode: tanggal_dari sampai tanggal_sampai
     *    - Note: Untuk liabilitas (kredit account), nilai diambil sebagai absolute value
     * 
     * 6. EKUITAS
     *    - Rule: Ambil dan jumlahkan nilai semua ekuitas pada periode yang dipilih
     *    - Kode Akun: 3101-3103
     *    - Perhitungan: SUM(nilai) dari semua transaksi akun ekuitas (kredit account)
     *    - Sumber: transaksi, jurnal, transaksi_bank, transaksi_bank_detail
     *    - Filter Periode: tanggal_dari sampai tanggal_sampai
     *    - Note: Untuk ekuitas (kredit account), nilai diambil sebagai absolute value
     * 
     * 7. TOTAL LIABILITAS & EKUITAS
     *    - Rule: Hitung total nilai liabilitas dan ekuitas
     *    - Formula: Total Liabilitas + Total Ekuitas
     *    - Validasi: Total Liabilitas + Total Ekuitas harus sama dengan Total Aset (prinsip neraca seimbang)
     * 
     * Penentuan Nilai:
     * - Semua nilai dihitung menggunakan method getFreshBalance() dari model KodeAkun
     * - Method ini mengagregasi data dari:
     *   a. Tabel transaksi (debit/kredit berdasarkan jenis)
     *   b. Tabel jurnal (kode dan lawan, dengan filter status Posted)
     *   c. Tabel transaksi_bank (tipe debit/kredit)
     *   d. Tabel transaksi_bank_detail (subtotal untuk akun lawan)
     * - Periode perhitungan: dari tanggal awal (1900-01-01) sampai tanggal_sampai untuk akumulasi saldo
     * - Untuk akun kredit (liabilitas/ekuitas), nilai ditampilkan sebagai absolute value
     */
    public function posisiKeuangan(Request $request): Response
    {
        // Jika tidak ada parameter, gunakan awal bulan sampai hari ini
        $tanggal_dari = $request->input('tanggal_dari', date('Y-m-01'));
        $tanggal_sampai = $request->input('tanggal_sampai', date('Y-m-d'));

        // Untuk backward compatibility, jika ada parameter 'tanggal', gunakan itu
        if ($request->has('tanggal') && !$request->has('tanggal_dari')) {
            $tanggal_dari = $request->input('tanggal');
            $tanggal_sampai = $request->input('tanggal');
        }

        // Parse tanggal
        $tanggalObj = Carbon::parse($tanggal_sampai);

        // Informasi bulan dengan Indonesia
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $bulan = $tanggalObj->format('F Y'); // Nama bulan dan tahun (English)
        $bulan_indo = $bulanMap[$tanggalObj->month] . ' ' . $tanggalObj->year; // Nama bulan Indonesia

        // Define account structure based on the provided chart of accounts
        //  kode akun ke kategori: Lancar, Tetap, Lainnya, Liabilitas, Ekuitas
        $accountbased = [
            'aset_lancar' => [
                '1101' => 'Kas dan Setara Kas',
                '1102' => 'Piutang Usaha',
                '1103' => 'Persediaan',
                '1104' => 'Biaya Dibayar Dimuka',
                '1105' => 'Aset Lancar Lainnya'
            ],
            'aset_tetap' => [
                '1201' => 'Tanah',
                '1202' => 'Bangunan',
                '1203' => 'Mesin dan Peralatan',
                '1204' => 'Kendaraan',
                '1299' => 'Akumulasi Penyusutan Aset Tetap'
            ],
            'aset_lainnya' => [
                '1301' => 'Investasi Jangka Panjang',
                '1302' => 'Aset Tak Berwujud (Goodwill, Hak Paten)',
                '1303' => 'Aset Lainnya'
            ],
            'liabilitas' => [
                '2101' => 'Utang Usaha',
                '2102' => 'Utang Pajak',
                '2103' => 'Utang Jangka Panjang',
                '2104' => 'Beban yang Masih Harus Dibayar'
            ],
            'ekuitas' => [
                '3101' => 'Modal Saham',
                '3102' => 'Laba Ditahan',
                '3103' => 'Ekuitas Lainnya'
            ]
        ];

        // Collect all account codes we need
        $allCodes = array_merge(
            array_keys($accountbased['aset_lancar']),
            array_keys($accountbased['aset_tetap']),
            array_keys($accountbased['aset_lainnya']),
            array_keys($accountbased['liabilitas']),
            array_keys($accountbased['ekuitas'])
        );

        // Method ini mengagregasi dari transaksi, jurnal, transaksi_bank, transaksi_bank_detail
        // sesuai dengan rule : SUM(nilai) WHERE kategori sesuai AND periode sesuai
        $akun = KodeAkun::select(['kode_akun', 'nama', 'tipe'])
            ->whereIn('kode_akun', $allCodes)
            ->orderBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');


        // Ini penting untuk laporan posisi keuangan yang harus menampilkan semua akun meskipun belum ada transaksi
        foreach ($allCodes as $akunCode) {
            if (!$akun->has($akunCode)) {
                // Cari nama akun dari accountbased
                $nama = $accountbased['aset_lancar'][$akunCode] ??
                       $accountbased['aset_tetap'][$akunCode] ??
                       $accountbased['aset_lainnya'][$akunCode] ??
                       $accountbased['liabilitas'][$akunCode] ??
                       $accountbased['ekuitas'][$akunCode] ??
                       null;

                // Jika ada di accountbased, buat instance KodeAkun baru
                if ($nama) {
                    $newAkun = new KodeAkun();
                    $newAkun->kode_akun = $akunCode;
                    $newAkun->nama = $nama;
                    $newAkun->tipe = (strpos((string)$akunCode, '1299') !== false ||
                                  in_array($akunCode, array_keys($accountbased['liabilitas'])) ||
                                  in_array($akunCode, array_keys($accountbased['ekuitas']))) ? 'Kredit' : 'Debit';
                    $akun->put($akunCode, $newAkun);
                }
            }
        }

        // Implementasi rule : Filter berdasarkan kategori (Lancar, Tetap, Lainnya, Liabilitas, Ekuitas)

        // ASET LANCAR (kategori: 'Lancar') - Rule: Ambil semua data aset dengan kategori 'Lancar'
        $asetLancar = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_lancar']);
        })->sortKeys();

        // ASET TETAP (kategori: 'Tetap') - Rule: Ambil semua data aset dengan kategori 'Tetap'
        $asetTetap = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_tetap']);
        })->sortKeys();

        // ASET LAINNYA (kategori: 'Lainnya') - Rule: Ambil semua data aset dengan kategori 'Lainnya'
        $asetLainnya = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_lainnya']);
        })->sortKeys();

        // LIABILITAS - Rule: Ambil dan jumlahkan nilai semua liabilitas pada periode
        $liabilitas = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['liabilitas']);
        })->sortKeys();

        // EKUITAS - Rule: Ambil dan jumlahkan nilai semua ekuitas pada periode
        $ekuitas = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['ekuitas']);
        })->sortKeys();

        // Catatan:
        // - Nilai untuk setiap kategori dihitung menggunakan getFreshBalance('1900-01-01', $tanggal_sampai)
        //   yang mengagregasi dari semua sumber transaksi (transaksi, jurnal, transaksi_bank, transaksi_bank_detail)
        // - Total Aset = Total Aset Lancar + Total Aset Tetap + Total Aset Lainnya
        // - Total Liabilitas & Ekuitas = Total Liabilitas + Total Ekuitas
        // - Validasi: Total Aset harus sama dengan Total Liabilitas & Ekuitas (neraca seimbang)

        // Gunakan NeracaRule untuk perhitungan berdasarkan rules
        $mappingService = app(NeracaRule::class);
        $ruleValues = $mappingService->generateNeracaKeuangan($tanggal_dari, $tanggal_sampai);

        // Map nilai berdasarkan rules ke struktur accountbased untuk digunakan di view
        // Aset Lancar - map semua nilai dari rules
        $mappingService->mapRuleValuesToAccounts($asetLancar, $accountbased['aset_lancar'], $ruleValues['aset_lancar']);

        // Aset Tetap - map nilai individual dari database, total dari rules
        foreach ($asetTetap as $kode => $akun) {
            if ($kode === '1299') {
                // Akumulasi penyusutan dari rules
                $akun->rule_value = $ruleValues['aset_tetap']['akumulasi_penyusutan'] ?? 0;
            } else {
                // Aset tetap individual dari database
                $balance = $akun->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                $akun->rule_value = max(0, $balance);
            }
        }

        // Aset Lainnya - map nilai individual dari database
        foreach ($asetLainnya as $kode => $akun) {
            $balance = $akun->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $akun->rule_value = max(0, $balance);
        }

        // Liabilitas - map nilai individual dari database
        foreach ($liabilitas as $kode => $akun) {
            $balance = $akun->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $akun->rule_value = abs($balance);
        }

        // Ekuitas - map nilai individual dari database
        foreach ($ekuitas as $kode => $akun) {
            $balance = $akun->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
            $akun->rule_value = abs($balance);
        }

        // PERBAIKAN: Tambahkan validasi keseimbangan neraca
        $isBalanced = $ruleValues['is_balanced'] ?? false;
        $balanceWarning = $ruleValues['balance_warning'] ?? null;

        // Log warning jika neraca tidak seimbang
        if (!$isBalanced) {
            Log::warning('Neraca tidak seimbang', [
                'tanggal_dari' => $tanggal_dari,
                'tanggal_sampai' => $tanggal_sampai,
                'total_aset' => $ruleValues['total_aset']['total'] ?? 0,
                'total_liabilitas_dan_ekuitas' => $ruleValues['total_liabilitas_dan_ekuitas'] ?? 0,
                'selisih' => $ruleValues['selisih'] ?? 0,
                'warning' => $balanceWarning
            ]);
        }

        return response()->view('ledger.posisi', [
            'tanggal_dari' => $tanggal_dari,
            'tanggal_sampai' => $tanggal_sampai,
            'bulan' => $bulan_indo,
            'aset_lancar' => $asetLancar,
            'aset_tetap' => $asetTetap,
            'aset_lainnya' => $asetLainnya,
            'liabilitas' => $liabilitas,
            'ekuitas' => $ekuitas,
            'accountbased' => $accountbased,
            'rule_values' => $ruleValues,
            'use_rules' => true, // Selalu gunakan rules
            'is_balanced' => $isBalanced,
            'balance_warning' => $balanceWarning,
        ])->header('Cache-Control', 'no-cache, no-store')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }



    public function trialBalance(Request $request): View
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        $cacheKey = 'trial_balance:'.md5($from.'|'.$to);
        [$akun, $totalDebit, $totalKredit] = Cache::remember($cacheKey, now()->addSeconds(30), function() use ($from, $to) {
            // Select only needed fields from accounts
            // Filter hanya akun 1xxx (aktiva), 2xxx (hutang), dan 3xxx (modal)
            $akun = Akun::select(['kode_akun','nama','tipe'])
                ->where(function($query) {
                    $query->where('kode_akun', 'like', '1%')
                          ->orWhere('kode_akun', 'like', '2%')
                          ->orWhere('kode_akun', 'like', '3%');
                })
                ->orderBy('kode_akun')
                ->get();

            $totalDebit = 0.0;
            $totalKredit = 0.0;
            $openingDate = date('Y-m-d', strtotime($from.' -1 day'));

            // Ambil transaksi pertama dari jurnal per kode akun (untuk saldo awal)
            $firstJurnalDebit = collect([]);
            $firstJurnalKredit = collect([]);
            if (Schema::hasTable('jurnal')) {
                // Ambil transaksi debit pertama per kode akun
                $firstJurnalDebit = DB::table('jurnal')
                    ->select('kode AS akun_kode', 'nominal', 'tanggal')
                    ->where('tipe', 'Debit')
                    ->whereIn('kode', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->nominal ?? 0);
                    });
                
                // Ambil transaksi kredit pertama per kode akun
                $firstJurnalKredit = DB::table('jurnal')
                    ->select('kode AS akun_kode', 'nominal', 'tanggal')
                    ->where('tipe', 'Kredit')
                    ->whereIn('kode', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->nominal ?? 0);
                    });
                
                // Juga ambil dari kolom lawan (karena lawan yang kredit berarti akun kode yang debit)
                $firstJurnalLawanDebit = DB::table('jurnal')
                    ->select('lawan AS akun_kode', 'nominal', 'tanggal')
                    ->where('tipe', 'Kredit') // Jika lawan kredit, berarti akun kode debit
                    ->whereIn('lawan', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->nominal ?? 0);
                    });
                
                $firstJurnalLawanKredit = DB::table('jurnal')
                    ->select('lawan AS akun_kode', 'nominal', 'tanggal')
                    ->where('tipe', 'Debit') // Jika lawan debit, berarti akun kode kredit
                    ->whereIn('lawan', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->nominal ?? 0);
                    });
                
                // Gabungkan hasil dari kode dan lawan
                foreach ($firstJurnalLawanDebit as $kode => $nominal) {
                    if (!$firstJurnalDebit->has($kode) || $firstJurnalDebit->get($kode) == 0) {
                        $firstJurnalDebit->put($kode, $nominal);
                    }
                }
                foreach ($firstJurnalLawanKredit as $kode => $nominal) {
                    if (!$firstJurnalKredit->has($kode) || $firstJurnalKredit->get($kode) == 0) {
                        $firstJurnalKredit->put($kode, $nominal);
                    }
                }
            }

            // Ambil transaksi pertama dari transaksi_bank per kode akun (untuk saldo awal)
            $firstBankDebit = collect([]);
            $firstBankKredit = collect([]);
            if (Schema::hasTable('transaksi_bank')) {
                $firstBankDebit = DB::table('transaksi_bank')
                    ->select('akun_kode', 'total', 'tanggal')
                    ->where('tipe', 'Debit')
                    ->whereNull('deleted_at')
                    ->where('is_edited', false)
                    ->whereIn('akun_kode', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('kode_transaksi_bank', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->total ?? 0);
                    });

                $firstBankKredit = DB::table('transaksi_bank')
                    ->select('akun_kode', 'total', 'tanggal')
                    ->where('tipe', 'Kredit')
                    ->whereNull('deleted_at')
                    ->where('is_edited', false)
                    ->whereIn('akun_kode', $akun->pluck('kode_akun'))
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('kode_transaksi_bank', 'asc')
                    ->get()
                    ->groupBy('akun_kode')
                    ->map(function($group) {
                        return (float) ($group->first()->total ?? 0);
                    });
            }

            // Aggregate jurnal untuk period changes (dalam periode) - untuk menghitung perubahan
            $periodJurnalAgg = collect([]);
            if (Schema::hasTable('jurnal')) {
                $periodJurnalAgg = DB::table('jurnal')
                    ->selectRaw('kode AS akun_kode,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE 0 END) AS sum_debit,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'kredit\' THEN COALESCE(nominal, 0) ELSE 0 END) AS sum_kredit')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->groupBy('kode')
                    ->get()
                    ->keyBy('akun_kode');
                
                // Juga hitung dari kolom lawan untuk jurnal
                $periodJurnalLawanAgg = DB::table('jurnal')
                    ->selectRaw('lawan AS akun_kode,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'kredit\' THEN COALESCE(nominal, 0) ELSE 0 END) AS sum_debit,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE 0 END) AS sum_kredit')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->groupBy('lawan')
                    ->get()
                    ->keyBy('akun_kode');
                
                // Gabungkan hasil dari kode dan lawan
                foreach ($periodJurnalLawanAgg as $kode => $data) {
                    if ($periodJurnalAgg->has($kode)) {
                        $periodJurnalAgg[$kode]->sum_debit += $data->sum_debit;
                        $periodJurnalAgg[$kode]->sum_kredit += $data->sum_kredit;
                    } else {
                        $periodJurnalAgg->put($kode, $data);
                    }
                }
            }

            // Aggregate transaksi_bank untuk period changes (dalam periode) - untuk menghitung perubahan
            $periodBankAgg = collect([]);
            if (Schema::hasTable('transaksi_bank')) {
                $periodBankAgg = DB::table('transaksi_bank')
                    ->selectRaw('akun_kode,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_debit,
                        SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'kredit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_kredit')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->whereNull('deleted_at')
                    ->where('is_edited', false)
                    ->groupBy('akun_kode')
                    ->get()
                    ->keyBy('akun_kode');
            }

            foreach ($akun as $a) {
                // Saldo Awal Debit = nilai debit pertama dari jurnal atau transaksi_bank
                $firstDebitJurnal = $firstJurnalDebit->get($a->kode_akun, 0);
                $firstDebitBank = $firstBankDebit->get($a->kode_akun, 0);
                // Prioritaskan jurnal jika ada, jika tidak gunakan bank
                $a->opDebit = $firstDebitJurnal > 0 ? $firstDebitJurnal : ($firstDebitBank > 0 ? $firstDebitBank : 0.0);
                
                // Saldo Awal Kredit = nilai kredit pertama dari jurnal atau transaksi_bank
                $firstKreditJurnal = $firstJurnalKredit->get($a->kode_akun, 0);
                $firstKreditBank = $firstBankKredit->get($a->kode_akun, 0);
                // Prioritaskan jurnal jika ada, jika tidak gunakan bank
                $a->opKredit = $firstKreditJurnal > 0 ? $firstKreditJurnal : ($firstKreditBank > 0 ? $firstKreditBank : 0.0);
                
                // Jika tidak ada transaksi pertama, gunakan saldo_awal dari akun
                if ($a->opDebit == 0 && $a->opKredit == 0 && $a->saldo_awal && $a->saldo_awal > 0) {
                    if (strtolower($a->saldo_normal ?? 'debit') === 'debit') {
                        $a->opDebit = (float) $a->saldo_awal;
                    } else {
                        $a->opKredit = (float) $a->saldo_awal;
                    }
                }

                // Debit/Kredit dalam periode = jumlah dari jurnal dan transaksi_bank dalam periode
                $chJurnal = $periodJurnalAgg->get($a->kode_akun);
                $chBank = $periodBankAgg->get($a->kode_akun);
                
                $a->chDebit = ($chJurnal ? (float) $chJurnal->sum_debit : 0.0) + 
                              ($chBank ? (float) $chBank->sum_debit : 0.0);
                $a->chKredit = ($chJurnal ? (float) $chJurnal->sum_kredit : 0.0) + 
                               ($chBank ? (float) $chBank->sum_kredit : 0.0);

                // Saldo Akhir Debit = Saldo Awal Debit + Debit (periode)
                // Saldo Akhir Kredit = Saldo Awal Kredit + Kredit (periode)
                $endingDebit = $a->opDebit + $a->chDebit;
                $endingKredit = $a->opKredit + $a->chKredit;

                // Hitung net balance untuk menentukan ending debit/kredit
                $netDebit = $endingDebit - $endingKredit;

                if ($a->tipe === 'debit') {
                    $a->endingDebit = $netDebit > 0 ? $netDebit : 0.0;
                    $a->endingKredit = 0.0;
                    $totalDebit += $a->endingDebit;
                } else {
                    $a->endingDebit = 0.0;
                    $a->endingKredit = $netDebit < 0 ? abs($netDebit) : 0.0;
                    $totalKredit += $a->endingKredit;
                }

                // Saldo = ditambah jika ada saldo akhir debit, dikurangi jika ada saldo akhir kredit
                $a->netBalance = $a->endingDebit - $a->endingKredit;
            }

            $akun = $akun->filter(function($a) {
                return ($a->opDebit ?? 0) != 0 ||
                       ($a->opKredit ?? 0) != 0 ||
                       ($a->chDebit ?? 0) != 0 ||
                       ($a->chKredit ?? 0) != 0 ||
                       ($a->endingDebit ?? 0) != 0 ||
                       ($a->endingKredit ?? 0) != 0;
            })->values();

            return [$akun, $totalDebit, $totalKredit];
        });

        return view('ledger.trial', compact('from','to','akun','totalDebit','totalKredit'));
    }

    public function glDetail(Request $request): View|RedirectResponse
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        try {
            // Ambil data dari transaksi, jurnal, dan transaksi_bank
            $rows = collect();

            // 1. Ambil dari tabel transaksi
            $transaksiRows = Transaksi::with('akun')
                ->whereBetween('tanggal', [$from, $to])
                ->orderBy('tanggal', 'asc')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($row) {
                    $row->source = 'transaksi';
                    $row->akun_kode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? 'N/A';
                    return $row;
                });

            // 2. Ambil dari tabel jurnal
            if (Schema::hasTable('jurnal')) {
                $jurnalRows = DB::table('jurnal')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function($row) {
                        return (object) [
                            'id' => $row->id ?? null,
                            'tanggal' => $row->tanggal ?? date('Y-m-d'),
                            'kode_voucer' => $row->kode_transaksi ?? 'N/A',
                            'kode_project' => null,
                            'akun_kode' => $row->kode ?? 'N/A',
                            'deskripsi' => $row->keterangan ?? '',
                            'tipe' => $row->tipe ?? 'debit',
                            'nominal' => (float)($row->nominal ?? 0),
                            'debit' => strtolower($row->tipe ?? '') === 'debit' ? (float)($row->nominal ?? 0) : 0,
                            'kredit' => strtolower($row->tipe ?? '') === 'kredit' ? (float)($row->nominal ?? 0) : 0,
                            'jenis' => strtolower($row->tipe ?? 'debit'),
                            'jumlah' => (float)($row->nominal ?? 0),
                            'transaksi_type' => $row->tipe ?? 'debit',
                            'source' => 'jurnal',
                            'akun' => null
                        ];
                    });
                $rows = $rows->merge($jurnalRows);
            }

            // 3. Ambil dari tabel transaksi_bank
            if (Schema::hasTable('transaksi_bank')) {
                $bankRows = DB::table('transaksi_bank')
                    ->whereNull('deleted_at')
                    ->where('is_edited', false)
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('kode_transaksi_bank', 'asc')
                    ->get()
                    ->map(function($row) {
                        return (object) [
                            'id' => $row->id ?? null,
                            'tanggal' => $row->tanggal ?? date('Y-m-d'),
                            'kode_voucer' => $row->kode_transaksi_bank ?? 'N/A',
                            'kode_project' => null,
                            'akun_kode' => $row->akun_kode ?? 'N/A',
                            'deskripsi' => $row->keterangan ?? '',
                            'tipe' => $row->tipe ?? 'debit',
                            'nominal' => (float)($row->total ?? 0),
                            'debit' => strtolower($row->tipe ?? '') === 'debit' ? (float)($row->total ?? 0) : 0,
                            'kredit' => strtolower($row->tipe ?? '') === 'kredit' ? (float)($row->total ?? 0) : 0,
                            'jenis' => strtolower($row->tipe ?? 'debit'),
                            'jumlah' => (float)($row->total ?? 0),
                            'transaksi_type' => $row->tipe ?? 'debit',
                            'source' => 'transaksi_bank',
                            'akun' => null
                        ];
                    });
                $rows = $rows->merge($bankRows);
            }

            // Gabungkan semua data
            $rows = $rows->merge($transaksiRows);

            // Filter out rows without valid account codes
            $rows = $rows->filter(function($row) {
                return !empty($row->akun_kode) && $row->akun_kode !== 'N/A';
            });

            // Urutkan berdasarkan akun_kode, lalu tanggal, lalu id
            $rows = $rows->sortBy(function($row) {
                $akunKode = $row->akun_kode ?? 'ZZZ';
                $tanggal = $row->tanggal ?? '9999-12-31';
                $id = $row->id ?? $row->kode_voucer ?? 'ZZZ';
                return $akunKode . '_' . $tanggal . '_' . $id;
            })->values();

            // Ensure all rows have required fields for PDF/Excel export
            $rows = $rows->map(function($row) {
                $row->tanggal = $row->tanggal ?? date('Y-m-d');
                $row->kode_voucer = $row->kode_voucer ?? 'N/A';
                $row->akun_kode = $row->akun_kode ?? 'N/A';
                $row->deskripsi = $row->deskripsi ?? '';
                return $row;
            });

            return view('ledger.gl', compact('from','to','rows'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GL Detail Error: ' . $e->getMessage(), [
                'from' => $from,
                'to' => $to,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal memuat rincian buku besar.');
        }
    }

    public function cashFlows(Request $request): View
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));
        
        // Cek apakah tabel jurnal ada
        $jurnalTableExists = Schema::hasTable('jurnal');
        
        // ===== OPERATING ACTIVITIES =====
        // Operating: jika debit +, jika kredit - (khusus di tipe transaksi bank/jurnal)
        // Operating biasanya dari akun 4xxx (pendapatan) dan 5xxx (beban)
        $operating = 0.0;
        
        // 1. Dari tabel jurnal - Operating (akun 4xxx dan 5xxx)
        $operatingJurnal = 0.0;
        if ($jurnalTableExists) {
            $operatingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '4%')
                      ->orWhere('kode', 'like', '5%')
                      ->orWhere('lawan', 'like', '4%')
                      ->orWhere('lawan', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        // 2. Dari tabel transaksi_bank - Operating (akun 4xxx dan 5xxx)
        $operatingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $operatingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->where('is_edited', false)
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '4%')
                      ->orWhere('akun_kode', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        $operating = $operatingJurnal + $operatingBank;
        
        // ===== INVESTING ACTIVITIES =====
        // Investing: jika debit +, jika kredit - (khusus di tipe transaksi bank/jurnal)
        // Investing biasanya dari akun 12xx (aset tetap)
        $investing = 0.0;
        
        // 1. Dari tabel jurnal - Investing (akun 12xx)
        $investingJurnal = 0.0;
        if ($jurnalTableExists) {
            $investingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '12%')
                      ->orWhere('lawan', 'like', '12%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        // 2. Dari tabel transaksi_bank - Investing (akun 12xx)
        $investingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $investingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where('akun_kode', 'like', '12%')
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        $investing = $investingJurnal + $investingBank;
        
        // ===== FINANCING ACTIVITIES =====
        // Financing: jika debit +, jika kredit - (khusus di tipe transaksi bank/jurnal)
        // Financing biasanya dari akun 2xxx (hutang) dan 3xxx (modal)
        $financing = 0.0;
        
        // 1. Dari tabel jurnal - Financing (akun 2xxx dan 3xxx)
        $financingJurnal = 0.0;
        if ($jurnalTableExists) {
            $financingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '2%')
                      ->orWhere('kode', 'like', '3%')
                      ->orWhere('lawan', 'like', '2%')
                      ->orWhere('lawan', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        // 2. Dari tabel transaksi_bank - Financing (akun 2xxx dan 3xxx)
        $financingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $financingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->where('is_edited', false)
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '2%')
                      ->orWhere('akun_kode', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        $financing = $financingJurnal + $financingBank;
        
        // ===== NET CASH FLOW =====
        // Net Cash Flow = Operating + Investing + Financing
        $netCashFlow = $operating + $investing + $financing;

        // Cash beginning balance: sum of 11xx accounts until day before period start
        $openingDate = date('Y-m-d', strtotime($from.' -1 day'));
        $cashAccounts = Akun::where('kode_akun', 'like', '11%')->get();
        $cashBeginning = 0.0;
        foreach ($cashAccounts as $acc) {
            $cashBeginning += $acc->getBalance('1900-01-01', $openingDate);
        }
        $cashEnding = $cashBeginning + $netCashFlow;

        return view('ledger.cash', compact('from','to','operating','investing','financing','netCashFlow','cashBeginning','cashEnding'));
    }

    public function activity(Request $request): View
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        // Get accounts based on account codes
        $pendapatan = KodeAkun::where('kode_akun', 'like', '4%')
            ->orderBy('kode_akun')
            ->get();
        $beban = KodeAkun::where('kode_akun', 'like', '5%')
            ->orderBy('kode_akun')
            ->get();

        // Income from operations: revenue accounts (4xxx) — credit increases revenue
        $incomeTotal = (float) DB::table('transaksi')
            ->whereBetween('tanggal', [$from, $to])
            // ->where('akun_kode', 'like', // Kolom tidak ada'4%')
            ->selectRaw('COALESCE(SUM(kredit - debit),0) AS total')
            ->value('total');

        // Operating expenses: expense accounts (5xxx) — debit increases expense
        $expenseTotal = (float) DB::table('transaksi')
            ->whereBetween('tanggal', [$from, $to])
            // ->where('akun_kode', 'like', // Kolom tidak ada'5%')
            ->selectRaw('COALESCE(SUM(debit - kredit),0) AS total')
            ->value('total');

        $netProfit = $incomeTotal - $expenseTotal; // Net Profit/Loss

        return view('ledger.activity', compact('from','to','pendapatan','beban','incomeTotal','expenseTotal','netProfit'));
    }

    public function fixedAssets(Request $request): View
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));

        // Fixed assets accounts: kode akun 12xx (1201, 1202, 1203, 1204, 1299)
        // Filter langsung berdasarkan akun_kode yang dimulai dengan "12" (aset tetap)
        $rows = Transaksi::with('akun')
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Filter untuk akun 12xx setelah fetch
        $rows = $rows->filter(function($row) {
            $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? '';
            return strpos($akunKode, '12') === 0;
        });

        return view('ledger.fixed', compact('from','to','rows'));
    }

    public function budget(Request $request): View
    {
        $from = $request->input('from', date('Y-01-01'));
        $to = $request->input('to', date('Y-m-d'));
        
        // Get budget data and actual transactions for comparison
        // Note: Budget model tidak tersedia, gunakan DB query langsung jika diperlukan
        $budgetData = collect([]);
            
        $actualData = Transaksi::with('akun')
            ->whereBetween('tanggal', [$from, $to])
            ->get();
            
        return view('ledger.budget', compact('from', 'to', 'budgetData', 'actualData'));
    }

    public function exportPosisiKeuanganPdf(Request $request)
    {
        $tanggal_dari = $request->input('tanggal_dari', date('Y-m-01'));
        $tanggal_sampai = $request->input('tanggal_sampai', date('Y-m-d'));
        $serial = null;
        $periodStr = $this->formatDatePeriod($tanggal_dari, $tanggal_sampai);
        
        // Untuk backward compatibility
        if ($request->has('tanggal') && !$request->has('tanggal_dari')) {
            $tanggal_dari = $request->input('tanggal');
            $tanggal_sampai = $request->input('tanggal');
        }
        
        // Prepare the same data structure as the main view
        $accountbased = [
            'aset_lancar' => [
                '1101' => 'Kas dan Setara Kas',
                '1102' => 'Piutang Usaha', 
                '1103' => 'Persediaan',
                '1104' => 'Biaya Dibayar Dimuka',
                '1105' => 'Aset Lancar Lainnya'
            ],
            'aset_tetap' => [
                '1201' => 'Tanah',
                '1202' => 'Bangunan',
                '1203' => 'Mesin dan Peralatan',
                '1204' => 'Kendaraan',
                '1299' => 'Akumulasi Penyusutan Aset Tetap'
            ],
            'aset_lainnya' => [
                '1301' => 'Investasi Jangka Panjang',
                '1302' => 'Aset Tak Berwujud (Goodwill, Hak Paten)',
                '1303' => 'Aset Lainnya'
            ],
            'liabilitas' => [
                '2101' => 'Utang Usaha',
                '2102' => 'Utang Pajak',
                '2103' => 'Utang Jangka Panjang',
                '2104' => 'Beban yang Masih Harus Dibayar'
            ],
            'ekuitas' => [
                '3101' => 'Modal Saham',
                '3102' => 'Laba Ditahan',
                '3103' => 'Ekuitas Lainnya'
            ]
        ];
        
        // Collect all account codes we need
        $allCodes = array_merge(
            array_keys($accountbased['aset_lancar']),
            array_keys($accountbased['aset_tetap']),
            array_keys($accountbased['aset_lainnya']),
            array_keys($accountbased['liabilitas']),
            array_keys($accountbased['ekuitas'])
        );
        
        // Load accounts
        $akun = KodeAkun::select(['kode_akun', 'nama', 'tipe'])
            ->whereIn('kode_akun', $allCodes)
            ->orderBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');
        
        // Ensure all accounts exist
        foreach ($allCodes as $akunCode) {
            if (!$akun->has($akunCode)) {
                $nama = $accountbased['aset_lancar'][$akunCode] ?? 
                       $accountbased['aset_tetap'][$akunCode] ?? 
                       $accountbased['aset_lainnya'][$akunCode] ?? 
                       $accountbased['liabilitas'][$akunCode] ?? 
                       $accountbased['ekuitas'][$akunCode] ?? 
                       null;
                
                if ($nama) {
                    $akun->put($akunCode, (object)[
                        'kode_akun' => $akunCode,
                        'nama' => $nama,
                        'tipe' => (strpos((string)$akunCode, '1299') !== false ||
                                  in_array($akunCode, array_keys($accountbased['liabilitas'])) ||
                                  in_array($akunCode, array_keys($accountbased['ekuitas']))) ? 'Kredit' : 'Debit'
                    ]);
                }
            }
        }
        
        // Categorize accounts
        $asetLancar = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_lancar']);
        })->sortKeys();
        $asetTetap = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_tetap']);
        })->sortKeys();
        $asetLainnya = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['aset_lainnya']);
        })->sortKeys();
        $liabilitas = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['liabilitas']);
        })->sortKeys();
        $ekuitas = $akun->filter(function($item, $key) use ($accountbased) {
            return array_key_exists($key, $accountbased['ekuitas']);
        })->sortKeys();

        $pdf = Pdf::loadView('ledger.posisi-pdf', [
            'tanggal_dari' => $tanggal_dari,
            'tanggal_sampai' => $tanggal_sampai,
            'periodStr' => $periodStr,
            'serial' => $serial,
            'accountbased' => $accountbased,
            'aset_lancar' => $asetLancar,
            'aset_tetap' => $asetTetap,
            'aset_lainnya' => $asetLainnya,
            'liabilitas' => $liabilitas,
            'ekuitas' => $ekuitas,
        ]);
        $filename = 'Neraca-Keuangan-' . date('Ymd', strtotime($tanggal_dari)) . '-' . date('Ymd', strtotime($tanggal_sampai)) . '.pdf';
        return $pdf->download($filename);
    }
    
    public function exportPosisiKeuanganExcel(Request $request): BinaryFileResponse
    {
        $tanggal_dari = $request->input('tanggal_dari', date('Y-m-01'));
        $tanggal_sampai = $request->input('tanggal_sampai', date('Y-m-d'));
        
        // Untuk backward compatibility
        if ($request->has('tanggal') && !$request->has('tanggal_dari')) {
            $tanggal_dari = $request->input('tanggal');
            $tanggal_sampai = $request->input('tanggal');
        }
        
        $filename = 'neraca-keuangan-' . date('Ymd-His') . '.xlsx';
        return Excel::download(
            new PosisiKeuanganExport($tanggal_dari, $tanggal_sampai), 
            $filename
        );
    }
    


    // mengambil data trial balance
    protected function getTrialBalanceData(string $from, string $to): array
    {
        $cacheKey = 'trial_balance_pdf:' . md5($from . '|' . $to);
        return Cache::remember($cacheKey, now()->addSeconds(30), function() use ($from, $to) {
            // Filter hanya akun 1xxx (aktiva), 2xxx (hutang), dan 3xxx (modal)
            $akun = Akun::select(['id','kode_akun','nama_akun','tipe','saldo_awal'])
                ->where(function($query) {
                    $query->where('kode_akun', 'like', '1%')
                          ->orWhere('kode_akun', 'like', '2%')
                          ->orWhere('kode_akun', 'like', '3%');
                })
                ->orderBy('kode_akun')
                ->get();

            $totalDebit = 0.0;
            $totalKredit = 0.0;
            $openingDate = date('Y-m-d', strtotime($from.' -1 day'));

            // Get first transaction for each account to determine Saldo Awal
            // Menggunakan query untuk mendapatkan transaksi pertama per akun berdasarkan tanggal terawal
            // Note: Tabel transaksi tidak memiliki kolom akun_kode, debit, kredit, transaksi_type, jumlah
            $allTransactions = Transaksi::select('kode_voucer', 'tipe', 'nominal', 'tanggal')
                ->orderBy('tanggal')
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();
            
            $firstTransactions = $allTransactions->groupBy('akun_kode')->map(function($group) {
                // Ambil transaksi pertama dari grup (transaksi dengan tanggal terawal)
                $first = $group->first();
                $firstDebit = 0;
                $firstKredit = 0;
                
                if (!empty($first->debit) && $first->debit > 0) {
                    $firstDebit = (float)$first->debit;
                } elseif (!empty($first->kredit) && $first->kredit > 0) {
                    $firstKredit = (float)$first->kredit;
                } elseif (strtolower(trim($first->jenis ?? $first->transaksi_type ?? '')) === 'debit') {
                    $firstDebit = (float)($first->jumlah ?? $first->nominal ?? 0);
                } elseif (strtolower(trim($first->jenis ?? $first->transaksi_type ?? '')) === 'kredit') {
                    $firstKredit = (float)($first->jumlah ?? $first->nominal ?? 0);
                }
                
                return (object)[
                    'first_debit' => $firstDebit,
                    'first_kredit' => $firstKredit
                ];
            });

            // Aggregate opening balances (transactions up to day before period start)
            // Gunakan logika CASE seperti di trialBalance() untuk menangani NULL dan berbagai format
            $openingAgg = Transaksi::selectRaw('akun_kode,
                SUM(
                    CASE
                        WHEN LOWER(TRIM(COALESCE(transaksi_type, \'\'))) = \'debit\' THEN COALESCE(nominal, 0)
                        ELSE 0
                    END
                ) AS sum_debit,
                SUM(
                    CASE
                        WHEN LOWER(TRIM(COALESCE(transaksi_type, \'\'))) = \'kredit\' THEN COALESCE(nominal, 0)
                        ELSE 0
                    END
                ) AS sum_kredit')
                ->whereDate('tanggal', '>=', '1900-01-01')
                ->whereDate('tanggal', '<=', $openingDate)
                ->groupBy('akun_kode')
                ->get()
                ->keyBy('akun_kode');

            // Aggregate period changes (transactions within selected period)
            // Gunakan logika CASE seperti di trialBalance() untuk menangani NULL dan berbagai format
            $periodAgg = Transaksi::selectRaw('akun_kode,
                SUM(
                    CASE
                        WHEN LOWER(TRIM(COALESCE(transaksi_type, \'\'))) = \'debit\' THEN COALESCE(nominal, 0)
                        ELSE 0
                    END
                ) AS sum_debit,
                SUM(
                    CASE
                        WHEN LOWER(TRIM(COALESCE(transaksi_type, \'\'))) = \'kredit\' THEN COALESCE(nominal, 0)
                        ELSE 0
                    END
                ) AS sum_kredit')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->groupBy('akun_kode')
                ->get()
                ->keyBy('akun_kode');

            // Aggregate transaksi_bank untuk opening balance
            $openingBankAgg = DB::table('transaksi_bank')
                ->selectRaw('akun_kode,
                    SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_debit,
                    SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'kredit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_kredit')
                ->whereDate('tanggal', '>=', '1900-01-01')
                ->whereDate('tanggal', '<=', $openingDate)
                ->whereNull('deleted_at')
                ->groupBy('akun_kode')
                ->get()
                ->keyBy('akun_kode');

            // Aggregate transaksi_bank untuk period changes
            $periodBankAgg = DB::table('transaksi_bank')
                ->selectRaw('akun_kode,
                    SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_debit,
                    SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'kredit\' THEN COALESCE(total, 0) ELSE 0 END) AS sum_kredit')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->whereNull('deleted_at')
                ->where('is_edited', false)
                ->groupBy('akun_kode')
                ->get()
                ->keyBy('akun_kode');

            foreach ($akun as $a) {
                // Gunakan transaksi pertama untuk menentukan Saldo Awal
                $firstTrans = $firstTransactions->get($a->kode_akun);
                if ($firstTrans) {
                    // Jika transaksi pertama adalah Debit, gunakan untuk Saldo Awal Debit
                    // Jika transaksi pertama adalah Kredit, gunakan untuk Saldo Awal Kredit
                    $openingDebitInitial = (float)($firstTrans->first_debit ?? 0);
                    $openingKreditInitial = (float)($firstTrans->first_kredit ?? 0);
                } else {
                    // Jika tidak ada transaksi pertama, gunakan saldo_awal dari akun
                    $initial = (float) ($a->saldo_awal ?? 0);
                    $openingDebitInitial = ($a->tipe === 'debit') ? $initial : 0.0;
                    $openingKreditInitial = ($a->tipe === 'kredit') ? $initial : 0.0;
                }

                $opAgg = $openingAgg->get($a->kode_akun);
                $chAgg = $periodAgg->get($a->kode_akun);
                $opBankAgg = $openingBankAgg->get($a->kode_akun);
                $chBankAgg = $periodBankAgg->get($a->kode_akun);

                $openingDebitTrans = ($opAgg ? (float) $opAgg->sum_debit : 0.0) + 
                                     ($opBankAgg ? (float) $opBankAgg->sum_debit : 0.0);
                $openingKreditTrans = ($opAgg ? (float) $opAgg->sum_kredit : 0.0) + 
                                      ($opBankAgg ? (float) $opBankAgg->sum_kredit : 0.0);

                // Saldo Awal = nilai dari transaksi pertama saja (jika ada)
                $a->opDebit = $openingDebitInitial;
                $a->opKredit = $openingKreditInitial;

                $a->chDebit = ($chAgg ? (float) $chAgg->sum_debit : 0.0) + 
                              ($chBankAgg ? (float) $chBankAgg->sum_debit : 0.0);
                $a->chKredit = ($chAgg ? (float) $chAgg->sum_kredit : 0.0) + 
                               ($chBankAgg ? (float) $chBankAgg->sum_kredit : 0.0);

                $netDebit = ($a->opDebit + $a->chDebit) - ($a->opKredit + $a->chKredit);

                if ($a->tipe === 'debit') {
                    $a->endingDebit = $netDebit > 0 ? $netDebit : 0.0;
                    $a->endingKredit = 0.0;
                    $totalDebit += $a->endingDebit;
                } else {
                    $a->endingDebit = 0.0;
                    $a->endingKredit = $netDebit < 0 ? abs($netDebit) : 0.0;
                    $totalKredit += $a->endingKredit;
                }
                $a->netBalance = $netDebit;
            }

            $totalDebit = $akun->sum('endingDebit');
            $totalKredit = $akun->sum('endingKredit');
            // filter akun, biarkan semua akun tercetak/trial-pdf
            // $akun = $akun->filter(function($a) {
            //     return ($a->opDebit ?? 0) != 0 ||
            //            ($a->opKredit ?? 0) != 0 ||
            //            ($a->chDebit ?? 0) != 0 ||
            //            ($a->chKredit ?? 0) != 0 ||
            //            ($a->endingDebit ?? 0) != 0 ||
            //            ($a->endingKredit ?? 0) != 0;
            // })->values();
            $akun = $akun->values();
            return [$akun, $totalDebit, $totalKredit];
        });
    }
    
    // Tambahkan helper di controller
    protected function formatDatePeriod(string $from, string $to): string
    {
        $carbonFrom = Carbon::parse($from)->locale('id');
        $carbonTo = Carbon::parse($to)->locale('id');
        $fromStr = $carbonFrom->format('d M Y');
        $toStr = $carbonTo->format('d M Y');
        if ($from == $to) {
            return $fromStr;
        }
        return $fromStr . ' s/d ' . $toStr;
    }
    
    // Pastikan mengimpor facade Excel
    protected function getExcelFacade(): mixed
    {
        return app('excel');
    }

    private function handleNeracaSaldoPdf(string $from, string $to, string $periodStr): \Illuminate\Http\Response
    {
        // Ambil data trial balance seperti trialBalance()
        [$akun, $totalDebit, $totalKredit] = $this->getTrialBalanceData($from, $to);
        // Generate serial number in format NS-YYYYMMDDHHMMSS
        $serial = 'NS-' . date('YmdHis');
        $pdf = Pdf::loadView('ledger.trial-pdf', [
            'from' => $from,
            'to' => $to,
            'periodStr' => $periodStr,
            'akun' => $akun,
            'totalDebit' => $totalDebit,
            'totalKredit' => $totalKredit,
            'serial' => $serial
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('enable-smart-shrinking', true);
        $filename = 'Neraca-Saldo-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
        return $pdf->download($filename);
    }

    private function handleGeneralLedgerDetailPdf(string $from, string $to, string $periodStr, string $menu): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        try {
            $serial = 'GL-' . date('YmdHis');

            // Use the same data collection logic as glDetail() method
            $rows = collect();

            // 1. Ambil dari tabel transaksi
            $transaksiRows = Transaksi::with('akun')
                ->whereBetween('tanggal', [$from, $to])
                ->orderBy('tanggal', 'asc')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($row) {
                    $row->source = 'transaksi';
                    $row->akun_kode = $row->akun_kode ?? $row->akun?->kode_akun ?? 'N/A';
                    return $row;
                });

            // 2. Ambil dari tabel jurnal
            if (Schema::hasTable('jurnal')) {
                $jurnalRows = DB::table('jurnal')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function($row) {
                        return (object) [
                            'id' => $row->id ?? null,
                            'tanggal' => $row->tanggal,
                            'kode_voucer' => $row->kode_transaksi ?? 'N/A',
                            'kode_project' => null,
                            'akun_kode' => $row->kode ?? 'N/A',
                            'deskripsi' => $row->keterangan ?? '',
                            'tipe' => $row->tipe ?? 'debit',
                            'nominal' => (float)($row->nominal ?? 0),
                            'debit' => strtolower($row->tipe ?? '') === 'debit' ? (float)($row->nominal ?? 0) : 0,
                            'kredit' => strtolower($row->tipe ?? '') === 'kredit' ? (float)($row->nominal ?? 0) : 0,
                            'jenis' => strtolower($row->tipe ?? 'debit'),
                            'jumlah' => (float)($row->nominal ?? 0),
                            'transaksi_type' => $row->tipe ?? 'debit',
                            'source' => 'jurnal',
                            'akun' => null
                        ];
                    });
                $rows = $rows->merge($jurnalRows);
            }

            // 3. Ambil dari tabel transaksi_bank
            if (Schema::hasTable('transaksi_bank')) {
                $bankRows = DB::table('transaksi_bank')
                    ->whereNull('deleted_at')
                    ->whereDate('tanggal', '>=', $from)
                    ->whereDate('tanggal', '<=', $to)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('kode_transaksi_bank', 'asc')
                    ->get()
                    ->map(function($row) {
                        return (object) [
                            'id' => $row->id ?? null,
                            'tanggal' => $row->tanggal,
                            'kode_voucer' => $row->kode_transaksi_bank ?? 'N/A',
                            'kode_project' => null,
                            'akun_kode' => $row->akun_kode ?? 'N/A',
                            'deskripsi' => $row->keterangan ?? '',
                            'tipe' => $row->tipe ?? 'debit',
                            'nominal' => (float)($row->total ?? 0),
                            'debit' => strtolower($row->tipe ?? '') === 'debit' ? (float)($row->total ?? 0) : 0,
                            'kredit' => strtolower($row->tipe ?? '') === 'kredit' ? (float)($row->total ?? 0) : 0,
                            'jenis' => strtolower($row->tipe ?? 'debit'),
                            'jumlah' => (float)($row->total ?? 0),
                            'transaksi_type' => $row->tipe ?? 'debit',
                            'source' => 'transaksi_bank',
                            'akun' => null
                        ];
                    });
                $rows = $rows->merge($bankRows);
            }

            // Gabungkan semua data
            $rows = $rows->merge($transaksiRows);

            // Filter out rows without valid account codes
            $rows = $rows->filter(function($row) {
                return !empty($row->akun_kode) && $row->akun_kode !== 'N/A';
            });

            // Urutkan berdasarkan akun_kode, lalu tanggal, lalu id
            $rows = $rows->sortBy(function($row) {
                $akunKode = $row->akun_kode ?? 'ZZZ';
                $tanggal = $row->tanggal ?? '9999-12-31';
                $id = $row->id ?? $row->kode_voucer ?? 'ZZZ';
                return $akunKode . '_' . $tanggal . '_' . $id;
            })->values();

            $pdf = Pdf::loadView('ledger.gl-pdf', [
                'from' => $from,
                'to' => $to,
                'periodStr' => $periodStr,
                'rows' => $rows,
                'serial' => $serial
            ]);
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOption('margin-top', 5);
            $pdf->setOption('margin-bottom', 5);
            $pdf->setOption('margin-left', 5);
            $pdf->setOption('margin-right', 5);
            $pdf->setOption('enable-smart-shrinking', true);
            $filename = 'General-Ledger-Detail-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage(), [
                'menu' => $menu,
                'from' => $from,
                'to' => $to,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal membuat file PDF.');
        }
    }

    private function handleCashFlowsPdf(string $from, string $to, string $periodStr): BinaryFileResponse|Response|RedirectResponse
    {
        $serial = 'CF-' . date('YmdHis');
        // Ambil data seperti cashFlows()
        $jurnalTableExists = Schema::hasTable('jurnal');

        // Operating Activities
        $operating = 0.0;
        $operatingJurnal = 0.0;
        if ($jurnalTableExists) {
            $operatingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '4%')
                      ->orWhere('kode', 'like', '5%')
                      ->orWhere('lawan', 'like', '4%')
                      ->orWhere('lawan', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }

        $operatingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $operatingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '4%')
                      ->orWhere('akun_kode', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        $operating = $operatingJurnal + $operatingBank;

        // Investing Activities
        $investing = 0.0;
        $investingJurnal = 0.0;
        if ($jurnalTableExists) {
            $investingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '12%')
                      ->orWhere('lawan', 'like', '12%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }

        $investingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $investingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where('akun_kode', 'like', '12%')
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        $investing = $investingJurnal + $investingBank;

        // Financing Activities
        $financing = 0.0;
        $financingJurnal = 0.0;
        if ($jurnalTableExists) {
            $financingJurnal = (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('kode', 'like', '2%')
                      ->orWhere('kode', 'like', '3%')
                      ->orWhere('lawan', 'like', '2%')
                      ->orWhere('lawan', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }

        $financingBank = 0.0;
        if (Schema::hasTable('transaksi_bank')) {
            $financingBank = (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $from)
                ->whereDate('tanggal', '<=', $to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '2%')
                      ->orWhere('akun_kode', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        $financing = $financingJurnal + $financingBank;

        $netCashFlow = $operating + $investing + $financing;

        // Cash beginning balance
        $openingDate = date('Y-m-d', strtotime($from.' -1 day'));
        $cashAccounts = Akun::where('kode_akun', 'like', '11%')->get();
        $cashBeginning = 0.0;
        foreach ($cashAccounts as $acc) {
            $cashBeginning += $acc->getBalance('1900-01-01', $openingDate);
        }
        $cashEnding = $cashBeginning + $netCashFlow;

        try {
            $pdf = Pdf::loadView('ledger.cash-pdf', [
                'from' => $from,
                'to' => $to,
                'periodStr' => $periodStr,
                'operating' => $operating,
                'investing' => $investing,
                'financing' => $financing,
                'netCashFlow' => $netCashFlow,
                'cashBeginning' => $cashBeginning,
                'cashEnding' => $cashEnding,
                'serial' => $serial
            ]);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('margin-top', 5);
            $pdf->setOption('margin-bottom', 5);
            $pdf->setOption('margin-left', 5);
            $pdf->setOption('margin-right', 5);
            $pdf->setOption('enable-smart-shrinking', true);
            $filename = 'Arus-Kas-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cash Flows PDF Error: ' . $e->getMessage(), [
                'from' => $from,
                'to' => $to,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal membuat file PDF arus kas.');
        }
    }

    private function handleFixedAssetsPdf(string $from, string $to, string $periodStr): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
    {
        $serial = 'FA-' . date('YmdHis');
        // Ambil data seperti fixedAssets()
        $rows = Transaksi::with('akun')
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Filter untuk akun 12xx
        $rows = $rows->filter(function($row) {
            $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? '';
            return strpos($akunKode, '12') === 0;
        });

        $pdf = Pdf::loadView('ledger.fixed-pdf', [
            'from' => $from,
            'to' => $to,
            'periodStr' => $periodStr,
            'rows' => $rows,
            'serial' => $serial
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('enable-smart-shrinking', true);
        $filename = 'Aset-Tetap-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
        return $pdf->download($filename);
    }

    private function handleLaporanKeuanganPdf(string $from, string $to, string $periodStr, string $filter_akun_id, string $menu): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $query = Akun::select(['kode_akun', 'nama', 'tipe']);

        if ($filter_akun_id !== 'semua') {
            $query->where('id', $filter_akun_id);
        }

        $akun_data = $query->orderBy('kode_akun')->get();
        $serial = 'MDK-' . date('YmdHis');

        $pdf = Pdf::loadView('ledger.neraca-pdf', [
            'from' => $from,
            'to' => $to,
            'periodStr' => $periodStr,
            'akun_data' => $akun_data,
            'menu' => $menu,
            'serial' => $serial,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('enable-smart-shrinking', true);
        $filename = 'Laporan-Keuangan-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
        return $pdf->download($filename);
    }

    private function handleDefaultPdf(string $from, string $to, string $periodStr, string $menu): BinaryFileResponse|Response|RedirectResponse
    {
        $query = Transaksi::with('akun')
            ->whereBetween('tanggal', [$from, $to]);

        // Filter khusus untuk menu "Aset Tetap" - hanya kode akun 12xx
        // Note: Tabel transaksi tidak memiliki kolom akun_kode, jadi filter dihapus
        // Filter by akun_id if specified - juga dihapus karena tidak ada relationship akun
        // if ($menu === 'Aset Tetap') {
        //     $query->where('akun_kode', 'like', '12%');
        // }

        $rows = $query->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();
        $pdf = Pdf::loadView('ledger.pdf', [
            'from' => $from,
            'to' => $to,
            'periodStr' => $periodStr,
            'rows' => $rows,
            'menu' => $menu,
            'serial' => null,
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('enable-smart-shrinking', true);
        $filename = str_replace(' ', '-', ucfirst($menu)) . '-' . date('Ymd', strtotime($from)) . '-' . date('Ymd', strtotime($to)) . '.pdf';
        return $pdf->download($filename);
    }
}
