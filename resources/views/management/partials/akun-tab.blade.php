<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="bi bi-list-ul me-2"></i>MASTER AKUN
        </h6>
        <div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#akunModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Data
            </button>
        </div>
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