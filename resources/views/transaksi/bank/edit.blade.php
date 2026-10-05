@extends('layouts.app')

@section('title', 'Edit Transaksi Bank')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Edit Transaksi Bank</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('transaksi.bank.update', $transaksi->kode_transaksi_bank) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="kode_transaksi_bank" class="form-label">Kode Transaksi Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_transaksi_bank" name="kode_transaksi_bank" value="{{ $transaksi->kode_transaksi_bank }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="akun_kode" class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <select class="form-control" id="akun_kode" name="akun_kode" required>
                                <option value="">Pilih Akun</option>
                                @foreach($akuns as $akun)
                                    <option value="{{ $akun->kode_akun }}" {{ $transaksi->akun_kode == $akun->kode_akun ? 'selected' : '' }}>{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                            <select class="form-control" id="tipe" name="tipe" required>
                                <option value="Debit" {{ $transaksi->tipe == 'Debit' ? 'selected' : '' }}>Debit</option>
                                <option value="Kredit" {{ $transaksi->tipe == 'Kredit' ? 'selected' : '' }}>Kredit</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="nominal" name="nominal" value="{{ $transaksi->nominal ?? $transaksi->total }}" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required>{{ $transaksi->deskripsi }}</textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
