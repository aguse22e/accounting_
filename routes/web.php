<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\TransaksiBankController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\ActivityUserController;
use App\Http\Controllers\BudgetActualController;
use App\Http\Controllers\AkunController;
use App\Imports\ProjectImport;
use App\Models\Project;
use App\Models\ActivityUser;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rute-rute
|ini dimuat oleh RouteServiceProvider dan semuanya akan ditetapkan ke
| grup middleware "web". Buat sesuatu yang hebat!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// autentikasi (login/logout)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Pembaruan pengaturan profil
Route::middleware('auth')->post('/settings', [\App\Http\Controllers\ProfileController::class, 'update'])->name('settings.update');

// Dashboard route 
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

// menu master (Akun, Project, Budget) - tidak untuk manager
Route::middleware(['auth'])->group(function () {
    Route::get('/master/akuntansi', function() { return redirect()->route('master.akun'); })->name('master.akuntansi');
    Route::get('/master/akun', function() { return view('management'); })->name('master.akun');
    Route::get('/master/akun/{id}/edit', function($id) { 
        return view('master.akun.edit', ['id' => $id]); 
    })->name('master.akun.edit');
    
    Route::get('/master/project', function() { return view('management'); })->name('master.project');
    Route::get('/master/project/{kode}/edit', function($kode) { 
        return view('master.project.edit', ['kode' => $kode]); 
    })->name('master.project.edit');
    
    Route::get('/master/budget', function() { return view('management'); })->name('master.budget');
    Route::get('/master/budget/{kode}/edit', function($kode) { 
        return view('master.budget.edit', ['kode' => $kode]); 
    })->name('master.budget.edit');
});

// Import routes untuk fitur tersembunyi
Route::middleware(['auth'])->group(function () {
    Route::post('/master/import', function() { 
        return back()->with('success', 'Import Master Data berhasil!'); 
    })->name('master.import');
    
    Route::post('/project/import', function(Request $request) {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv']
        ]);

        try {
            $import = new ProjectImport();
            Excel::import($import, $request->file('file'));
            
            $created = $import->created;
            $skipped = $import->skipped + count($import->failures());
            $errorDetails = [];
            
            // Kumpulkan detail error 
            if (count($import->failures()) > 0) {
                foreach ($import->failures() as $failure) {
                    $row = $failure->row();
                    $errorsList = implode(', ', $failure->errors());
                    $errorDetails[] = "Baris $row: $errorsList";
                }
            }
            
            $message = "Import berhasil. Data tersimpan: $created, dilewati: $skipped";
            if (!empty($errorDetails)) {
                $message .= "<br><small>Error detail:</small><br>" . implode("<br>", $errorDetails);
            }
            
            return back()->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorDetails = [];
            
            foreach ($failures as $failure) {
                $row = $failure->row();
                $errorsList = implode(', ', $failure->errors());
                $errorDetails[] = "Baris $row: $errorsList";
            }
            
            return back()->with('error', 'Import gagal: ' . implode('<br>', $errorDetails));
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    })->name('project.import');
    
    Route::post('/budget/import', function(Request $request) {
        $request->validate([
            'file' => ['required','file','mimes:xlsx,csv']
        ]);

        if (!Schema::hasTable('budget')) {
            return back()->with('error', 'Tabel budget tidak ditemukan');
        }

        try {
            $file = $request->file('file');
            if (!$file || !is_object($file)) {
                return back()->with('error', 'File tidak ditemukan atau tidak valid.');
            }
            $collection = Excel::toCollection(null, $file);
            if ($collection->isEmpty() || ($collection->first() && $collection->first()->isEmpty())) {
                return back()->with('error', 'File kosong atau tidak terbaca.');
            }

            $rows = $collection->first();
            $dataRows = $rows ? $rows->slice(1) : collect(); // Skip header
            
            $created = 0;
            $skipped = 0;
            $errorDetails = [];
            
            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2; // +2 karena index mulai 0 dan skip header
                
                try {
                    // Mapping kolom fleksibel
                    $rowArray = $row->toArray();
                    $kode_project = $rowArray[0] ?? $rowArray['kode_project'] ?? null;
                    $kode_budget = $rowArray[1] ?? $rowArray['kode_budget'] ?? null;
                    $deskripsi = $rowArray[2] ?? $rowArray['deskripsi'] ?? '';
                    $anggaran = $rowArray[3] ?? $rowArray['anggaran'] ?? $rowArray['budget'] ?? 0;
                    $tanggal = $rowArray[4] ?? $rowArray['tanggal'] ?? null;
                    
                    if (empty($kode_project) || empty($kode_budget)) {
                        $skipped++;
                        $errorDetails[] = "Baris $rowNumber: kode_project atau kode_budget kosong";
                        continue;
                    }
                    
                    // Cek apakah kode_budget sudah ada
                    $exists = DB::table('budget')->where('kode_budget', $kode_budget)->exists();
                    if ($exists) {
                        $skipped++;
                        $errorDetails[] = "Baris $rowNumber: kode_budget '$kode_budget' sudah ada";
                        continue;
                    }
                    
                    $budgetData = [
                        'kode_project' => $kode_project,
                        'kode_budget' => $kode_budget,
                        'deskripsi' => $deskripsi,
                        'anggaran' => is_numeric($anggaran) ? $anggaran : 0,
                        'budget' => is_numeric($anggaran) ? $anggaran : 0,
                        'tanggal' => $tanggal ? (is_string($tanggal) ? \Carbon\Carbon::parse($tanggal) : $tanggal) : now(),
                    ];
                    
                    // Add actual column if it exists
                    if (Schema::hasColumn('budget', 'actual')) {
                        $budgetData['actual'] = 0;
                    }
                    
                    DB::table('budget')->insert($budgetData);
                    $created++;
                    
                    $userId = Auth::id() ?? optional(User::first())->id;
                    if ($userId) {
                        ActivityUser::logActivity('CREATE', 'Budget', $kode_budget, null, $budgetData, $userId);
                    }
                } catch (\Exception $e) {
                    $skipped++;
                    $errorDetails[] = "Baris $rowNumber: " . $e->getMessage();
                }
            }
            
            $message = "Import berhasil. Data tersimpan: $created, dilewati: $skipped";
            if (!empty($errorDetails)) {
                $message .= "<br><small>Error detail:</small><br>" . implode("<br>", array_slice($errorDetails, 0, 10));
                if (count($errorDetails) > 10) {
                    $message .= "<br><small>... dan " . (count($errorDetails) - 10) . " error lainnya</small>";
                }
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    })->name('budget.import');
});

