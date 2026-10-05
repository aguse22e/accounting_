@extends('layouts.app')

@section('title', 'View Transaksi Bank')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Transaksi Bank Details</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('transaksi.bank.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
                <a href="{{ route('transaksi.bank.edit', $transaksi->kode_transaksi_bank) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Kode Transaksi:</th>
                            <td>{{ $transaksi->kode_transaksi_bank }}</td>
                        </tr>
                        <tr>
                            <th>Akun:</th>
                            <td>{{ $transaksi->akun->kode_akun ?? 'N/A' }} - {{ $transaksi->akun->nama_akun ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Project:</th>
                            <td>{{ $transaksi->project->kode_project ?? 'N/A' }} - {{ $transaksi->project->nama_project ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Tipe Transaksi:</th>
                            <td>
                                <span class="badge {{ $transaksi->tipe == 'Debit' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $transaksi->tipe }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Deskripsi:</th>
                            <td>{{ $transaksi->deskripsi }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Nominal:</th>
                            <td><strong>{{ number_format($transaksi->nominal, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Total:</th>
                            <td><strong>{{ number_format($transaksi->total, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tanggal:</th>
                            <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Dibuat:</th>
                            <td>{{ $transaksi->created_at ? \Carbon\Carbon::parse($transaksi->created_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Diupdate:</th>
                            <td>{{ $transaksi->updated_at ? \Carbon\Carbon::parse($transaksi->updated_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
