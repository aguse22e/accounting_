document.addEventListener('DOMContentLoaded', function(){
    function applyFilter(){
        const q = (document.getElementById('trxSearch').value||'').toLowerCase();
        const t = (document.getElementById('trxType').value||'');
        const fromVal = document.getElementById('trxFrom').value;
        const toVal = document.getElementById('trxTo').value;
        const from = fromVal ? new Date(fromVal) : null;
        const to = toVal ? new Date(toVal) : null;

        // Bank tab columns: 0 vou,1 akun,2 project,3 deskripsi,4 nominal,5 saldo,6 waktu
        Array.from(document.querySelectorAll('#bank tbody tr')).forEach(tr=>{
            const vou = tr.children[0]?.textContent.toLowerCase()||'';
            const proj = tr.children[2]?.textContent.toLowerCase()||'';
            const desc = tr.children[3]?.textContent.toLowerCase()||'';
            const waktuCell = tr.children[6];
            const waktu = waktuCell ? waktuCell.textContent : '';
            const dt = waktu ? new Date(waktu.split('/').reverse().join('-')) : null;
            const matchQ = !q || vou.includes(q) || desc.includes(q) || proj.includes(q);
            const matchT = !t || t === 'Debit'; // bank tab shows only Debit
            const matchFrom = !from || (dt && dt >= from);
            const matchTo = !to || (dt && dt <= new Date(to.getFullYear(), to.getMonth(), to.getDate(), 23,59,59));
            tr.style.display = (matchQ && matchT && matchFrom && matchTo) ? '' : 'none';
        });

        // Jurnal tab columns: same structure, but represents Kredit
        Array.from(document.querySelectorAll('#jurnal tbody tr')).forEach(tr=>{
            const vou = tr.children[0]?.textContent.toLowerCase()||'';
            const proj = tr.children[2]?.textContent.toLowerCase()||'';
            const desc = tr.children[3]?.textContent.toLowerCase()||'';
            const waktuCell = tr.children[6];
            const waktu = waktuCell ? waktuCell.textContent : '';
            const dt = waktu ? new Date(waktu.split('/').reverse().join('-')) : null;
            const matchQ = !q || vou.includes(q) || desc.includes(q) || proj.includes(q);
            const matchT = !t || t === 'Kredit'; // jurnal tab shows only Kredit
            const matchFrom = !from || (dt && dt >= from);
            const matchTo = !to || (dt && dt <= new Date(to.getFullYear(), to.getMonth(), to.getDate(), 23,59,59));
            tr.style.display = (matchQ && matchT && matchFrom && matchTo) ? '' : 'none';
        });
    }
    // Remove auto-filtering and wait for apply button click
    const applyBtn = document.getElementById('trxApply');
    const todayBtn = document.getElementById('trxToday');
    const resetBtn = document.getElementById('trxReset');
    
    applyBtn?.addEventListener('click', function(e){ e.preventDefault();
        // Untuk filtering, gunakan frontend
        applyFilter();
    });
    
    // Hari ini button - set dates to today and filter
    todayBtn?.addEventListener('click', function(e){ e.preventDefault();
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const da = String(d.getDate()).padStart(2,'0');
        document.getElementById('trxFrom').value = `${y}-${m}-${da}`;
        document.getElementById('trxTo').value = `${y}-${m}-${da}`;
        applyFilter();
    });
    
    // Reset button - clear all filters
    resetBtn?.addEventListener('click', function(e){ e.preventDefault();
        document.getElementById('trxSearch').value = '';
        document.getElementById('trxType').value = '';
        document.getElementById('trxFrom').value = '';
        document.getElementById('trxTo').value = '';
        applyFilter();
    });
    const btn = document.getElementById('saveTransaksiBtn');
    const form = document.getElementById('transaksiForm');
    const errorBox = document.getElementById('transaksiError');
    const modalEl = document.getElementById('transaksiModal');
    modalEl && modalEl.addEventListener('hidden.bs.modal', function(){
        form.reset();
        errorBox.classList.add('d-none');
    });
    btn && btn.addEventListener('click', async function(){
        errorBox.classList.add('d-none');
        const fd = new FormData(form);
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        btn.disabled = true; const old = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan';
        try {
            const res = await fetch(window.transaksiRoutes?.store || '/transaksi', { method: 'POST', headers: headers, body: fd });
            const data = await res.json();
            if (!res.ok || !data.success) {
                // Handle validation errors
                let errorMessage = data.message || 'Gagal menyimpan';
                if (data.errors) {
                    // Extract first error message from validation errors
                    const firstError = Object.values(data.errors)[0];
                    if (Array.isArray(firstError) && firstError.length > 0) {
                        errorMessage = firstError[0];
                    } else if (typeof firstError === 'string') {
                        errorMessage = firstError;
                    }
                }
                throw new Error(errorMessage);
            }
            const b = data.data;
            const targetTab = (b.transaksi_type === 'Kredit') ? 'jurnal' : 'bank';
            const tbody = document.querySelector(`#${targetTab} tbody`);
            
            if (!tbody) {
                console.error(`Tbody not found for tab: ${targetTab}`);
                return;
            }
            
            const row = document.createElement('tr');
            
            // Format tanggal
            let tanggalFormatted = '';
            if (b.tanggal) {
                const dateObj = new Date(b.tanggal);
                const day = String(dateObj.getDate()).padStart(2, '0');
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const year = dateObj.getFullYear();
                const hours = String(dateObj.getHours()).padStart(2, '0');
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                const seconds = String(dateObj.getSeconds()).padStart(2, '0');
                tanggalFormatted = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            }
            
            // Format nominal dan saldo
            const nominal = parseFloat(b.nominal || b.debit || b.kredit || 0);
            const saldo = parseFloat(b.saldo || 0);
            const nominalFormatted = nominal.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
            const saldoFormatted = saldo.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
            
            // Tentukan class warna untuk nominal berdasarkan transaksi_type
            const nominalClass = (b.transaksi_type === 'Debit') ? 'text-success' : 'text-danger';
            // Tentukan class warna untuk saldo
            const saldoClass = saldo >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
            
            row.innerHTML = `
                <td>${b.kode_voucer || ''}</td>
                <td>${b.akun?.kode_akun || b.akun_kode || 'N/A'}</td>
                <td>${b.project?.kode_project || b.kode_project || ''}</td>
                <td>${b.deskripsi || ''}</td>
                <td class="text-end ${nominalClass}">${nominalFormatted}</td>
                <td class="text-end ${saldoClass}">${saldoFormatted}</td>
                <td>${tanggalFormatted}</td>
                <td class="actions-cell">
                    <div class="d-flex justify-content-between align-items-center w-100" style="gap:.25rem;">
                    <a href="/transaksi/${b.kode_voucer}/edit" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <form action="/transaksi/${b.kode_voucer}" method="POST" class="m-0 p-0" onsubmit="return confirm('Hapus transaksi ini?');">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}" />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                    </div>
                </td>`;
            // Tambahkan row di bagian bawah (append)
            tbody && tbody.append(row);
            
            // Refresh halaman untuk melihat saldo yang sudah dihitung ulang dengan benar
            // Ini memastikan akumulasi saldo selalu akurat setelah transaksi baru ditambahkan
            setTimeout(() => {
                window.location.reload();
            }, 500); // Delay 500ms untuk memberi waktu server menyimpan dan menghitung ulang
            
            form.reset();
            bootstrap.Modal.getInstance(modalEl).hide();
        } catch (e) {
            errorBox.textContent = e.message;
            errorBox.classList.remove('d-none');
        } finally {
            btn.disabled = false; btn.innerHTML = old;
        }
    });
    
    // Print PDF functionality
    const printPdfBtn = document.getElementById('printPdfBtn');
    const printForm = document.getElementById('printForm');
    
    printPdfBtn && printPdfBtn.addEventListener('click', function() {
        printForm.submit();
    });
    
    // Export Excel functionality
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const exportForm = document.getElementById('exportForm');
    
    exportExcelBtn && exportExcelBtn.addEventListener('click', function() {
        exportForm.submit();
    });
    
});

