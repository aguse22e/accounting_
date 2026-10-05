/**
 * management-crud.js
 * CRUD lengkap untuk Master Akun, Project, Budget.
 * Di-load via <script src="/js/management-crud.js"> di @section('scripts').
 */

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

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

async function parseJSON(res) {
    try { return await res.json(); }
    catch (_) { return { success: false, message: 'Response bukan JSON (status ' + res.status + ')' }; }
}

function fmtDate(val) {
    if (!val) return '';
    const d = new Date(String(val).replace(' ', 'T'));
    if (isNaN(d)) return val;
    const p = n => String(n).padStart(2, '0');
    return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

function notify(msg, type = 'success') {
    document.querySelectorAll('.mgmt-notify').forEach(e => e.remove());
    const el = document.createElement('div');
    el.className = 'mgmt-notify';
    el.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;
        border-radius:8px;color:#fff;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,.2);
        background:${type === 'success' ? '#198754' : '#dc3545'};transition:opacity .4s;`;
    el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}`;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3000);
}

function showErr(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('d-none');
}

function hideErr(id) {
    document.getElementById(id)?.classList.add('d-none');
}

function setBusy(btn, busy) {
    if (!btn) return;
    if (busy) {
        btn._old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn._old || 'Simpan';
    }
}

function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return alert('Modal tidak ditemukan: ' + id);
    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).show();
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
}

// ─────────────────────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────────────────────

