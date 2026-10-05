@extends('layouts.app')

@section('title','Trial Balance')

@section('styles')
@vite(['resources/css/pages/ledger-trial.css'])
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
</style>
<div class="content-wrapper ledger-content-wrapper">
    <div class="card">
        <div class="card-header">
            <form id="formTrial" class="row g-2 align-items-center" method="GET" action="{{ route('ledger.trial') }}">
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
                        <button type="button" class="btn btn-danger btn-sm" onclick="exportLedger('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportLedger('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h2 class="fw-bold text-uppercase mb-1" style="letter-spacing:.5px;">Neraca Saldo (Trial Balance)</h2>
                <div class="fs-5">Periode: {{ \Carbon\Carbon::parse($from)->format('d F Y') }} SAMPAI {{ \Carbon\Carbon::parse($to)->format('d F Y') }}</div>
            </div>
            @if(isset($totalDebit) && isset($totalKredit))
                @if(abs($totalDebit - $totalKredit) > 0.01)
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Peringatan:</strong> Total Debit ({{ number_format($totalDebit, 2, ',', '.') }}) tidak sama dengan Total Kredit ({{ number_format($totalKredit, 2, ',', '.') }}).
                        Selisih: {{ number_format(abs($totalDebit - $totalKredit), 2, ',', '.') }}
                    </div>
                @else
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Seimbang:</strong> Total Debit dan Kredit sudah seimbang.
                    </div>
                @endif
            @endif
            <div class="table-responsive ledger-table-wrapper">
            <table class="table table-bordered table-sm">
                <thead style="background:#fdebd3;">
                    <tr>
                        <th>No. Akun</th>
                        <th>Nama Akun</th>
                        <th class="text-end">Saldo Awal Debit</th>
                        <th class="text-end">Saldo Awal Kredit</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Saldo Akhir Debit</th>
                        <th class="text-end">Saldo Akhir Kredit</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($akun as $a)
                    @php
                        $saldo = ($a->netBalance ?? 0);
                        $saldoClass = $saldo >= 0 ? 'text-success' : 'text-danger';
                    @endphp
                    <tr>
                        <td>{{ $a->kode_akun }}</td>
                        <td>{{ $a->nama_akun }}</td>
                        <td class="text-end">{{ number_format($a->opDebit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a->opKredit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a->chDebit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a->chKredit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a->endingDebit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a->endingKredit ?? 0, 2, ',', '.') }}</td>
                        <td class="text-end {{ $saldoClass }}">
                            {{ number_format($saldo, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2">TOTAL</th>
                        <th class="text-end">{{ number_format($akun->sum('opDebit'), 2, ',', '.') }}</th>
                        <th class="text-end">{{ number_format($akun->sum('opKredit'), 2, ',', '.') }}</th>
                        <th class="text-end">{{ number_format($akun->sum('chDebit'), 2, ',', '.') }}</th>
                        <th class="text-end">{{ number_format($akun->sum('chKredit'), 2, ',', '.') }}</th>
                        <th class="text-end">{{ number_format($akun->sum('endingDebit'), 2, ',', '.') }}</th>
                        <th class="text-end">{{ number_format($akun->sum('endingKredit'), 2, ',', '.') }}</th>
                        <th class="text-end">
                            @php
                                $totalSaldo = $akun->reduce(function($carry, $a) { return $carry + ($a->netBalance ?? 0); }, 0);
                                $totalSaldoClass = $totalSaldo >= 0 ? 'text-success' : 'text-danger';
                            @endphp
                            <span class="{{ $totalSaldoClass }}">
                                {{ number_format($totalSaldo, 2, ',', '.') }}
                            </span>
                        </th>
                    </tr>
                </tfoot>
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

    function exportLedger(type){
        const form = document.getElementById('formTrial');
        if (!form) {
            alert('Form not found');
            return;
        }
        const params = new URLSearchParams(new FormData(form));
        const url = type==='pdf' ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
        params.append('menu', 'Neraca Saldo');
        window.location = url + '?' + params.toString();
    }
</script>
@endsection
@endsection

