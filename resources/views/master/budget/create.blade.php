@extends('layouts.app')

@section('title', 'Create Budget')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Create Budget</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('budget.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="kode_project" class="form-label">Project</label>
                    <select class="form-control" id="kode_project" name="kode_project" required>
                        <option value="">Pilih Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->kode_project }}">{{ $project->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('budget.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
