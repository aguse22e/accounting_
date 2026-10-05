@extends('layouts.app')

@section('title', 'Activity User')

@section('styles')
@vite(['resources/css/pages/activity-user.css'])
@endsection

@section('content')
<div class="content-wrapper p-4">
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-12 mb-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="totalActivities">-</h4>
                                    <small>Total Aktivitas</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-activity fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="todayActivities">-</h4>
                                    <small>Hari Ini</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-day fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="weekActivities">-</h4>
                                    <small>Minggu Ini</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-week fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="monthActivities">-</h4>
                                    <small>Bulan Ini</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-month fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-funnel me-2"></i>Filter Aktivitas
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('activity-user.index') }}" class="row g-3 align-items-end" id="filter-form">
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                                <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise me-1"></i>Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Activity List -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-list-ul me-2"></i>Daftar Aktivitas User
                    </h6>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="refreshActivities()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="activities-table">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                    <th>Model</th>
                                    <th>ID Record</th>
                                    <th>Data Lama</th>
                                    <th>Data Baru</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody id="activities-tbody">
                                @forelse($activities as $index => $activity)
                                    <tr>
                                        <td>{{ $activities->firstItem() + $index }}</td>
                                        <td>
                                            <div>
                                                <div class="fw-bold">{{ $activity->user->User_name ?? 'Unknown User' }}</div>
                                                <small class="text-muted">{{ $activity->user->email ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $activity->action == 'CREATE' ? 'success' : ($activity->action == 'UPDATE' ? 'warning' : 'danger') }}">
                                                {{ $activity->action }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $activity->model_name }}</span>
                                        </td>
                                        <td>{{ $activity->model_id }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" 
                                                data-payload='@json($activity->old_data ?? null)' 
                                                data-alternate-payload='@json($activity->new_data ?? null)'
                                                onclick="showDataModalFromButton(this)">
                                                <i class="bi bi-eye me-1"></i>Lihat
                                            </button>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success" 
                                                data-payload='@json($activity->new_data ?? null)' 
                                                data-alternate-payload='@json($activity->old_data ?? null)'
                                                onclick="showDataModalFromButton(this)">
                                                <i class="bi bi-eye me-1"></i>Lihat
                                            </button>
                                        </td>
                                        
                                        <td>
                                            <div>
                                                <div class="fw-bold">{{ $activity->created_at->format('d/m/Y') }}</div>
                                                <small class="text-muted">{{ $activity->created_at->format('H:i:s') }}</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                Tidak ada aktivitas yang tercatat
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Modal -->
<div class="modal fade" id="dataModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataModalTitle">Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="dataModalContent" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
window.activityUserIndexRoute = '{{ route("activity-user.index") }}';
window.activityUserStatsRoute = '{{ route("activity-user.statistics") }}';
</script>
@vite(['resources/js/pages/activity-user.js'])
@endsection
