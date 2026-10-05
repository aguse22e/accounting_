@extends('layouts.app')

@section('title', 'Transaksi')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Transaksi List</h3>
            <div>
                <a href="{{ route('transaksi.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add New Transaksi
                </a>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" id="transaksiTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                        <i class="bi bi-journal-text me-1"></i> All
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab" aria-controls="bank" aria-selected="false">
                        <i class="bi bi-bank me-1"></i> Transaksi Bank
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="jurnal-tab" data-bs-toggle="tab" data-bs-target="#jurnal" type="button" role="tab" aria-controls="jurnal" aria-selected="false">
                        <i class="bi bi-journal-bookmark me-1"></i> Jurnal
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="transaksiTabsContent">
                <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kode Voucer</th>
                                <th>Kode Budget</th>
                                <th>Kode Project</th>
                                <th>Transaksi Type</th>
                                <th>Deskripsi</th>
                                <th>Nominal</th>
                                <th>Budget</th>
                                <th>Actual</th>
                                <th>Waktu</th>
                                <th>Create At</th>
                                <th>Update At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksis as $transaksi)
                            <tr>
                                <td>{{ $transaksi->kode_voucer }}</td>
                                <td>{{ $transaksi->kode_budget }}</td>
                                <td>{{ $transaksi->kode_project }}</td>
                                <td>{{ $transaksi->transaksi_type }}</td>
                                <td>{{ $transaksi->deskripsi }}</td>
                                <td>{{ number_format($transaksi->nominal, 2) }}</td>
                                <td>{{ number_format($transaksi->budget, 2) }}</td>
                                <td>{{ number_format($transaksi->actual, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $transaksi->create_at }}</td>
                                <td>{{ $transaksi->update_at }}</td>
                                <td>
                                    <a href="{{ route('transaksi.edit', $transaksi->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('transaksi.destroy', $transaksi->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transaksi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                    <a href="{{ route('transaksi.bank.create') }}" class="btn btn-primary mb-3">
                        <i class="bi bi-plus-circle me-1"></i> Add New Transaksi Bank
                    </a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kode Voucer</th>
                                <th>Kode Budget</th>
                                <th>Kode Project</th>
                                <th>Deskripsi</th>
                                <th>Nominal</th>
                                <th>Waktu</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksis->where('transaksi_type', 1) as $transaksi)
                            <tr>
                                <td>{{ $transaksi->kode_voucer }}</td>
                                <td>{{ $transaksi->kode_budget }}</td>
                                <td>{{ $transaksi->kode_project }}</td>
                                <td>{{ $transaksi->deskripsi }}</td>
                                <td>{{ number_format($transaksi->nominal, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <a href="{{ route('transaksi.bank.edit', $transaksi->kode_transaksi_bank) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('transaksi.bank.destroy', $transaksi->kode_transaksi_bank) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transaksi bank?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="jurnal" role="tabpanel" aria-labelledby="jurnal-tab">
                    <a href="{{ route('transaksi.jurnal.create') }}" class="btn btn-primary mb-3">
                        <i class="bi bi-plus-circle me-1"></i> Add New Jurnal
                    </a>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Kode Voucer</th>
                                <th>Kode Budget</th>
                                <th>Kode Project</th>
                                <th>Deskripsi</th>
                                <th>Nominal</th>
                                <th>Waktu</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksis->where('transaksi_type', 2) as $transaksi)
                            <tr>
                                <td>{{ $transaksi->kode_voucer }}</td>
                                <td>{{ $transaksi->kode_budget }}</td>
                                <td>{{ $transaksi->kode_project }}</td>
                                <td>{{ $transaksi->deskripsi }}</td>
                                <td>{{ number_format($transaksi->nominal, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <a href="{{ route('transaksi.jurnal.edit', $transaksi->kode_voucer) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('transaksi.jurnal.destroy', $transaksi->kode_voucer) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this jurnal?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
