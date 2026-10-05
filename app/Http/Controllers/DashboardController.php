<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Ambil transaksi terbaru
        $transaksi_terbaru = Transaksi::with('akun')
            ->latest()
            ->take(5)
            ->get();

        // Periode default: bulan berjalan
        $from = date('Y-m-01');
        $to = date('Y-m-t');

        // Tentukan akun bank (berdasarkan nama atau pola kode umum 12*)
        $bankAccountCodes = Akun::query()
            ->where(function ($q) {
                $q->where('nama', 'like', '%bank%')
                  ->orWhere('kode_akun', 'like', '12%');
            })
            ->pluck('kode_akun');

        // Hitung saldo akhir bank menggunakan logika yang sama seperti KodeAkun->getFreshBalance()
        // Menghitung dari tanggal awal sampai hari ini (akumulatif), bukan hanya MTD
        $tanggal_sampai = date('Y-m-d');
        
        // 1. Hitung dari tabel transaksi menggunakan kolom tipe dan nominal
        // Note: Tabel transaksi tidak memiliki kolom akun_kode, jadi skip untuk sekarang
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
        
        // 3. transaksi_bank_detail removed - kode_lawan column no longer exists
        
        // Total saldo akhir bank = (debit - kredit) dari semua sumber
        $totalBankDebet = $bankDebet + $bankDebetBank;
        $totalBankKredit = $bankKredit + $bankKreditBank;
        $saldo_akhir_bank = $totalBankDebet - $totalBankKredit;

        // Klasifikasi pendapatan (4*) dan biaya (5*)
        $pendapatanCodes = Akun::where('kode_akun', 'like', '4%')->pluck('kode_akun');
        $biayaCodes = Akun::where('kode_akun', 'like', '5%')->pluck('kode_akun');

        // Pendapatan normal kredit: pendapatan = kredit - debit
        $pendapatanKredit = (float) Transaksi::whereIn('akun_kode', $pendapatanCodes)
            ->whereBetween('tanggal', [$from, $to])
            ->sum('kredit');
        $pendapatanDebet = (float) Transaksi::whereIn('akun_kode', $pendapatanCodes)
            ->whereBetween('tanggal', [$from, $to])
            ->sum('debit');
        $totalPendapatan = $pendapatanKredit - $pendapatanDebet;

        // Biaya normal debit: biaya = debit - kredit
        $biayaDebet = (float) Transaksi::whereIn('akun_kode', $biayaCodes)
            ->whereBetween('tanggal', [$from, $to])
            ->sum('debit');
        $biayaKredit = (float) Transaksi::whereIn('akun_kode', $biayaCodes)
            ->whereBetween('tanggal', [$from, $to])
            ->sum('kredit');
        $totalBiaya = $biayaDebet - $biayaKredit;

        // Net/Loss = Pendapatan - Biaya (positif = laba, negatif = rugi)
        $net_loss = $totalPendapatan - $totalBiaya;

        return view('dashboard', compact(
            'transaksi_terbaru',
            'saldo_akhir_bank',
            'net_loss'
        ));
    }
}
