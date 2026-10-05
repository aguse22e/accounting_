@extends('layouts.app')

@section('title', 'Budget')

@section('styles')
@vite(['resources/css/pages/management.css'])
<style>
    /* Ensure budget list adjusts properly with sticky sidebar */
    .content-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 1.5rem;
    }
    
    .table-responsive {
        max-height: calc(100vh - 300px);
        overflow-y: auto;
    }
    
    #budgetTable {
        width: 100%;
        margin: 0;
    }
    
    #budgetTable thead {
        position: sticky;
        top: 0;
        background-color: #f8f9fc;
        z-index: 10;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper p-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="bi bi-wallet2 me-2"></i>BUDGET
            </h6>
            <div>
                <button type="button" class="btn btn-danger btn-sm" onclick="exportBudget('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                <button type="button" class="btn btn-success btn-sm" onclick="exportBudget('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" id="budgetSearch" class="form-control" placeholder="Kode/Nama Akun">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Kategori</label>
                    <select id="budgetKategori" class="form-select">
                        <option value="">Semua</option>
                        <option value="aset">Aset</option>
                        <option value="kewajiban">Kewajiban</option>
                        <option value="ekuitas">Ekuitas</option>
                        <option value="pendapatan">Pendapatan</option>
                        <option value="beban">Beban</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Jenis</label>
                    <select id="budgetJenis" class="form-select">
                        <option value="">Semua</option>
                        <option value="debit">Debit</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" id="budgetCariBtn" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <button class="btn btn-outline-secondary flex-fill" id="budgetToday">Hari ini</button>
                    <button class="btn btn-outline-secondary flex-fill" id="budgetReset">Reset</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="budgetTable">
                    <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Jenis Trans</th>
                            <th class="text-end">Budget</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Variance</th>
                        </tr>
                    </thead>
                    <tbody id="budgetTableBody">
                        @forelse($budgetData as $budget)
                            @php
                                $actual = $actualData->where('akun_id', $budget->akun_id)->sum('jumlah');
                                $variance = $budget->jumlah - $actual;
                            @endphp
                            <tr>
                                <td>{{ $budget->akun->kode_akun ?? 'N/A' }}</td>
                                <td>{{ $budget->akun->nama_akun ?? 'N/A' }}</td>
                                <td>{{ $budget->akun->saldo_normal ?? '-' }}</td>
                                <td class="text-end">{{ number_format($budget->jumlah ?? 0, 2) }}</td>
                                <td class="text-end">{{ number_format($actual, 2) }}</td>
                                <td class="text-end {{ $variance < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($variance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
window.ledgerRoutes = {
    exportPdf: '{{ route("ledger.export-pdf") }}',
    exportExcel: '{{ route("ledger.export-excel") }}'
};

document.addEventListener('DOMContentLoaded', function() {
    // Budget filters - same style as Master Akuntansi
    const budgetSearch = document.getElementById('budgetSearch');
    const budgetKategori = document.getElementById('budgetKategori');
    const budgetJenis = document.getElementById('budgetJenis');
    const budgetCariBtn = document.getElementById('budgetCariBtn');
    const budgetReset = document.getElementById('budgetReset');
    const budgetToday = document.getElementById('budgetToday');
    
    function filterBudget(){
        const q = (budgetSearch?.value || '').toLowerCase();
        const kat = (budgetKategori?.value || '').toLowerCase();
        const jenis = (budgetJenis?.value || '').toLowerCase();
        
        const tbody = document.querySelector('#budgetTable tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.forEach(row => {
            const kode = (row.children[0]?.textContent || '').toLowerCase();
            const nama = (row.children[1]?.textContent || '').toLowerCase();
            const cjenis = (row.children[2]?.textContent || '').toLowerCase();
            
            const matchQ = !q || kode.includes(q) || nama.includes(q);
            const matchJenis = !jenis || cjenis === jenis;
            
            row.style.display = (matchQ && matchJenis) ? '' : 'none';
        });
    }
    
    budgetCariBtn?.addEventListener('click', (e) => { 
        e.preventDefault(); 
        filterBudget(); 
    });
    
    budgetReset?.addEventListener('click', (e) => { 
        e.preventDefault(); 
        budgetSearch.value = ''; 
        budgetKategori.value = ''; 
        budgetJenis.value = ''; 
        filterBudget(); 
    });
    
    budgetToday?.addEventListener('click', (e) => { 
        e.preventDefault();
        // Show rows with today date if needed
        filterBudget();
    });
    
    // Export functions
    window.exportBudget = function(type) {
        const from = '{{ $from }}';
        const to = '{{ $to }}';
        if (type === 'pdf') {
            window.open(`{{ route('ledger.export-pdf') }}?from=${from}&to=${to}`, '_blank');
        } else if (type === 'excel') {
            window.open(`{{ route('ledger.export-excel') }}?from=${from}&to=${to}`, '_blank');
        }
    };
});
</script>
@vite(['resources/js/pages/ledger-budget.js'])
@endsection
