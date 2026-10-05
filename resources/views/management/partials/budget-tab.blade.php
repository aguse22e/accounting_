<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-cash-stack me-2"></i>MASTER BUDGET
        </h6>
        <div>
            <button class="btn btn-primary btn-sm" onclick="showBudgetForm()">
                <i class="bi bi-plus-circle me-1"></i>Tambah Data
            </button>
        </div>
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
                <button class="btn btn-primary w-100" id="budgetCariBtn" style="background: linear-gradient(135deg, #8B5CF6, #EC4899, #3B82F6); border: 0; box-shadow: 0 8px 20px rgba(139,92,246,.35); white-space: nowrap;">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary flex-fill" id="budgetToday" style="background-color: #fff; border-color: #d0d0d0; color: #333; border-radius: 6px; white-space: nowrap;">
                        <i class="bi bi-calendar-event me-1"></i> Hari ini
                    </button>
                    <button class="btn btn-outline-secondary flex-fill" id="budgetReset" style="background-color: #fff; border-color: #d0d0d0; color: #333; border-radius: 6px; white-space: nowrap;">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="budgetTable">
                <thead>
                    <tr>
                        <th>Kode Project</th>
                        <th>Deskripsi</th>
                        <th>Kode Budget</th>
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