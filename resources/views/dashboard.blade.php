@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="content-wrapper p-4">
    <!-- Header Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1 text-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </h2>
                            <p class="text-muted mb-0">Selamat datang di Sistem Akuntansi Organisasi Perempuan Mahardika</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Tanggal: <span id="current-date">{{ date('d F Y') }}</span></small>
                            <br>
                            <small class="text-muted">Waktu: <span id="current-time">{{ date('H:i:s') }}</span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Cards Real-time -->
    <div class="row mb-4" id="stats-cards">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Saldo Akhir Bank
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="saldo-akhir-bank">
                                Rp {{ number_format($saldo_akhir_bank ?? 0, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-bank fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Transaksi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-transaksi">
                                {{ \App\Models\Transaksi::count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-arrow-left-right fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Transaksi Hari Ini
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="transaksi-hari-ini">
                                {{ \App\Models\Transaksi::whereDate('tanggal', today())->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Net/Loss
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="net-loss">
                                Rp {{ number_format($net_loss ?? 0, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up-arrow fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Real-time -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-graph-up me-2"></i>Grafik Transaksi 7 Hari Terakhir
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-primary active">Line Chart</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="transaksiChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Real-time -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i>Transaksi Terbaru
                    </h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTransactions()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
                <div class="card-body" id="transactions-container">
                    @if(\App\Models\Transaksi::count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Akun</th>
                                        <th>Deskripsi</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-table">
                                    @foreach(\App\Models\Transaksi::with('akun')->latest()->take(5)->get() as $transaksi)
                                    <tr>
                                        <td>{{ $transaksi->tanggal instanceof \Carbon\Carbon ? $transaksi->tanggal->format('d/m/Y H:i:s') : \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $transaksi->akun->nama_akun ?? 'N/A' }}</td>
                                        <td>{{ $transaksi->deskripsi ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $transaksi->transaksi_type == 'Debit' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $transaksi->transaksi_type }}
                                            </span>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada transaksi</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@section('styles')
@vite(['resources/css/pages/dashboard.css'])
@endsection

@section('scripts')
@vite(['resources/js/pages/dashboard.js'])
@endsection
@endsection