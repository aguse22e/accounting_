@extends('layouts.app')

@section('title', 'Ledger & Reports')

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
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark-text me-1"></i> Laporan Keuangan - Organisasi Perempuan Mahardika
            </h5>
            
        </div>
        <div class="card-body">
            <!-- Chip buttons moved to sidebar under Ledger menu -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Neraca</h6>
                <div class="d-flex align-items-center gap-2">
                    @php $isManager = auth()->check() && auth()->user()->role === 'manager'; @endphp
                    @if(!$isManager)
                    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-upload me-1"></i> Import File
                    </button>
                    @endif
                    <a href="{{ route('ledger.export-excel') }}?from={{ $tanggal_dari }}&to={{ $tanggal_sampai }}&akun_id={{ $filter_akun }}&menu=Laporan%20Keuangan" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                    </a>
                </div>
            </div>
            
            
            <form action="{{ route('ledger.index') }}" method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">Dari</span>
                        <input type="date" class="form-control" name="tanggal_dari" id="tanggal_dari" value="{{ $tanggal_dari }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">Sampai</span>
                        <input type="date" class="form-control" name="tanggal_sampai" id="tanggal_sampai" value="{{ $tanggal_sampai }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="akun_id">
                        <option value="semua" {{ $filter_akun == 'semua' ? 'selected' : '' }}>Semua Akun</option>
                        @foreach($akun_list as $akun)
                            <option value="{{ $akun->id }}" {{ $filter_akun == $akun->id ? 'selected' : '' }}>
                                {{ $akun->nama_akun }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Terapkan
                        </button>
                    </div>
                </div>
            </form>
            
            @if(session('imported_data') && count(session('imported_data')) > 0)
            <div class="alert alert-info mb-3">
                <h6><i class="bi bi-info-circle me-2"></i>Data yang Baru Diimport:</h6>
                <small>Total {{ count(session('imported_data')) }} transaksi berhasil diimport.</small>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th class="text-end">Saldo Debit</th>
                            <th class="text-end">Saldo Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($akun_data as $akun)
                            <tr>
                                <td>{{ $akun->kode_akun }}</td>
                                <td>{{ $akun->nama_akun }}</td>
                                <td class="text-end">{{ number_format($akun->getSaldoDebit($tanggal_dari, $tanggal_sampai), 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($akun->getSaldoKredit($tanggal_dari, $tanggal_sampai), 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('ledger.export-pdf') }}?from={{ $tanggal_dari }}&to={{ $tanggal_sampai }}&akun_id={{ $filter_akun }}&menu=Laporan%20Keuangan" class="btn btn-danger me-2">
                    <i class="bi bi-file-pdf me-1"></i> PDF
                </a>
                <a href="{{ route('ledger.export-excel') }}?from={{ $tanggal_dari }}&to={{ $tanggal_sampai }}&akun_id={{ $filter_akun }}&menu=Laporan%20Keuangan" class="btn btn-success">
                    <i class="bi bi-file-excel me-1"></i> Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('ledger.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File (.xlsx, .csv)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
</script>
@endsection