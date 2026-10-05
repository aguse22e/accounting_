@extends('layouts.app')

@section('title', 'Edit Master Project')

@section('styles')
@vite(['resources/css/pages/management.css'])
@endsection

@section('content')
<div class="content-wrapper p-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="bi bi-pencil me-2"></i>Edit Master Project
            </h6>
        </div>
        <div class="card-body">
            <form id="projectEditForm" novalidate>
                <input type="hidden" id="project_id" name="id">
                <input type="hidden" id="project_original_kode" name="original_kode" value="{{ $kode }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Kode Project <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kode_project" name="kode_project" required pattern="[A-Za-z0-9\- ]+" placeholder="Misal: PRJ001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Project <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_project" name="nama_project" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal & Waktu</label>
                        <input type="datetime-local" class="form-control" id="tanggal" name="tanggal">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Kode Akun (Opsional)</label>
                        <input class="form-control" id="project_kode_akun" name="kode_akun" list="akun_datalist" placeholder="Ketik kode atau nama akun">
                        <datalist id="akun_datalist"></datalist>
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-3" id="projectError" style="display: none !important;"></div>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="saveProjectBtn">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                    <a href="{{ route('master.project') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectKode = '{{ $kode }}';
    const saveProjectBtn = document.getElementById('saveProjectBtn');
    const projectForm = document.getElementById('projectEditForm');
    const projectErr = document.getElementById('projectError');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Ensure error is hidden on page load
    if (projectErr) {
        projectErr.style.display = 'none';
        projectErr.classList.add('d-none');
    }
    
    // Load project data
    async function loadProjectData() {
        try {
            // Show loading state
            saveProjectBtn.disabled = true;
            saveProjectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memuat data...';
            
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            const r = await fetch(`/api/project`, { 
                headers,
                credentials: 'same-origin'
            });
            if (!r.ok) throw new Error('Gagal memuat data');
            const data = await r.json();
            // Handle both array and object response
            const projectList = Array.isArray(data) ? data : (data.data || []);
            const project = projectList.find(p => {
                const kode = p.kode_project || p.Kode_project || '';
                return kode === projectKode || kode === decodeURIComponent(projectKode);
            });
            
            // Load akun list for datalist
            try {
                const akunRes = await fetch(`/api/management-data`, { 
                    headers,
                    credentials: 'same-origin'
                });
                const akunData = await akunRes.json();
                const dl = document.getElementById('akun_datalist');
                if (dl && akunData.akun_data) {
                    dl.innerHTML = akunData.akun_data.map(a => 
                        `<option value="${a.kode_akun}">${a.nama_akun}</option>`
                    ).join('');
                }
            } catch(e) {
                console.error('Error loading akun list:', e);
            }
            
            if (!project) {
                projectErr.textContent = 'Project tidak ditemukan';
                projectErr.style.display = 'block';
                projectErr.classList.remove('d-none');
                return;
            }
            
            // Fill form
            if (project) {
                document.getElementById('project_id').value = project.id || '';
                const projectKode = project.kode_project || project.Kode_project || '';
                document.getElementById('project_original_kode').value = projectKode;
                document.getElementById('kode_project').value = projectKode;
                document.getElementById('nama_project').value = project.nama_project || project.Nama_Project || '';
                
                const tanggal = project.tanggal || project.Tanggal_Mulai || '';
                if (tanggal) {
                    const d = new Date(tanggal);
                    if (!isNaN(d.getTime())) {
                        const pad = (n) => String(n).padStart(2, '0');
                        // Format untuk datetime-local input: YYYY-MM-DDTHH:MM
                        const dateTimeValue = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                        document.getElementById('tanggal').value = dateTimeValue;
                    }
                } else {
                    // Jika tidak ada tanggal, isi dengan waktu saat ini
                    const now = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    const dateTimeValue = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
                    document.getElementById('tanggal').value = dateTimeValue;
                }
                
                document.getElementById('project_kode_akun').value = project.kode_akun || '';
            } else {
                throw new Error('Project tidak ditemukan');
            }
            
            // Reset button state
            saveProjectBtn.disabled = false;
            saveProjectBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        } catch(e) {
            console.error('Error loading project:', e);
            projectErr.textContent = 'Gagal memuat data project: ' + (e.message || 'Terjadi kesalahan');
            projectErr.style.display = 'block';
            projectErr.classList.remove('d-none');
            // Reset button state
            saveProjectBtn.disabled = false;
            saveProjectBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        }
    }
    
    // Save handler
    saveProjectBtn.addEventListener('click', async function(e){
        e.preventDefault();
        e.stopPropagation();
        // Hide error message first
        projectErr.style.display = 'none';
        projectErr.classList.add('d-none');
        
        const id = document.getElementById('project_id').value;
        const originalKode = document.getElementById('project_original_kode').value;
        
        const kodeProjectInput = document.getElementById('kode_project');
        const namaProjectInput = document.getElementById('nama_project');
        
        if (!kodeProjectInput || !namaProjectInput) {
            projectErr.textContent = 'Form input tidak ditemukan';
            projectErr.classList.remove('d-none');
            return;
        }
        
        const kodeProjectValue = kodeProjectInput.value.trim();
        const namaProjectValue = namaProjectInput.value.trim();
        
        // Only validate required fields - field opsional tidak perlu divalidasi
        if (!kodeProjectValue || !namaProjectValue) {
            const errors = [];
            if (!kodeProjectValue) errors.push('Kode Project wajib diisi.');
            if (!namaProjectValue) errors.push('Nama Project wajib diisi.');
            projectErr.textContent = errors.join(' ');
            projectErr.style.display = 'block';
            projectErr.classList.remove('d-none');
            return;
        }
        
        // Use original kode for URL if exists, otherwise use current kode
        const kode = originalKode || kodeProjectValue;
        
        // Prepare data as JSON (more reliable for PUT requests)
        const requestData = {
            kode_project: kodeProjectValue,
            nama_project: namaProjectValue
        };
        
        // Field opsional - hanya ditambahkan jika ada nilainya
        const tanggalInput = document.getElementById('tanggal');
        if (tanggalInput && tanggalInput.value) {
            requestData.tanggal = tanggalInput.value;
        }
        
        const kodeAkunInput = document.getElementById('project_kode_akun');
        const kodeAkunValue = kodeAkunInput ? kodeAkunInput.value.trim() : '';
        // Kode Akun opsional - hanya dikirim jika ada nilainya, jika kosong tidak perlu dikirim
        if (kodeAkunValue) {
            requestData.kode_akun = kodeAkunValue;
        }
        
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        saveProjectBtn.disabled = true;
        const old = saveProjectBtn.innerHTML;
        saveProjectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        
        try {
            // Use PUT method with JSON
            const res = await fetch(`/api/project/${encodeURIComponent(kode)}`, { 
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
                let errorMsg = j.message || 'Gagal menyimpan project';
                throw new Error(errorMsg);
            }
            
            // Success - redirect back
            if (j && j.success === true) {
                window.location.href = '{{ route("master.project") }}';
                return;
            }
        } catch(e) {
            // Show error message
            projectErr.textContent = e.message || 'Terjadi kesalahan saat menyimpan data';
            projectErr.style.display = 'block';
            projectErr.classList.remove('d-none');
        } finally {
            saveProjectBtn.disabled = false;
            saveProjectBtn.innerHTML = old;
        }
    });
    
    // Prevent form default submit
    projectForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });
    
    // Load data on page load
    loadProjectData();
});
</script>
@endsection

