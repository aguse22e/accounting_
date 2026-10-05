<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BudgetActualService
{
    public function getBudgetActualData($from, $to, $projectId, $akunKode, $search = '')
    {
        // Initialize empty data structure
        $budgetActualData = [
            'data' => [],
            'summary' => [
                'grand_total_budget' => 0,
                'grand_total_actual' => 0,
                'grand_total_variance' => 0,
                'grand_total_variance_percent' => 0,
            ]
        ];

        try {
            // Set a reasonable timeout for the entire operation (30 seconds)
            set_time_limit(30);

            // Calculate budget vs actual data according to user specifications
            // Total budget = sum of anggaran column from budget table
            // Total actual = sum of nominal from transaksi_bank + sum of nominal from jurnal (if exists) + sum of debit-kredit from transaksi
            // Variance = budget - actual

            // Get all budget data with account and project info
            $budgetQuery = DB::table('budget')
                ->leftJoin('akun', 'budget.kode_akun', '=', 'akun.kode_akun')
                ->leftJoin('project', 'budget.kode_project', '=', 'project.kode_project')
                ->select(
                    'budget.kode_akun',
                    DB::raw("COALESCE(akun.nama_akun, 'Unknown Account') as nama_akun"),
                    'budget.kode_project',
                    DB::raw("COALESCE(project.nama_project, 'Unknown Project') as nama_project"),
                    DB::raw('SUM(COALESCE(budget.anggaran, 0)) as total_budget')
                )
                ->groupBy('budget.kode_akun', 'akun.nama_akun', 'budget.kode_project', 'project.nama_project');

            if ($projectId !== '') {
                $budgetQuery->where('budget.kode_project', $projectId);
            }
            if ($akunKode !== '') {
                $budgetQuery->where('budget.kode_akun', $akunKode);
            }
            if ($from) {
                $budgetQuery->whereDate('budget.tanggal', '>=', $from);
            }
            if ($to) {
                $budgetQuery->whereDate('budget.tanggal', '<=', $to);
            }

            $budgetData = $budgetQuery->get();

            // Calculate actual amounts for each account-project combination
            $data = [];
            foreach ($budgetData as $budget) {
                $akunKodeCurrent = $budget->kode_akun;
                $projectKodeCurrent = $budget->kode_project;

                // PERBAIKAN: Ambil tipe akun dari master data untuk perhitungan saldo normal
                $akunData = DB::table('akun')->where('kode_akun', $akunKodeCurrent)->first();
                $tipeAkun = $akunData ? ($akunData->saldo_normal ?? 'debit') : 'debit';

                // Calculate actual from transaksi_bank - match by account code and project
                $actualBank = DB::table('transaksi_bank')
                    ->where('akun_kode', $akunKodeCurrent)
                    ->whereNull('deleted_at');

                // PERBAIKAN: Tambah filter project yang konsisten
                if ($projectKodeCurrent && $projectKodeCurrent !== 'NO_PROJECT') {
                    $actualBank->where('kode_project', $projectKodeCurrent);
                }

                if ($from) {
                    $actualBank->whereDate('tanggal', '>=', $from);
                }
                if ($to) {
                    $actualBank->whereDate('tanggal', '<=', $to);
                }

                // PERBAIKAN: Bedakan debit/kredit berdasarkan tipe akun
                $debitBank = (float) $actualBank->where('tipe', 'Debit')->sum('total');
                $kreditBank = (float) $actualBank->where('tipe', 'Kredit')->sum('total');

                if ($tipeAkun === 'debit') {
                    // Aset & Beban: Debit (+), Kredit (-)
                    $totalActualBank = $debitBank - $kreditBank;
                } else {
                    // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
                    $totalActualBank = $kreditBank - $debitBank;
                }

                // Calculate actual from transaksi (journal entries) - match by account code and project
                $actualTransaksi = DB::table('transaksi')
                    ->where('akun_kode', $akunKodeCurrent);

                // PERBAIKAN: Tambah filter project yang konsisten
                if ($projectKodeCurrent && $projectKodeCurrent !== 'NO_PROJECT') {
                    $actualTransaksi->where('kode_project', $projectKodeCurrent);
                }

                if ($from) {
                    $actualTransaksi->whereDate('tanggal', '>=', $from);
                }
                if ($to) {
                    $actualTransaksi->whereDate('tanggal', '<=', $to);
                }

                $debitSum = (float) $actualTransaksi->sum('debit');
                $kreditSum = (float) $actualTransaksi->sum('kredit');

                // PERBAIKAN: Hitung berdasarkan tipe akun (saldo normal)
                if ($tipeAkun === 'debit') {
                    // Aset & Beban: Debit (+), Kredit (-)
                    $totalActualTransaksi = $debitSum - $kreditSum;
                } else {
                    // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
                    $totalActualTransaksi = $kreditSum - $debitSum;
                }

                // Calculate actual from jurnal (if exists)
                $totalActualJurnal = 0;
                if (Schema::hasTable('jurnal')) {
                    // PERBAIKAN: Bedakan debit/kredit untuk jurnal
                    $debitJurnal = DB::table('jurnal')
                        ->where('kode', $akunKodeCurrent)
                        ->where('tipe', 'Debit')
                        ->whereNotNull('kode');

                    $kreditJurnal = DB::table('jurnal')
                        ->where('kode', $akunKodeCurrent)
                        ->where('tipe', 'Kredit')
                        ->whereNotNull('kode');

                    // PERBAIKAN: Tambah filter project yang konsisten
                    if ($projectKodeCurrent && $projectKodeCurrent !== 'NO_PROJECT') {
                        $debitJurnal->where('kode_project', $projectKodeCurrent);
                        $kreditJurnal->where('kode_project', $projectKodeCurrent);
                    }

                    if ($from) {
                        $debitJurnal->whereDate('tanggal', '>=', $from);
                        $kreditJurnal->whereDate('tanggal', '>=', $from);
                    }
                    if ($to) {
                        $debitJurnal->whereDate('tanggal', '<=', $to);
                        $kreditJurnal->whereDate('tanggal', '<=', $to);
                    }

                    $debitJurnalSum = (float) $debitJurnal->sum('nominal');
                    $kreditJurnalSum = (float) $kreditJurnal->sum('nominal');

                    // PERBAIKAN: Hitung berdasarkan tipe akun (saldo normal)
                    if ($tipeAkun === 'debit') {
                        // Aset & Beban: Debit (+), Kredit (-)
                        $totalActualJurnal = $debitJurnalSum - $kreditJurnalSum;
                    } else {
                        // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
                        $totalActualJurnal = $kreditJurnalSum - $debitJurnalSum;
                    }
                }

                // Total actual = bank + transaksi + jurnal
                $totalActual = $totalActualBank + $totalActualTransaksi + $totalActualJurnal;

                // Calculate variance
                $budgetAmount = (float) $budget->total_budget;
                $variance = $budgetAmount - $totalActual;
                $variancePercent = $budgetAmount != 0 ? ($variance / $budgetAmount) * 100 : 0;

                $data[] = [
                    'kode_akun' => $budget->kode_akun,
                    'nama_akun' => $budget->nama_akun,
                    'kode_project' => $budget->kode_project,
                    'nama_project' => $budget->nama_project,
                    'budget' => $budgetAmount,
                    'actual' => $totalActual,
                    'variance' => $variance,
                    'variance_percent' => $variancePercent,
                ];
            }

            $budgetActualData['data'] = $data;

            // Calculate grand totals
            $grandTotalBudget = array_sum(array_column($data, 'budget'));
            $grandTotalActual = array_sum(array_column($data, 'actual'));
            $grandTotalVariance = $grandTotalBudget - $grandTotalActual;
            // PERBAIKAN: Gunakan budget sebagai denominator, bukan actual
            $grandTotalVariancePercent = $grandTotalBudget != 0 ? ($grandTotalVariance / $grandTotalBudget) * 100 : 0;

            $budgetActualData['summary'] = [
                'grand_total_budget' => round($grandTotalBudget, 2),
                'grand_total_actual' => round($grandTotalActual, 2),
                'grand_total_variance' => round($grandTotalVariance, 2),
                'grand_total_variance_percent' => round($grandTotalVariancePercent, 2),
            ];
        } catch (\Exception $e) {
            // Return empty data on error
        }

        return $budgetActualData;
    }
}
