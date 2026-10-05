@extends('layouts.app')

@section('title','')

@section('styles')
@vite(['resources/css/pages/profile.css'])
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}" id="profileForm">
                        @csrf
                        <div class="mb-3 text-start">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $user->User_name) }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="saveProfileBtn">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-3">
        <a href="{{ route('settings') }}" class="btn btn-primary">Pengaturan</a>
    </div>
</div>

@section('scripts')
@vite(['resources/js/pages/profile.js'])
@endsection


