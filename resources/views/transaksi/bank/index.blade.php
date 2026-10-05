@extends('layouts.app')

@push('styles')
<style>
    :root {
        --primary-purple: #8B5CF6;
        --primary-pink: #EC4899;
        --primary-blue: #3B82F6;
        --gradient: linear-gradient(135deg, var(--primary-purple), var(--primary-pink), var(--primary-blue));
    }

    .content-wrapper {
        background-color: #f8f9fa;
        padding: 0;
        box-shadow: none;
        border-radius: 0;
        margin-bottom: 0;
    }

    .transaksi-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin: 0;
        overflow: hidden;
    }

    /* Header Section */
    .transaksi-header {
        background: white;
        padding: 1.25rem 1.75rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .transaksi-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .transaksi-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .btn-print, .btn-export {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-print {
        background-color: #ef4444;
        color: white;
    }

    .btn-print:hover {
        background-color: #dc2626;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        transform: translateY(-2px);
        color: white;
    }

    .btn-export {
        background-color: #10b981;
        color: white;
    }

    .btn-export:hover {
        background-color: #059669;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transform: translateY(-2px);
        color: white;
    }

    /* Tab Navigation */
    .transaksi-tabs {
        background: white;
        padding: 0 1.75rem;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        position: relative;
        z-index: 1;
    }

    .tab-item {
        padding: 1rem 0;
        margin-right: 2rem;
        border: none;
        background: transparent !important;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 500;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        transition: all 0.3s ease;
        padding-bottom: 0.75rem;
        z-index: 1;
        outline: none;
        text-decoration: none;
        user-select: none;
        pointer-events: auto !important;
    }

    .tab-item:hover {
        color: var(--primary-purple) !important;
        background: transparent !important;
        text-decoration: none;
    }

    .tab-item.active {
        color: var(--primary-purple) !important;
        border-bottom: 3px solid var(--primary-purple);
        background: transparent !important;
        text-decoration: none;
    }

    .tab-item:focus {
        outline: none;
        background: transparent !important;
        box-shadow: none;
    }

    .tab-item:active {
        background: transparent !important;
        transform: none;
    }

    .tab-badge {
        background-color: #4b5563;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Content Area */
    .tab-content {
        padding: 1.75rem;
    }

    .tab-pane {
        display: none !important;
    }

    .tab-pane.active {
        display: block !important;
    }

    /* Button Tambah */
    .btn-tambah {
        background: var(--gradient);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 0.7rem 1.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        transition: all 0.3s ease;
        margin-bottom: 1.25rem;
        pointer-events: auto !important;
        text-decoration: none;
    }

    .btn-tambah:hover {
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    /* Filter Section */
    .filter-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .filter-row {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
        flex-wrap: nowrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        flex: 0 0 auto;
        min-width: 0;
    }

    .filter-group.search-group {
        flex: 1 1 200px;
    }

    .filter-group.type-group {
        flex: 0 0 140px;
    }

    .filter-group.date-group {
        flex: 0 0 130px;
    }

    .filter-group.button-group {
        flex: 0 0 auto;
        flex-direction: row;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }

    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 0.4rem;
    }

    .filter-input, .filter-select {
        padding: 0.6rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 0.9rem;
        font-family: inherit;
        transition: border-color 0.3s ease;
        height: 38px;
    }

    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: var(--primary-purple);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .filter-input::placeholder {
        color: #9ca3af;
    }

    /* Filter Action Buttons */
    .btn-filter-action {
        padding: 0.6rem 0.75rem;
        border: none;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        white-space: nowrap;
        height: 38px;
        min-width: 100px;
    }

    .btn-filter-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-filter-action i {
        font-size: 0.8rem;
    }

    .btn-filter-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: nowrap;
        align-items: center;
    }

    /* Table Section */
    .table-wrapper {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        font-size: 0.9rem;
    }

    .table thead {
        background-color: #f3f4f6;
        border-bottom: 2px solid #e5e7eb;
    }

    .table th {
        padding: 1rem 1.25rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table th.text-end {
        text-align: right;
    }

    .table th.text-center {
        text-align: center;
    }

    .table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #fafafa;
    }

    .table td {
        padding: 1rem 1.25rem;
        color: #374151;
        vertical-align: middle;
    }

    .table td.text-end {
        text-align: right;
    }

    .table td.text-center {
        text-align: center;
    }

    /* Badge Styles */
    .badge-code {
        background-color: #dbeafe;
        color: #0c4a6e;
        padding: 0.4rem 0.75rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-akun {
        background-color: #e0e7ff;
        color: #3730a3;
        padding: 0.4rem 0.75rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-type-debit {
        background-color: #bbf7d0;
        color: #065f46;
        padding: 0.4rem 0.75rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-type-kredit {
        background-color: #fecaca;
        color: #991b1b;
        padding: 0.4rem 0.75rem;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    /* Amount Styles */
    .amount-debit {
        color: #059669;
        font-weight: 600;
    }

    .amount-kredit {
        color: #dc2626;
        font-weight: 600;
    }

    .amount-saldo {
        font-weight: 700;
    }

    .amount-saldo.positive {
        color: #059669;
    }

    .amount-saldo.negative {
        color: #dc2626;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }

    .action-btn.edit {
        background-color: #fbbf24;
        color: white;
    }

    .action-btn.edit:hover {
        background-color: #f59e0b;
        transform: scale(1.1);
    }

    .action-btn.delete {
        background-color: #ef4444;
        color: white;
    }

    .action-btn.delete:hover {
        background-color: #dc2626;
        transform: scale(1.1);
    }

    .action-btn.view {
        background-color: #3b82f6;
        color: white;
    }

    .action-btn.view:hover {
        background-color: #2563eb;
        transform: scale(1.1);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #9ca3af;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state-text {
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .transaksi-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .transaksi-actions {
            width: 100%;
        }

        .filter-row {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .table {
            font-size: 0.8rem;
        }

        .table th, .table td {
            padding: 0.75rem 0.5rem;
        }

        .action-btn {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }
    }
</style>
@endpush

@section('title', 'Transaksi Bank')

@section('content')
<div class="content-wrapper">
    <div class="transaksi-container">
        <!-- Header -->
        <div class="transaksi-header">
            <h1 class="transaksi-title">Transaksi Bank List</h1>
            <div class="transaksi-actions">
                <button class="btn-print" data-bs-toggle="modal" data-bs-target="#printModal">
                    <i class="bi bi-file-earmark-pdf"></i> Print PDF
                </button>
                <button class="btn-export" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="transaksi-tabs">
            <div style="display: flex; align-items: flex-end; gap: 0; position: relative; z-index: 1;">
                <button class="tab-item active" id="tab-all" type="button" onclick="showAllTab(); return false;" style="pointer-events: auto !important; cursor: pointer;">
                    <i class="bi bi-bank"></i>
                    Semua Transaksi Bank
                    <span class="tab-badge">{{ ($transaksis ?? collect())->count() }}</span>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- All Transaksi Bank Tab -->
            <div class="tab-pane active" id="pane-all">
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-group search-group">
                            <label class="filter-label">Cari</label>
                            <input type="text" id="bankSearch" class="filter-input" placeholder="Cari kode, akun, atau nominal">
                        </div>
                        <div class="filter-group type-group">
                            <label class="filter-label">Tipe</label>
                            <select id="bankType" class="filter-select">
                                <option value="">Semua Tipe</option>
                                <option value="Debit">Debit</option>
                                <option value="Kredit">Kredit</option>
                            </select>
                        </div>
                        <div class="filter-group date-group">
                            <label class="filter-label">Dari Tanggal</label>
                            <input type="date" id="bankFromDate" class="filter-input">
                        </div>
                        <div class="filter-group date-group">
                            <label class="filter-label">Sampai Tanggal</label>
                            <input type="date" id="bankToDate" class="filter-input">
                        </div>
                        <div class="filter-group button-group">
                            <button type="button" class="btn-filter-action" id="bankSearchBtn" onclick="filterBankTable()" style="background-color: var(--primary-purple); color: white; cursor: pointer; pointer-events: auto;">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            <button type="button" class="btn-filter-action" id="bankTodayBtn" onclick="setBankToday()" style="background-color: #6b7280; color: white; cursor: pointer; pointer-events: auto;">
                                <i class="bi bi-calendar-event"></i> Hari ini
                            </button>
                            <button type="button" class="btn-filter-action" id="bankResetBtn" onclick="resetBankFilter()" style="background-color: #d1d5db; color: #374151; cursor: pointer; pointer-events: auto;">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Button Tambah -->
                <a href="{{ route('transaksi.bank.create') }}" class="btn-tambah" style="text-decoration: none; pointer-events: auto; cursor: pointer;">
                    <i class="bi bi-plus-circle"></i> Tambah Transaksi Bank
                </a>

                <!-- Success Alert -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 1.5rem;">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Table -->
                <div class="table-wrapper">
                    <table class="table" id="bankTable">
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Tanggal</th>
                                <th>Akun</th>
                                <th>Tipe</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksis ?? [] as $transaksi)
                            <tr class="bank-row">
                                <td><span class="badge-code">{{ $transaksi->kode_transaksi_bank ?? 'N/A' }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($transaksi->tanggal ?? now())->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge-akun">
                                        {{ $transaksi->akun_kode ?? 'N/A' }} - {{ $transaksi->akun->nama_akun ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if(($transaksi->tipe ?? 'Debit') === 'Debit')
                                        <span class="badge-type-debit">Debit</span>
                                    @else
                                        <span class="badge-type-kredit">Kredit</span>
                                    @endif
                                </td>
                                <td class="text-end amount-debit">
                                    {{ ($transaksi->tipe ?? 'Debit') === 'Debit' ? number_format($transaksi->nominal ?? 0, 2, ',', '.') : '-' }}
                                </td>
                                <td class="text-end amount-kredit">
                                    {{ ($transaksi->tipe ?? 'Debit') === 'Kredit' ? number_format($transaksi->nominal ?? 0, 2, ',', '.') : '-' }}
                                </td>
                                <td class="text-end amount-saldo {{ ($transaksi->saldo ?? 0) >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($transaksi->saldo ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <a href="{{ route('transaksi.bank.show', $transaksi->kode_transaksi_bank) }}" class="action-btn view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('transaksi.bank.edit', ['kode' => $transaksi->kode_transaksi_bank]) }}" class="action-btn edit" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transaksi.bank.destroy', $transaksi->kode_transaksi_bank) }}" method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus transaksi bank ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                        <div class="empty-state-text">Belum ada data transaksi bank</div>
                                        <a href="{{ route('transaksi.bank.create') }}" class="btn-tambah" style="text-decoration: none; pointer-events: auto; cursor: pointer;">
                                            <i class="bi bi-plus-circle"></i> Tambah Transaksi Bank
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print PDF Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Print PDF Transaksi Bank
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="printForm" method="GET" action="{{ route('transaksi.bank.print-pdf') ?? '#' }}">
                    <div class="mb-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="from" class="form-control" value="{{ date('Y-m-01') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="to" class="form-control" value="{{ date('Y-m-t') }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="printPdfBtn" onclick="printPdf()">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Print PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Excel Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Excel Transaksi Bank
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="GET" action="{{ route('transaksi.bank.export-excel') ?? '#' }}">
                    <div class="mb-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="from" class="form-control" value="{{ date('Y-m-01') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="to" class="form-control" value="{{ date('Y-m-t') }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="exportExcelBtn" onclick="exportExcel()">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Excel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function localDateYmd() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const da = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${da}`;
}

// Global functions for modal actions
function printPdf() {
    console.log('printPdf called');
    const printForm = document.getElementById('printForm');
    if (printForm) {
        const action = printForm.getAttribute('action');
        if (action && action !== '#') {
            printForm.submit();
        } else {
            alert('Print PDF feature is not configured yet. Please add route "transaksi.bank.print-pdf"');
        }
    }
}

function exportExcel() {
    console.log('exportExcel called');
    const exportForm = document.getElementById('exportForm');
    if (exportForm) {
        const action = exportForm.getAttribute('action');
        if (action && action !== '#') {
            exportForm.submit();
        } else {
            alert('Export Excel feature is not configured yet. Please add route "transaksi.bank.export-excel"');
        }
    }
}

// Global functions for bank filter
function filterBankTable() {
    console.log('filterBankTable called');
    
    const bankSearch = document.getElementById('bankSearch');
    const bankType = document.getElementById('bankType');
    const bankFromDate = document.getElementById('bankFromDate');
    const bankToDate = document.getElementById('bankToDate');
    const bankTable = document.getElementById('bankTable');
    
    if (!bankTable) return;
    
    const searchTerm = bankSearch ? bankSearch.value.toLowerCase() : '';
    const typeFilter = bankType ? bankType.value : '';
    const fromDate = bankFromDate ? bankFromDate.value : null;
    const toDate = bankToDate ? bankToDate.value : null;
    
    const rows = bankTable.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const kodeTransaksi = row.cells[0].textContent.toLowerCase();
        const dateText = row.cells[1].textContent.trim();
        const akun = row.cells[2].textContent.toLowerCase();
        const type = row.cells[3].textContent.toLowerCase().trim();
        const debit = row.cells[4].textContent.toLowerCase();
        const kredit = row.cells[5].textContent.toLowerCase();
        
        let show = true;
        
        // Search filter
        if (searchTerm && !kodeTransaksi.includes(searchTerm) && 
            !akun.includes(searchTerm) && !debit.includes(searchTerm) && !kredit.includes(searchTerm)) {
            show = false;
        }
        
        // Type filter
        if (typeFilter) {
            if (typeFilter.toLowerCase() === 'debit' && type !== 'debit') show = false;
            if (typeFilter.toLowerCase() === 'kredit' && type !== 'kredit') show = false;
        }
        
        // Date filter
        if (show && (fromDate || toDate)) {
            const dateParts = dateText.split('/');
            if (dateParts.length === 3) {
                const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                
                if (fromDate && rowDate < new Date(fromDate)) show = false;
                if (toDate && rowDate > new Date(toDate)) show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    });
    
    console.log('Bank filter applied');
}

function setBankToday() {
    console.log('setBankToday called');
    
    const bankFromDate = document.getElementById('bankFromDate');
    const bankToDate = document.getElementById('bankToDate');
    
    if (bankFromDate && bankToDate) {
        const today = localDateYmd();
        bankFromDate.value = today;
        bankToDate.value = today;
        filterBankTable();
    }
}

function resetBankFilter() {
    console.log('resetBankFilter called');
    
    const bankSearch = document.getElementById('bankSearch');
    const bankType = document.getElementById('bankType');
    const bankFromDate = document.getElementById('bankFromDate');
    const bankToDate = document.getElementById('bankToDate');
    const bankTable = document.getElementById('bankTable');
    
    if (bankSearch) bankSearch.value = '';
    if (bankType) bankType.value = '';
    if (bankFromDate) bankFromDate.value = '';
    if (bankToDate) bankToDate.value = '';
    
    if (bankTable) {
        const rows = bankTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
    }
    
    console.log('Bank filter reset');
}

function showAllTab() {
    console.log('showAllTab called');
    // Single tab, no switching needed
    console.log('Already on all bank tab');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded - all global functions ready');
});
</script>
@endsection
