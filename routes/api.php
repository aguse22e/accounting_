<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ActivityUser;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Akun;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Project;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan rute API untuk aplikasi Anda. Rute-rute
| ini dimuat oleh RouteServiceProvider dan semuanya akan ditetapkan ke
| grup middleware "api". Buat sesuatu yang hebat!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API untuk dashboard real-time - tidak untuk manager
Route::get('/dashboard-data', function () {
    // Cek jika user adalah manager, tolak akses
    if (Auth::check() && Auth::user()->role === 'manager') {
        return response()->json(['error' => 'Akses ditolak. Manager hanya dapat mengakses halaman Ledger.'], 403);
    }
    // Periode default bulan berjalan
    $from = date('Y-m-01');
    $to = date('Y-m-t');

    // Tentukan akun bank
    $bankAccountCodes = Akun::query()
        ->where(function ($q) {
            $q->where('nama', 'like', '%bank%')
              ->orWhere('kode_akun', 'like', '11%');
        })
        ->pluck('kode_akun');

    // Hitung saldo akhir bank menggunakan logika yang sama seperti KodeAkun->getFreshBalance()
    // Menghitung dari tanggal awal sampai hari ini (akumulatif), bukan hanya MTD
    $tanggal_sampai = date('Y-m-d');
    
    // 1. Hitung dari tabel transaksi menggunakan kolom tipe dan nominal
    // Catatan: Tabel transaksi tidak memiliki kolom akun_kode, jadi lewati untuk sekarang
    $bankDebet = 0;
    $bankKredit = 0;
    
    // 2. Tambahkan dari tabel transaksi_bank (akun bank utama)
    $bankDebetBank = (float) DB::table('transaksi_bank')
        ->whereIn('akun_kode', $bankAccountCodes)
        ->whereDate('tanggal', '>=', '1900-01-01')
        ->whereDate('tanggal', '<=', $tanggal_sampai)
        ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
        ->whereNull('deleted_at')
        ->sum(DB::raw('COALESCE(total, 0)'));
    $bankKreditBank = (float) DB::table('transaksi_bank')
        ->whereIn('akun_kode', $bankAccountCodes)
        ->whereDate('tanggal', '>=', '1900-01-01')
        ->whereDate('tanggal', '<=', $tanggal_sampai)
        ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
        ->whereNull('deleted_at')
        ->sum(DB::raw('COALESCE(total, 0)'));
    
    // 3. transaksi_bank_detail dihapus - kolom kode_lawan tidak lagi ada
    
    // Total saldo akhir bank = (debit - kredit) dari semua sumber
    $totalBankDebet = $bankDebet + $bankDebetBank;
    $totalBankKredit = $bankKredit + $bankKreditBank;
    $saldo_akhir_bank = $totalBankDebet - $totalBankKredit;

    // Klasifikasi pendapatan (4*) dan biaya (5*)
    $pendapatanCodes = Akun::where('kode_akun', 'like', '4%')->pluck('kode_akun');
    $biayaCodes = Akun::where('kode_akun', 'like', '5%')->pluck('kode_akun');

    // Pendapatan normal kredit
    $pendapatanKredit = (float) Transaksi::whereIn('akun_kode', $pendapatanCodes)
        ->whereBetween('tanggal', [$from, $to])
        ->sum(DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'kredit' THEN COALESCE(nominal, 0) ELSE 0 END"));
    $pendapatanDebet = (float) Transaksi::whereIn('akun_kode', $pendapatanCodes)
        ->whereBetween('tanggal', [$from, $to])
        ->sum(DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'debit' THEN COALESCE(nominal, 0) ELSE 0 END"));
    $totalPendapatan = $pendapatanKredit - $pendapatanDebet;

    // Biaya normal debit
    $biayaDebet = (float) Transaksi::whereIn('akun_kode', $biayaCodes)
        ->whereBetween('tanggal', [$from, $to])
        ->sum(DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'debit' THEN COALESCE(nominal, 0) ELSE 0 END"));
    $biayaKredit = (float) Transaksi::whereIn('akun_kode', $biayaCodes)
        ->whereBetween('tanggal', [$from, $to])
        ->sum(DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'kredit' THEN COALESCE(nominal, 0) ELSE 0 END"));
    $totalBiaya = $biayaDebet - $biayaKredit;

    // Net/Loss = Pendapatan - Biaya
    $net_loss = $totalPendapatan - $totalBiaya;
    
    $total_transaksi = Transaksi::count();
    $transaksi_hari_ini = Transaksi::whereDate('tanggal', today())->count();
    
    // Data grafik transaksi 7 hari terakhir
    $chart_data = [];
    $labels = [];
    $values = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $count = Transaksi::whereDate('tanggal', $date)->count();
        $labels[] = $date->format('d/m');
        $values[] = $count;
    }
    
    $chart_data = [
        'labels' => $labels,
        'values' => $values
    ];
    
    // Set tipe data kosong karena sistem kategori dihapus
    $tipe_data = [
        'labels' => [],
        'values' => []
    ];
    
    return response()->json([
        'saldo_akhir_bank' => $saldo_akhir_bank,
        'total_transaksi' => $total_transaksi,
        'transaksi_hari_ini' => $transaksi_hari_ini,
        'net_loss' => $net_loss,
        'chart_data' => $chart_data,
        'tipe_data' => $tipe_data
    ]);
});

// API untuk transaksi terbaru - tidak untuk manager
Route::get('/recent-transactions', function () {
    // Cek jika user adalah manager, tolak akses
    if (Auth::check() && Auth::user()->role === 'manager') {
        return response()->json(['error' => 'Akses ditolak. Manager hanya dapat mengakses halaman Ledger.'], 403);
    }
    $transactions = Transaksi::with('akun')
        ->latest()
        ->take(5)
        ->get();
    
    return response()->json($transactions);
});

// API untuk refresh data budget vs actual secara real-time - tidak untuk manager
Route::middleware('auth')->get('/budget-actual/refresh', function (Request $request) {
    // Cek jika user adalah manager, tolak akses
    if (Auth::user()->role === 'manager') {
        return response()->json(['error' => 'Akses ditolak. Manager hanya dapat mengakses halaman Ledger.'], 403);
    }
    try {
        $from = $request->query('from_date');
        $to = $request->query('to_date');
        $projectId = (string) $request->query('project_id', '');
        $akunKode = (string) $request->query('akun_kode', '');

        // Gunakan BudgetActualService untuk mendapatkan data terbaru
        $service = new \App\Services\BudgetActualService();
        $data = $service->getBudgetActualData($from, $to, $projectId, $akunKode);

        return response()->json([
            'success' => true,
            'data' => $data['data'],
            'summary' => $data['summary'],
            'timestamp' => now()->toISOString(),
            'message' => 'Data refreshed successfully'
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
});

// Realtime Budget vs Actual - tidak untuk manager
Route::get('/budget-actual/summary', function (Request $request) {
    // Cek jika user adalah manager, tolak akses
    if (Auth::check() && Auth::user()->role === 'manager') {
        return response()->json(['error' => 'Akses ditolak. Manager hanya dapat mengakses halaman Ledger.'], 403);
    }
    try {
        if (!Schema::hasTable('budget')) {
            return response()->json(['success'=>true,'total_budget'=>0,'total_actual'=>0,'variance'=>0,'variance_percent'=>0]);
        }

        $from = $request->query('from_date');
        $to = $request->query('to_date');
        $projectId = (string) $request->query('project_id', '');
        $akunKode = (string) $request->query('akun_kode', '');

        $budgetQuery = DB::table('budget');
        if ($projectId !== '') { $budgetQuery->where('kode_project', $projectId); }
        if ($from) { $budgetQuery->whereDate('tanggal', '>=', $from); }
        if ($to) { $budgetQuery->whereDate('tanggal', '<=', $to); }
        
        // ===== TOTAL BUDGET =====
        // Total Budget = Jumlah dari semua anggaran di tabel budget
        // (Sum dari kolom anggaran berdasarkan filter project, tanggal, dll)
        $totalBudget = 0;
        if (Schema::hasColumn('budget', 'anggaran')) {
            $totalBudget = (float) (clone $budgetQuery)->sum('anggaran');
        } elseif (Schema::hasColumn('budget', 'budget')) {
            $totalBudget = (float) (clone $budgetQuery)->sum('budget');
        }

        // ===== TOTAL ACTUAL =====
        // Total Actual = Jumlah nominal dari transaksi jurnal + transaksi bank
        $totalActual = 0.0;
        
        // 1. Jumlahkan nominal dari tabel jurnal
        $totalActualJurnal = (float) DB::table('jurnal')
            ->when($from, function($q) use ($from) {
                $q->whereDate('tanggal', '>=', $from);
            })
            ->when($to, function($q) use ($to) {
                $q->whereDate('tanggal', '<=', $to);
            })
            ->when($projectId !== '', function($q) use ($projectId) {
                // Jika jurnal memiliki kolom kode_project, filter berdasarkan itu
                if (Schema::hasColumn('jurnal', 'kode_project')) {
                    $q->where('kode_project', $projectId);
                }
            })
            ->sum(DB::raw('COALESCE(nominal, 0)'));
        
        // 2. Jumlahkan total dari tabel transaksi_bank
        $totalActualBank = (float) DB::table('transaksi_bank')
            ->whereNull('deleted_at')
            ->when($from, function($q) use ($from) {
                $q->whereDate('tanggal', '>=', $from);
            })
            ->when($to, function($q) use ($to) {
                $q->whereDate('tanggal', '<=', $to);
            })
            ->when($projectId !== '', function($q) use ($projectId) {
                // Jika transaksi_bank memiliki kolom kode_project, filter berdasarkan itu
                if (Schema::hasColumn('transaksi_bank', 'kode_project')) {
                    $q->where('kode_project', $projectId);
                }
            })
            ->sum(DB::raw('COALESCE(total, 0)'));
        
        // Total Actual = Jumlah transaksi jurnal + transaksi bank
        $totalActual = $totalActualJurnal + $totalActualBank;

        // ===== VARIANCE / SELISIH =====
        // Variance = Selisih antara Budget dan Actual
        // Formula: Variance = Total Budget - Total Actual
        $variance = $totalBudget - $totalActual;
        // Variance Percent = Persentase selisih dari budget
        $variancePercent = $totalBudget != 0.0 ? ($variance / $totalBudget) * 100.0 : 0.0;

        return response()->json([
            'success' => true,
            'grand_total_budget' => round($totalBudget, 2),
            'grand_total_actual' => round($totalActual, 2),
            'grand_total_variance' => round($variance, 2),
            'grand_total_variance_percent' => round($variancePercent, 2),
            // Pertahankan kompatibilitas ke belakang
            'total_budget' => round($totalBudget, 2),
            'total_actual' => round($totalActual, 2),
            'variance' => round($variance, 2),
            'variance_percent' => round($variancePercent, 2),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['success'=>false,'message'=>$e->getMessage()], 500);
    }
});

// API untuk aktivitas user terbaru - tidak untuk manager
Route::get('/recent-activities', function () {
    // Cek jika user adalah manager, tolak akses
    if (Auth::check() && Auth::user()->role === 'manager') {
        return response()->json(['error' => 'Akses ditolak. Manager hanya dapat mengakses halaman Ledger.'], 403);
    }
    $activities = ActivityUser::with('user')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($a) {
            return [
                'id' => $a->id,
                'user' => [
                    'name' => optional($a->user)->name,
                    'email' => optional($a->user)->email,
                    'photo_url' => optional($a->user)->photo_url,
                ],
                'action' => $a->action,
                'model_name' => $a->model_name,
                'model_id' => $a->model_id,
                'created_at' => $a->created_at,
                'ip_address' => $a->ip_address,
            ];
        });
    return response()->json($activities);
});

// API untuk daftar aktivitas user dengan filter dan limit (untuk realtime table)
Route::get('/activity-user', function (Request $request) {
    $limit = (int) ($request->query('limit', 20));
    $action = strtoupper(trim((string) $request->query('action', '')));

    $query = ActivityUser::with('user')->orderBy('created_at', 'desc');
    if (!empty($action)) {
        $query->where('action', $action);
    }

    $items = $query->take($limit)->get()->map(function ($a) {
        return [
            'id' => $a->id,
            'user' => [
                'User_name' => optional($a->user)->User_name,
                'name' => optional($a->user)->User_name,
                'email' => optional($a->user)->email,
            ],
            'action' => $a->action,
            'model_name' => $a->model_name,
            'model_id' => $a->model_id,
            'created_at' => optional($a->created_at)->toIso8601String(),
            'old_data' => $a->old_data,
            'new_data' => $a->new_data,
        ];
    });

    return response()->json($items);
});

// CRUD DB-based (projects table) - MOVED TO web.php for session-based auth
// Project routes are now in web.php with /api prefix to use web middleware

// PROJECT: Get projects with "No Project" option (keep this in API for compatibility)
Route::get('/project-with-no-project', function () {
    return Project::getAllWithNoProject();
});

// API CRUD untuk Akun - using AkunController
use App\Http\Controllers\AkunController;

// Akun CRUD routes moved to web.php for session-based authentication
// Route::middleware('auth')->group(function () {
//     Route::get('/akun', [AkunController::class, 'index']);
//     Route::get('/akun/{id}', [AkunController::class, 'show']);
//     Route::get('/akun-by-kode/{kode}', [AkunController::class, 'getByKode']);
//     Route::post('/akun', [AkunController::class, 'store']);
//     Route::put('/akun/{id}', [AkunController::class, 'update']);
//     Route::delete('/akun/{id}', [AkunController::class, 'destroy']);
// });

// Management data aggregator moved to web.php for session-based auth

// Voucher CRUD - REMOVED: vouchers table has been dropped
// Update profile
Route::middleware('auth')->post('/profile', function (Request $request) {
    return app(ProfileController::class)->update($request);
});

// API untuk posisi keuangan (real-time)
Route::get('/posisi-keuangan', function (Request $request) {
    try {
        $tanggal = $request->input('tanggal', date('Y-m-d'));
        
        // Define account structure
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
        
        // Collect all account codes
        $allCodes = array_merge(
            array_keys($accountbased['aset_lancar']),
            array_keys($accountbased['aset_tetap']),
            array_keys($accountbased['aset_lainnya']),
            array_keys($accountbased['liabilitas']),
            array_keys($accountbased['ekuitas'])
        );
        
        // Load accounts fresh from database
        $akun = \App\Models\KodeAkun::select(['kode_akun', 'nama', 'tipe'])
            ->whereIn('kode_akun', $allCodes)
            ->orderBy('kode_akun')
            ->get()
            ->keyBy('kode_akun');
        
        // Prepare data
        $data = [
            'aset_lancar' => [],
            'aset_tetap' => [],
            'aset_lainnya' => [],
            'liabilitas' => [],
            'ekuitas' => []
        ];
        
        $totals = [
            'total_lancar' => 0,
            'total_tetap' => 0,
            'total_lain' => 0,
            'total_liab' => 0,
            'total_eku' => 0
        ];
        
        // Calculate balances for each category
        foreach ($accountbased['aset_lancar'] as $kode => $nama) {
            $akunItem = $akun->get($kode);
            $val = $akunItem ? $akunItem->getFreshBalance('1900-01-01', $tanggal) : 0;
            $val = $val > 0 ? $val : 0;
            $totals['total_lancar'] += $val;
            $data['aset_lancar'][] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $val];
        }
        
        foreach ($accountbased['aset_tetap'] as $kode => $nama) {
            $akunItem = $akun->get($kode);
            $val = $akunItem ? $akunItem->getFreshBalance('1900-01-01', $tanggal) : 0;
            $val = $val > 0 ? $val : 0;
            $totals['total_tetap'] += $val;
            $data['aset_tetap'][] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $val];
        }
        
        foreach ($accountbased['aset_lainnya'] as $kode => $nama) {
            $akunItem = $akun->get($kode);
            $val = $akunItem ? $akunItem->getFreshBalance('1900-01-01', $tanggal) : 0;
            $val = $val > 0 ? $val : 0;
            $totals['total_lain'] += $val;
            $data['aset_lainnya'][] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $val];
        }
        
        foreach ($accountbased['liabilitas'] as $kode => $nama) {
            $akunItem = $akun->get($kode);
            $val = $akunItem ? $akunItem->getFreshBalance('1900-01-01', $tanggal) : 0;
            $val = abs($val);
            $totals['total_liab'] += $val;
            $data['liabilitas'][] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $val];
        }
        
        foreach ($accountbased['ekuitas'] as $kode => $nama) {
            $akunItem = $akun->get($kode);
            $val = $akunItem ? $akunItem->getFreshBalance('1900-01-01', $tanggal) : 0;
            $val = abs($val);
            $totals['total_eku'] += $val;
            $data['ekuitas'][] = ['kode' => $kode, 'nama' => $nama, 'nilai' => $val];
        }
        
        $total_aset = $totals['total_lancar'] + $totals['total_tetap'] + $totals['total_lain'];
        $tle = $totals['total_liab'] + $totals['total_eku'];
        
        return response()->json([
            'success' => true,
            'tanggal' => $tanggal,
            'data' => $data,
            'totals' => $totals,
            'total_aset' => $total_aset,
            'total_liabilitas_ekuitas' => $tle,
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
});
