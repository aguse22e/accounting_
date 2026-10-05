<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Akun;
use App\Models\Project;
use App\Models\TransaksiBank;
use App\Models\ActivityUser;
use App\Helpers\TransactionHelper;
use App\Services\JournalPostingService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Excel;

class JurnalController extends Controller
{
    protected $journalPostingService;

    public function __construct(JournalPostingService $journalPostingService)
    {
        $this->journalPostingService = $journalPostingService;
    }

    public function index()
    {
        // #region agent log
        @file_put_contents(base_path('debug-ddb0e0.log'), json_encode([
            'sessionId' => 'ddb0e0',
            'runId' => 'pre-fix',
            'hypothesisId' => 'B',
            'location' => 'JurnalController.php:index',
            'message' => 'JurnalController index reached',
            'data' => ['path' => request()->path(), 'route' => optional(request()->route())->getName()],
            'timestamp' => (int) (microtime(true) * 1000),
        ]) . "\n", FILE_APPEND);
        // #endregion

        $transaksiBank = TransaksiBank::with(['akun'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $runningBalance = 0;
        foreach ($transaksiBank as $transaksi) {
            $nominal = floatval($transaksi->nominal ?? 0);
            $isDebit = ($transaksi->tipe === 'Debit');
            if ($isDebit) {
                $runningBalance += $nominal;
            } else {
                $runningBalance -= $nominal;
            }
            $transaksi->saldo = $runningBalance;
        }

        $transaksiDebit = $transaksiBank;
        $journalEntries = JournalEntry::with(['lines.account', 'project'])
            ->orderBy('journal_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        $projects = Project::getAllWithNoProject();
        $activeTab = 'jurnal';

        return view('transaksi.index', compact('transaksiDebit', 'journalEntries', 'projects', 'activeTab'));
    }

    public function create()
    {
        // Cache master data for 1 hour
        $akuns = cache()->remember('akuns_all', 3600, function () {
            return Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
        });

        // Fallback: if cache returns empty, clear cache and fetch directly from database
        if ($akuns->isEmpty()) {
            cache()->forget('akuns_all');
            $akuns = Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
            // Update cache with fresh data
            if (!$akuns->isEmpty()) {
                cache()->put('akuns_all', $akuns, 3600);
            }
        }

        $projects = cache()->remember('projects_all', 3600, function () {
            return Project::orderBy('kode_project')->get();
        });

        return view('transaksi.jurnal.create', compact('akuns', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'reference' => 'required|string|max:20',
            'akun_kode' => 'required|string|exists:kode_akun,kode_akun',
            'tipe' => 'required|string|in:Debit,Kredit',
            'nominal' => 'required|numeric|min:0',
            'description' => 'required|string',
            'kode_project' => 'nullable|string|exists:projects,kode_project',
        ]);

        // Create journal entry with single line (similar to bank transaction)
        // Use helper to normalize type from Indonesian to database ENUM
        $lines = [
            [
                'account_code' => $validated['akun_kode'],
                'type' => TransactionHelper::normalizeType($validated['tipe']),
                'amount' => floatval($validated['nominal']),
                'description' => $validated['description'],
            ]
        ];

        try {
            // Create journal entry using the service
            $journalEntry = $this->journalPostingService->createJournalEntry([
                'journal_date' => $validated['journal_date'],
                'description' => $validated['description'],
                'reference' => $validated['reference'],
                'kode_project' => $validated['kode_project'] ?? null,
                'notes' => null,
                'lines' => $lines,
            ]);

            ActivityUser::logActivity('CREATE', 'JournalEntry', $journalEntry->id, null, $journalEntry->toArray());

            // Clear cache after creating new journal entry
            CacheService::clearMasterDataCache();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Journal entry created successfully',
                    'data' => $journalEntry->load('lines')->toArray(),
                ]);
            }

            self::recalculateBalances();

            return redirect()->route('transaksi.index', ['tab' => 'jurnal'])->with('success', 'Jurnal berhasil ditambahkan.');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(JournalEntry $journalEntry)
    {
        // #region agent log
        @file_put_contents(base_path('debug-ddb0e0.log'), json_encode([
            'sessionId' => 'ddb0e0',
            'runId' => 'pre-fix',
            'hypothesisId' => 'C',
            'location' => 'JurnalController.php:show',
            'message' => 'JurnalController show reached',
            'data' => ['id' => $journalEntry->id, 'path' => request()->path()],
            'timestamp' => (int) (microtime(true) * 1000),
        ]) . "\n", FILE_APPEND);
        // #endregion

        $journalEntry->load(['lines.account', 'project', 'creator', 'poster']);
        return view('transaksi.jurnal.show', compact('journalEntry'));
    }

    public function edit(JournalEntry $journalEntry)
    {
        // Cache master data for 1 hour
        $akuns = cache()->remember('akuns_all', 3600, function () {
            return Akun::select(['kode_akun', 'nama', 'tipe'])->orderBy('kode_akun')->get();
        });

        // Fallback: if cache returns empty, clear cache and fetch directly from database
        if ($akuns->isEmpty()) {
            cache()->forget('akuns_all');
            $akuns = Akun::select(['kode_akun', 'nama', 'tipe'])->orderBy('kode_akun')->get();
            // Update cache with fresh data
            if (!$akuns->isEmpty()) {
                cache()->put('akuns_all', $akuns, 3600);
            }
        }

        $projects = cache()->remember('projects_all', 3600, function () {
            return Project::getAllWithNoProject();
        });

        $journalEntry->load('lines');

        return view('transaksi.jurnal.edit', compact('journalEntry', 'akuns', 'projects'));
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        // Validate header + lines sekaligus
        $validated = $request->validate([
            'journal_date'                   => 'required|date',
            'description'                    => 'required|string',
            'reference'                      => 'nullable|string|max:50',
            'kode_project'                   => 'nullable|string|exists:projects,kode_project',
            'notes'                          => 'nullable|string',
            'lines'                          => 'required|array|min:1',
            'lines.*.account_code'           => 'required|string|exists:kode_akun,kode_akun',
            'lines.*.type'                   => 'required|in:debit,credit',
            'lines.*.amount'                 => 'required|numeric|min:0',
            'lines.*.description'            => 'nullable|string|max:255',
        ], [
            'journal_date.required'          => 'Tanggal jurnal wajib diisi.',
            'journal_date.date'              => 'Format tanggal tidak valid.',
            'description.required'           => 'Deskripsi wajib diisi.',
            'kode_project.exists'            => 'Project yang dipilih tidak ditemukan.',
            'lines.required'                 => 'Minimal harus ada 1 baris jurnal.',
            'lines.min'                      => 'Minimal harus ada 1 baris jurnal.',
            'lines.*.account_code.required'  => 'Kode Akun wajib dipilih di setiap baris.',
            'lines.*.account_code.exists'    => 'Kode Akun tidak ditemukan di master akun.',
            'lines.*.type.required'          => 'Tipe (Debit/Kredit) wajib dipilih.',
            'lines.*.amount.required'        => 'Nominal wajib diisi.',
            'lines.*.amount.min'             => 'Nominal tidak boleh negatif.',
        ]);

        try {
            \DB::beginTransaction();

            // ── Update header ────────────────────────────────────────
            $journalEntry->update([
                'journal_date' => $validated['journal_date'],
                'description'  => $validated['description'],
                'reference'    => $validated['reference'] ?? null,
                'kode_project' => $validated['kode_project'] ?? null,
                'notes'        => $validated['notes'] ?? null,
            ]);

            // ── Update/create lines ──────────────────────────────────
            $submittedIds  = [];
            $totalDebit    = 0;
            $totalCredit   = 0;

            foreach ($validated['lines'] as $lineData) {
                $id     = $lineData['id'] ?? null;
                $amount = floatval($lineData['amount']);
                $type   = $lineData['type'];

                if ($id) {
                    // Update existing line
                    $line = JournalLine::where('id', $id)
                        ->where('journal_entry_id', $journalEntry->id)
                        ->first();

                    if ($line) {
                        $line->update([
                            'account_code' => $lineData['account_code'],
                            'type'         => $type,
                            'amount'       => $amount,
                            'description'  => $lineData['description'] ?? null,
                        ]);
                        $submittedIds[] = $line->id;
                    }
                } else {
                    // Baris baru
                    $newLine = JournalLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_code'     => $lineData['account_code'],
                        'type'             => $type,
                        'amount'           => $amount,
                        'description'      => $lineData['description'] ?? null,
                        'balance_after'    => null,
                    ]);
                    $submittedIds[] = $newLine->id;
                }

                if ($type === 'debit')  $totalDebit  += $amount;
                if ($type === 'credit') $totalCredit += $amount;
            }

            // Hapus baris yang dihapus user dari form
            $journalEntry->lines()
                ->whereNotIn('id', $submittedIds)
                ->delete();

            // Update total debit/credit di header
            $journalEntry->update([
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            ActivityUser::logActivity('UPDATE', 'JournalEntry', $journalEntry->id, null, $journalEntry->fresh()->load('lines')->toArray());

            CacheService::clearMasterDataCache();
            \DB::commit();

            // ── Hitung ulang balance_after per akun (running balance akuntansi) ──
            self::recalculateBalances();

            return redirect()->route('transaksi.index', ['tab' => 'jurnal'])
                ->with('success', 'Jurnal berhasil diperbarui.');

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(JournalEntry $journalEntry)
    {
        $oldData = $journalEntry->load('lines')->toArray();
        $id = $journalEntry->id;

        // Delete lines first (due to foreign key constraint)
        $journalEntry->lines()->delete();
        $journalEntry->delete();

        ActivityUser::logActivity('DELETE', 'JournalEntry', $id, $oldData, null);

        // Clear cache after deleting journal entry
        CacheService::clearMasterDataCache();

        return redirect()->route('transaksi.index', ['tab' => 'jurnal'])->with('success', 'Journal entry deleted successfully.');
    }

    /**
     * Post journal entry to general ledger
     */
    public function post(JournalEntry $journalEntry)
    {
        try {
            $this->journalPostingService->postToLedger($journalEntry);

            ActivityUser::logActivity('POST', 'JournalEntry', $journalEntry->id, null, $journalEntry->fresh()->toArray());

            // Clear cache after posting
            CacheService::clearMasterDataCache();

            return redirect()->route('transaksi.index', ['tab' => 'jurnal'])->with('success', 'Journal entry posted to general ledger successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reverse journal entry
     */
    public function reverse(JournalEntry $journalEntry)
    {
        try {
            $reversingEntry = $this->journalPostingService->reverseJournalEntry($journalEntry);

            ActivityUser::logActivity('REVERSE', 'JournalEntry', $journalEntry->id, null, [
                'original' => $journalEntry->toArray(),
                'reversing' => $reversingEntry->toArray(),
            ]);

            // Clear cache after reversing
            CacheService::clearMasterDataCache();

            return redirect()->route('transaksi.index', ['tab' => 'jurnal'])->with('success', 'Journal entry reversed successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a specific journal line
     */
    public function destroyLine(JournalEntry $journalEntry, JournalLine $journalLine)
    {
        // Check if the line belongs to this journal entry
        if ($journalLine->journal_entry_id !== $journalEntry->id) {
            return back()->withErrors(['error' => 'Journal line does not belong to this journal entry.']);
        }

        // Check if this is the last line (must have at least 2 lines)
        if ($journalEntry->lines()->count() <= 2) {
            return back()->withErrors(['error' => 'Cannot delete line. Journal entry must have at least 2 lines for double entry.']);
        }

        try {
            $oldData = $journalLine->toArray();
            $lineId = $journalLine->id;

            // Delete the line
            $journalLine->delete();

            // Recalculate totals for the journal entry
            $totalDebit = $journalEntry->lines()->where('type', 'debit')->sum('amount');
            $totalCredit = $journalEntry->lines()->where('type', 'credit')->sum('amount');

            $journalEntry->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            ActivityUser::logActivity('DELETE', 'JournalLine', $lineId, $oldData, null);

            // Clear cache after deleting line
            CacheService::clearMasterDataCache();

            return back()->with('success', 'Journal line deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Edit a specific journal line
     */
    public function editLine(JournalEntry $journalEntry, JournalLine $journalLine)
    {
        // Check if the line belongs to this journal entry
        if ($journalLine->journal_entry_id !== $journalEntry->id) {
            return back()->withErrors(['error' => 'Journal line does not belong to this journal entry.']);
        }

        $akuns = cache()->remember('akuns_all', 3600, function () {
            return Akun::select(['kode_akun', 'nama', 'tipe'])->orderBy('kode_akun')->get();
        });

        // Fallback: if cache returns empty, clear cache and fetch directly from database
        if ($akuns->isEmpty()) {
            cache()->forget('akuns_all');
            $akuns = Akun::select(['kode_akun', 'nama', 'tipe'])->orderBy('kode_akun')->get();
            // Update cache with fresh data
            if (!$akuns->isEmpty()) {
                cache()->put('akuns_all', $akuns, 3600);
            }
        }

        return view('transaksi.jurnal.edit-line', compact('journalEntry', 'journalLine', 'akuns'));
    }

    /**
     * Update a specific journal line
     */
    public function updateLine(Request $request, JournalEntry $journalEntry, JournalLine $journalLine)
    {
        // Check if the line belongs to this journal entry
        if ($journalLine->journal_entry_id !== $journalEntry->id) {
            return back()->withErrors(['error' => 'Journal line does not belong to this journal entry.']);
        }

        $validated = $request->validate([
            'account_code' => 'required|string|exists:kode_akun,kode_akun',
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ], [
            'account_code.exists' => 'Selected account does not exist',
        ]);

        try {
            $oldData = $journalLine->toArray();

            // Update the line
            $journalLine->update([
                'account_code' => $validated['account_code'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
            ]);

            // Recalculate totals for the journal entry
            $totalDebit = $journalEntry->lines()->where('type', 'debit')->sum('amount');
            $totalCredit = $journalEntry->lines()->where('type', 'credit')->sum('amount');

            // Check if still balanced
            if (abs($totalDebit - $totalCredit) > 0.01) {
                // Rollback the update
                $journalLine->update($oldData);
                return back()->withInput()->withErrors(['error' => 'Journal entry must remain balanced. Total Debit: ' . $totalDebit . ', Total Credit: ' . $totalCredit]);
            }

            $journalEntry->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            ActivityUser::logActivity('UPDATE', 'JournalLine', $journalLine->id, $oldData, $journalLine->fresh()->toArray());

            // Clear cache after updating line
            CacheService::clearMasterDataCache();

            return redirect()->route('transaksi.index', ['tab' => 'jurnal'])
                ->with('success', 'Journal line updated successfully.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate journal lines in real-time (API endpoint)
     * Used for frontend validation before submission
     */
    public function validateLines(Request $request)
    {
        try {
            $lines = $request->input('lines', []);

            // Transform debit/credit columns to type/amount format if needed
            $transformedLines = [];
            foreach ($lines as $line) {
                // Check if using new format (debit/credit columns)
                if (isset($line['debit']) || isset($line['credit'])) {
                    $debit = floatval($line['debit'] ?? 0);
                    $credit = floatval($line['credit'] ?? 0);

                    if ($debit > 0) {
                        $transformedLines[] = [
                            'account_code' => $line['account_code'],
                            'type' => 'debit',
                            'amount' => $debit,
                            'description' => $line['description'] ?? null,
                        ];
                    } elseif ($credit > 0) {
                        $transformedLines[] = [
                            'account_code' => $line['account_code'],
                            'type' => 'credit',
                            'amount' => $credit,
                            'description' => $line['description'] ?? null,
                        ];
                    }
                } else {
                    // Using old format (type/amount) - normalize type to lowercase
                    $transformedLines[] = [
                        'account_code' => $line['account_code'] ?? null,
                        'type' => strtolower($line['type'] ?? ''),
                        'amount' => $line['amount'] ?? 0,
                        'description' => $line['description'] ?? null,
                    ];
                }
            }

            $lines = $transformedLines;

            // Validation result structure
            $result = [
                'valid' => true,
                'errors' => [],
                'total_debit' => 0,
                'total_credit' => 0,
                'balance_status' => 'Not Balanced',
                'can_submit' => false,
            ];

            // Check minimum lines
            if (count($lines) < 2) {
                $result['valid'] = false;
                $result['errors'][] = 'Journal entry harus memiliki minimal 2 baris dengan nominal';
                return response()->json($result);
            }

            // Calculate totals and validate each line
            $totalDebit = 0;
            $totalCredit = 0;
            $lineErrors = [];

            foreach ($lines as $index => $line) {
                $lineNum = $index + 1;

                // Validate account
                if (empty($line['account_code'])) {
                    $lineErrors[] = "Baris {$lineNum}: Akun harus dipilih";
                    continue;
                }

                $account = Akun::where('kode_akun', $line['account_code'])->first();
                if (!$account) {
                    $lineErrors[] = "Baris {$lineNum}: Akun '{$line['account_code']}' tidak ditemukan";
                    continue;
                }

                // Validate type - normalize to lowercase
                $type = strtolower($line['type'] ?? '');
                if (empty($type) || !in_array($type, ['debit', 'credit'])) {
                    $lineErrors[] = "Baris {$lineNum}: Tipe harus debit atau credit";
                    continue;
                }

                // Validate amount
                if (!isset($line['amount']) || !is_numeric($line['amount']) || floatval($line['amount']) < 0) {
                    $lineErrors[] = "Baris {$lineNum}: Amount harus berupa angka positif";
                    continue;
                }

                $amount = floatval($line['amount']);

                // Calculate totals
                if ($type === 'debit') {
                    $totalDebit += $amount;
                } else {
                    $totalCredit += $amount;
                }
            }

            // Add line errors to result
            if (!empty($lineErrors)) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $lineErrors);
            }

            // Update totals
            $result['total_debit'] = $totalDebit;
            $result['total_credit'] = $totalCredit;

            // Check balance (allow 0.01 difference for floating point precision)
            $isBalanced = abs($totalDebit - $totalCredit) < 0.01;
            $result['balance_status'] = $isBalanced ? 'Balanced' : 'Not Balanced';

            if (!$isBalanced) {
                $result['valid'] = false;
                $result['errors'][] = "Jurnal tidak seimbang. Total Debit: " . number_format($totalDebit, 2) . ", Total Credit: " . number_format($totalCredit, 2) . ". Selisih: " . number_format(abs($totalDebit - $totalCredit), 2);
            }

            // Determine if can submit
            $result['can_submit'] = $result['valid'] && $isBalanced;

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => [$e->getMessage()],
                'total_debit' => 0,
                'total_credit' => 0,
                'balance_status' => 'Not Balanced',
                'can_submit' => false,
            ], 500);
        }
    }

    /**
     * Calculate totals for journal lines (lightweight version)
     * Used for real-time UI updates without full validation
     */
    public function calculateTotals(Request $request)
    {
        try {
            $lines = $request->input('lines', []);

            // Transform debit/credit columns to type/amount format if needed
            $transformedLines = [];
            foreach ($lines as $line) {
                // Check if using new format (debit/credit columns)
                if (isset($line['debit']) || isset($line['credit'])) {
                    $debit = floatval($line['debit'] ?? 0);
                    $credit = floatval($line['credit'] ?? 0);

                    if ($debit > 0) {
                        $transformedLines[] = [
                            'type' => 'debit',
                            'amount' => $debit,
                        ];
                    } elseif ($credit > 0) {
                        $transformedLines[] = [
                            'type' => 'credit',
                            'amount' => $credit,
                        ];
                    }
                } else {
                    // Using old format (type/amount)
                    $transformedLines[] = $line;
                }
            }

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($transformedLines as $line) {
                if (isset($line['type']) && isset($line['amount']) && is_numeric($line['amount'])) {
                    $amount = floatval($line['amount']);
                    if ($line['type'] === 'debit') {
                        $totalDebit += $amount;
                    } elseif ($line['type'] === 'credit') {
                        $totalCredit += $amount;
                    }
                }
            }

            $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

            return response()->json([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance_status' => $isBalanced ? 'Balanced' : 'Not Balanced',
                'difference' => abs($totalDebit - $totalCredit),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'total_debit' => 0,
                'total_credit' => 0,
                'balance_status' => 'Not Balanced',
                'difference' => 0,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print PDF Jurnal
     */
    public function printPdf(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        $query = JournalEntry::with(['lines.account', 'project'])
            ->whereBetween('journal_date', [$from, $to])
            ->orderBy('journal_date', 'desc')
            ->orderBy('created_at', 'desc');

        $journalEntries = $query->get();

        // Generate PDF
        $pdf = \PDF::loadView('transaksi.jurnal.pdf', compact('journalEntries', 'from', 'to'));
        return $pdf->download('jurnal-' . $from . '-to-' . $to . '.pdf');
    }

    /**
     * Export Excel Jurnal
     */
    public function exportExcel(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        $query = JournalEntry::with(['lines.account', 'project'])
            ->whereBetween('journal_date', [$from, $to])
            ->orderBy('journal_date', 'desc')
            ->orderBy('created_at', 'desc');

        $journalEntries = $query->get();

        return \Excel::download(new \App\Exports\JurnalExport($journalEntries), 'jurnal-' . $from . '-to-' . $to . '.xlsx');
    }
    /**
     * Hitung ulang balance_after untuk semua journal lines berdasarkan aturan akuntansi.
     *
     * Aturan saldo normal (normal balance):
     * - Akun Debit  (Aset, Beban):                    saldo += amount jika debit,  saldo -= amount jika credit
     * - Akun Kredit (Liabilitas, Ekuitas, Pendapatan): saldo += amount jika credit, saldo -= amount jika debit
     *
     * Running balance dihitung per akun, urut berdasarkan journal_date lalu id.
     */
    public static function recalculateBalances(): void
    {
        // Ambil semua lines dengan info akun dan tanggal journal, urut chronologis
        $lines = JournalLine::join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('kode_akun', 'journal_lines.account_code', '=', 'kode_akun.kode_akun')
            ->orderBy('journal_entries.journal_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.id')
            ->select(
                'journal_lines.id',
                'journal_lines.account_code',
                'journal_lines.type',
                'journal_lines.amount',
                'kode_akun.tipe as akun_tipe'
            )
            ->get();

        // Running balance per kode_akun
        $runningBalance = [];

        foreach ($lines as $line) {
            $kode   = $line->account_code;
            $amount = (float) $line->amount;
            $type   = strtolower($line->type);           // 'debit' atau 'credit'
            $normal = strtolower($line->akun_tipe ?? 'debit'); // 'debit' atau 'kredit'

            if (!isset($runningBalance[$kode])) {
                $runningBalance[$kode] = 0.0;
            }

            // Akun normal debit : bertambah saat debit,  berkurang saat credit
            // Akun normal kredit: bertambah saat credit, berkurang saat debit
            if ($normal === 'debit') {
                $runningBalance[$kode] += ($type === 'debit') ? $amount : -$amount;
            } else {
                $runningBalance[$kode] += ($type === 'credit') ? $amount : -$amount;
            }

            JournalLine::where('id', $line->id)
                ->update(['balance_after' => $runningBalance[$kode]]);
        }
    }
}
