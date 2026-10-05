@extends('layouts.app')

@section('title','Statement of Activity')

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
            <form id="formActivity" class="row g-2 align-items-center" method="GET" action="{{ route('ledger.activity') }}">
                <div class="col-auto"><label class="col-form-label">Periode</label></div>
                <div class="col-auto"><input type="date" class="form-control" name="from" value="{{ $from }}"></div>
                <div class="col-auto"><input type="date" class="form-control" name="to" value="{{ $to }}"></div>
                <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                <div class="col-auto ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm" onclick="exportActivity('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportActivity('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="mb-2">Income from Operation</h6>
                    <ul class="mb-3">
                        @foreach($pendapatan as $a)
                            <li>{{ $a->kode_akun }} - {{ $a->nama_akun }}</li>
                        @endforeach
                    </ul>
                    <div class="fw-semibold">Total Income: <span class="text-success">{{ number_format($incomeTotal ?? 0, 2, ',', '.') }}</span></div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-2">Operating Expenses</h6>
                    <ul class="mb-3">
                        @foreach($beban as $a)
                            <li>{{ $a->kode_akun }} - {{ $a->nama_akun }}</li>
                        @endforeach
                    </ul>
                    <div class="fw-semibold">Total Expenses: <span class="text-danger">{{ number_format($expenseTotal ?? 0, 2, ',', '.') }}</span></div>
                </div>
            </div>

            <hr>
            <div class="fs-5">
                <span class="fw-bold">Net Profit/Loss</span> =
                <span class="text-success">Income ({{ number_format($incomeTotal ?? 0, 2, ',', '.') }})</span>
                −
                <span class="text-danger">Expenses ({{ number_format($expenseTotal ?? 0, 2, ',', '.') }})</span>
                =
                <span class="fw-bold {{ ($netProfit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netProfit ?? 0, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
<script>
function exportActivity(type){
    const form = document.getElementById('formActivity');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? "{{ route('ledger.export-pdf') }}" : "{{ route('ledger.export-excel') }}";
    params.append('menu', 'Aktivitas');
    window.location = url + '?' + params.toString();
}
</script>
@endsection


