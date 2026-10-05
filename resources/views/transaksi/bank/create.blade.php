@extends('layouts.app')

@section('title', 'Tambah Transaksi Bank')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-bank me-2"></i>Tambah Transaksi Bank</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="bi bi-exclamation-triangle me-2"></i>Error:</strong> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('transaksi.bank.store') }}" method="POST" id="bankTransactionForm">
                @csrf

                <!-- Bank Transaction Header Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="kode_transaksi_bank" class="form-label">Kode Transaksi Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_transaksi_bank" name="kode_transaksi_bank" placeholder="Nomor bukti transaksi" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="akun_kode" class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <select class="form-select" id="akun_kode" name="akun_kode" required>
                                <option value="">Pilih Akun</option>
                                @foreach($akuns as $akun)
                                    <option value="{{ $akun->kode_akun }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipe" name="tipe" required>
                                <option value="Debit">Debit</option>
                                <option value="Kredit" selected>Kredit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="nominal" name="nominal" value="0" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="kode_project" class="form-label">Kode Project</label>
                            <select class="form-select" id="kode_project" name="kode_project">
                                <option value="">-- Pilih Project (Opsional) --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->kode_project }}">{{ $project->kode_project }} - {{ $project->nama_project }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details Section -->
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="2" placeholder="Masukkan deskripsi transaksi..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to today
    const tanggalInput = document.getElementById('tanggal');
    if (tanggalInput && !tanggalInput.value) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        tanggalInput.value = `${year}-${month}-${day}`;
    }
});
</script>
@endsection
