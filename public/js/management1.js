/**
 * management-crud.js
 * Semua logika CRUD untuk halaman Master Akun, Project, Budget.
 * Dimuat sekali via @section('scripts') setelah DOM selesai dirender.
 */

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function jsonHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
    };
}

function formDataHeaders() {
    return {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
    };
}

async function parseJSON(res) {
    try {
        return await res.json();
    } catch (_) {
        return { success: false, message: 'Response bukan JSON (status ' + res.status + ')' };
    }
}

function fmtDate(val) {
    if (!val) return '';
    const d = new Date(String(val).replace(' ', 'T'));
    if (isNaN(d)) return val;
    const p = n => String(n).padStart(2, '0');
    return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

function notify(msg, type = 'success') {
    document.querySelectorAll('.mgmt-notify').forEach(el => el.remove());
    const el = document.createElement('div');
    el.className = 'mgmt-notify';
    el.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;
        border-radius:8px;color:#fff;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,.2);
        background:${type === 'success' ? '#198754' : '#dc3545'};
        transition:opacity .4s;`;
    el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}`;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3000);
}

function showErr(errEl, msg) {
    if (!errEl) return;
    errEl.textContent = msg;
    errEl.classList.remove('d-none');
}

function hideErr(errEl) {
    if (!errEl) return;
    errEl.classList.add('d-none');
}

function setBusy(btn, busy) {
    if (!btn) return;
    if (busy) {
        btn._oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn._oldHtml || 'Simpan';
    }
}

function bsModal(id) {
    const el = document.getElementById(id);
    return el ? new bootstrap.Modal(el) : null;
}

function hideBsModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    // getInstance bisa null jika modal dibuka via new bootstrap.Modal() tanpa disimpan
    const inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    inst.hide();
}

// ─────────────────────────────────────────────
// TABLE FILTER HELPER
// ─────────────────────────────────────────────

function applyFilter(tbodyId, predicate) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    Array.from(tbody.rows).forEach(row => {
        row.style.display = predicate(row) ? '' : 'none';
    });
}

// ─────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────