// Ledger & Laporan - bisa diakses oleh semua user termasuk manager
Route::prefix('ledger')->middleware(['auth'])->group(function () {
    Route::get('/', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/export-pdf', [LedgerController::class, 'exportPdf'])->name('ledger.export-pdf');
    Route::get('/export-excel', [LedgerController::class, 'exportExcel'])->name('ledger.export-excel');
    Route::get('/import', function () {
        return view('ledger.import');
    })->name('ledger.import.form');

    Route::post('/import', [LedgerController::class, 'import'])->name('ledger.import');
    Route::post('/commit-serial', [LedgerController::class, 'commitSerial'])->name('ledger.commit-serial');
    Route::get('/posisi-keuangan', [LedgerController::class, 'posisiKeuangan'])->name('ledger.posisi');
    Route::get('/posisi-keuangan/export-pdf', [LedgerController::class, 'exportPosisiKeuanganPdf'])->name('ledger.posisi.export-pdf');
    Route::get('/posisi-keuangan/export-excel', [LedgerController::class, 'exportPosisiKeuanganExcel'])->name('ledger.posisi.export-excel');

    Route::get('/trial-balance', [LedgerController::class, 'trialBalance'])->name('ledger.trial');
    Route::get('/gl-detail', [LedgerController::class, 'glDetail'])->name('ledger.gl');
    Route::get('/cash-flows', [LedgerController::class, 'cashFlows'])->name('ledger.cash');
    Route::get('/activity', [LedgerController::class, 'activity'])->name('ledger.activity');
    Route::get('/fixed-assets', [LedgerController::class, 'fixedAssets'])->name('ledger.fixed');
    Route::get('/budget', [LedgerController::class, 'budget'])->name('ledger.budget');
    Route::get('/statement-of-activity', [\App\Http\Controllers\StatementOfActivityController::class, 'index'])->name('ledger.statement-of-activity');
    Route::get('/statement-of-activity/export-pdf', [\App\Http\Controllers\StatementOfActivityController::class, 'exportPdf'])->name('ledger.statement-of-activity.export-pdf');
    Route::get('/statement-of-activity/export-excel', [\App\Http\Controllers\StatementOfActivityController::class, 'exportExcel'])->name('ledger.statement-of-activity.export-excel');
    Route::get('/laba-rugi', [LedgerController::class, 'labaRugi'])->name('ledger.laba-rugi');
    Route::get('/laba-rugi/export-pdf', [LedgerController::class, 'exportLabaRugiPdf'])->name('ledger.laba-rugi.export-pdf');
    Route::get('/laba-rugi/export-excel', [LedgerController::class, 'exportLabaRugiExcel'])->name('ledger.laba-rugi.export-excel');
});

// Trial Balance (Double Entry System) - bisa diakses oleh semua user termasuk manager
Route::prefix('trial-balance')->middleware(['auth'])->group(function () {
    Route::get('/', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
    Route::get('/export-pdf', [TrialBalanceController::class, 'exportPdf'])->name('trial-balance.export-pdf');
    Route::get('/export-excel', [TrialBalanceController::class, 'exportExcel'])->name('trial-balance.export-excel');
});

// Catatan: JurnalController menggunakan model JournalEntry (Double Entry System) - tidak untuk manager
Route::middleware(['auth', 'cache.headers:private;no_store;max_age=0'])->group(function () {
    Route::get('transaksi/printpdf', [TransaksiController::class, 'printPdf'])->name('transaksi.printpdf');
    Route::get('transaksi/export-excel', [TransaksiController::class, 'exportExcel'])->name('transaksi.export-excel');

    // Jurnal harus didaftarkan sebelum resource transaksi, agar GET /transaksi/jurnal
    // tidak tertangkap transaksi.show ({transaksi} = "jurnal") yang menghasilkan 404.
    Route::prefix('transaksi')->group(function () {
        Route::get('jurnal/print-pdf', [JurnalController::class, 'printPdf'])->name('transaksi.jurnal.print-pdf');
        Route::get('jurnal/export-excel', [JurnalController::class, 'exportExcel'])->name('transaksi.jurnal.export-excel');
        Route::resource('jurnal', JurnalController::class)->parameters(['jurnal' => 'journalEntry'])->names('transaksi.jurnal');
        Route::post('jurnal/{journalEntry}/post', [JurnalController::class, 'post'])->name('transaksi.jurnal.post');
        Route::post('jurnal/{journalEntry}/reverse', [JurnalController::class, 'reverse'])->name('transaksi.jurnal.reverse');
        Route::delete('jurnal/{journalEntry}/lines/{journalLine}', [JurnalController::class, 'destroyLine'])->name('transaksi.jurnal.destroyLine');
        Route::get('jurnal/{journalEntry}/lines/{journalLine}/edit', [JurnalController::class, 'editLine'])->name('transaksi.jurnal.editLine');
        Route::put('jurnal/{journalEntry}/lines/{journalLine}', [JurnalController::class, 'updateLine'])->name('transaksi.jurnal.updateLine');
        
        // API endpoints for journal lines validation
        Route::post('api/jurnal/validate-lines', [JurnalController::class, 'validateLines'])->name('transaksi.jurnal.api.validate-lines');
        Route::post('api/jurnal/calculate-totals', [JurnalController::class, 'calculateTotals'])->name('transaksi.jurnal.api.calculate-totals');
        
        // Transaksi Bank routes - specific routes BEFORE resource transaksi
        Route::get('bank/print-pdf', [TransaksiBankController::class, 'printPdf'])->name('transaksi.bank.print-pdf');
        Route::get('bank/export-excel', [TransaksiBankController::class, 'exportExcel'])->name('transaksi.bank.export-excel');
        Route::get('bank/edit', [TransaksiBankController::class, 'edit'])->name('transaksi.bank.edit');
        
        // Resource routes excluding edit to avoid conflicts
        Route::get('bank', [TransaksiBankController::class, 'index'])->name('transaksi.bank.index');
        Route::get('bank/create', [TransaksiBankController::class, 'create'])->name('transaksi.bank.create');
        Route::post('bank', [TransaksiBankController::class, 'store'])->name('transaksi.bank.store');
        Route::get('bank/{bank}', [TransaksiBankController::class, 'show'])->name('transaksi.bank.show');
        Route::put('bank/{bank}', [TransaksiBankController::class, 'update'])->name('transaksi.bank.update');
        Route::delete('bank/{bank}', [TransaksiBankController::class, 'destroy'])->name('transaksi.bank.destroy');
    });

    Route::resource('transaksi', TransaksiController::class);
    
    // Activity User
    Route::get('activity-user', [ActivityUserController::class, 'index'])->name('activity-user.index');
    Route::get('activity-user/statistics', [ActivityUserController::class, 'statistics'])->name('activity-user.statistics');
    
    // Budget vs Actual - tidak untuk manager (dibatasi oleh ManagerAccess middleware)
    Route::get('budget-actual', [BudgetActualController::class, 'index'])->name('budget-actual.index');
    
    Route::get('budget-actual/data', [BudgetActualController::class, 'data'])->name('budget-actual.data');
    
    Route::get('budget-actual/export-excel', [BudgetActualController::class, 'exportExcel'])->name('budget-actual.export-excel');

    Route::get('budget-actual/export-pdf', [BudgetActualController::class, 'exportPdf'])->name('budget-actual.export-pdf');
});

// API routes for project CRUD (using web middleware for session auth) - tidak untuk manager
Route::prefix('api')->middleware(['auth'])->group(function () {
    // Akun CRUD routes
    Route::get('/akun', [AkunController::class, 'index'])->name('api.akun.index');
    Route::get('/akun/{id}', [AkunController::class, 'show'])->name('api.akun.show');
    Route::get('/akun-by-kode/{kode}', [AkunController::class, 'getByKode'])->name('api.akun.by-kode');
    Route::post('/akun', [AkunController::class, 'store'])->name('api.akun.store');
    Route::put('/akun/{id}', [AkunController::class, 'update'])->name('api.akun.update');
    Route::delete('/akun/{id}', [AkunController::class, 'destroy'])->name('api.akun.destroy');
    
    // Management data aggregator for management.blade.php
    Route::get('/management-data', function () {
        try {
            // Get akun data with error handling
            $akun = \App\Models\Akun::orderBy('kode_akun')->get()->map(function($item) {
                return [
                    'id' => $item->id,           // ID numerik asli dari database
                    'kode_akun' => $item->kode_akun,
                    'nama_akun' => $item->nama,
                    'saldo_normal' => $item->tipe ?? 'Debit',
                    'tipe' => $item->tipe ?? 'Debit',
                    'tanggal' => $item->tanggal,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'akun_data' => $akun,
            ]);
        } catch (\Throwable $e) {
            Log::error('Management data error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
            ], 500);
        }
    })->name('api.management-data');
    Route::get('/project', function () {
        $projects = Project::orderBy('created_at', 'desc')
                          ->orderBy('tanggal', 'desc')
                          ->orderBy('kode_project', 'asc')
                          ->get();
        
        // Transform to PascalCase format expected by frontend
        return response()->json($projects->map(function ($p) {
            return [
                'Kode_project' => $p->kode_project,
                'kode_project' => $p->kode_project,
                'Nama_Project' => $p->nama_project,
                'nama_project' => $p->nama_project,
                'Tanggal_Mulai' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
                'tanggal' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
                'kode_akun' => $p->kode_akun,
                'saldo' => $p->saldo,
                'id' => $p->id,
                'created_at' => $p->created_at,
                'updated_at' => $p->updated_at,
            ];
        }));
    })->name('api.project.index');

    Route::post('/project', function (Request $request) {
        $validated = $request->validate([
            'kode_project' => ['required','regex:/^[A-Za-z0-9\- ]+$/','unique:projects,kode_project'],
            'nama_project' => ['required','string','max:255'],
            'saldo' => ['nullable','numeric'],
            'tanggal' => ['nullable'],
            'kode_akun' => ['nullable','string','max:50'],
        ], [
            'kode_project.required' => 'Kode Project wajib diisi.',
            'kode_project.regex' => 'Kode Project hanya boleh berisi huruf, angka, dan tanda hubung.',
            'kode_project.unique' => 'Kode Project sudah digunakan.',
            'nama_project.required' => 'Nama Project wajib diisi.',
            'kode_akun.max' => 'Kode Akun maksimal 50 karakter.',
        ]);
        if (!array_key_exists('saldo', $validated) || is_null($validated['saldo'])) {
            $validated['saldo'] = 0;
        }
        
        $projectData = [
            'kode_project' => $validated['kode_project'],
            'nama_project' => $validated['nama_project'],
            'saldo' => $validated['saldo'] ?? 0,
            'kode_akun' => !empty($validated['kode_akun']) ? trim($validated['kode_akun']) : null,
        ];
        
        if (isset($validated['tanggal']) && !empty($validated['tanggal'])) {
            try {
                // Try to parse datetime-local format first (YYYY-MM-DDTHH:MM)
                if (strpos($validated['tanggal'], 'T') !== false) {
                    $projectData['tanggal'] = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $validated['tanggal']);
                } else {
                    // Parse as date format (YYYY-MM-DD)
                    $projectData['tanggal'] = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['tanggal']);
                }
            } catch (\Exception $e) {
                // If parsing fails, use current timestamp
                $projectData['tanggal'] = now();
            }
        } else {
            $projectData['tanggal'] = now();
        }
        
        $p = Project::create($projectData);
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId) {
            ActivityUser::logActivity('CREATE', 'Project', $p->kode_project, null, $p->toArray(), $userId);
        }
        
        $responseData = [
            'Kode_project' => $p->kode_project,
            'kode_project' => $p->kode_project,
            'Nama_Project' => $p->nama_project,
            'nama_project' => $p->nama_project,
            'Tanggal_Mulai' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
            'tanggal' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
            'kode_akun' => $p->kode_akun,
            'saldo' => $p->saldo,
            'id' => $p->id,
        ];
        
        return response()->json(['success' => true, 'message' => 'Project berhasil ditambahkan', 'data' => $responseData]);
    })->name('api.project.store');

    Route::put('/project/{kode}', function (Request $request, $kode) {
        $p = Project::where('kode_project', $kode)->first();
        if (!$p) {
            return response()->json(['success'=>false,'message'=>'Project tidak ditemukan'], 404);
        }
        
        $oldData = $p->toArray();
        $newKode = $request->input('kode_project');
        $isKodeChanging = $newKode && $newKode !== $p->kode_project;
        
        $validationRules = [
            'nama_project' => ['required','string','max:255'],
            'saldo' => ['nullable','numeric'],
            'tanggal' => ['nullable'],
            'kode_akun' => ['nullable','string','max:50'],
        ];
        
        // Handle kode_project validation
        if ($isKodeChanging) {
            // If kode is changing, check if new kode is unique
            $validationRules['kode_project'] = ['required','regex:/^[A-Za-z0-9\- ]+$/', 'unique:projects,kode_project'];
        } else {
            // If kode is not changing, just validate format and ignore current record
            $validationRules['kode_project'] = ['required','regex:/^[A-Za-z0-9\- ]+$/', Rule::unique('projects', 'kode_project')->ignore($p->id, 'id')];
        }
        
        try {
            $validated = $request->validate($validationRules, [
                'kode_project.required' => 'Kode Project wajib diisi.',
                'kode_project.regex' => 'Kode Project hanya boleh berisi huruf, angka, dan tanda hubung.',
                'kode_project.unique' => 'Kode Project sudah digunakan.',
                'nama_project.required' => 'Nama Project wajib diisi.',
                'kode_akun.max' => 'Kode Akun maksimal 50 karakter.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }
        
        $updateData = [
            'nama_project' => $validated['nama_project'],
            'saldo' => $validated['saldo'] ?? $p->saldo,
            'kode_akun' => !empty($validated['kode_akun']) ? trim($validated['kode_akun']) : null,
        ];
        
        if (isset($validated['tanggal']) && !empty($validated['tanggal'])) {
            try {
                // Try to parse datetime-local format first (YYYY-MM-DDTHH:MM)
                if (strpos($validated['tanggal'], 'T') !== false) {
                    $updateData['tanggal'] = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $validated['tanggal']);
                } else {
                    // Parse as date format (YYYY-MM-DD)
                    $updateData['tanggal'] = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['tanggal']);
                }
            } catch (\Exception $e) {
                // If parsing fails, don't update tanggal
            }
        }
        
        if ($isKodeChanging) {
            $updateData['kode_project'] = $validated['kode_project'];
        }
        
        $p->update($updateData);
        $p->refresh(); // Refresh model to get latest data from database
        
        $responseData = [
            'Kode_project' => $p->kode_project,
            'kode_project' => $p->kode_project,
            'Nama_Project' => $p->nama_project,
            'nama_project' => $p->nama_project,
            'Tanggal_Mulai' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
            'tanggal' => $p->tanggal ? $p->tanggal->format('Y-m-d H:i:s') : null,
            'kode_akun' => $p->kode_akun,
            'saldo' => $p->saldo,
            'id' => $p->id,
        ];
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId) {
            ActivityUser::logActivity('UPDATE', 'Project', $p->kode_project, $oldData, $p->toArray(), $userId);
        }
        
        return response()->json(['success'=>true,'message'=>'Project diperbarui','data'=>$responseData]);
    })->name('api.project.update');

    Route::delete('/project/{kode}', function ($kode) {
        $p = Project::where('kode_project', $kode)->first();
        if (!$p) {
            return response()->json(['success'=>false,'message'=>'Project tidak ditemukan'], 404);
        }
        $oldData = $p->toArray();
        $p->delete();
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId) {
            ActivityUser::logActivity('DELETE', 'Project', $oldData['kode_project'], $oldData, null, $userId);
        }
        
        return response()->json(['success'=>true,'message'=>'Project dihapus']);
    })->name('api.project.destroy');

    // Budget CRUD API routes
    Route::get('/budget', function () {
        try {
            if (!Schema::hasTable('budget')) {
                return response()->json([]);
            }
            $budgets = DB::table('budget')
                ->orderBy('tanggal', 'desc')
                ->orderBy('kode_budget', 'asc')
                ->get();
            
            return response()->json($budgets->map(function ($b) {
                return [
                    'id' => $b->kode_budget ?? null,
                    'kode_project' => $b->kode_project ?? '',
                    'kode_budget' => $b->kode_budget ?? '',
                    'deskripsi' => $b->deskripsi ?? '',
                    'anggaran' => $b->anggaran ?? $b->budget ?? 0,
                    'tanggal' => $b->tanggal ?? null,
                    'created_at' => $b->tanggal ?? null, // Budget table doesn't have created_at, use tanggal
                    'updated_at' => $b->tanggal ?? null, // Budget table doesn't have updated_at, use tanggal
                ];
            }));
        } catch (\Exception $e) {
            Log::error('Error loading budget: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Gagal memuat data budget: ' . $e->getMessage()
            ], 500);
        }
    })->name('api.budget.index');

    Route::post('/budget', function (Request $request) {
        if (!Schema::hasTable('budget')) {
            return response()->json(['success'=>false,'message'=>'Tabel budget tidak ditemukan'], 404);
        }
        
        $validated = $request->validate([
            'kode_project' => ['required','string','max:50','exists:projects,kode_project'],
            'kode_budget' => ['required','string','max:50','unique:budget,kode_budget'],
            'deskripsi' => ['required','string'],
            'anggaran' => ['required','numeric','min:0'],
            'tanggal' => ['nullable','date'],
        ]);
        
        $budgetData = [
            'kode_project' => $validated['kode_project'],
            'kode_budget' => $validated['kode_budget'],
            'deskripsi' => $validated['deskripsi'],
        ];
        
        // Add anggaran column if it exists, otherwise use budget
        if (Schema::hasColumn('budget', 'anggaran')) {
            $budgetData['anggaran'] = $validated['anggaran'];
        }
        if (Schema::hasColumn('budget', 'budget')) {
            $budgetData['budget'] = $validated['anggaran'];
        }
        
        // Add actual column if it exists
        if (Schema::hasColumn('budget', 'actual')) {
            $budgetData['actual'] = 0;
        }
        
        if (isset($validated['tanggal'])) {
            $budgetData['tanggal'] = $validated['tanggal'];
        } else {
            $budgetData['tanggal'] = now();
        }
        
        $kodeBudget = $validated['kode_budget'];
        DB::table('budget')->insert($budgetData);
        $b = DB::table('budget')->where('kode_budget', $kodeBudget)->first();
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId && $b) {
            ActivityUser::logActivity('CREATE', 'Budget', $b->kode_budget, null, (array)$b, $userId);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Budget berhasil ditambahkan', 
            'data' => [
                'id' => $b->kode_budget ?? null,
                'kode_project' => $b->kode_project ?? '',
                'kode_budget' => $b->kode_budget ?? '',
                'deskripsi' => $b->deskripsi ?? '',
                'anggaran' => $b->anggaran ?? $b->budget ?? 0,
                'tanggal' => $b->tanggal ?? null,
            ]
        ]);
    })->name('api.budget.store');

    Route::put('/budget/{kode}', function (Request $request, $kode) {
        if (!Schema::hasTable('budget')) {
            return response()->json(['success'=>false,'message'=>'Tabel budget tidak ditemukan'], 404);
        }
        
        // Decode URL-encoded kode
        $kode = urldecode($kode);
        
        $b = DB::table('budget')->where('kode_budget', $kode)->first();
        if (!$b) {
            return response()->json(['success'=>false,'message'=>'Budget tidak ditemukan'], 404);
        }
        
        $oldData = (array)$b;
        $newKode = $request->input('kode_budget');
        $isKodeChanging = $newKode && $newKode !== $b->kode_budget;
        
        $validationRules = [
            'kode_project' => ['required','string','max:50','exists:projects,kode_project'],
            'deskripsi' => ['required','string'],
            'anggaran' => ['required','numeric','min:0'],
            'tanggal' => ['nullable','date'],
        ];
        
        // Handle kode_budget validation
        if ($isKodeChanging) {
            // If kode is changing, check if new kode is unique
            $validationRules['kode_budget'] = ['required','string','max:50', 'unique:budget,kode_budget'];
        } else {
            // If kode is not changing, just validate format and ignore current record
            $validationRules['kode_budget'] = ['required','string','max:50', Rule::unique('budget', 'kode_budget')->ignore($b->kode_budget, 'kode_budget')];
        }
        
        try {
            $validated = $request->validate($validationRules, [
                'kode_project.required' => 'Kode Project wajib diisi.',
                'kode_budget.required' => 'Kode Budget wajib diisi.',
                'kode_budget.unique' => 'Kode Budget sudah digunakan.',
                'deskripsi.required' => 'Deskripsi wajib diisi.',
                'anggaran.required' => 'Anggaran wajib diisi.',
                'anggaran.numeric' => 'Anggaran harus berupa angka.',
                'anggaran.min' => 'Anggaran tidak boleh negatif.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }
        
        $updateData = [
            'kode_project' => $validated['kode_project'],
            'deskripsi' => $validated['deskripsi'],
        ];
        
        // Add anggaran column if it exists, otherwise use budget
        if (Schema::hasColumn('budget', 'anggaran')) {
            $updateData['anggaran'] = $validated['anggaran'];
        }
        if (Schema::hasColumn('budget', 'budget')) {
            $updateData['budget'] = $validated['anggaran'];
        }
        
        if ($isKodeChanging) {
            $updateData['kode_budget'] = $validated['kode_budget'];
        }
        
        if (isset($validated['tanggal']) && $validated['tanggal']) {
            $updateData['tanggal'] = $validated['tanggal'];
        }
        
        DB::table('budget')->where('kode_budget', $kode)->update($updateData);
        // Get updated record - use new kode if changed, otherwise use original kode
        $updatedKode = $isKodeChanging ? $validated['kode_budget'] : $kode;
        $updated = DB::table('budget')->where('kode_budget', $updatedKode)->first();
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId) {
            ActivityUser::logActivity('UPDATE', 'Budget', $updated->kode_budget, $oldData, (array)$updated, $userId);
        }
        
        return response()->json([
            'success'=>true,
            'message'=>'Budget berhasil diperbarui',
            'data'=>[
                'id' => $updated->kode_budget ?? null,
                'kode_project' => $updated->kode_project ?? '',
                'kode_budget' => $updated->kode_budget ?? '',
                'deskripsi' => $updated->deskripsi ?? '',
                'anggaran' => $updated->anggaran ?? $updated->budget ?? 0,
                'tanggal' => $updated->tanggal ?? null,
            ]
        ]);
    })->name('api.budget.update');

    Route::delete('/budget/{kode}', function ($kode) {
        if (!Schema::hasTable('budget')) {
            return response()->json(['success'=>false,'message'=>'Tabel budget tidak ditemukan'], 404);
        }
        
        // Decode URL-encoded kode
        $kode = urldecode($kode);
        
        $b = DB::table('budget')->where('kode_budget', $kode)->first();
        if (!$b) {
            return response()->json(['success'=>false,'message'=>'Budget tidak ditemukan'], 404);
        }
        $oldData = (array)$b;
        DB::table('budget')->where('kode_budget', $kode)->delete();
        
        $userId = Auth::id() ?? optional(User::first())->id;
        if ($userId) {
            ActivityUser::logActivity('DELETE', 'Budget', $oldData['kode_budget'], $oldData, null, $userId);
        }
        
        return response()->json(['success'=>true,'message'=>'Budget dihapus']);
    })->name('api.budget.destroy');
});
