@extends('layouts.app')

@section('title', 'Edit Transaksi')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Edit Transaksi - {{ $transaksi->kode_voucer }}</h3>
        </div>
        <div class="card-body">
            <!-- Data yang Disimpan -->
            <div class="alert alert-info mb-4">
                <h5><i class="bi bi-info-circle me-2"></i>Data yang Disimpan</h5>
                <div class="row mt-3">
                    <div class="col-md-3"><strong>Kode Voucer:</strong></div>
                    <div class="col-md-3">{{ $transaksi->kode_voucer ?? 'N/A' }}</div>
                    <div class="col-md-3"><strong>Kode Akun:</strong></div>
                    <div class="col-md-3">{{ $transaksi->akun_kode ?? 'N/A' }}</div>
                    <div class="col-md-3"><strong>Kode Project:</strong></div>
                    <div class="col-md-3">{{ $transaksi->kode_project ?? 'N/A' }}</div>
                    <div class="col-md-3"><strong>Transaksi Type:</strong></div>
                    <div class="col-md-3">
                        <span class="badge {{ $transaksi->transaksi_type == 'Debit' ? 'bg-success' : 'bg-danger' }}">
                            {{ $transaksi->transaksi_type ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="col-md-3"><strong>Nominal:</strong></div>
                    <div class="col-md-3">Rp {{ number_format($transaksi->nominal ?? 0, 2, ',', '.') }}</div>
                    <div class="col-md-3"><strong>Saldo:</strong></div>
                    <div class="col-md-3">Rp {{ number_format($transaksi->saldo ?? 0, 2, ',', '.') }}</div>
                    <div class="col-md-3"><strong>Deskripsi:</strong></div>
                    <div class="col-md-9">{{ $transaksi->deskripsi ?? 'N/A' }}</div>
                    <div class="col-md-3"><strong>Tanggal:</strong></div>
                    <div class="col-md-9">{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y H:i:s') }}</div>
                </div>
            </div>

            <form action="{{ route('transaksi.update', $transaksi->kode_voucer) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Kolom 1 -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Informasi Transaksi</h5>
                        
                        <div class="mb-3">
                            <label for="kode_voucer" class="form-label">Kode Voucer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_voucer" name="kode_voucer" value="{{ $transaksi->kode_voucer }}" required>
                            <small class="form-text text-muted">Kode Voucer dapat diubah</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="akun_kode" class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <select class="form-control" id="akun_kode" name="akun_kode" required>
                                <option value="">Pilih Akun</option>
                                @foreach($akuns as $akun)
                                <option value="{{ $akun->kode_akun }}" {{ $transaksi->akun_kode == $akun->kode_akun ? 'selected' : '' }}>
                                    {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kode_project" class="form-label">Kode Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="kode_project" name="kode_project" required>
                                <option value="">Select Project</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->kode_project }}" {{ $transaksi->kode_project == $project->kode_project ? 'selected' : '' }}>
                                    {{ $project->display_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transaksi_type" class="form-label">Transaksi Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="transaksi_type" name="transaksi_type" required>
                                <option value="">Select Type</option>
                                <option value="Debit" {{ $transaksi->transaksi_type == 'Debit' ? 'selected' : '' }}>Debit</option>
                                <option value="Kredit" {{ $transaksi->transaksi_type == 'Kredit' ? 'selected' : '' }}>Kredit</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Kolom 2 -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Detail Transaksi</h5>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" required maxlength="250" rows="2">{{ $transaksi->deskripsi }}</textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="nominal" name="nominal" value="{{ number_format($transaksi->nominal ?? 0, 2, '.', '') }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-check-circle me-1"></i> Save
                        </button>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Format nominal input to use dots instead of commas
    const nominalInput = document.getElementById('nominal');
    nominalInput.addEventListener('blur', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.value = value.toFixed(2);
        }
    });
});
</script>
@endsection
