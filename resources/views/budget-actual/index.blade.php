@extends('layouts.app')

@section('title', 'Budget vs Actual Report')

@section('styles')
@vite(['resources/css/pages/budget-actual.css'])
@endsection

@section('content')

<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-graph-up me-2"></i>Budget vs Actual Report</h3>
        </div>
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Budget</h6>
                                    <h4>Rp {{ number_format($budgetActualData['summary']['grand_total_budget'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-wallet2 fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Actual</h6>
                                    <h4>Rp {{ number_format($budgetActualData['summary']['grand_total_actual'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cash-stack fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Variance</h6>
                                    <h4>Rp {{ number_format($budgetActualData['summary']['grand_total_variance'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-graph-up fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Variance %</h6>
                                    <h4>{{ number_format($budgetActualData['summary']['grand_total_variance_percent'] ?? 0, 1) }}%</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-percent fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Data</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('budget-actual.index') }}" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Dari Tanggal</label>
                                    <input type="date" name="from_date" class="form-control" value="{{ $displayFromDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="to_date" class="form-control" value="{{ $displayToDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Project</label>
                                    <select name="project_id" class="form-select">
                                        <option value="">Semua Project</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->Kode_project }}" {{ $projectId == $project->Kode_project ? 'selected' : '' }}>
                                                {{ $project->Nama_Project }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Akun</label>
                                    <select name="akun_kode" class="form-select">
                                        <option value="">Semua Akun</option>
                                        @foreach($akuns as $akun)
                                            <option value="{{ $akun->kode_akun }}" {{ $akunKode == $akun->kode_akun ? 'selected' : '' }}>
                                                {{ $akun->kode_akun }} - {{ $akun->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search me-1"></i>Filter
                                    </button>
                                    <a href="{{ route('budget-actual.index') }}" class="btn btn-secondary me-2">
                                        <i class="bi bi-x-circle me-1"></i>Reset
                                    </a>
                                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="exportPdf()">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Kode Project</th>
                            <th>Nama Project</th>
                            <th class="text-center">Budget</th>
                            <th class="text-center">Actual</th>
                            <th class="text-center">Variance</th>
                            <th class="text-center">Variance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($budgetActualData['data']) && count($budgetActualData['data']) > 0)
                            @foreach($budgetActualData['data'] as $item)
                                <tr>
                                    <td>{{ $item['kode_akun'] }}</td>
                                    <td>{{ $item['nama_akun'] }}</td>
                                    <td>{{ $item['kode_project'] }}</td>
                                    <td>{{ $item['nama_project'] }}</td>
                                    <td class="text-end">{{ number_format($item['budget'], 2) }}</td>
                                    <td class="text-end">{{ number_format($item['actual'], 2) }}</td>
                                    <td class="text-end {{ $item['variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($item['variance'], 2) }}
                                    </td>
                                    <td class="text-end {{ $item['variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($item['variance_percent'], 2) }}%
                                    </td>
                                </tr>
                            @endforeach
                            <!-- Grand Total Row -->
                            @if(isset($budgetActualData['summary']))
                                <tr class="table-primary fw-bold">
                                    <td colspan="4" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                    <td class="text-end"><strong>{{ number_format($budgetActualData['summary']['grand_total_budget'], 2) }}</strong></td>
                                    <td class="text-end"><strong>{{ number_format($budgetActualData['summary']['grand_total_actual'], 2) }}</strong></td>
                                    <td class="text-end {{ $budgetActualData['summary']['grand_total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ number_format($budgetActualData['summary']['grand_total_variance'], 2) }}</strong>
                                    </td>
                                    <td class="text-end {{ $budgetActualData['summary']['grand_total_variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ number_format($budgetActualData['summary']['grand_total_variance_percent'], 2) }}%</strong>
                                    </td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-graph-up fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Belum Ada Data Budget vs Actual</h5>
                                        <p class="text-muted">Data akan muncul secara real-time saat ada budget atau transaksi baru</p>
                                        <div class="mt-3">
                                            <span class="badge bg-info">Status: memuat Data</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Export Excel Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Excel Budget vs Actual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="GET" action="{{ route('budget-actual.export-excel') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $displayFromDate }}">
                            <small class="text-muted">Kosongkan untuk semua data</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $displayToDate }}">
                            <small class="text-muted">Kosongkan untuk semua data</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari (Kode/Nama Akun/Project)</label>
                            <input type="text" name="search" id="exportSearch" class="form-control" placeholder="Kode Akun / Project / Nama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">Semua Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->Kode_project }}" {{ $projectId == $project->Kode_project ? 'selected' : '' }}>
                                        {{ $project->Nama_Project }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Akun</label>
                            <select name="akun_kode" class="form-select">
                                <option value="">Semua Akun</option>
                                @foreach($akuns as $akun)
                                    <option value="{{ $akun->kode_akun }}" {{ $akunKode == $akun->kode_akun ? 'selected' : '' }}>
                                        {{ $akun->kode_akun }} - {{ $akun->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="submitExportForm" class="btn btn-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Print Excel
                </button>
            </div>
        </div>
    </div>
</div>

<script id="summaryDataJson" type="application/json">@json($budgetActualData['summary'] ?? null)</script>
<script id="initialDataJson" type="application/json">@json($budgetActualData ?? null)</script>
@vite(['resources/js/pages/budget-actual.js'])
<script>
    // Pass Blade variables to JavaScript
    window.budgetActualRoutes = {
        exportPdf: '{{ route("budget-actual.export-pdf") }}'
    };

    function exportPdf() {
        const url = new URL(window.budgetActualRoutes.exportPdf);
        const formData = new FormData(document.querySelector('form[action*="budget-actual.index"]'));

        for (let [key, value] of formData.entries()) {
            if (value) {
                url.searchParams.append(key, value);
            }
        }

        window.open(url.toString(), '_blank');
    }

    // Handle export form submission
    document.getElementById('submitExportForm').addEventListener('click', function() {
        document.getElementById('exportForm').submit();
    });
</script>
@endsection
