// Budget specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('saveBudgetBtn');
    const form = document.getElementById('budgetForm');
    const errorBox = document.getElementById('budgetError');
    const modalEl = document.getElementById('budgetModal');
    
    if (!btn || !form) return;
    
    // Reset form on cancel/close
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function(){
            form.reset();
            if (errorBox) errorBox.classList.add('d-none');
        });
    }
    
    btn.addEventListener('click', async function() {
        if (errorBox) errorBox.classList.add('d-none');
        const fd = new FormData(form);
        btn.disabled = true;
        const old = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan';
        
        try {
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };
            const storeRoute = form.getAttribute('data-store-route') || window.budgetStoreRoute || '/budget';
            const res = await fetch(storeRoute, { method: 'POST', headers: headers, body: fd });
            const data = await res.json();
            
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal menyimpan');
            }
            
            const tbody = document.querySelector('#budgetTableBody');
            if (tbody && data.data) {
                const b = data.data;
                const tr = document.createElement('tr');
                tr.className = 'table-success';
                
                // Format tanggal
                let waktuFormatted = '-';
                if (b.tanggal) {
                    const tanggal = new Date(b.tanggal);
                    if (!isNaN(tanggal.getTime())) {
                        const d = String(tanggal.getDate()).padStart(2, '0');
                        const m = String(tanggal.getMonth() + 1).padStart(2, '0');
                        const y = tanggal.getFullYear();
                        const h = String(tanggal.getHours()).padStart(2, '0');
                        const min = String(tanggal.getMinutes()).padStart(2, '0');
                        waktuFormatted = `${d}/${m}/${y} ${h}:${min}`;
                    }
                }
                
                const projectName = b.project?.nama_project || b.project?.Nama_Project || b.kode_project || '';
                const budgetFormatted = parseFloat(b.budget || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                tr.setAttribute('data-project', b.kode_project || '');
                tr.setAttribute('data-tanggal', b.tanggal ? new Date(b.tanggal).toISOString().split('T')[0] : '');
                tr.innerHTML = `
                    <td>${projectName}</td>
                    <td>${b.deskripsi || ''}</td>
                    <td class="text-end">${budgetFormatted}</td>
                    <td>${waktuFormatted}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="/budget/${b.kode_budget}/edit" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <form action="/budget/${b.kode_budget}" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete this budget?');">
                                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}" />
                                <input type="hidden" name="_method" value="DELETE" />
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>`;
                tbody.prepend(tr);
                
                // Remove highlight after 3 seconds
                setTimeout(() => {
                    tr.classList.remove('table-success');
                }, 3000);
            }
            
            // Reset and close modal
            form.reset();
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
        } catch (e) {
            if (errorBox) {
                errorBox.textContent = e.message;
                errorBox.classList.remove('d-none');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = old;
        }
    });
    
    console.log('Budget page loaded');
});
