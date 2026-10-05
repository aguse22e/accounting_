@extends('layouts.app')

@section('title', 'Edit Journal Line')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Edit Journal Line</h3>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Journal Entry:</strong> {{ $journalEntry->journal_number }} - {{ $journalEntry->description }}
            </div>

            <form action="{{ route('transaksi.jurnal.updateLine', [$journalEntry, $journalLine]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Account <span class="text-danger">*</span></label>
                            <select name="account_code" class="form-select" required>
                                <option value="">Select Account</option>
                                @foreach($akuns as $akun)
                                    <option value="{{ $akun->kode_akun }}" {{ $journalLine->account_code === $akun->kode_akun ? 'selected' : '' }}>
                                        {{ $akun->kode_akun }} - {{ $akun->nama_akun }} ({{ $akun->saldo_normal }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="debit" {{ $journalLine->type === 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ $journalLine->type === 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ $journalLine->amount }}" required min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" value="{{ $journalLine->description ?? '' }}">
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Jurnal harus tetap seimbang (Total Debit = Total Credit). Sistem akan validasi ini sebelum menyimpan.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Update Line
                    </button>
                    <a href="{{ route('transaksi.index', ['tab' => 'jurnal']) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection