@extends('layouts.app')

@section('title','Daftar Aset Tetap')

@section('styles')
@vite(['resources/css/pages/ledger-fixed.css'])
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .content-wrapper, .content-wrapper * {
        visibility: visible;
    }
    .content-wrapper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .btn, .form-control, form {
        display: none !important;
    }
    .card {
        border: none;
        box-shadow: none;
    }
    .table {
        font-size: 10px;
    }
    .table th, .table td {
        padding: 4px;
    }
    @page {
        margin: 1cm;
        size: A4 landscape;
    }
}
</style>
@endsection

@section('content')
<style>
.ledger-content-wrapper {
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding: 1.5rem !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
}
.ledger-card {
    width: 100% !important;
    margin: 0 !important;
    box-sizing: border-box;
}
.ledger-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    overflow-y: auto !important;
    margin: 0 !important;
    box-sizing: border-box;
}
.ledger-table-wrapper table {
    width: 100% !important;
    margin: 0 !important;
    min-width: 100% !important;
}
.content-wrapper {
    padding: 1.5rem;
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box;
}

.card {
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    width: 100% !important;
    margin: 0 !important;
    box-sizing: border-box;
}
</style>
<div class="content-wrapper ledger-content-wrapper">
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <h2 class="fw-bold text-uppercase mb-1" style="letter-spacing:.5px;">Daftar Aset Tetap</h2>
                <div class="fs-5 mb-3">Periode: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} SAMPAI {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</div>
            </div>
            
            <form id="formFixed" class="row g-2 align-items-center mb-3" method="GET" action="{{ route('ledger.fixed') }}">
                <div class="col-auto"><label class="col-form-label">Periode</label></div>
                <div class="col-auto"><input type="date" class="form-control" name="from" id="from" value="{{ $from }}"></div>
                <div class="col-auto"><input type="date" class="form-control" name="to" id="to" value="{{ $to }}"></div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Terapkan
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm" onclick="exportFixed('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportFixed('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
                    </div>
                </div>
            </form>
            
            @if($rows->count() > 0)
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Info:</strong> Menampilkan transaksi aset tetap untuk periode yang dipilih.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Transaksi</th>
                                <th>Kode Akun</th>
                                <th>Nama Akun</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $row->kode_voucer ?? '-' }}</td>
                                    <td><strong>{{ $row->akun_kode }}</strong></td>
                                    <td>{{ $row->akun ? $row->akun->nama_akun : '-' }}</td>
                                    <td>{{ $row->deskripsi ?? '-' }}</td>
                                    <td class="text-end">{{ $row->debit > 0 ? number_format($row->debit, 2, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $row->kredit > 0 ? number_format($row->kredit, 2, ',', '.') : '-' }}</td>
                                    <td class="text-end"><strong>{{ number_format($row->nominal ?? $row->jumlah ?? 0, 2, ',', '.') }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Tidak ada data:</strong> Tidak ada transaksi aset tetap (kode akun 12xx) untuk periode yang dipilih.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.ledgerRoutes = {
    exportPdf: '{{ route("ledger.export-pdf") }}',
    exportExcel: '{{ route("ledger.export-excel") }}'
};
</script>
@vite(['resources/js/pages/ledger-fixed.js'])
@endsection
