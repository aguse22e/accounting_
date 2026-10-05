<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KodeAkun;
use Barryvdh\DomPDF\Facade\Pdf;

class StatementOfActivityController extends Controller
{
    /**
     * Display Statement of Activity
     */
    public function index(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-01-01'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        
        $data = $this->getStatementData($fromDate, $toDate);
        
        return view('statement-of-activity.index', compact('data', 'fromDate', 'toDate'));
    }
    
    /**
     * Export Statement of Activity to PDF
     */
    public function exportPdf(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-01-01'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        
        $data = $this->getStatementData($fromDate, $toDate);
        
        $pdf = Pdf::loadView('statement-of-activity.pdf', compact('data', 'fromDate', 'toDate'));
        $pdf->setPaper('A4', 'portrait');
        $filename = 'Statement-of-Activity-' . date('Ymd', strtotime($fromDate)) . '-' . date('Ymd', strtotime($toDate)) . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * Export Statement of Activity to Excel
     */
    public function exportExcel(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-01-01'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        
        $data = $this->getStatementData($fromDate, $toDate);
        
        // TODO: Implement Excel export if needed
        return redirect()->back()->with('error', 'Excel export belum tersedia');
    }
    
    /**
     * Get Statement of Activity Data
     */
    private function getStatementData($fromDate, $toDate)
    {
        $results = [
            'operating_revenue' => [],
            'cost_of_goods_sold' => [],
            'operating_expenses' => [],
            'other_income' => [],
            'other_expenses' => [],
            'totals' => [
                'operating_revenue' => 0,
                'cost_of_goods_sold' => 0,
                'gross_profit' => 0,
                'operating_expenses' => 0,
                'income_from_operation' => 0,
                'other_income' => 0,
                'other_expenses' => 0,
                'net_profit_before_tax' => 0,
                'net_profit_after_tax' => 0,
            ]
        ];
        
        // Get all accounts
        $accounts = KodeAkun::all();
        
        foreach ($accounts as $account) {
            $accountCode = $account->kode_akun;
            $accountCodeInt = (int) $accountCode;
            
            // Skip if account code is not in the valid ranges (4000-8999)
            if ($accountCodeInt < 4000 || $accountCodeInt >= 9000) {
                continue;
            }
            
            $balance = $this->getAccountBalance($accountCode, $fromDate, $toDate);
            
            if ($balance == 0) {
                continue;
            }
            
            // Categorize based on account code ranges
            if ($accountCodeInt >= 4000 && $accountCodeInt < 5000) {
                // Operating Revenue (4000-4999) - Pendapatan Operasi
                $absBalance = abs($balance);
                $category = $this->getCategoryForAccount($account);
                $this->addToSection($results['operating_revenue'], $category, $absBalance);
            } elseif ($accountCodeInt >= 5000 && $accountCodeInt < 6000) {
                // Cost of Goods Sold (5000-5999) - Harga Pokok Penjualan (HPP)
                $absBalance = abs($balance);
                $category = $this->getCategoryForAccount($account);
                $this->addToSection($results['cost_of_goods_sold'], $category, $absBalance);
            } elseif ($accountCodeInt >= 6000 && $accountCodeInt < 7000) {
                // Operating Expenses (6000-6999) - Beban Operasi
                $absBalance = abs($balance);
                $category = $this->getCategoryForAccount($account);
                $this->addToSection($results['operating_expenses'], $category, $absBalance);
            } elseif ($accountCodeInt >= 7000 && $accountCodeInt < 8000) {
                // Other Income (7000-7999) - Pendapatan Lain-lain
                $absBalance = abs($balance);
                $category = $this->getCategoryForAccount($account);
                $this->addToSection($results['other_income'], $category, $absBalance);
            } elseif ($accountCodeInt >= 8000 && $accountCodeInt < 9000) {
                // Other Expenses (8000-8999) - Beban Lain-lain
                $absBalance = abs($balance);
                $category = $this->getCategoryForAccount($account);
                $this->addToSection($results['other_expenses'], $category, $absBalance);
            }
        }
        
        // Calculate totals
        $results['totals']['operating_revenue'] = $this->calculateSectionTotal($results['operating_revenue']);
        $results['totals']['cost_of_goods_sold'] = $this->calculateSectionTotal($results['cost_of_goods_sold']);
        $results['totals']['gross_profit'] = $results['totals']['operating_revenue'] - $results['totals']['cost_of_goods_sold'];
        $results['totals']['operating_expenses'] = $this->calculateSectionTotal($results['operating_expenses']);
        $results['totals']['income_from_operation'] = $results['totals']['gross_profit'] - $results['totals']['operating_expenses'];
        $results['totals']['other_income'] = $this->calculateSectionTotal($results['other_income']);
        $results['totals']['other_expenses'] = $this->calculateSectionTotal($results['other_expenses']);
        $results['totals']['net_profit_before_tax'] = $results['totals']['income_from_operation'] + $results['totals']['other_income'] - $results['totals']['other_expenses'];
        $results['totals']['net_profit_after_tax'] = $results['totals']['net_profit_before_tax'];
        
        return $results;
    }
    
    /**
     * Get category for account based on account code
     */
    private function getCategoryForAccount($account)
    {
        $accountCode = $account->kode_akun;
        $accountName = $account->nama ?? 'Unknown';
        
        // Use account code as category key, and account name as display name
        return [
            'section' => $this->getSectionForAccountCode($accountCode),
            'category' => $accountCode,
            'name' => $accountName
        ];
    }
    
    /**
     * Get section for account code based on ranges
     */
    private function getSectionForAccountCode($accountCode)
    {
        $code = (int) $accountCode;
        
        if ($code >= 4000 && $code < 5000) {
            return 'operating_revenue';
        } elseif ($code >= 5000 && $code < 6000) {
            return 'cost_of_goods_sold';
        } elseif ($code >= 6000 && $code < 7000) {
            return 'operating_expenses';
        } elseif ($code >= 7000 && $code < 8000) {
            return 'other_income';
        } elseif ($code >= 8000 && $code < 9000) {
            return 'other_expenses';
        }
        
        return 'other_expenses';
    }
    
    /**
     * Add account to section
     */
    private function addToSection(&$section, $category, $balance)
    {
        $categoryKey = $category['category'];
        
        if (!isset($section[$categoryKey])) {
            $section[$categoryKey] = [
                'name' => $category['name'],
                'total' => 0
            ];
        }
        
        $section[$categoryKey]['total'] += $balance;
    }
    
    /**
     * Calculate section total
     */
    private function calculateSectionTotal($section)
    {
        $total = 0;
        foreach ($section as $category) {
            $total += $category['total'];
        }
        return $total;
    }
    
    /**
     * Get account balance
     */
    private function getAccountBalance($accountCode, $fromDate, $toDate)
    {
        $account = KodeAkun::where('kode_akun', $accountCode)->first();
        if (!$account) {
            return 0;
        }
        
        return $account->getBalance($fromDate, $toDate);
    }
}
