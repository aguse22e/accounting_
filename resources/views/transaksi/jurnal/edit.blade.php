@extends('layouts.app')

@section('title', 'Edit Jurnal')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Edit Jurnal</h3>
        </div>
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('transaksi.jurnal.update', $journalEntry) }}" method="POST" id="journalEditForm">
                @csrf
                @method('PUT')

                {{-- ─── Baris 1: Kode Voucher + Waktu ─────────────────── --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="journal_number" class="form-label">Kode Voucher</label>
                            <input type="text" class="form-control" id="journal_number"
                                   value="{{ $journalEntry->journal_number }}" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="journal_date" class="form-label">Waktu <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="journal_date" name="journal_date"
                                   value="{{ old('journal_date', $journalEntry->journal_date->format('Y-m-d')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="reference" class="form-label">Referensi</label>
                            <input type="text" class="form-control" id="reference" name="reference"
                                   value="{{ old('reference', $journalEntry->reference ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="kode_project" class="form-label">Kode Project</label>
                            <select class="form-control" id="kode_project" name="kode_project">
                                <option value="">Pilih Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->kode_project }}"
                                        {{ old('kode_project', $journalEntry->kode_project) === $project->kode_project ? 'selected' : '' }}>
                                        {{ $project->display_name ?? ($project->kode_project . ' - ' . $project->nama_project) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" required>{{ old('description', $journalEntry->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ─── Rincian Jurnal (Lines) ─────────────────────────── --}}
                <hr>
                <h6 class="mb-3">Rincian Jurnal</h6>

                @foreach($journalEntry->lines as $idx => $line)
                <div class="row" id="line-row-{{ $idx }}">
                    <input type="hidden" name="lines[{{ $idx }}][id]" value="{{ $line->id }}">

                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                            <select class="form-control" name="lines[{{ $idx }}][account_code]" required>
                                <option value="">Pilih Akun</option>
                                @foreach($akuns as $akun)
                                    <option value="{{ $akun->kode_akun }}"
                                        {{ $line->account_code === $akun->kode_akun ? 'selected' : '' }}>
                                        {{ $akun->kode_akun }} - {{ $akun->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Tipe <span class="text-danger">*</span></label>
                            <select class="form-control" name="lines[{{ $idx }}][type]" required>
                                <option value="debit"  {{ $line->type === 'debit'  ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ $line->type === 'credit' ? 'selected' : '' }}>Kredit</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Nominal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control"
                                   name="lines[{{ $idx }}][amount]"
                                   value="{{ old('lines.'.$idx.'.amount', $line->amount) }}" required>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Saldo</label>
                            <input type="text" class="form-control"
                                   value="{{ $line->balance_after !== null ? number_format($line->balance_after, 0, ',', '.') : '—' }}"
                                   disabled>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <input type="text" class="form-control"
                                   name="lines[{{ $idx }}][description]"
                                   value="{{ old('lines.'.$idx.'.description', $line->description ?? '') }}">
                        </div>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <div class="mb-3">
                            @if($journalEntry->lines->count() > 1)
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="removeLine({{ $idx }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Tambah baris baru --}}
                <div id="extraLines"></div>

                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addLine()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris
                    </button>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-save me-1"></i> Simpan
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

@section('scripts')
<script>
// Daftar akun untuk baris baru
const akunOptions = [
    <option value="">Pilih Akun</option>
    @foreach($akuns as $akun)
    { value: "{{ $akun->kode_akun }}", label: "{{ $akun->kode_akun }} - {{ addslashes($akun->nama) }}" },
    @endforeach
];

let extraLineCount = 0;
const baseLineCount = {{ $journalEntry->lines->count() }};

function addLine() {
    const idx = baseLineCount + extraLineCount;
    extraLineCount++;

    let opts = '<option value="">Pilih Akun</option>';
    akunOptions.forEach(a => {
        opts += `<option value="${a.value}">${a.label}</option>`;
    });

    const html = `
    <div class="row" id="line-row-${idx}">
        <input type="hidden" name="lines[${idx}][id]" value="">
        <div class="col-md-3">
            <div class="mb-3">
                <label class="form-label">Kode Akun <span class="text-danger">*</span></label>
                <select class="form-control" name="lines[${idx}][account_code]" required>
                    ${opts}
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                <select class="form-control" name="lines[${idx}][type]" required>
                    <option value="debit">Debit</option>
                    <option value="credit">Kredit</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label">Nominal <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control"
                       name="lines[${idx}][amount]" value="0" required>
            </div>
        </div>
        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label">Saldo</label>
                <input type="text" class="form-control" value="—" disabled>
            </div>
        </div>
        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <input type="text" class="form-control" name="lines[${idx}][description]" value="">
            </div>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <div class="mb-3">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeLine(${idx})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </div>`;

    document.getElementById('extraLines').insertAdjacentHTML('beforeend', html);
}

function removeLine(idx) {
    const row = document.getElementById('line-row-' + idx);
    if (row) row.remove();
}

document.getElementById('journalEditForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
});
</script>
@endsection
