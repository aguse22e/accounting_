@extends('layouts.app')

@section('title', 'View Transaksi')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>View Transaksi</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Kode Voucer</dt>
                <dd class="col-sm-9">{{ $transaksi->kode_voucer }}</dd>

                <dt class="col-sm-3">Kode Budget</dt>
                <dd class="col-sm-9">{{ $transaksi->kode_budget }}</dd>

                <dt class="col-sm-3">Kode Project</dt>
                <dd class="col-sm-9">{{ $transaksi->kode_project }}</dd>

                <dt class="col-sm-3">Transaksi Type</dt>
                <dd class="col-sm-9">{{ $transaksi->transaksi_type }}</dd>

                <dt class="col-sm-3">Deskripsi</dt>
                <dd class="col-sm-9">{{ $transaksi->deskripsi }}</dd>


                <dt class="col-sm-3">Debit</dt>
                <dd class="col-sm-9">{{ number_format($transaksi->debit ?? 0, 2) }}</dd>

                <dt class="col-sm-3">Kredit</dt>
                <dd class="col-sm-9">{{ number_format($transaksi->kredit ?? 0, 2) }}</dd>

                <dt class="col-sm-3">Saldo</dt>
                <dd class="col-sm-9">{{ number_format($transaksi->saldo ?? 0, 2) }}</dd>

                <dt class="col-sm-3">Budget</dt>
                <dd class="col-sm-9">{{ number_format($transaksi->budget, 2) }}</dd>

                <dt class="col-sm-3">Actual</dt>
                <dd class="col-sm-9">{{ number_format($transaksi->actual, 2) }}</dd>

                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $transaksi->tanggal }}</dd>

                <dt class="col-sm-3">Create At</dt>
                <dd class="col-sm-9">{{ $transaksi->create_at }}</dd>

                <dt class="col-sm-3">Update At</dt>
                <dd class="col-sm-9">{{ $transaksi->update_at }}</dd>
            </dl>
            <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
@endsection
