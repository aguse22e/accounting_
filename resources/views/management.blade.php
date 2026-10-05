@extends('layouts.app')

@section('title', 'Management')

@section('styles')
@vite(['resources/css/pages/management.css'])
@endsection

@section('content')
<div class="content-wrapper p-4">
    @php
        $routeName = optional(request()->route())->getName();
        $tabMap = [
            'master.akun' => 'akun',
            'master.project' => 'project',
            'master.budget' => 'budget',
        ];
        $defaultTabId = $tabMap[$routeName] ?? 'akun';
    @endphp

    <div class="tab-content" id="managementTabsContent">

        {{-- AKUN TAB --}}
        <div class="tab-pane fade {{ $defaultTabId === 'akun' ? 'show active' : '' }}" id="akun" role="tabpanel">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-list-ul me-2"></i>MASTER AKUN
                    </h6>
                    <button class="btn btn-primary btn-sm" onclick="showAkunForm()" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">Cari</label>
                            <input type="text" id="akunSearch" class="form-control" placeholder="Kode/Nama Akun">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label mb-1">Jenis</label>
                            <select id="akunJenis" class="form-select">
                                <option value="">Semua</option>
                                <option value="debit">Debit</option>
                                <option value="kredit">Kredit</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="akunTable">
                            <thead>
                                <tr>
                                    <th>Kode Akun</th>
                                    <th>Nama Akun</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="akunTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- PROJECT TAB --}}
        <div class="tab-pane fade {{ $defaultTabId === 'project' ? 'show active' : '' }}" id="project" role="tabpanel">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-briefcase me-2"></i>MASTER PROJECT
                    </h6>
                    <button class="btn btn-primary btn-sm" onclick="showProjectForm()" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1">Cari</label>
                            <input type="text" id="projectSearch" class="form-control" placeholder="Kode/Nama Project">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Tanggal Dari</label>
                            <input type="date" id="projectFrom" class="form-control">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Tanggal Sampai</label>
                            <input type="date" id="projectTo" class="form-control">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1">&nbsp;</label>
                            <button class="btn btn-primary w-100" id="projectCariBtn" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary flex-fill" id="projectToday">
                                    <i class="bi bi-calendar-event me-1"></i> Hari ini
                                </button>
                                <button class="btn btn-outline-secondary flex-fill" id="projectReset">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="projectTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode Project</th>
                                    <th>Nama Project</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="projectTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- BUDGET TAB --}}
        <div class="tab-pane fade {{ $defaultTabId === 'budget' ? 'show active' : '' }}" id="budget" role="tabpanel">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-cash-stack me-2"></i>MASTER BUDGET
                    </h6>
                    <button class="btn btn-primary btn-sm" onclick="showBudgetForm()" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Data
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1">Cari</label>
                            <input type="text" id="budgetSearch" class="form-control" placeholder="Kode Project/Budget">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Tanggal Dari</label>
                            <input type="date" id="budgetFrom" class="form-control">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label mb-1">Tanggal Sampai</label>
                            <input type="date" id="budgetTo" class="form-control">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1">&nbsp;</label>
                            <button class="btn btn-primary w-100" id="budgetCariBtn" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35);">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary flex-fill" id="budgetToday">
                                    <i class="bi bi-calendar-event me-1"></i> Hari ini
                                </button>
                                <button class="btn btn-outline-secondary flex-fill" id="budgetReset">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="budgetTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode Project</th>
                                    <th>Kode Budget</th>
                                    <th>Deskripsi</th>
                                    <th>Anggaran</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="budgetTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}
</div>{{-- end content-wrapper --}}

{{-- ===== MODALS ===== --}}

<!-- Modal Project -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalTitle">Tambah Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm">
                    <input type="hidden" id="project_id">
                    <input type="hidden" id="project_original_kode">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_project" required placeholder="Misal: PRJ001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_project" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal & Waktu</label>
                            <input type="datetime-local" class="form-control" id="tanggal">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Kode Akun (Opsional)</label>
                            <input class="form-control" id="project_kode_akun" list="akun_datalist" placeholder="Ketik kode atau nama akun">
                            <datalist id="akun_datalist"></datalist>
                        </div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mt-3" id="projectError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveProjectBtn">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Delete Project -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus project ini?</p>
                <p><strong>Kode Project:</strong> <span id="deleteProjectKode" class="text-danger"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProjectBtn">
                    <i class="bi bi-trash me-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Budget -->
<div class="modal fade" id="budgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="budgetModalTitle">Tambah Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="budgetForm">
                    <input type="hidden" id="budget_id">
                    <input type="hidden" id="budget_original_kode">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="budget_kode_project" required list="budget_project_datalist" placeholder="Misal: PRJ001">
                            <datalist id="budget_project_datalist"></datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kode Budget <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_budget" required placeholder="Misal: BGT001">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="budget_deskripsi" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Anggaran <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="budget_anggaran" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="budget_tanggal">
                        </div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mt-3" id="budgetError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveBudgetBtn">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Akun -->
<div class="modal fade" id="akunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="akunModalTitle">Tambah Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="akunForm">
                    <input type="hidden" id="akun_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_akun" required
                                   pattern="^[0-9]{1,4}$" maxlength="4" inputmode="numeric"
                                   placeholder="Angka, maks 4 digit">
                            <div class="form-text">Angka saja, maksimal 4 digit, unik.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_akun" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                            <select class="form-select" id="saldo_normal" required>
                                <option value="">-- Pilih --</option>
                                <option value="Debit">Debit</option>
                                <option value="Kredit">Kredit</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal_akun">
                        </div>
                    </div>
                </form>
                <div class="alert alert-danger d-none mt-3" id="akunError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveAkunBtn">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- defaultTabId untuk JS --}}
<script>
    window._defaultTabId = '{{ $defaultTabId }}';
</script>

@endsection

@section('scripts')
<script src="{{ asset('js/management-crud.js') }}"></script>
@endsection
