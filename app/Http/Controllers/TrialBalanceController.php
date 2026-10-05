<?php

namespace App\Http\Controllers;

use App\Models\GeneralLedger;
use App\Models\Akun;
use App\Services\JournalPostingService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TrialBalanceController extends Controller
{
    protected $journalPostingService;

    public function __construct(JournalPostingService $journalPostingService)
    {
        $this->journalPostingService = $journalPostingService;
    }

    public function index(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-t'));

        // Get trial balance data from service
        $trialBalanceData = $this->journalPostingService->getTrialBalance($fromDate, $toDate);

        return view('trial-balance.index', [
            'trialBalance' => $trialBalanceData['accounts'],
            'totalDebit' => $trialBalanceData['total_debit'],
            'totalCredit' => $trialBalanceData['total_credit'],
            'isBalanced' => $trialBalanceData['is_balanced'],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-t'));

        $trialBalanceData = $this->journalPostingService->getTrialBalance($fromDate, $toDate);

        $pdf = Pdf::loadView('trial-balance.pdf', [
            'trialBalance' => $trialBalanceData['accounts'],
            'totalDebit' => $trialBalanceData['total_debit'],
            'totalCredit' => $trialBalanceData['total_credit'],
            'isBalanced' => $trialBalanceData['is_balanced'],
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);

        $filename = 'trial-balance-' . date('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-t'));

        $trialBalanceData = $this->journalPostingService->getTrialBalance($fromDate, $toDate);

        // Create simple CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="trial-balance-' . date('Ymd-His') . '.csv"',
        ];

        $callback = function() use ($trialBalanceData, $fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Account Code', 'Account Name', 'Account Type', 'Debit', 'Credit']);
            
            // Data
            foreach ($trialBalanceData['accounts'] as $account) {
                fputcsv($file, [
                    $account['account_code'],
                    $account['account_name'],
                    $account['account_type'],
                    number_format($account['debit'], 2),
                    number_format($account['credit'], 2),
                ]);
            }
            
            // Totals
            fputcsv($file, []);
            fputcsv($file, ['TOTAL', '', '', number_format($trialBalanceData['total_debit'], 2), number_format($trialBalanceData['total_credit'], 2)]);
            fputcsv($file, ['Period', $fromDate, 'to', $toDate]);
            fputcsv($file, ['Balanced', $trialBalanceData['is_balanced'] ? 'YES' : 'NO']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
