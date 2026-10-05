@extends('layouts.app')

@section('title', 'Create Transaksi')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Create Transaksi Baru</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Penting:</strong> Pastikan untuk mengisi <strong>Kode Akun</strong> agar transaksi dapat ditampilkan di posisi keuangan.
            </div>

            <form action="{{ route('transaksi.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <!-- Kolom 1 -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Informasi Transaksi</h5>
                        
                        <div class="mb-3">
                            <label for="akun_kode" class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <select class="form-control" id="akun_kode" name="akun_kode" required>
                                <option value="">Pilih Akun</option>
                                @foreach($akuns as $akun)
                                <option value="{{ $akun->kode_akun }}">{{ $akun->kode_akun }} - {{ $akun->nama_akun }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger">Harus diisi!</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kode_project" class="form-label">Kode Project <span class="text-danger">*</span></label>
                            <select class="form-control" id="kode_project" name="kode_project" required>
                                <option value="">Select Project</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->kode_project }}">{{ $project->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transaksi_type" class="form-label">Transaksi Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="transaksi_type" name="transaksi_type" required>
                                <option value="">Select Type</option>
                                <option value="Debit">Debit</option>
                                <option value="Kredit">Kredit</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Kolom 2 -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Detail Transaksi</h5>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" required maxlength="250" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="nominal" name="nominal" value="0" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-check-circle me-1"></i> Create Transaksi
                        </button>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>

            <hr>

            <h4>Transaksi Kredit</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kode Voucer</th>
                        <th>Kode Akun</th>
                        <th>Kode Project</th>
                        <th>Deskripsi</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                        <th>Saldo</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kreditTransaksis as $transaksi)
                    <tr>
                        <td>{{ $transaksi->kode_voucer }}</td>
                        <td>{{ optional($transaksi->akun)->kode_akun }} - {{ optional($transaksi->akun)->nama_akun }}</td>
                        <td>{{ $transaksi->kode_project }}</td>
                        <td>{{ $transaksi->deskripsi }}</td>
                        <td>0.00</td>
                        <td>{{ number_format($transaksi->nominal ?? 0, 2) }}</td>
                        <td>{{ number_format($transaksi->saldo ?? 0, 2) }}</td>
                        <td>{{ $transaksi->tanggal }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h4>Transaksi Debit</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kode Voucer</th>
                        <th>Kode Akun</th>
                        <th>Kode Project</th>
                        <th>Deskripsi</th>
                        <th>Debit</th>
                        <th>Kredit</th>
                        <th>Saldo</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($debitTransaksis as $transaksi)
                    <tr>
                        <td>{{ $transaksi->kode_voucer }}</td>
                        <td>{{ optional($transaksi->akun)->kode_akun }} - {{ optional($transaksi->akun)->nama_akun }}</td>
                        <td>{{ $transaksi->kode_project }}</td>
                        <td>{{ $transaksi->deskripsi }}</td>
                        <td>{{ number_format($transaksi->nominal ?? 0, 2) }}</td>
                        <td>0.00</td>
                        <td>{{ number_format($transaksi->saldo ?? 0, 2) }}</td>
                        <td>{{ $transaksi->tanggal }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date to today
    const tanggalInput = document.getElementById('tanggal');
    if (!tanggalInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        tanggalInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});
</script>
@endsection