async function loadAkunList() {
    const tbody = document.getElementById('akunTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>';
    try {
        const res  = await fetch('/api/akun?_=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin', cache: 'no-store',
        });
        const j    = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal memuat data');
        const data = j.data || [];

        const dl = document.getElementById('akun_datalist');
        if (dl) dl.innerHTML = data.map(a => `<option value="${a.kode_akun}">${a.kode_akun} - ${a.nama || ''}</option>`).join('');

        if (!data.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>'; return; }

        tbody.innerHTML = data.map(a => {
            const kode = (a.kode_akun || '').replace(/'/g, "\\'");
            return `<tr data-kode="${a.kode_akun}">
                <td>${a.kode_akun}</td>
                <td>${a.nama || ''}</td>
                <td>${a.tipe || ''}</td>
                <td>${fmtDate(a.updated_at || a.created_at)}</td>
                <td><div class="d-flex gap-1">
                    <button class="btn btn-sm btn-warning" onclick="editAkun('${kode}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger"  onclick="deleteAkun('${kode}')"><i class="bi bi-trash"></i></button>
                </div></td></tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadProjects() {
    const tbody = document.getElementById('projectTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>';
    try {
        const res  = await fetch('/api/project?_=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin', cache: 'no-store',
        });
        const data = await parseJSON(res);
        if (!res.ok) throw new Error(data.message || 'Gagal memuat data');
        const list = Array.isArray(data) ? data : (data.data || []);

        if (!list.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>'; return; }
        tbody.innerHTML = list.map((p, i) => {
            const k = (p.kode_project || '').replace(/'/g, "\\'");
            return `<tr data-kode="${p.kode_project}">
                <td>${i+1}</td>
                <td>${p.kode_project || ''}</td>
                <td>${p.nama_project || p.Nama_Project || ''}</td>
                <td>${fmtDate(p.tanggal || p.created_at)}</td>
                <td><div class="d-flex gap-1">
                    <button class="btn btn-sm btn-warning" onclick="editProject('${k}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger"  onclick="deleteProject('${k}')"><i class="bi bi-trash"></i></button>
                </div></td></tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadBudgets() {
    const tbody = document.getElementById('budgetTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>';
    try {
        const res  = await fetch('/api/budget?_=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin', cache: 'no-store',
        });
        const data = await parseJSON(res);
        if (!res.ok) throw new Error(data.message || 'Gagal memuat data');
        const list = Array.isArray(data) ? data : (data.data || []);

        if (!list.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>'; return; }
        tbody.innerHTML = list.map((b, i) => {
            const k = (b.kode_budget || '').replace(/'/g, "\\'");
            return `<tr data-kode="${b.kode_budget}">
                <td>${i+1}</td>
                <td>${b.kode_project || ''}</td>
                <td>${b.kode_budget || ''}</td>
                <td>${b.deskripsi || ''}</td>
                <td class="text-end">Rp ${parseFloat(b.anggaran||0).toLocaleString('id-ID')}</td>
                <td>${fmtDate(b.tanggal || b.created_at)}</td>
                <td><div class="d-flex gap-1">
                    <button class="btn btn-sm btn-warning" onclick="editBudget('${k}')"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger"  onclick="deleteBudget('${k}')"><i class="bi bi-trash"></i></button>
                </div></td></tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

async function loadProjectsForBudget() {
    const dl = document.getElementById('budget_project_datalist');
    if (!dl) return;
    try {
        const res  = await fetch('/api/project', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, credentials: 'same-origin' });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        dl.innerHTML = list.map(p => `<option value="${p.kode_project}">${p.kode_project} - ${p.nama_project || ''}</option>`).join('');
    } catch (_) {}
}

// ─────────────────────────────────────────────────────────────
// SHOW FORM (Tambah Data)
// ─────────────────────────────────────────────────────────────

window.showAkunForm = function () {
    document.getElementById('akun_id').value      = '';
    document.getElementById('kode_akun').value    = '';
    document.getElementById('nama_akun').value    = '';
    document.getElementById('saldo_normal').value = '';
    document.getElementById('tanggal_akun').value = '';
    hideErr('akunError');
    document.getElementById('akunModalTitle').textContent = 'Tambah Akun';
    openModal('akunModal');
};

window.showProjectForm = function () {
    document.getElementById('project_id').value            = '';
    document.getElementById('project_original_kode').value = '';
    document.getElementById('kode_project').value          = '';
    document.getElementById('nama_project').value          = '';
    document.getElementById('project_kode_akun').value     = '';
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    document.getElementById('tanggal').value =
        `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    hideErr('projectError');
    document.getElementById('projectModalTitle').textContent = 'Tambah Project';
    openModal('projectModal');
};

window.showBudgetForm = async function () {
    document.getElementById('budget_id').value            = '';
    document.getElementById('budget_original_kode').value = '';
    document.getElementById('budget_kode_project').value  = '';
    document.getElementById('kode_budget').value          = '';
    document.getElementById('budget_deskripsi').value     = '';
    document.getElementById('budget_anggaran').value      = '';
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    document.getElementById('budget_tanggal').value =
        `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    hideErr('budgetError');
    document.getElementById('budgetModalTitle').textContent = 'Tambah Budget';
    await loadProjectsForBudget();
    openModal('budgetModal');
};

// ─────────────────────────────────────────────────────────────
// SAVE
// ─────────────────────────────────────────────────────────────

async function saveAkun() {
    const btn  = document.getElementById('saveAkunBtn');
    const orig = document.getElementById('akun_id').value.trim();
    const kode = document.getElementById('kode_akun').value.trim();
    const nama = document.getElementById('nama_akun').value.trim();
    const tipe = document.getElementById('saldo_normal').value.trim();
    const tgl  = document.getElementById('tanggal_akun').value.trim();

    hideErr('akunError');
    if (!kode) return showErr('akunError', 'Nomor Akun wajib diisi.');
    if (!nama) return showErr('akunError', 'Nama Akun wajib diisi.');
    if (!tipe) return showErr('akunError', 'Jenis Transaksi wajib dipilih.');

    const url    = orig ? `/api/akun/${encodeURIComponent(orig)}` : '/api/akun';
    const method = orig ? 'PUT' : 'POST';
    const body   = { kode_akun: kode, nama, tipe };
    if (tgl) body.tanggal = tgl;

    setBusy(btn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan'));
        notify(orig ? 'Akun berhasil diperbarui' : 'Akun berhasil ditambahkan');
        const modalEl = document.getElementById('akunModal');
        modalEl.addEventListener('hidden.bs.modal', async () => { await loadAkunList(); }, { once: true });
        closeModal('akunModal');
        setTimeout(loadAkunList, 600);
    } catch (e) {
        showErr('akunError', e.message);
    } finally {
        setBusy(btn, false);
    }
}

async function saveProject() {
    const btn  = document.getElementById('saveProjectBtn');
    const orig = document.getElementById('project_original_kode').value.trim();
    const kode = document.getElementById('kode_project').value.trim();
    const nama = document.getElementById('nama_project').value.trim();
    const akun = document.getElementById('project_kode_akun').value.trim();
    const tgl  = document.getElementById('tanggal').value.trim();

    hideErr('projectError');
    if (!kode) return showErr('projectError', 'Kode Project wajib diisi.');
    if (!nama) return showErr('projectError', 'Nama Project wajib diisi.');

    const url    = orig ? `/api/project/${encodeURIComponent(orig)}` : '/api/project';
    const method = orig ? 'PUT' : 'POST';
    const body   = { kode_project: kode, nama_project: nama };
    if (akun) body.kode_akun = akun;
    if (tgl)  body.tanggal   = tgl;

    setBusy(btn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan'));
        notify(orig ? 'Project berhasil diperbarui' : 'Project berhasil ditambahkan');
        closeModal('projectModal');
        setTimeout(loadProjects, 400);
    } catch (e) {
        showErr('projectError', e.message);
    } finally {
        setBusy(btn, false);
    }
}

async function saveBudget() {
    const btn      = document.getElementById('saveBudgetBtn');
    const orig     = document.getElementById('budget_original_kode').value.trim();
    const kodeProj = document.getElementById('budget_kode_project').value.trim();
    const kodeBgt  = document.getElementById('kode_budget').value.trim();
    const desk     = document.getElementById('budget_deskripsi').value.trim();
    const ang      = document.getElementById('budget_anggaran').value.trim();
    const tgl      = document.getElementById('budget_tanggal').value.trim();

    hideErr('budgetError');
    if (!kodeProj) return showErr('budgetError', 'Kode Project wajib diisi.');
    if (!kodeBgt)  return showErr('budgetError', 'Kode Budget wajib diisi.');
    if (!desk)     return showErr('budgetError', 'Deskripsi wajib diisi.');
    if (!ang)      return showErr('budgetError', 'Anggaran wajib diisi.');

    const url    = orig ? `/api/budget/${encodeURIComponent(orig)}` : '/api/budget';
    const method = orig ? 'PUT' : 'POST';
    const body   = { kode_project: kodeProj, kode_budget: kodeBgt, deskripsi: desk, anggaran: ang };
    if (tgl) body.tanggal = tgl;

    setBusy(btn, true);
    try {
        const res = await fetch(url, { method, headers: jsonHeaders(), body: JSON.stringify(body), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.errors ? Object.values(j.errors).flat().join(' ') : (j.message || 'Gagal menyimpan'));
        notify(orig ? 'Budget berhasil diperbarui' : 'Budget berhasil ditambahkan');
        closeModal('budgetModal');
        setTimeout(loadBudgets, 400);
    } catch (e) {
        showErr('budgetError', e.message);
    } finally {
        setBusy(btn, false);
    }
}

// ─────────────────────────────────────────────────────────────
// EDIT
// ─────────────────────────────────────────────────────────────

window.editAkun = async function (kode) {
    try {
        const res  = await fetch('/api/akun?_=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const j    = await parseJSON(res);
        const akun = (j.data || []).find(a => String(a.kode_akun) === String(kode));
        if (!akun) return alert('Akun tidak ditemukan: ' + kode);

        document.getElementById('akun_id').value      = akun.kode_akun;
        document.getElementById('kode_akun').value    = akun.kode_akun;
        document.getElementById('nama_akun').value    = akun.nama || '';
        const t = (akun.tipe || '');
        document.getElementById('saldo_normal').value = t.charAt(0).toUpperCase() + t.slice(1).toLowerCase();
        document.getElementById('tanggal_akun').value = (akun.tanggal || '').substring(0, 10);
        hideErr('akunError');
        document.getElementById('akunModalTitle').textContent = 'Edit Akun';
        openModal('akunModal');
    } catch (e) { alert('Gagal memuat akun: ' + e.message); }
};

window.editProject = async function (kode) {
    try {
        const res  = await fetch('/api/project', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, credentials: 'same-origin' });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        const p    = list.find(x => x.kode_project === kode);
        if (!p) return alert('Project tidak ditemukan: ' + kode);

        document.getElementById('project_id').value            = p.id || '';
        document.getElementById('project_original_kode').value = p.kode_project;
        document.getElementById('kode_project').value          = p.kode_project;
        document.getElementById('nama_project').value          = p.nama_project || p.Nama_Project || '';
        document.getElementById('project_kode_akun').value     = p.kode_akun || '';

        const tgl = p.tanggal || p.Tanggal_Mulai || '';
        if (tgl) {
            const d = new Date(tgl);
            if (!isNaN(d)) {
                const pad = n => String(n).padStart(2, '0');
                document.getElementById('tanggal').value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }
        }
        hideErr('projectError');
        document.getElementById('projectModalTitle').textContent = 'Edit Project';
        openModal('projectModal');
    } catch (e) { alert('Gagal memuat project: ' + e.message); }
};

window.editBudget = async function (kode) {
    try {
        const res  = await fetch('/api/budget', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }, credentials: 'same-origin' });
        const data = await parseJSON(res);
        const list = Array.isArray(data) ? data : (data.data || []);
        const b    = list.find(x => x.kode_budget === kode);
        if (!b) return alert('Budget tidak ditemukan: ' + kode);

        document.getElementById('budget_id').value            = b.id || '';
        document.getElementById('budget_original_kode').value = b.kode_budget;
        document.getElementById('budget_kode_project').value  = b.kode_project || '';
        document.getElementById('kode_budget').value          = b.kode_budget;
        document.getElementById('budget_deskripsi').value     = b.deskripsi || '';
        document.getElementById('budget_anggaran').value      = b.anggaran || '';

        const tgl = b.tanggal || b.created_at || '';
        if (tgl) {
            const d = new Date(tgl);
            if (!isNaN(d)) {
                const pad = n => String(n).padStart(2, '0');
                document.getElementById('budget_tanggal').value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            }
        }
        hideErr('budgetError');
        document.getElementById('budgetModalTitle').textContent = 'Edit Budget';
        await loadProjectsForBudget();
        openModal('budgetModal');
    } catch (e) { alert('Gagal memuat budget: ' + e.message); }
};

// ─────────────────────────────────────────────────────────────
// DELETE
// ─────────────────────────────────────────────────────────────

window.deleteAkun = async function (kode) {
    if (!confirm(`Hapus akun "${kode}"?`)) return;
    try {
        const res = await fetch(`/api/akun/${encodeURIComponent(kode)}`, { method: 'DELETE', headers: jsonHeaders(), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
        notify('Akun berhasil dihapus');
        await loadAkunList();
    } catch (e) { alert(e.message); }
};

let _deleteProjectKode = null;

window.deleteProject = function (kode) {
    _deleteProjectKode = kode;
    const span = document.getElementById('deleteProjectKode');
    if (span) span.textContent = kode;
    openModal('deleteProjectModal');
};

window.deleteBudget = async function (kode) {
    if (!confirm(`Hapus budget "${kode}"?`)) return;
    try {
        const res = await fetch(`/api/budget/${encodeURIComponent(kode)}`, { method: 'DELETE', headers: jsonHeaders(), credentials: 'same-origin' });
        const j   = await parseJSON(res);
        if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
        notify('Budget berhasil dihapus');
        await loadBudgets();
    } catch (e) { alert(e.message); }
};

// ─────────────────────────────────────────────────────────────
// FILTER TABLE
// ─────────────────────────────────────────────────────────────

function filterRows(tbodyId, predicate) {
    document.querySelectorAll(`#${tbodyId} tr`).forEach(row => {
        row.style.display = predicate(row) ? '' : 'none';
    });
}

// ─────────────────────────────────────────────────────────────
// DOM READY
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    // Wire save buttons
    document.getElementById('saveAkunBtn')?.addEventListener('click', saveAkun);
    document.getElementById('saveProjectBtn')?.addEventListener('click', saveProject);
    document.getElementById('saveBudgetBtn')?.addEventListener('click', saveBudget);

    // Confirm delete project
    document.getElementById('confirmDeleteProjectBtn')?.addEventListener('click', async function () {
        if (!_deleteProjectKode) return;
        this.disabled = true;
        try {
            const res = await fetch(`/api/project/${encodeURIComponent(_deleteProjectKode)}`, { method: 'DELETE', headers: jsonHeaders(), credentials: 'same-origin' });
            const j   = await parseJSON(res);
            if (!res.ok) throw new Error(j.message || 'Gagal menghapus');
            notify('Project berhasil dihapus');
            closeModal('deleteProjectModal');
            _deleteProjectKode = null;
            await loadProjects();
        } catch (e) { alert(e.message); }
        finally { this.disabled = false; }
    });

    // Filter Akun
    const filterAkun = () => {
        const q = (document.getElementById('akunSearch')?.value || '').toLowerCase();
        const j = (document.getElementById('akunJenis')?.value || '').toLowerCase();
        filterRows('akunTableBody', row => {
            const kode = row.cells[0]?.textContent.toLowerCase() || '';
            const nama = row.cells[1]?.textContent.toLowerCase() || '';
            const tipe = row.cells[2]?.textContent.toLowerCase() || '';
            return (!q || kode.includes(q) || nama.includes(q)) && (!j || tipe.includes(j));
        });
    };
    document.getElementById('akunSearch')?.addEventListener('input', filterAkun);
    document.getElementById('akunJenis')?.addEventListener('change', filterAkun);

    // Filter Project
    const filterProject = () => {
        const q    = (document.getElementById('projectSearch')?.value || '').toLowerCase();
        const from = document.getElementById('projectFrom')?.value;
        const to   = document.getElementById('projectTo')?.value;
        filterRows('projectTableBody', row => {
            const kode = row.cells[1]?.textContent.toLowerCase() || '';
            const nama = row.cells[2]?.textContent.toLowerCase() || '';
            const raw  = row.cells[3]?.textContent || '';
            const matchQ = !q || kode.includes(q) || nama.includes(q);
            if (!matchQ) return false;
            if (!from && !to) return true;
            const parts = raw.split(' ')[0].split('/');
            const dt    = parts.length === 3 ? new Date(`${parts[2]}-${parts[1]}-${parts[0]}`) : null;
            const mFrom = !from || (dt && dt >= new Date(from));
            const mTo   = !to   || (dt && dt <= new Date(to));
            return mFrom && mTo;
        });
    };
    document.getElementById('projectCariBtn')?.addEventListener('click', filterProject);
    document.getElementById('projectSearch')?.addEventListener('input', filterProject);
    document.getElementById('projectReset')?.addEventListener('click', () => {
        ['projectSearch','projectFrom','projectTo'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        filterProject();
    });
    document.getElementById('projectToday')?.addEventListener('click', () => {
        const t = new Date().toISOString().substring(0,10);
        document.getElementById('projectFrom').value = t;
        document.getElementById('projectTo').value   = t;
        filterProject();
    });

    // Filter Budget
    const filterBudget = () => {
        const q    = (document.getElementById('budgetSearch')?.value || '').toLowerCase();
        const from = document.getElementById('budgetFrom')?.value;
        const to   = document.getElementById('budgetTo')?.value;
        filterRows('budgetTableBody', row => {
            const kodeP = row.cells[1]?.textContent.toLowerCase() || '';
            const kodeB = row.cells[2]?.textContent.toLowerCase() || '';
            const matchQ = !q || kodeP.includes(q) || kodeB.includes(q);
            if (!matchQ) return false;
            if (!from && !to) return true;
            const raw   = row.cells[5]?.textContent || '';
            const parts = raw.split(' ')[0].split('/');
            const dt    = parts.length === 3 ? new Date(`${parts[2]}-${parts[1]}-${parts[0]}`) : null;
            const mFrom = !from || (dt && dt >= new Date(from));
            const mTo   = !to   || (dt && dt <= new Date(to));
            return mFrom && mTo;
        });
    };
    document.getElementById('budgetCariBtn')?.addEventListener('click', filterBudget);
    document.getElementById('budgetSearch')?.addEventListener('input', filterBudget);
    document.getElementById('budgetReset')?.addEventListener('click', () => {
        ['budgetSearch','budgetFrom','budgetTo'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        filterBudget();
    });
    document.getElementById('budgetToday')?.addEventListener('click', () => {
        const t = new Date().toISOString().substring(0,10);
        document.getElementById('budgetFrom').value = t;
        document.getElementById('budgetTo').value   = t;
        filterBudget();
    });

    // Load data sesuai halaman aktif
    const tab = window._defaultTabId || 'akun';
    if (tab === 'akun')    loadAkunList();
    if (tab === 'project') loadProjects();
    if (tab === 'budget')  loadBudgets();

    console.log('Management CRUD ready. Tab:', tab);
});
