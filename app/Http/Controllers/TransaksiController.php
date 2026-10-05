<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\TransaksiBank;
use App\Models\ActivityUser;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Akun;
use Illuminate\Support\Facades\Schema;
use App\Services\CacheService;
use App\Exports\TransaksiExport;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Validation\Rule;
use App\Models\Project;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch bank transactions for the bank tab
        $transaksiBank = TransaksiBank::with(['akun'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate simple running balance for bank transactions
        $runningBalance = 0;
        foreach ($transaksiBank as $transaksi) {
            $nominal = floatval($transaksi->nominal ?? 0);
            $isDebit = ($transaksi->tipe === 'Debit');

            // Simple balance calculation for display
            if ($isDebit) {
                $runningBalance += $nominal;
            } else {
                $runningBalance -= $nominal;
            }
            $transaksi->saldo = $runningBalance;
        }

        // Use bank transactions for the bank tab
        $transaksiDebit = $transaksiBank;

        // Get journal entries for the jurnal tab (double entry system)
        $journalEntries = \App\Models\JournalEntry::with(['lines.account', 'project'])
            ->orderBy('journal_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $projects = Project::getAllWithNoProject();
        
        // Determine active tab from query parameter or default to 'bank'
        $activeTab = $request->get('tab', 'bank');

        return view('transaksi.index', compact(
            'transaksiDebit',
            'journalEntries',
            'projects',
            'activeTab'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Cache master data for 1 hour
        $projects = cache()->remember('projects_all', 3600, function () {
            return Project::getAllWithNoProject();
        });
        
        $akuns = cache()->remember('akuns_all', 3600, function () {
            return Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
        });
        
        // Get existing transactions for display with pagination
        // Note: Kolom akun_kode dan transaksi_type tidak ada, menggunakan tipe sebagai gantinya
        $kreditTransaksis = Transaksi::select(['kode_voucer', 'kode_project', 'tipe', 'deskripsi', 'nominal', 'tanggal'])
            ->where('tipe', 'kredit')
            ->orderBy('tanggal', 'desc')
            ->limit(50)
            ->get();
            
        $debitTransaksis = Transaksi::select(['kode_voucer', 'kode_project', 'tipe', 'deskripsi', 'nominal', 'tanggal'])
            ->where('tipe', 'debit')
            ->orderBy('tanggal', 'desc')
            ->limit(50)
            ->get();
        
        return view('transaksi.create', compact('projects', 'akuns', 'kreditTransaksis', 'debitTransaksis'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_voucer' => 'nullable|string|max:255|unique:transaksi,kode_voucer',
            'akun_kode' => 'required|string|max:255',
            'kode_project' => ['required','string',Rule::in(array_merge(['NO_PROJECT'], Project::pluck('kode_project')->toArray()))],
            'transaksi_type' => 'required|string|in:Debit,Kredit',
            'deskripsi' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
        ], [
            'kode_voucer.unique' => 'Kode voucer sudah digunakan.',
            'kode_project.required' => 'Project harus dipilih.',
            'kode_project.in' => 'Project yang dipilih tidak valid.',
        ]);

        // Prepare payload
        $nominal = floatval($validated['nominal']);
        
        // Untuk kompatibilitas ledger
        $payload = $validated;
        // akun_kode sudah dalam format yang benar
        $payload['jenis'] = strtolower($validated['transaksi_type']) === 'debit' ? 'debit' : 'kredit';
        $payload['jumlah'] = $nominal;
        $payload['nominal'] = $nominal;
        
        // Isi kolom debit/kredit berdasarkan transaksi_type
        if (strtolower($validated['transaksi_type']) === 'debit') {
            $payload['debit'] = $nominal;
            $payload['kredit'] = 0;
        } else {
            $payload['debit'] = 0;
            $payload['kredit'] = $nominal;
        }
        
        // Saldo akan dihitung oleh recalculateRunningBalance()
        $payload['saldo'] = 0;

        // Set default values for required columns
        if (!isset($payload['kode_budget']) || $payload['kode_budget'] === '') {
            $payload['kode_budget'] = null;
        }
        $payload['budget'] = $payload['budget'] ?? 0.00;
        $payload['actual'] = $payload['actual'] ?? 0.00;
        $payload['tipe'] = strtolower($validated['transaksi_type']) === 'debit' ? 'debit' : 'kredit';

        // kode_voucer will be manually set or auto-generated by model
        if (empty($payload['kode_voucer'])) {
            $payload['kode_voucer'] = null; // let model boot() generate
        }
        // Keep only columns that actually exist on the table
        $columns = Schema::getColumnListing('transaksi');
        $payload = array_intersect_key($payload, array_flip($columns));

        $transaksi = Transaksi::create($payload);

        // Recalculate running balance for all transactions
        $this->recalculateRunningBalance();

        ActivityUser::logActivity('CREATE', 'Transaksi', $transaksi->kode_voucer, null, $transaksi->toArray());

        // Clear cache after creating new transaction
        CacheService::clearMasterDataCache();

        if ($request->ajax() || $request->wantsJson()) {
            // Load relationships dan refresh saldo setelah recalculate
            $transaksi->refresh();
            $transaksi->load(['akun', 'project']);
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => $transaksi->toArray(),
            ]);
        }

        return redirect()->route('transaksi.index')->with('success', 'Transaksi created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi)
    {
        // #region agent log
        @file_put_contents(base_path('debug-ddb0e0.log'), json_encode([
            'sessionId' => 'ddb0e0',
            'runId' => 'pre-fix',
            'hypothesisId' => 'A',
            'location' => 'TransaksiController.php:show',
            'message' => 'TransaksiController show reached',
            'data' => ['key' => $transaksi->getKey(), 'path' => request()->path()],
            'timestamp' => (int) (microtime(true) * 1000),
        ]) . "\n", FILE_APPEND);
        // #endregion

        return view('transaksi.show', compact('transaksi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        // Cache master data for 1 hour
        $projects = cache()->remember('projects_all', 3600, function () {
            return \App\Models\Project::getAllWithNoProject();
        });
        
        $akuns = cache()->remember('akuns_all', 3600, function () {
            return Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
        });
        
        return view('transaksi.edit', compact('transaksi', 'projects', 'akuns'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        $validated = $request->validate([
            'kode_voucer' => 'required|string|max:255|unique:transaksi,kode_voucer,' . $transaksi->kode_voucer . ',kode_voucer',
            'akun_kode' => 'required|string|max:255',
            'kode_project' => ['required','string',Rule::in(array_merge(['NO_PROJECT'], Project::pluck('kode_project')->toArray()))],
            'transaksi_type' => 'required|string|in:Debit,Kredit',
            'deskripsi' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
        ]);

        // Prepare payload
        $nominal = floatval($validated['nominal']);
        
        $payload = $validated;
        // akun_kode is already in the correct format
        $payload['jenis'] = strtolower($validated['transaksi_type']) === 'debit' ? 'debit' : 'kredit';
        $payload['jumlah'] = $nominal;
        $payload['nominal'] = $nominal;
        
        // Isi kolom debit/kredit berdasarkan transaksi_type
        if (strtolower($validated['transaksi_type']) === 'debit') {
            $payload['debit'] = $nominal;
            $payload['kredit'] = 0;
        } else {
            $payload['debit'] = 0;
            $payload['kredit'] = $nominal;
        }
        
        // Saldo will be calculated by recalculateRunningBalance()
        $payload['saldo'] = 0;

        // Set default values for required columns if not provided
        if (!isset($payload['kode_budget']) || $payload['kode_budget'] === '') {
            $payload['kode_budget'] = $transaksi->kode_budget ?? null;
        }
        if (!isset($payload['budget'])) {
            $payload['budget'] = $transaksi->budget ?? 0.00;
        }
        if (!isset($payload['actual'])) {
            $payload['actual'] = $transaksi->actual ?? 0.00;
        }
        $payload['tipe'] = strtolower($validated['transaksi_type']) === 'debit' ? 'debit' : 'kredit';

        // Store old kode_voucer for activity log
        $oldKodeVoucer = $transaksi->kode_voucer;

        // Keep only columns that actually exist on the table
        $columns = Schema::getColumnListing('transaksi');
        $payload = array_intersect_key($payload, array_flip($columns));

        $transaksi->update($payload);

        $this->recalculateRunningBalance();

        ActivityUser::logActivity('UPDATE', 'Transaksi', $oldKodeVoucer, null, $transaksi->fresh()->toArray());

        // Clear cache after updating transaction
        CacheService::clearMasterDataCache();

        return redirect()->route('transaksi.index')->with('success', 'Transaksi updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        $id = $transaksi->kode_voucer;

        $transaksi->delete();

        // Recalculate running balance for all transactions
        $this->recalculateRunningBalance();

        ActivityUser::logActivity('DELETE', 'Transaksi', $id, $transaksi->toArray(), null);

        // Clear cache after deleting transaction
        CacheService::clearMasterDataCache();

        return redirect()->route('transaksi.index')->with('success', 'Transaksi deleted successfully.');
    }
    
    /**
     * Recalculate running balance for all transactions PER AKUN
     * Setiap akun memiliki running balance sendiri
     */
    private function recalculateRunningBalance()
    {
        // Ambil semua transaksi diurutkan berdasarkan tanggal, lalu waktu dibuat
        $transaksis = Transaksi::orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Array untuk menyimpan saldo per akun
        $runningBalances = [];
        $akunSaldoAwal = Akun::select(['kode_akun','saldo_awal'])
            ->get()
            ->keyBy('kode_akun');

        foreach ($transaksis as $transaksi) {
            $akunKode = $transaksi->akun_kode ?? 'UNKNOWN';

            // Inisialisasi saldo untuk akun jika belum ada
            if (!isset($runningBalances[$akunKode])) {
                $runningBalances[$akunKode] = (float)($akunSaldoAwal->get($akunKode)->saldo_awal ?? 0);
            }

            // Hitung perubahan saldo berdasarkan debit/kredit
            // Prioritaskan kolom debit/kredit, lalu fallback ke transaksi_type
            $nominal = 0;
            $isDebit = false;

            if (!empty($transaksi->debit) && $transaksi->debit > 0) {
                $nominal = (float)$transaksi->debit;
                $isDebit = true;
            } else if (!empty($transaksi->kredit) && $transaksi->kredit > 0) {
                $nominal = (float)$transaksi->kredit;
                $isDebit = false;
            } else {
                // Fallback ke transaksi_type dan nominal
                $nominal = floatval($transaksi->nominal ?? 0);
                $isDebit = ($transaksi->transaksi_type === 'Debit');
            }

            // PERBAIKAN: Akumulasi saldo berdasarkan tipe akun (saldo normal)
            // Ambil tipe akun dari master data akun
            $akun = Akun::where('kode_akun', $akunKode)->first();
            $tipeAkun = $akun ? ($akun->saldo_normal ?? 'debit') : 'debit';

            if ($tipeAkun === 'debit') {
                // Aset & Beban: Debit (+), Kredit (-)
                if ($isDebit) {
                    $runningBalances[$akunKode] += $nominal;
                } else {
                    $runningBalances[$akunKode] -= $nominal;
                }
            } else {
                // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
                if ($isDebit) {
                    $runningBalances[$akunKode] -= $nominal;
                } else {
                    $runningBalances[$akunKode] += $nominal;
                }
            }

            // Update saldo di database untuk transaksi ini (saldo kumulatif per akun)
            $transaksi->update(['saldo' => $runningBalances[$akunKode]]);
        }
    }



    /**
     * Generate PDF of transaksi list.
     */
    public function printPdf(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));
        
        // Note: Relationship akun() tidak ada karena kolom akun_kode tidak ada
        $transaksis = Transaksi::query()
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Calculate running balance for PDF display PER AKUN
        $runningBalances = []; // Array untuk menyimpan saldo per akun
        
        foreach ($transaksis as $transaksi) {
            $akunKode = $transaksi->akun_kode ?? 'UNKNOWN';
            
            // Inisialisasi saldo untuk akun jika belum ada
            if (!isset($runningBalances[$akunKode])) {
                $runningBalances[$akunKode] = 0;
            }
            
            $nominal = floatval($transaksi->nominal ?? 0);

            // PERBAIKAN: Hitung perubahan saldo berdasarkan debit/kredit dengan tipe akun
            $akun = \App\Models\Akun::where('kode_akun', $akunKode)->first();
            $tipeAkun = $akun ? ($akun->saldo_normal ?? 'debit') : 'debit';

            if (!empty($transaksi->debit) && $transaksi->debit > 0) {
                if ($tipeAkun === 'debit') {
                    // Aset & Beban: Debit (+)
                    $runningBalances[$akunKode] += (float)$transaksi->debit;
                } else {
                    // Liabilitas, Ekuitas, Pendapatan: Debit (-)
                    $runningBalances[$akunKode] -= (float)$transaksi->debit;
                }
            } else if (!empty($transaksi->kredit) && $transaksi->kredit > 0) {
                if ($tipeAkun === 'debit') {
                    // Aset & Beban: Kredit (-)
                    $runningBalances[$akunKode] -= (float)$transaksi->kredit;
                } else {
                    // Liabilitas, Ekuitas, Pendapatan: Kredit (+)
                    $runningBalances[$akunKode] += (float)$transaksi->kredit;
                }
            } else {
                // Fallback ke transaksi_type
                if ($transaksi->transaksi_type === 'Kredit') {
                    if ($tipeAkun === 'debit') {
                        $runningBalances[$akunKode] -= $nominal;
                    } else {
                        $runningBalances[$akunKode] += $nominal;
                    }
                } else {
                    if ($tipeAkun === 'debit') {
                        $runningBalances[$akunKode] += $nominal;
                    } else {
                        $runningBalances[$akunKode] -= $nominal;
                    }
                }
            }
            
            // Set the calculated running balance per akun
            $transaksi->saldo = $runningBalances[$akunKode];
        }

        $pdf = Pdf::loadView('transaksi.pdf', compact('transaksis', 'from', 'to'));
        $filename = 'transaksi-list-' . date('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export transaksi to Excel.
     */
    public function exportExcel(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));
        
        $filename = 'transaksi-list-' . date('Ymd-His') . '.xlsx';
        return Excel::download(
            new TransaksiExport($from, $to), 
            $filename
        );
    }
}
