@extends('layouts.app')

@section('title', 'View Budget')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>View Budget</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">No. Project</dt>
                <dd class="col-sm-9">{{ $budget->kode_budget }}</dd>

                <dt class="col-sm-3">Deskripsi</dt>
                <dd class="col-sm-9">{{ $budget->deskripsi }}</dd>

                <dt class="col-sm-3">Project</dt>
                <dd class="col-sm-9">{{ $budget->project->nama_project ?? $budget->kode_project }}</dd>


                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($budget->tanggal)->format('d/m/Y H:i:s') }}</dd>
            </dl>
            <a href="{{ route('budget.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
@endsection
