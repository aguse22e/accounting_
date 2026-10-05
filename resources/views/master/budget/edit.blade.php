@extends('layouts.app')

@section('title', 'Edit Master Budget')

@section('styles')
@vite(['resources/css/pages/management.css'])
@endsection

@section('content')
<div class="content-wrapper p-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="bi bi-pencil me-2"></i>Edit Master Budget
            </h6>
        </div>
        <div class="card-body">
            <form id="budgetEditForm" novalidate>
                <input type="hidden" id="budget_id" name="id">
                <input type="hidden" id="budget_original_kode" name="original_kode" value="{{ $kode }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Kode Project <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="budget_kode_project" name="kode_project" required placeholder="Misal: PRJ001">
                        <div class="form-text">Huruf, angka, & tanda hubung, unik.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kode Budget <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kode_budget" name="kode_budget" required pattern="[A-Za-z0-9\- ]+" placeholder="Misal: BGT001">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="budget_deskripsi" name="deskripsi" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Anggaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="budget_anggaran" name="anggaran" required placeholder="Misal: 1000000.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="budget_tanggal" name="tanggal">
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-3" id="budgetError" style="display: none !important;"></div>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" id="saveBudgetBtn">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                    <a href="{{ route('master.budget') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const budgetKode = '{{ $kode }}';
    const saveBudgetBtn = document.getElementById('saveBudgetBtn');
    const budgetForm = document.getElementById('budgetEditForm');
    const budgetErr = document.getElementById('budgetError');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Ensure error is hidden on page load
    if (budgetErr) {
        budgetErr.style.display = 'none';
        budgetErr.classList.add('d-none');
    }
    
    // Load budget data
    async function loadBudgetData() {
        try {
            // Show loading state
            saveBudgetBtn.disabled = true;
            saveBudgetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memuat data...';
            
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            const r = await fetch(`/api/budget`, { 
                headers,
                credentials: 'same-origin'
            });
            if (!r.ok) throw new Error('Gagal memuat data');
            const data = await r.json();
            // Handle both array and object response
            const budgetList = Array.isArray(data) ? data : (data.data || []);
            const budget = budgetList.find(b => {
                const kode = b.kode_budget || '';
                return kode === budgetKode || kode === decodeURIComponent(budgetKode);
            });
            
            if (!budget) {
                budgetErr.textContent = 'Budget tidak ditemukan';
                budgetErr.style.display = 'block';
                budgetErr.classList.remove('d-none');
                return;
            }
            
            // Fill form
            document.getElementById('budget_id').value = budget.id || '';
            document.getElementById('budget_original_kode').value = budget.kode_budget || '';
            document.getElementById('budget_kode_project').value = budget.kode_project || '';
            document.getElementById('kode_budget').value = budget.kode_budget || '';
            document.getElementById('budget_deskripsi').value = budget.deskripsi || '';
            // Format anggaran - ensure it's a valid number string
            const anggaran = budget.anggaran || 0;
            const anggaranStr = String(anggaran);
            document.getElementById('budget_anggaran').value = anggaranStr;
            
            const tanggal = budget.tanggal || budget.created_at || '';
            if (tanggal) {
                const d = new Date(tanggal);
                if (!isNaN(d.getTime())) {
                    const pad = (n) => String(n).padStart(2, '0');
                    document.getElementById('budget_tanggal').value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
                }
            }
            
            // Reset button state
            saveBudgetBtn.disabled = false;
            saveBudgetBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        } catch(e) {
            console.error('Error loading budget:', e);
            budgetErr.textContent = 'Gagal memuat data budget: ' + (e.message || 'Terjadi kesalahan');
            budgetErr.style.display = 'block';
            budgetErr.classList.remove('d-none');
            // Reset button state
            saveBudgetBtn.disabled = false;
            saveBudgetBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save';
        }
    }
    
    // Save handler
    saveBudgetBtn.addEventListener('click', async function(e){
        e.preventDefault();
        e.stopPropagation();
        // Hide error message first
        budgetErr.style.display = 'none';
        budgetErr.classList.add('d-none');
        
        const id = document.getElementById('budget_id').value;
        const originalKode = document.getElementById('budget_original_kode').value;
        
        const kodeProjectInput = document.getElementById('budget_kode_project');
        const kodeBudgetInput = document.getElementById('kode_budget');
        const deskripsiInput = document.getElementById('budget_deskripsi');
        const anggaranInput = document.getElementById('budget_anggaran');
        
        if (!kodeProjectInput || !kodeBudgetInput || !deskripsiInput || !anggaranInput) {
            budgetErr.textContent = 'Form input tidak ditemukan';
            budgetErr.classList.remove('d-none');
            return;
        }
        
        const kodeProjectValue = kodeProjectInput.value.trim();
        const kodeBudgetValue = kodeBudgetInput.value.trim();
        const deskripsiValue = deskripsiInput.value.trim();
        let anggaranValue = anggaranInput.value.trim();
        
        // Convert comma to dot for decimal separator and remove spaces
        if (anggaranValue) {
            anggaranValue = anggaranValue.replace(/,/g, '.').replace(/\s/g, '');
        }
        
        // Only show error if fields are truly empty
        if (!kodeProjectValue || !kodeBudgetValue || !deskripsiValue || !anggaranValue) {
            const errors = [];
            if (!kodeProjectValue) errors.push('Kode Project wajib diisi.');
            if (!kodeBudgetValue) errors.push('Kode Budget wajib diisi.');
            if (!deskripsiValue) errors.push('Deskripsi wajib diisi.');
            if (!anggaranValue) errors.push('Anggaran wajib diisi.');
            budgetErr.textContent = errors.join(' ');
            budgetErr.style.display = 'block';
            budgetErr.classList.remove('d-none');
            return;
        }
        
        // Use original kode for URL if exists, otherwise use current kode
        const kode = originalKode || kodeBudgetValue;
        
        // Prepare data as JSON (more reliable for PUT requests)
        const requestData = {
            kode_project: kodeProjectValue,
            kode_budget: kodeBudgetValue,
            deskripsi: deskripsiValue,
            anggaran: anggaranValue
        };
        
        const tanggalInput = document.getElementById('budget_tanggal');
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
        
        saveBudgetBtn.disabled = true;
        const old = saveBudgetBtn.innerHTML;
        saveBudgetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        
        try {
            // Use PUT method with JSON
            const res = await fetch(`/api/budget/${encodeURIComponent(kode)}`, { 
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
                let errorMsg = j.message || 'Gagal menyimpan budget';
                throw new Error(errorMsg);
            }
            
            // Success - redirect back
            if (j && j.success === true) {
                window.location.href = '{{ route("master.budget") }}';
                return;
            }
        } catch(e) {
            // Show error message
            budgetErr.textContent = e.message || 'Terjadi kesalahan saat menyimpan data';
            budgetErr.style.display = 'block';
            budgetErr.classList.remove('d-none');
        } finally {
            saveBudgetBtn.disabled = false;
            saveBudgetBtn.innerHTML = old;
        }
    });
    
    // Prevent form default submit
    budgetForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });
    
    // Load data on page load
    loadBudgetData();
});
</script>
@endsection

