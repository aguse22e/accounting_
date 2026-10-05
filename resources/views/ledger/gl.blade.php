@extends('layouts.app')

@section('title','General Ledger Detail')

@section('styles')
@vite(['resources/css/pages/ledger-gl.css'])
@endsection

@section('scripts')
@vite(['resources/js/pages/ledger-gl.js'])
@endsection

@section('content')
<div class="content-wrapper ledger-content-wrapper">
    <div class="card">
        <div class="card-header">
            <form id="formGL" class="row g-2 align-items-center" method="GET" action="{{ route('ledger.gl') }}">
                <div class="col-auto"><label class="col-form-label">Periode</label></div>
                <div class="col-auto"><input type="date" class="form-control" name="from" id="from" value="{{ $from }}"></div>
                <div class="col-auto"><input type="date" class="form-control" name="to" id="to" value="{{ $to }}"></div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Terapkan
                    </button>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm" onclick="exportGL('pdf')"><i class="bi bi-file-pdf me-1"></i> PDF</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportGL('excel')"><i class="bi bi-file-excel me-1"></i> Excel</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            @php 
                $lastAccount = null; 
                $running = 0; 
                $isFirstRow = true;
            @endphp
            <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>No. Transaksi</th>
                        <th>No. Project</th>
                        <th>Kode Akun</th>
                        <th>Nama Akun</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    @php
                        // Ambil kode akun untuk tracking per akun
                        $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? 'N/A';
                        
                        // Reset running balance jika pindah ke akun baru
                        if ($lastAccount !== $akunKode) { 
                            $running = 0; 
                            $lastAccount = $akunKode;
                            $isFirstRow = true;
                        }
                        
                        // Tentukan debit dan kredit
                        $debit = 0;
                        $kredit = 0;
                        
                        // Prioritaskan kolom debit/kredit jika ada
                        if (isset($row->debit) && $row->debit > 0) {
                            $debit = (float)$row->debit;
                        } elseif (isset($row->kredit) && $row->kredit > 0) {
                            $kredit = (float)$row->kredit;
                        } else {
                            // Fallback ke jenis/transaksi_type
                            $jenis = $row->jenis ?? strtolower($row->transaksi_type ?? 'debit');
                            $jumlah = $row->jumlah ?? $row->nominal ?? 0;
                            if ($jenis === 'debit') {
                                $debit = (float)$jumlah;
                            } else {
                                $kredit = (float)$jumlah;
                            }
                        }
                        
                        // Saldo baris 1 = nilai pertama (debit +, kredit -)
                        // Saldo baris berikutnya = saldo sebelumnya + debit - kredit
                        if ($isFirstRow) {
                            // Baris pertama untuk akun ini: saldo = debit - kredit
                            $running = $debit - $kredit;
                            $isFirstRow = false;
                        } else {
                            // Baris berikutnya: saldo = saldo sebelumnya + debit - kredit
                            $running = $running + $debit - $kredit;
                        }
                        
                        // Get akun code and name with multiple fallbacks
                        $akunNama = optional($row->akun)->nama_akun;
                        
                        // If nama_akun is null but akun_kode exists, try to find from database
                        if (!$akunNama && $akunKode && $akunKode !== 'N/A') {
                            $akunFromDb = \App\Models\Akun::where('kode_akun', $akunKode)->first();
                            $akunNama = $akunFromDb ? $akunFromDb->nama_akun : 'N/A';
                        } else {
                            $akunNama = $akunNama ?? 'N/A';
                        }
                    @endphp
                    <tr>
                        <td>{{ optional($row->tanggal)->format('d M Y') ?? (\Carbon\Carbon::parse($row->tanggal)->format('d M Y')) }}</td>
                        <td>{{ $row->kode_voucer }}</td>
                        <td>{{ $row->kode_project }}</td>
                        <td>{{ $akunKode }}</td>
                        <td>{{ $akunNama }}</td>
                        <td>{{ $row->deskripsi }}</td>
                        <td class="text-end">{{ $debit ? number_format($debit,2,',','.') : '' }}</td>
                        <td class="text-end">{{ $kredit ? number_format($kredit,2,',','.') : '' }}</td>
                        <td class="text-end">{{ number_format($running,2,',','.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

@endsection


