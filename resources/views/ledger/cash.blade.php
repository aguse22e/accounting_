@extends('layouts.app')

@section('title','Statement of Cash Flows')

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
</style>
<div class="content-wrapper ledger-content-wrapper">
    <div class="card">
        <div class="card-header">
            <form id="formCash" class="row g-2 align-items-center" method="GET" action="{{ route('ledger.cash') }}">
                <div class="col-auto"><label class="col-form-label">Periode</label></div>
                <div class="col-auto"><input type="date" class="form-control" name="from" value="{{ $from }}"></div>
                <div class="col-auto"><input type="date" class="form-control" name="to" value="{{ $to }}"></div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                <div class="col-auto ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm" onclick="exportCash('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportCash('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6>Operating Activities</h6>
                    <div class="fs-5 {{ ($operating ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($operating ?? 0, 2, ',', '.') }}</div>
                    <small class="text-muted">Contoh: penerimaan kas dari penjualan, pembayaran kas biaya operasional.</small>
                </div>
                <div class="col-md-6">
                    <h6>Investing Activities</h6>
                    <div class="fs-5 {{ ($investing ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($investing ?? 0, 2, ',', '.') }}</div>
                    <small class="text-muted">Contoh: beli aset tetap (keluar), jual investasi (masuk).</small>
                </div>
                <div class="col-md-6">
                    <h6 class="mt-3">Financing Activities</h6>
                    <div class="fs-5 {{ ($financing ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($financing ?? 0, 2, ',', '.') }}</div>
                    <small class="text-muted">Contoh: terima pinjaman (masuk), bayar dividen (keluar).</small>
                </div>
            </div>

            <hr>
            <div class="mb-2">Net Cash Flow = Operating + Investing + Financing</div>
            <div class="fs-5 mb-3 fw-bold {{ ($netCashFlow ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netCashFlow ?? 0, 2, ',', '.') }}</div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="fw-semibold">Saldo Kas Awal</div>
                    <div class="fs-6">{{ number_format($cashBeginning ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fw-semibold">Net Cash Flow</div>
                    <div class="fs-6 {{ ($netCashFlow ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netCashFlow ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fw-semibold">Saldo Kas Akhir</div>
                    <div class="fs-5 fw-bold">{{ number_format($cashEnding ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function exportCash(type){
    const form = document.getElementById('formCash');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? "{{ route('ledger.export-pdf') }}" : "{{ route('ledger.export-excel') }}";
    params.append('menu', 'Arus Kas');
    window.location = url + '?' + params.toString();
}
</script>
@endsection


