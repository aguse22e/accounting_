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
    }

    .btn-tambah:hover {
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
        color: white;
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

    .badge-project {
        background-color: #cffafe;
        color: #0e7490;
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

@section('title', 'Transaksi Jurnal')

@section('content')
<div class="content-wrapper">
    <div class="transaksi-container">
        <!-- Header -->
        <div class="transaksi-header">
            <h1 class="transaksi-title">Transaksi Jurnal List</h1>
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
                    <i class="bi bi-journal-text"></i>
                    Semua Transaksi Jurnal
                    <span class="tab-badge">{{ ($journalEntries ?? collect())->count() }}</span>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- All Jurnal Tab -->
            <div class="tab-pane active" id="pane-all">
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-group search-group">
                            <label class="filter-label">Cari</label>
                            <input type="text" id="jurnalSearch" class="filter-input" placeholder="Cari">
                        </div>
                        <div class="filter-group date-group">
                            <label class="filter-label">Dari Tanggal</label>
                            <input type="date" id="jurnalFromDate" class="filter-input">
                        </div>
                        <div class="filter-group date-group">
                            <label class="filter-label">Sampai Tanggal</label>
                            <input type="date" id="jurnalToDate" class="filter-input">
                        </div>
                        <div class="filter-group button-group">
                            <button class="btn-filter-action" id="jurnalSearchBtn" onclick="filterJurnalTable()" style="background-color: var(--primary-purple); color: white; cursor: pointer;">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            <button class="btn-filter-action" id="jurnalTodayBtn" onclick="setJurnalToday()" style="background-color: #6b7280; color: white; cursor: pointer;">
                                <i class="bi bi-calendar-event"></i> Hari ini
                            </button>
                            <button class="btn-filter-action" id="jurnalResetBtn" onclick="resetJurnalFilter()" style="background-color: #d1d5db; color: #374151; cursor: pointer;">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Button Tambah -->
                <a href="{{ route('transaksi.jurnal.create') }}" class="btn-tambah">
                    <i class="bi bi-plus-circle"></i> Tambah Transaksi Jurnal
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
                    <table class="table" id="jurnalTable">
                        <thead>
                            <tr>
                                <th>Kode Voucher</th>
                                <th>Kode Akun</th>
                                <th>Kode Project</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Nominal</th>
                                <th class="text-end">Saldo</th>
                                <th>Waktu</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($journalEntries ?? [] as $entry)
                                @foreach($entry->lines as $line)
                                <tr class="jurnal-row">
                                    <td><span class="badge-code">{{ $entry->journal_number ?? 'N/A' }}</span></td>
                                    <td>{{ $line->account_code ?? 'N/A' }}</td>
                                    <td><span class="badge-project">{{ $entry->kode_project ?? 'N/A' }}</span></td>
                                    <td>{{ $line->description ?? $entry->description ?? '-' }}</td>
                                    <td class="text-end {{ $line->type === 'debit' ? 'amount-debit' : 'amount-kredit' }}">
                                        {{ number_format(abs($line->amount ?? 0), 2, ',', '.') }}
                                    </td>
                                    <td class="text-end {{ ($line->balance_after ?? 0) >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                        {{ number_format($line->balance_after ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($entry->journal_date ?? now())->format('d/m/Y H:i:s') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            @if(($entry->status ?? 'draft') === 'draft')
                                                <a href="{{ route('transaksi.jurnal.edit', $entry) }}" class="action-btn edit" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" title="Delete" onclick="deleteJournalEntry('{{ $entry->id }}')" style="border: none; cursor: pointer;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-journal-x"></i>
                                        </div>
                                        <div class="empty-state-text">Belum ada data transaksi jurnal</div>
                                        <a href="{{ route('transaksi.jurnal.create') }}" class="btn-tambah">
                                            <i class="bi bi-plus-circle"></i> Tambah Transaksi Jurnal
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
                    <i class="bi bi-file-earmark-pdf me-2"></i>Print PDF Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="printForm" method="GET" action="{{ route('transaksi.jurnal.print-pdf') }}">
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
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export Excel Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="GET" action="{{ route('transaksi.jurnal.export-excel') }}">
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

// Function to handle journal entry deletion
function deleteJournalEntry(journalId) {
    console.log('deleteJournalEntry called with:', journalId);
    
    if (!confirm('Yakin ingin menghapus jurnal ini?')) {
        return;
    }
    
    // Create a hidden form to submit delete request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/transaksi/jurnal/${journalId}`;
    form.style.display = 'none';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    
    const tokenField = document.createElement('input');
    tokenField.type = 'hidden';
    tokenField.name = '_token';
    tokenField.value = csrfToken;
    
    form.appendChild(methodField);
    form.appendChild(tokenField);
    document.body.appendChild(form);
    form.submit();
}

// Global functions for modal actions
function printPdf() {
    console.log('printPdf called');
    const printForm = document.getElementById('printForm');
    if (printForm) {
        printForm.submit();
    }
}

function exportExcel() {
    console.log('exportExcel called');
    const exportForm = document.getElementById('exportForm');
    if (exportForm) {
        exportForm.submit();
    }
}

// Global functions for jurnal filter
function filterJurnalTable() {
    console.log('filterJurnalTable called');
    
    const jurnalSearch = document.getElementById('jurnalSearch');
    const jurnalFromDate = document.getElementById('jurnalFromDate');
    const jurnalToDate = document.getElementById('jurnalToDate');
    const jurnalTable = document.getElementById('jurnalTable');
    
    if (!jurnalTable) return;
    
    const searchTerm = jurnalSearch ? jurnalSearch.value.toLowerCase() : '';
    const fromDate = jurnalFromDate ? jurnalFromDate.value : null;
    const toDate = jurnalToDate ? jurnalToDate.value : null;
    
    const rows = jurnalTable.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        
        const voucherCode = row.cells[0].textContent.toLowerCase();
        const accountCode = row.cells[1].textContent.toLowerCase();
        const project = row.cells[2].textContent.toLowerCase();
        const description = row.cells[3].textContent.toLowerCase();
        const dateText = row.cells[6].textContent.trim();
        
        let show = true;
        
        if (searchTerm && !voucherCode.includes(searchTerm) && 
            !description.includes(searchTerm) && !project.includes(searchTerm) &&
            !accountCode.includes(searchTerm)) {
            show = false;
        }
        
        if (show && (fromDate || toDate)) {
            const dateParts = dateText.split(' ')[0].split('/');
            const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
            
            if (fromDate && rowDate < new Date(fromDate)) show = false;
            if (toDate && rowDate > new Date(toDate)) show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
    
    console.log('Jurnal filter applied - showing results');
}

function setJurnalToday() {
    console.log('setJurnalToday called');
    
    const jurnalFromDate = document.getElementById('jurnalFromDate');
    const jurnalToDate = document.getElementById('jurnalToDate');
    
    if (jurnalFromDate && jurnalToDate) {
        const today = localDateYmd();
        jurnalFromDate.value = today;
        jurnalToDate.value = today;
        filterJurnalTable();
    }
}

function resetJurnalFilter() {
    console.log('resetJurnalFilter called');
    
    const jurnalSearch = document.getElementById('jurnalSearch');
    const jurnalFromDate = document.getElementById('jurnalFromDate');
    const jurnalToDate = document.getElementById('jurnalToDate');
    const jurnalTable = document.getElementById('jurnalTable');
    
    if (jurnalSearch) jurnalSearch.value = '';
    if (jurnalFromDate) jurnalFromDate.value = '';
    if (jurnalToDate) jurnalToDate.value = '';
    
    if (jurnalTable) {
        const rows = jurnalTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });
    }
    
    console.log('Jurnal filter reset');
}

function showAllTab() {
    console.log('showAllTab called');
    // Single tab, no switching needed
    console.log('Already on all jurnal tab');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded - all global functions ready');
});
</script>
@endsection
