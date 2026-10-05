<?php

namespace App\Http\Controllers;

use App\Models\TransaksiBank;
use App\Models\ActivityUser;
use App\Models\Akun;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransaksiBankController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $kode_voucher = $request->input('kode_voucher', '');
        $kode_akun = $request->input('kode_akun', '');
        $nominal = $request->input('nominal', '');
        $search = $request->input('search', ''); // General search

        // Query with filters
        $query = TransaksiBank::with(['akun'])
            ->orderBy('created_at', 'asc')  // Primary: chronological order
            ->orderBy('tanggal', 'asc');    // Secondary: by date

        // Apply filters
        if (!empty($kode_voucher)) {
            $query->where('kode_transaksi_bank', 'like', '%' . $kode_voucher . '%');
        }
        if (!empty($kode_akun)) {
            $query->where('akun_kode', 'like', '%' . $kode_akun . '%');
        }
        if (!empty($nominal)) {
            $query->where('nominal', 'like', '%' . $nominal . '%');
        }

        // Apply general search (automatic search across multiple fields)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('kode_transaksi_bank', 'like', '%' . $search . '%')
                  ->orWhere('akun_kode', 'like', '%' . $search . '%')
                  ->orWhere('nominal', 'like', '%' . $search . '%')
                  ->orWhere('tipe', 'like', '%' . $search . '%')
                  ->orWhereHas('akun', function($aq) use ($search) {
                      $aq->where('nama', 'like', '%' . $search . '%');
                  });
            });
        }

        $transaksis = $query->get();

        // Calculate running balance (saldo) - Saldo Kumulatif
        $runningBalance = 0;
        foreach ($transaksis as $transaksi) {
            // Use tipe and nominal since debit/kredit columns don't exist
            $nominal = floatval($transaksi->nominal ?? 0);
            $isDebit = ($transaksi->tipe === 'Debit');

            // Debit menambah saldo, Kredit mengurangi saldo
            if ($isDebit) {
                $runningBalance += $nominal;
            } else {
                $runningBalance -= $nominal;
            }

            // Saldo running balance
            $transaksi->saldo = $runningBalance;
        }

        return view('transaksi.bank.index', compact('transaksis', 'kode_voucher', 'kode_akun', 'nominal', 'search'));
    }

    public function create()
    {
        $akuns = Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
        $projects = Project::orderBy('kode_project')->get();
        return view('transaksi.bank.create', compact('akuns', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_transaksi_bank' => 'required|string|max:20|unique:transaksi_bank,kode_transaksi_bank',
            'akun_kode' => 'required|string|exists:kode_akun,kode_akun',
            'tipe' => 'required|string|in:Debit,Kredit',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'kode_project' => 'nullable|string|exists:projects,kode_project',
            'deskripsi' => 'nullable|string',
        ]);

        // Calculate total from nominal
        $nominal = floatval($validated['nominal']);
        $validated['total'] = $nominal;
        $validated['nominal'] = $nominal;

        $transaksi = TransaksiBank::create($validated);
        ActivityUser::logActivity('CREATE', 'TransaksiBank', $transaksi->kode_transaksi_bank, null, $transaksi->toArray());

        return redirect()->route('transaksi.index', ['tab' => 'bank'])->with('success', 'Transaksi Bank created successfully.');
    }

    public function show(TransaksiBank $bank)
    {
        $bank->load(['akun', 'project']);
        return view('transaksi.bank.show', ['transaksi' => $bank]);
    }

    public function edit(Request $request)
    {
        // Debug logging
        \Log::info('TransaksiBankController edit called', [
            'query_kode' => $request->get('kode'),
            'route_bank' => $request->route('bank'),
            'all_params' => $request->all(),
        ]);
        
        // Get kode_transaksi_bank from query parameter or route parameter
        $kode_transaksi_bank = $request->get('kode') ?? $request->route('bank');
        
        if (!$kode_transaksi_bank) {
            \Log::warning('Kode transaksi bank tidak ditemukan');
            return redirect()->route('transaksi.index', ['tab' => 'bank'])->with('error', 'Kode transaksi bank tidak ditemukan.');
        }
        
        $bank = TransaksiBank::where('kode_transaksi_bank', $kode_transaksi_bank)->first();
        
        if (!$bank) {
            \Log::warning('Transaksi bank tidak ditemukan', ['kode' => $kode_transaksi_bank]);
            return redirect()->route('transaksi.index', ['tab' => 'bank'])->with('error', 'Transaksi bank tidak ditemukan.');
        }
        
        \Log::info('Transaksi bank found', ['bank_id' => $bank->id]);
        
        $akuns = Akun::select(['kode_akun', 'nama'])->orderBy('kode_akun')->get();
        return view('transaksi.bank.edit', ['transaksi' => $bank, 'akuns' => $akuns]);
    }

    public function update(Request $request, TransaksiBank $bank)
    {
        $validated = $request->validate([
            'kode_transaksi_bank' => 'required|string|max:20|unique:transaksi_bank,kode_transaksi_bank,' . $bank->kode_transaksi_bank . ',kode_transaksi_bank',
            'akun_kode' => 'required|string|exists:kode_akun,kode_akun',
            'tipe' => 'required|string|in:Debit,Kredit',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'deskripsi' => 'nullable|string',
        ]);

        // Calculate total from nominal
        $nominal = floatval($validated['nominal']);
        $validated['total'] = $nominal;
        $validated['nominal'] = $nominal;

        $bank->update($validated);
        $bank->updated_at = now();
        $bank->save();
        ActivityUser::logActivity('UPDATE', 'TransaksiBank', $bank->kode_transaksi_bank, null, $bank->fresh()->toArray());

        return redirect()->route('transaksi.index', ['tab' => 'bank'])->with('success', 'Transaksi Bank updated successfully.');
    }

    public function destroy(TransaksiBank $bank)
    {
        $id = $bank->kode_transaksi_bank;
        $bank->delete();
        ActivityUser::logActivity('DELETE', 'TransaksiBank', $id, $bank->toArray(), null);

        return redirect()->route('transaksi.index', ['tab' => 'bank'])->with('success', 'Transaksi Bank deleted successfully.');
    }

    /**
     * Print PDF Transaksi Bank
     */
    public function printPdf(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        $query = TransaksiBank::with(['akun'])
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('created_at', 'asc')
            ->orderBy('tanggal', 'asc');

        $transaksis = $query->get();

        // Calculate running balance
        $runningBalance = 0;
        foreach ($transaksis as $transaksi) {
            $nominal = floatval($transaksi->nominal ?? 0);
            $isDebit = ($transaksi->tipe === 'Debit');

            if ($isDebit) {
                $runningBalance += $nominal;
            } else {
                $runningBalance -= $nominal;
            }

            $transaksi->saldo = $runningBalance;
        }

        // Generate PDF
        $pdf = \PDF::loadView('transaksi.bank.pdf', compact('transaksis', 'from', 'to'));
        return $pdf->download('transaksi-bank-' . $from . '-to-' . $to . '.pdf');
    }

    /**
     * Export Excel Transaksi Bank
     */
    public function exportExcel(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to = $request->input('to', date('Y-m-t'));

        $query = TransaksiBank::with(['akun'])
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('created_at', 'asc')
            ->orderBy('tanggal', 'asc');

        $transaksis = $query->get();

        // Calculate running balance
        $runningBalance = 0;
        foreach ($transaksis as $transaksi) {
            $nominal = floatval($transaksi->nominal ?? 0);
            $isDebit = ($transaksi->tipe === 'Debit');

            if ($isDebit) {
                $runningBalance += $nominal;
            } else {
                $runningBalance -= $nominal;
            }

            $transaksi->saldo = $runningBalance;
        }

        return \Excel::download(new \App\Exports\TransaksiBankExport($transaksis), 'transaksi-bank-' . $from . '-to-' . $to . '.xlsx');
    }
}
