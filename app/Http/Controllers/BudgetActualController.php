<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Models\Project;
use App\Models\Akun;
use App\Exports\BudgetActualExport;
use App\Services\BudgetActualService;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class BudgetActualController extends Controller
{
    protected $budgetActualService;

    public function __construct(BudgetActualService $budgetActualService)
    {
        $this->budgetActualService = $budgetActualService;
    }

    public function index(Request $request)
    {
        $from = $request->query('from_date');
        $to = $request->query('to_date');
        $projectId = (string) $request->query('project_id', '');
        $akunKode = (string) $request->query('akun_kode', '');

        // Set display dates for view
        $displayFromDate = $from ?: null;
        $displayToDate = $to ?: null;

        // Get projects for dropdown
        $projects = Project::orderBy('kode_project')->get()->map(function($p) {
            return (object)[
                'Kode_project' => $p->kode_project,
                'Nama_Project' => $p->nama_project,
            ];
        });

        // Get akuns for dropdown
        $akuns = Akun::orderBy('kode_akun')->get()->map(function($a) {
            return (object)[
                'kode_akun' => $a->kode_akun,
                'nama' => $a->nama_akun,
            ];
        });

        // Get budget vs actual data
        $budgetActualData = $this->budgetActualService->getBudgetActualData($from, $to, $projectId, $akunKode);

        return view('budget-actual.index', compact('budgetActualData', 'displayFromDate', 'displayToDate', 'projects', 'projectId', 'akunKode', 'akuns'));
    }



    public function data(Request $request)
    {
        $from = $request->query('from_date');
        $to = $request->query('to_date');
        $projectId = (string) $request->query('project_id', '');
        $akunKode = (string) $request->query('akun_kode', '');
        $search = $request->query('search', '');

        // Get budget vs actual data
        $budgetActualData = $this->budgetActualService->getBudgetActualData($from, $to, $projectId, $akunKode, $search);

        return response()->json($budgetActualData);
    }

    public function exportExcel(Request $request)
    {
        $from = $request->input('from_date');
        $to = $request->input('to_date');
        $projectId = (string) $request->input('project_id', '');
        $akunKode = (string) $request->input('akun_kode', '');
        $search = $request->input('search', '');

        // Get budget vs actual data
        $budgetActualData = $this->budgetActualService->getBudgetActualData($from, $to, $projectId, $akunKode, $search);

        $filename = 'budget-actual-' . date('Ymd-His') . '.xlsx';
        return Excel::download(new BudgetActualExport($budgetActualData, $from, $to), $filename);
    }

    public function exportPdf(Request $request)
    {
        $from = $request->query('from_date');
        $to = $request->query('to_date');
        $projectId = (string) $request->query('project_id', '');
        $akunKode = (string) $request->query('akun_kode', '');
        $search = $request->query('search', '');

        // Get budget vs actual data
        $budgetActualData = $this->budgetActualService->getBudgetActualData($from, $to, $projectId, $akunKode, $search);

        // Generate PDF
        $pdf = Pdf::loadView('budget-actual.export-pdf', [
            'budgetActualData' => $budgetActualData,
            'from_date' => $from,
            'to_date' => $to,
        ]);

        $filename = 'budget-actual-' . date('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }
}
