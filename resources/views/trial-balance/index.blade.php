@extends('layouts.app')

@section('title', 'Trial Balance (Neraca Saldo)')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Trial Balance (Neraca Saldo)</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exportPdfModal">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportExcelModal">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Date Filter -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="fromDate" value="{{ $fromDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="toDate" value="{{ $toDate }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary" id="applyFilter">
                        <i class="bi bi-search me-1"></i> Apply Filter
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="alert {{ $isBalanced ? 'alert-success' : 'alert-danger' }} mb-0">
                        <strong>{{ $isBalanced ? '✓ Balanced' : '✗ Not Balanced' }}</strong>
                    </div>
                </div>
            </div>

            <!-- Trial Balance Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Account Type</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance as $account)
                        <tr>
                            <td><strong>{{ $account['account_code'] }}</strong></td>
                            <td>{{ $account['account_name'] }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $account['account_type'] }}</span>
                            </td>
                            <td class="text-end {{ $account['debit'] > 0 ? 'text-success fw-bold' : '' }}">
                                {{ $account['debit'] > 0 ? number_format($account['debit'], 2, ',', '.') : '-' }}
                            </td>
                            <td class="text-end {{ $account['credit'] > 0 ? 'text-danger fw-bold' : '' }}">
                                {{ $account['credit'] > 0 ? number_format($account['credit'], 2, ',', '.') : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <p class="text-muted mb-0">No trial balance data available for the selected period.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="3" class="text-end"><strong>TOTAL</strong></td>
                            <td class="text-end fw-bold">
                                {{ number_format($totalDebit, 2, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($totalCredit, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Balance Summary -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card {{ $isBalanced ? 'border-success' : 'border-danger' }}">
                        <div class="card-body">
                            <h5 class="card-title">Balance Summary</h5>
                            <p class="card-text">
                                <strong>Total Debit:</strong> {{ number_format($totalDebit, 2, ',', '.') }}<br>
                                <strong>Total Credit:</strong> {{ number_format($totalCredit, 2, ',', '.') }}<br>
                                <strong>Difference:</strong> {{ number_format(abs($totalDebit - $totalCredit), 2, ',', '.') }}
                            </p>
                            @if($isBalanced)
                                <div class="alert alert-success mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Trial balance is balanced. Double entry system is working correctly.
                                </div>
                            @else
                                <div class="alert alert-danger mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Trial balance is not balanced. Please check journal entries.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Period Information</h5>
                            <p class="card-text">
                                <strong>From:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d F Y') }}<br>
                                <strong>To:</strong> {{ \Carbon\Carbon::parse($toDate)->format('d F Y') }}<br>
                                <strong>Generated:</strong> {{ now()->format('d F Y H:i:s') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export PDF Modal -->
<div class="modal fade" id="exportPdfModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Trial Balance PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportPdfForm" method="GET" action="{{ route('trial-balance.export-pdf') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="exportPdfBtn">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Excel Modal -->
<div class="modal fade" id="exportExcelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Trial Balance Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportExcelForm" method="GET" action="{{ route('trial-balance.export-excel') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="exportExcelBtn">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply filter button
    document.getElementById('applyFilter').addEventListener('click', function() {
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        
        window.location.href = `{{ route('trial-balance.index') }}?from_date=${fromDate}&to_date=${toDate}`;
    });

    // Export PDF button
    document.getElementById('exportPdfBtn').addEventListener('click', function() {
        document.getElementById('exportPdfForm').submit();
    });

    // Export Excel button
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        document.getElementById('exportExcelForm').submit();
    });
});
</script>
@endsection