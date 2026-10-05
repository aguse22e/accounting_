@extends('layouts.app')

@section('title', 'Import Ledger Data')

@section('styles')
@vite(['resources/css/pages/ledger-import.css'])
@endsection

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3>Import Ledger Data</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('ledger.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Select Excel or CSV file</label>
                    <input type="file" class="form-control" id="file" name="file" required>
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>

            @if(session('imported_data'))
                <hr>
                <h4>Imported Data Preview</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            @foreach(array_keys(session('imported_data')[0]) as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('imported_data') as $row)
                            <tr>
                                @foreach($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

@section('scripts')
@vite(['resources/js/pages/ledger-import.js'])
@endsection
