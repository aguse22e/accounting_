@extends('layouts.app')

@section('title','Pengaturan Akun')

@section('styles')
@vite(['resources/css/pages/settings.css'])
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="card">
                <div class="card-header">Pengaturan Akun</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $user->User_name) }}" required>
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" placeholder="Kosongkan bila tidak ganti">
                            @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Ulangi password baru">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-2">
        <a href="{{ route('profile') }}" class="text-muted">Kembali ke Profil</a>
    </div>
</div>

@section('scripts')
@vite(['resources/js/pages/settings.js'])
@endsection


