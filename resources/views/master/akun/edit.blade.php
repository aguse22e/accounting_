@extends('layouts.app')

@section('title', 'Edit Master Akun')

@section('styles')
@vite(['resources/css/pages/management.css'])
@endsection

@section('content')
<div class="content-wrapper p-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="bi bi-pencil me-2"></i>Edit Master Akun
            </h6>
        </div>
        <div class="card-body">
            <form id="akunEditForm" novalidate>
                <input type="hidden" id="akun_id" name="id" value="{{ $id }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kode_akun" name="kode_akun" required pattern="^[0-9]{1,15}$" maxlength="15" inputmode="numeric">
                        <div class="form-text">Hanya angka, maksimal 15 digit, unik.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_akun" name="nama_akun" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                        <select class="form-control" id="saldo_normal" name="saldo_normal" required>
                            <option value="Debit">Debit</option>
                            <option value="Kredit">Kredit</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal_akun" name="tanggal">
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-3" id="akunError" style="display: none !important;"></div>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="saveAkunBtn">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                    <a href="{{ route('master.akun') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const akunId = document.getElementById('akun_id').value;
    const saveAkunBtn = document.getElementById('saveAkunBtn');
    const akunForm = document.getElementById('akunEditForm');
    const akunErr = document.getElementById('akunError');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Ensure error is hidden on page load
    if (akunErr) {
        akunErr.style.display = 'none';
        akunErr.classList.add('d-none');
    }
    
    // Load akun data
    async function loadAkunData() {
        try {
            // Show loading state
            saveAkunBtn.disabled = true;
            saveAkunBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memuat data...';
            
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            const r = await fetch(`/api/akun`, { 
                headers,
                credentials: 'same-origin'
            });
            if (!r.ok) throw new Error('Gagal memuat data');
            const data = await r.json();
            // Handle both array and object response
            const akunList = Array.isArray(data) ? data : (data.data || []);
            const akun = akunList.find(a => a.id == akunId || a.id == parseInt(akunId));
            
            if (!akun) {
                akunErr.textContent = 'Akun tidak ditemukan';
                akunErr.style.display = 'block';
                akunErr.classList.remove('d-none');
                return;
            }
            
            // Fill form
            document.getElementById('kode_akun').value = akun.kode_akun || '';
            document.getElementById('nama_akun').value = akun.nama || '';
            document.getElementById('saldo_normal').value = akun.tipe || 'Debit';
            const tanggal = akun.tanggal || akun.created_at || '';
            if (tanggal) {
                const d = new Date(tanggal);
                if (!isNaN(d.getTime())) {
                    const pad = (n) => String(n).padStart(2, '0');
                    document.getElementById('tanggal_akun').value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
                }
            }
            
            // Reset button state
            saveAkunBtn.disabled = false;
            saveAkunBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        } catch(e) {
            console.error('Error loading akun:', e);
            akunErr.textContent = 'Gagal memuat data akun: ' + (e.message || 'Terjadi kesalahan');
            akunErr.style.display = 'block';
            akunErr.classList.remove('d-none');
            // Reset button state
            saveAkunBtn.disabled = false;
            saveAkunBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        }
    }
    
    // Save handler
    saveAkunBtn.addEventListener('click', async function(e){
        e.preventDefault();
        e.stopPropagation();
        // Hide error message first
        akunErr.style.display = 'none';
        akunErr.classList.add('d-none');
        
        const kodeAkunInput = document.getElementById('kode_akun');
        const namaAkunInput = document.getElementById('nama_akun');
        const saldoNormalInput = document.getElementById('saldo_normal');
        
        if (!kodeAkunInput || !namaAkunInput || !saldoNormalInput) {
            akunErr.textContent = 'Form input tidak ditemukan';
            akunErr.classList.remove('d-none');
            return;
        }
        
        const kodeAkunValue = kodeAkunInput.value.trim();
        const namaAkunValue = namaAkunInput.value.trim();
        const saldoNormalValue = saldoNormalInput.value.trim();
        
        // Only show error if fields are truly empty (not just whitespace)
        if (!kodeAkunValue || !namaAkunValue || !saldoNormalValue) {
            const errors = [];
            if (!kodeAkunValue) errors.push('Nomor Akun wajib diisi.');
            if (!namaAkunValue) errors.push('Nama Akun wajib diisi.');
            if (!saldoNormalValue) errors.push('Jenis Transaksi wajib diisi.');
            akunErr.textContent = errors.join(' ');
            akunErr.style.display = 'block';
            akunErr.classList.remove('d-none');
            return;
        }
        
        // Prepare data as JSON (more reliable for PUT requests)
        const requestData = {
            kode_akun: kodeAkunValue,
            nama: namaAkunValue,
            tipe: saldoNormalValue
        };
        
        const tanggalInput = document.getElementById('tanggal_akun');
        if (tanggalInput && tanggalInput.value) {
            requestData.tanggal = tanggalInput.value;
        }
        
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        saveAkunBtn.disabled = true;
        const old = saveAkunBtn.innerHTML;
        saveAkunBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        
        try {
            // Use PUT method with JSON
            const res = await fetch(`/api/akun/${akunId}`, { 
                method: 'PUT',
                headers, 
                body: JSON.stringify(requestData),
                credentials: 'same-origin'
            });
            
            const j = await res.json().catch(() => ({ success: false, message: 'Gagal memproses respons' }));
            
            if (!res.ok || (j && j.success === false)) {
                // Only show error if there are actual validation errors
                if (j && j.errors) {
                    const errorMessages = [];
                    Object.keys(j.errors).forEach(field => {
                        const fieldErrors = Array.isArray(j.errors[field]) ? j.errors[field] : [j.errors[field]];
                        fieldErrors.forEach(err => {
                            if (err && typeof err === 'string') {
                                // Use custom error messages from server
                                errorMessages.push(err);
                            }
                        });
                    });
                    // Only show error if there are actual errors
                    if (errorMessages.length > 0) {
                        throw new Error(errorMessages.join(' '));
                    }
                }
                // Handle other errors
                let errorMsg = j.message || 'Gagal menyimpan akun';
                throw new Error(errorMsg);
            }
            
            // Success - redirect back
            if (j && j.success === true) {
                window.location.href = '{{ route("master.akun") }}';
                return;
            }
        } catch(e) {
            // Show error message
            akunErr.textContent = e.message || 'Terjadi kesalahan saat menyimpan data';
            akunErr.style.display = 'block';
            akunErr.classList.remove('d-none');
        } finally {
            saveAkunBtn.disabled = false;
            saveAkunBtn.innerHTML = old;
        }
    });
    
    // Prevent form default submit
    akunForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });
    
    // Load data on page load
    loadAkunData();
});
</script>
@endsection