async function loadAkunList() {
    const tbody = document.getElementById('akunTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><span class="spinner-border spinner-border-sm"></span> Memuat...</td></tr>';
    try {
        const res = await fetch('/api/akun?_=' + Date.now(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache, no-store',
                'Pragma': 'no-cache',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        const j = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal memuat data akun');

        // /api/akun returns { success: true, data: [...] }
        const data = j.data || [];

        // Update datalist for project kode akun
        const dl = document.getElementById('akun_datalist');
        if (dl) dl.innerHTML = data.map(a => `<option value="${a.kode_akun}">${a.kode_akun} - ${a.nama || ''}</option>`).join('');

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(a => {
            const kode   = (a.kode_akun || '').replace(/'/g, "\\'");
            const tglRaw = a.updated_at || a.created_at || '';
            return `
            <tr data-kode="${a.kode_akun}">
                <td>${a.kode_akun}</td>
                <td>${a.nama || ''}</td>
                <td>${a.tipe || ''}</td>
                <td data-raw="${tglRaw}">${fmtDate(tglRaw)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-warning" onclick="editAkun('${kode}')"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAkun('${kode}')"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadProjects() {
    const tbody = document.getElementById('projectTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><span class="spinner-border spinner-border-sm"></span> Memuat...</td></tr>';
    try {
        const res = await fetch('/api/project', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin',
        });
        const data = await parseJSON(res);
        if (!res.ok) throw new Error(data.message || 'Gagal memuat data project');
        const list = Array.isArray(data) ? data : (data.data || []);
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
            return;
        }
        tbody.innerHTML = list.map((p, i) => {
            const kode = (p.kode_project || '').replace(/'/g, "\\'");
            return `
            <tr data-kode="${p.kode_project||''}">
                <td>${i+1}</td>
                <td>${p.kode_project||''}</td>
                <td>${p.nama_project||p.Nama_Project||''}</td>
                <td data-raw="${p.tanggal||p.created_at||''}">${fmtDate(p.tanggal||p.created_at)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-warning" onclick="editProject('${kode}')"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProject('${kode}')"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadBudgets() {
    const tbody = document.getElementById('budgetTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span> Memuat...</td></tr>';
    try {
        const res = await fetch('/api/budget', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin',
        });
        const data = await parseJSON(res);
        if (!res.ok) throw new Error(data.message || 'Gagal memuat data budget');
        const list = Array.isArray(data) ? data : (data.data || []);
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>';
            return;
        }
        tbody.innerHTML = list.map((b, i) => `
            <tr data-kode="${b.kode_budget||''}">
                <td>${i+1}</td>
                <td>${b.kode_project||''}</td>
                <td>${b.kode_budget||''}</td>
                <td>${b.deskripsi||''}</td>
                <td>${b.anggaran ? 'Rp ' + parseFloat(b.anggaran).toLocaleString('id-ID') : '-'}</td>
                <td data-raw="${b.tanggal||b.created_at||''}">${fmtDate(b.tanggal||b.created_at)}</td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-warning" onclick="editBudget('${(b.kode_budget||'').replace(/'/g, "\\'")}')"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBudget('${(b.kode_budget||'').replace(/'/g, "\\'")}')"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadProjectsForBudget() {
    const dl = document.getElementById('budget_project_datalist');
    if (!dl) return;
    try {
        const res = await fetch('/api/project', { headers: { 'Accept': 'application/json' } });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        dl.innerHTML = list.map(p => `<option value="${p.kode_project}">${p.kode_project} - ${p.nama_project||''}</option>`).join('');
    } catch (_) {}
}

// ─────────────────────────────────────────────
// SHOW FORM (Tambah Data)
// ─────────────────────────────────────────────

window.showAkunForm = function () {
    document.getElementById('akun_id').value = '';
    document.getElementById('kode_akun').value = '';
    document.getElementById('nama_akun').value = '';
    document.getElementById('saldo_normal').value = '';
    document.getElementById('tanggal_akun').value = '';
    hideErr(document.getElementById('akunError'));
    document.getElementById('akunModalTitle').textContent = 'Tambah Akun';
    bsModal('akunModal').show();
};

window.showProjectForm = function () {
    document.getElementById('project_id').value = '';
    document.getElementById('project_original_kode').value = '';
    document.getElementById('kode_project').value = '';
    document.getElementById('nama_project').value = '';
    document.getElementById('project_kode_akun').value = '';
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    document.getElementById('tanggal').value =
        `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    hideErr(document.getElementById('projectError'));
    document.getElementById('projectModalTitle').textContent = 'Tambah Project';
    bsModal('projectModal').show();
};

window.showBudgetForm = async function () {
    document.getElementById('budget_id').value = '';
    document.getElementById('budget_original_kode').value = '';
    document.getElementById('budget_kode_project').value = '';
    document.getElementById('kode_budget').value = '';
    document.getElementById('budget_deskripsi').value = '';
    document.getElementById('budget_anggaran').value = '';
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    document.getElementById('budget_tanggal').value =
        `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    hideErr(document.getElementById('budgetError'));
    document.getElementById('budgetModalTitle').textContent = 'Tambah Budget';
    await loadProjectsForBudget();
    bsModal('budgetModal').show();
};

// ─────────────────────────────────────────────
// SAVE AKUN
// ─────────────────────────────────────────────

async function saveAkun() {
    const errEl   = document.getElementById('akunError');
    const saveBtn = document.getElementById('saveAkunBtn');
    hideErr(errEl);

    // akun_id menyimpan kode_akun (primary key) saat edit, kosong saat tambah baru
    const originalKode = document.getElementById('akun_id').value.trim();
    const kode  = document.getElementById('kode_akun').value.trim();
    const nama  = document.getElementById('nama_akun').value.trim();
    const tipe  = document.getElementById('saldo_normal').value.trim();
    const tgl   = document.getElementById('tanggal_akun').value.trim();

    if (!kode) return showErr(errEl, 'Nomor Akun wajib diisi.');
    if (!nama)  return showErr(errEl, 'Nama Akun wajib diisi.');
    if (!tipe)  return showErr(errEl, 'Jenis Transaksi wajib dipilih.');

    // PUT ke /api/akun/{kode_akun} saat edit, POST ke /api/akun saat tambah baru
    const url    = originalKode ? `/api/akun/${encodeURIComponent(originalKode)}` : '/api/akun';
    const method = originalKode ? 'PUT' : 'POST';
    const body   = { kode_akun: kode, nama, tipe };
    if (tgl) body.tanggal = tgl;

    setBusy(saveBtn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) {
            const msg = j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan');
            throw new Error(msg);
        }
        notify(originalKode ? 'Akun berhasil diperbarui' : 'Akun berhasil ditambahkan');
        // Tunggu modal benar-benar tertutup (animasi selesai) baru reload tabel
        const modalEl = document.getElementById('akunModal');
        let reloaded = false;
        const onHidden = async () => {
            if (reloaded) return;
            reloaded = true;
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            await loadAkunList();
        };
        modalEl.addEventListener('hidden.bs.modal', onHidden);
        hideBsModal('akunModal');
        // Fallback: jika hidden.bs.modal tidak fire dalam 600ms, reload tetap dijalankan
        setTimeout(async () => {
            if (!reloaded) {
                reloaded = true;
                modalEl.removeEventListener('hidden.bs.modal', onHidden);
                await loadAkunList();
            }
        }, 600);
    } catch (e) {
        showErr(errEl, e.message);
    } finally {
        setBusy(saveBtn, false);
    }
}

// ─────────────────────────────────────────────
// SAVE PROJECT
// ─────────────────────────────────────────────

async function saveProject() {
    const errEl   = document.getElementById('projectError');
    const saveBtn = document.getElementById('saveProjectBtn');
    hideErr(errEl);

    const id           = document.getElementById('project_id').value.trim();
    const originalKode = document.getElementById('project_original_kode').value.trim();
    const kode         = document.getElementById('kode_project').value.trim();
    const nama         = document.getElementById('nama_project').value.trim();
    const kodeAkun     = document.getElementById('project_kode_akun').value.trim();
    const tanggal      = document.getElementById('tanggal').value.trim();

    if (!kode) return showErr(errEl, 'Kode Project wajib diisi.');
    if (!nama)  return showErr(errEl, 'Nama Project wajib diisi.');

    const url    = originalKode ? `/api/project/${encodeURIComponent(originalKode)}` : '/api/project';
    const method = originalKode ? 'PUT' : 'POST';
    const body   = { kode_project: kode, nama_project: nama };
    if (kodeAkun) body.kode_akun = kodeAkun;
    if (tanggal)  body.tanggal   = tanggal;

    setBusy(saveBtn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) {
            const msg = j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan');
            throw new Error(msg);
        }
        notify(originalKode ? 'Project berhasil diperbarui' : 'Project berhasil ditambahkan');
        const modalElP = document.getElementById('projectModal');
        const onHiddenP = async () => {
            modalElP.removeEventListener('hidden.bs.modal', onHiddenP);
            await loadProjects();
        };
        modalElP.addEventListener('hidden.bs.modal', onHiddenP);
        hideBsModal('projectModal');
    } catch (e) {
        showErr(errEl, e.message);
    } finally {
        setBusy(saveBtn, false);
    }
}

// ─────────────────────────────────────────────
// SAVE BUDGET
// ─────────────────────────────────────────────

async function saveBudget() {
    const errEl   = document.getElementById('budgetError');
    const saveBtn = document.getElementById('saveBudgetBtn');
    hideErr(errEl);

    const id           = document.getElementById('budget_id').value.trim();
    const originalKode = document.getElementById('budget_original_kode').value.trim();
    const kodeProject  = document.getElementById('budget_kode_project').value.trim();
    const kodeBudget   = document.getElementById('kode_budget').value.trim();
    const deskripsi    = document.getElementById('budget_deskripsi').value.trim();
    const anggaran     = document.getElementById('budget_anggaran').value.trim();
    const tanggal      = document.getElementById('budget_tanggal').value.trim();

    if (!kodeProject) return showErr(errEl, 'Kode Project wajib diisi.');
    if (!kodeBudget)  return showErr(errEl, 'Kode Budget wajib diisi.');
    if (!deskripsi)   return showErr(errEl, 'Deskripsi wajib diisi.');
    if (!anggaran)    return showErr(errEl, 'Anggaran wajib diisi.');

    const url    = originalKode ? `/api/budget/${encodeURIComponent(originalKode)}` : '/api/budget';
    const method = originalKode ? 'PUT' : 'POST';
    const body   = { kode_project: kodeProject, kode_budget: kodeBudget, deskripsi, anggaran };
    if (tanggal) body.tanggal = tanggal;

    setBusy(saveBtn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) {
            const msg = j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan');
            throw new Error(msg);
        }
        notify(originalKode ? 'Budget berhasil diperbarui' : 'Budget berhasil ditambahkan');
        const modalElB = document.getElementById('budgetModal');
        const onHiddenB = async () => {
            modalElB.removeEventListener('hidden.bs.modal', onHiddenB);
            await loadBudgets();
        };
        modalElB.addEventListener('hidden.bs.modal', onHiddenB);
        hideBsModal('budgetModal');
    } catch (e) {
        showErr(errEl, e.message);
    } finally {
        setBusy(saveBtn, false);
    }
}

// ─────────────────────────────────────────────
// EDIT
// ─────────────────────────────────────────────

window.editAkun = async function (kodeAkun) {
    try {
        const res  = await fetch('/api/akun', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const j    = await parseJSON(res);
        const list = j.data || [];
        const akun = list.find(a => String(a.kode_akun) === String(kodeAkun));
        if (!akun) return alert('Akun tidak ditemukan (kode: ' + kodeAkun + ')');

        // Simpan kode_akun (primary key) di hidden field untuk dipakai saat PUT
        document.getElementById('akun_id').value      = akun.kode_akun;
        document.getElementById('kode_akun').value    = akun.kode_akun || '';
        document.getElementById('nama_akun').value    = akun.nama || '';
        // Normalize ke Debit/Kredit agar cocok dengan option value di select
        const tipeRaw  = akun.tipe || '';
        const tipeNorm = tipeRaw.charAt(0).toUpperCase() + tipeRaw.slice(1).toLowerCase();
        document.getElementById('saldo_normal').value = tipeNorm;
        document.getElementById('tanggal_akun').value = (akun.tanggal || '').substring(0, 10);
        hideErr(document.getElementById('akunError'));
        document.getElementById('akunModalTitle').textContent = 'Edit Akun';
        bsModal('akunModal').show();
    } catch (e) {
        alert('Gagal memuat data akun: ' + e.message);
    }
};

window.editProject = async function (kode) {
    try {
        const res  = await fetch('/api/project', { headers: { 'Accept': 'application/json' } });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        const p    = list.find(x => x.kode_project === kode || x.kode_project === decodeURIComponent(kode));
        if (!p) return alert('Project tidak ditemukan');

        document.getElementById('project_id').value          = p.id || '';
        document.getElementById('project_original_kode').value = p.kode_project || '';
        document.getElementById('kode_project').value        = p.kode_project || '';
        document.getElementById('nama_project').value        = p.nama_project || p.Nama_Project || '';
        document.getElementById('project_kode_akun').value   = p.kode_akun || '';

        const tgl = p.tanggal || p.Tanggal_Mulai || '';
        if (tgl) {
            const d = new Date(tgl);
            if (!isNaN(d)) {
                const pad = n => String(n).padStart(2, '0');
                document.getElementById('tanggal').value =
                    `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }
        }
        hideErr(document.getElementById('projectError'));
        document.getElementById('projectModalTitle').textContent = 'Edit Project';
        bsModal('projectModal').show();
    } catch (e) {
        alert('Gagal memuat data project: ' + e.message);
    }
};

window.editBudget = async function (kode) {
    try {
        const res  = await fetch('/api/budget', { headers: { 'Accept': 'application/json' } });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        const b    = list.find(x => x.kode_budget === kode || x.kode_budget === decodeURIComponent(kode));
        if (!b) return alert('Budget tidak ditemukan');

        document.getElementById('budget_id').value            = b.id || '';
        document.getElementById('budget_original_kode').value = b.kode_budget || '';
        document.getElementById('budget_kode_project').value  = b.kode_project || '';
        document.getElementById('kode_budget').value          = b.kode_budget || '';
        document.getElementById('budget_deskripsi').value     = b.deskripsi || '';
        document.getElementById('budget_anggaran').value      = b.anggaran || '';

        const tgl = b.tanggal || b.created_at || '';
        if (tgl) {
            const d = new Date(tgl);
            if (!isNaN(d)) {
                const pad = n => String(n).padStart(2, '0');
                document.getElementById('budget_tanggal').value =
                    `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            }
        }
        hideErr(document.getElementById('budgetError'));
        document.getElementById('budgetModalTitle').textContent = 'Edit Budget';
        await loadProjectsForBudget();
        bsModal('budgetModal').show();
    } catch (e) {
        alert('Gagal memuat data budget: ' + e.message);
    }
};

// ─────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────

window.deleteAkun = async function (kodeAkun) {
    if (!confirm(`Hapus akun "${kodeAkun}"?`)) return;
    try {
        const res = await fetch(`/api/akun/${encodeURIComponent(kodeAkun)}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
            credentials: 'same-origin',
        });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
        notify('Akun berhasil dihapus');
        await loadAkunList();
    } catch (e) {
        alert(e.message);
    }
};

let _deleteProjectKode = null;

window.deleteProject = function (kode) {
    _deleteProjectKode = kode;
    const span = document.getElementById('deleteProjectKode');
    if (span) span.textContent = kode;
    bsModal('deleteProjectModal').show();
};

window.deleteBudget = async function (kode) {
    if (!confirm(`Hapus budget "${kode}"?`)) return;
    try {
        const res = await fetch(`/api/budget/${encodeURIComponent(kode)}`, { method: 'DELETE', headers: jsonHeaders() });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
        notify('Budget berhasil dihapus');
        await loadBudgets();
    } catch (e) {
        alert(e.message);
    }
};

// ─────────────────────────────────────────────
// DOM READY – register event listeners & load data
// ─────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    // ── Wire save buttons ──────────────────────
    document.getElementById('saveAkunBtn')?.addEventListener('click', saveAkun);
    document.getElementById('saveProjectBtn')?.addEventListener('click', saveProject);
    document.getElementById('saveBudgetBtn')?.addEventListener('click', saveBudget);

    // ── Wire confirm delete project ────────────
    document.getElementById('confirmDeleteProjectBtn')?.addEventListener('click', async function () {
        if (!_deleteProjectKode) return;
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch(`/api/project/${encodeURIComponent(_deleteProjectKode)}`, { method: 'DELETE', headers: jsonHeaders() });
            const j   = await parseJSON(res);
            if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
            notify('Project berhasil dihapus');
            hideBsModal('deleteProjectModal');
            _deleteProjectKode = null;
            await loadProjects();
        } catch (e) {
            alert(e.message);
        } finally {
            btn.disabled = false;
        }
    });

    // ── Filter Akun ────────────────────────────
    function filterAkun() {
        const q    = (document.getElementById('akunSearch')?.value || '').toLowerCase();
        const jenis = (document.getElementById('akunJenis')?.value || '').toLowerCase();
        applyFilter('akunTableBody', row => {
            const kode = row.cells[0]?.textContent.toLowerCase() || '';
            const nama = row.cells[1]?.textContent.toLowerCase() || '';
            const tipe = row.cells[2]?.textContent.toLowerCase() || '';
            const matchQ = !q || kode.includes(q) || nama.includes(q);
            const matchJ = !jenis || tipe.includes(jenis);
            return matchQ && matchJ;
        });
    }
    document.getElementById('akunSearch')?.addEventListener('input', filterAkun);
    document.getElementById('akunJenis')?.addEventListener('change', filterAkun);

    // ── Filter Project ─────────────────────────
    function filterProject() {
        const q    = (document.getElementById('projectSearch')?.value || '').toLowerCase();
        const from = document.getElementById('projectFrom')?.value;
        const to   = document.getElementById('projectTo')?.value;
        applyFilter('projectTableBody', row => {
            const kode = row.cells[1]?.textContent.toLowerCase() || '';
            const nama = row.cells[2]?.textContent.toLowerCase() || '';
            const raw  = row.cells[3]?.getAttribute('data-raw') || '';
            const dt   = raw ? new Date(raw) : null;
            const matchQ    = !q || kode.includes(q) || nama.includes(q);
            const matchFrom = !from || (dt && dt >= new Date(from));
            const matchTo   = !to   || (dt && dt <= new Date(to + 'T23:59:59'));
            return matchQ && matchFrom && matchTo;
        });
    }
    document.getElementById('projectCariBtn')?.addEventListener('click', filterProject);
    document.getElementById('projectSearch')?.addEventListener('input', filterProject);
    document.getElementById('projectReset')?.addEventListener('click', () => {
        ['projectSearch','projectFrom','projectTo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        filterProject();
    });
    document.getElementById('projectToday')?.addEventListener('click', () => {
        const today = new Date().toISOString().substring(0, 10);
        document.getElementById('projectFrom').value = today;
        document.getElementById('projectTo').value   = today;
        filterProject();
    });

    // ── Filter Budget ──────────────────────────
    function filterBudget() {
        const q    = (document.getElementById('budgetSearch')?.value || '').toLowerCase();
        const from = document.getElementById('budgetFrom')?.value;
        const to   = document.getElementById('budgetTo')?.value;
        applyFilter('budgetTableBody', row => {
            const kodeP = row.cells[1]?.textContent.toLowerCase() || '';
            const kodeB = row.cells[2]?.textContent.toLowerCase() || '';
            const raw   = row.cells[5]?.getAttribute('data-raw') || '';
            const dt    = raw ? new Date(raw) : null;
            const matchQ    = !q || kodeP.includes(q) || kodeB.includes(q);
            const matchFrom = !from || (dt && dt >= new Date(from));
            const matchTo   = !to   || (dt && dt <= new Date(to + 'T23:59:59'));
            return matchQ && matchFrom && matchTo;
        });
    }
    document.getElementById('budgetCariBtn')?.addEventListener('click', filterBudget);
    document.getElementById('budgetSearch')?.addEventListener('input', filterBudget);
    document.getElementById('budgetReset')?.addEventListener('click', () => {
        ['budgetSearch','budgetFrom','budgetTo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        filterBudget();
    });
    document.getElementById('budgetToday')?.addEventListener('click', () => {
        const today = new Date().toISOString().substring(0, 10);
        document.getElementById('budgetFrom').value = today;
        document.getElementById('budgetTo').value   = today;
        filterBudget();
    });

    // ── Load initial data based on current page ─
    const tab = window._defaultTabId || 'akun';
    if (tab === 'akun')    loadAkunList();
    if (tab === 'project') loadProjects();
    if (tab === 'budget')  loadBudgets();

    console.log('Management CRUD ready. Tab:', tab);
});
