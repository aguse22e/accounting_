@extends('layouts.app')

@section('title', 'Report')

@section('styles')
@vite(['resources/css/pages/ledger-posisi.css'])
@endsection

@section('content')
<style>
    .ledger-content-wrapper {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 1.5rem !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }
    .ledger-card {
        width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box;
    }
    .ledger-table-wrapper {
        width: 100% !important;
        overflow-x: auto !important;
        overflow-y: auto !important;
        margin: 0 !important;
        box-sizing: border-box;
    }
    .ledger-table-wrapper table {
        width: 100% !important;
        margin: 0 !important;
        min-width: 100% !important;
    }
</style>
<div class="content-wrapper ledger-content-wrapper">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-light btn-sm active" href="{{ route('ledger.posisi') }}"><i class="bi bi-diagram-3 me-1"></i>Report</a>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Balance Sheet</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('ledger.posisi') }}" class="mb-4">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <label class="form-label mb-0 me-2 fw-semibold text-dark" style="min-width: 65px; font-size: 14px;">Periode</label>
                    <input type="date" name="tanggal_dari" id="tanggal_dari" class="form-control" value="{{ $tanggal_dari }}" style="width: 150px; border-radius: 6px;">
                    <input type="date" name="tanggal_sampai" id="tanggal_sampai" class="form-control" value="{{ $tanggal_sampai }}" style="width: 150px; border-radius: 6px;">
                    <button type="submit" class="btn text-white fw-bold px-4" style="background: linear-gradient(to right, #ec4899, #a855f7); border: none; border-radius: 6px; padding: 8px 20px; white-space: nowrap;">
                        Terapkan
                    </button>
                    <div class="d-flex gap-2 ms-auto">
                        <a class="btn btn-danger" href="{{ route('ledger.posisi.export-pdf') }}?tanggal_dari={{ $tanggal_dari }}&tanggal_sampai={{ $tanggal_sampai }}" style="border-radius: 6px;">
                            <i class="bi bi-file-pdf me-1"></i> PDF
                        </a>
                        <a class="btn btn-success" href="{{ route('ledger.posisi.export-excel') }}?tanggal_dari={{ $tanggal_dari }}&tanggal_sampai={{ $tanggal_sampai }}" style="border-radius: 6px;">
                            <i class="bi bi-file-excel me-1"></i> Excel
                        </a>
                    </div>
                </div>
            </form>
            
            <div class="mb-3">
                <a href="{{ route('ledger.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>

            @php
                // Helper function untuk menghitung saldo dari semua sumber data
                function calculateSaldo($akun, $kode, $tanggal_sampai, $isCreditAccount = false) {
                    $val = 0;
                    if ($akun && method_exists($akun, 'getFreshBalance')) {
                        // Gunakan method dari model jika tersedia
                        $val = $akun->getFreshBalance('1900-01-01', $tanggal_sampai) ?? 0;
                        // Jangan gunakan abs() di sini, biarkan nilai negatif tetap negatif
                        // Logika saldo normal sudah ditangani di method getFreshBalance
                    } else {
                        // Fallback: hitung langsung dari database
                        $debit_transaksi = \DB::table('transaksi')
                            ->where('akun_kode', $kode)
                            ->whereDate('tanggal', '>=', '1900-01-01')
                            ->whereDate('tanggal', '<=', $tanggal_sampai)
                            ->sum(\DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'debit' THEN COALESCE(nominal, 0) ELSE 0 END"));
                        $kredit_transaksi = \DB::table('transaksi')
                            ->where('akun_kode', $kode)
                            ->whereDate('tanggal', '>=', '1900-01-01')
                            ->whereDate('tanggal', '<=', $tanggal_sampai)
                            ->sum(\DB::raw("CASE WHEN LOWER(COALESCE(tipe, '')) = 'kredit' THEN COALESCE(nominal, 0) ELSE 0 END"));

                        // Hitung dari jurnal
                        $debit_jurnal = \DB::table('jurnal')
                            ->where(function($q) use ($kode) {
                                $q->where('kode', $kode)->where('tipe', 'Debit');
                            })
                            ->orWhere(function($q) use ($kode) {
                                $q->where('lawan', $kode)->where('tipe', 'Kredit');
                            })
                            ->whereBetween('tanggal', ['1900-01-01', $tanggal_sampai])
                            ->where(function($q) {
                                $q->where('status', 'Posted')->orWhereNull('status')->orWhere('status', '');
                            })
                            ->whereNull('reversed_at')
                            ->sum('nominal');
                        $kredit_jurnal = \DB::table('jurnal')
                            ->where(function($q) use ($kode) {
                                $q->where('kode', $kode)->where('tipe', 'Kredit');
                            })
                            ->orWhere(function($q) use ($kode) {
                                $q->where('lawan', $kode)->where('tipe', 'Debit');
                            })
                            ->whereBetween('tanggal', ['1900-01-01', $tanggal_sampai])
                            ->where(function($q) {
                                $q->where('status', 'Posted')->orWhereNull('status')->orWhere('status', '');
                            })
                            ->whereNull('reversed_at')
                            ->sum('nominal');

                        // Hitung dari transaksi_bank
                        $debit_bank = \DB::table('transaksi_bank')
                            ->where('akun_kode', $kode)
                            ->whereDate('tanggal', '>=', '1900-01-01')
                            ->whereDate('tanggal', '<=', $tanggal_sampai)
                            ->where(\DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
                            ->whereNull('deleted_at')
                            ->sum(\DB::raw('COALESCE(total, 0)'));
                        $kredit_bank = \DB::table('transaksi_bank')
                            ->where('akun_kode', $kode)
                            ->whereDate('tanggal', '>=', '1900-01-01')
                            ->whereDate('tanggal', '<=', $tanggal_sampai)
                            ->where(\DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
                            ->whereNull('deleted_at')
                            ->sum(\DB::raw('COALESCE(total, 0)'));

                        // transaksi_bank_detail removed - kode_lawan column no longer exists
                        $debit_bank_detail = 0;
                        $kredit_bank_detail = 0;

                        $debit_total = ($debit_transaksi ?? 0) + ($debit_jurnal ?? 0) + ($debit_bank ?? 0) + ($debit_bank_detail ?? 0);
                        $kredit_total = ($kredit_transaksi ?? 0) + ($kredit_jurnal ?? 0) + ($kredit_bank ?? 0) + ($kredit_bank_detail ?? 0);

                        if ($isCreditAccount) {
                            // Untuk akun kredit (liabilitas/ekuitas/pendapatan), saldo = kredit - debit
                            $val = ($kredit_total ?? 0) - ($debit_total ?? 0);
                        } else {
                            // Untuk akun debit (aset/beban), saldo = debit - kredit
                            $val = ($debit_total ?? 0) - ($kredit_total ?? 0);
                        }
                    }

                    // Jika nilai 0 atau negatif, return 0 (tanpa dummy data)
                    if ($val <= 0) {
                        return 0;
                    }

                    return $val;
                }
                
                // Initialize total variables
                $total_lancar = 0;
                $total_tetap = 0;
                $total_lain = 0;
                $total_liab = 0;
                $total_eku = 0;
                
                // Initialize display variables
                $total_lancar_display = 0;
                $total_tetap_display = 0;
                $total_lain_display = 0;
                $total_liab_display = 0;
                $total_eku_display = 0;
                
                // Catatan: Struktur data selalu ditampilkan meskipun nilai 0
                // Ini untuk memberikan informasi lengkap tentang struktur neraca keuangan
                
                // Calculate aset lancar - semua nilai dihitung untuk tampilan struktur
                foreach($accountbased['aset_lancar'] as $kode => $nama) {
                    $akun = $aset_lancar->get($kode) ?? $aset_lancar->where('kode_akun', $kode)->first();
                    $val = calculateSaldo($akun, $kode, $tanggal_sampai, false);
                    $total_lancar += $val;
                }
                
                // Calculate aset tetap - semua nilai dihitung untuk tampilan struktur
                foreach($accountbased['aset_tetap'] as $kode => $nama) {
                    $akun = $aset_tetap->get($kode) ?? $aset_tetap->where('kode_akun', $kode)->first();
                    // 1299 adalah akun kredit (Akumulasi Penyusutan)
                    $isCredit = ($kode === '1299');
                    $val = calculateSaldo($akun, $kode, $tanggal_sampai, $isCredit);
                    $total_tetap += $val;
                }
                
                // Calculate aset lainnya - semua nilai dihitung untuk tampilan struktur
                foreach($accountbased['aset_lainnya'] as $kode => $nama) {
                    $akun = $aset_lainnya->get($kode) ?? $aset_lainnya->where('kode_akun', $kode)->first();
                    $val = calculateSaldo($akun, $kode, $tanggal_sampai, false);
                    $total_lain += $val;
                }
                
                // Calculate liabilitas (credit accounts) - semua nilai dihitung untuk tampilan struktur
                foreach($accountbased['liabilitas'] as $kode => $nama) {
                    $akun = $liabilitas->get($kode) ?? $liabilitas->where('kode_akun', $kode)->first();
                    $val = calculateSaldo($akun, $kode, $tanggal_sampai, true);
                    $total_liab += $val;
                }
                
                // Calculate ekuitas (credit accounts) - semua nilai dihitung untuk tampilan struktur
                foreach($accountbased['ekuitas'] as $kode => $nama) {
                    $akun = $ekuitas->get($kode) ?? $ekuitas->where('kode_akun', $kode)->first();
                    $val = calculateSaldo($akun, $kode, $tanggal_sampai, true);
                    $total_eku += $val;
                }
            @endphp
            
            {{-- Struktur data selalu ditampilkan sesuai rule mapping --}}
            <h6 class="text-muted">I. ASET</h6>
            
            <h6 class="mt-3">A. Aset Lancar</h6>
            <table class="table table-sm">
                <thead><tr><th>Keterangan</th><th class="text-end">Nilai (Rp)</th></tr></thead>
                <tbody>
                @php 
                    // Recalculate untuk tampilan tabel
                    $total_lancar_display = 0; 
                @endphp
                @foreach($accountbased['aset_lancar'] as $kode => $nama)
                    @php 
                        $akun = $aset_lancar->get($kode);
                        // Pastikan $akun ada, jika tidak ada di database, nilai akan 0
                        if (!$akun) {
                            $akun = null; // Set null untuk menghindari error
                        }
                        // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan calculateSaldo
                        if (isset($use_rules) && $use_rules && $akun && isset($akun->rule_value)) {
                            $val = $akun->rule_value;
                        } else {
                            $val = calculateSaldo($akun, $kode, $tanggal_sampai, false);
                        }
                        $total_lancar_display += $val; 
                    @endphp
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="text-end">{{ number_format($val, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                    <tr class="table-light">
                        <th>Total Aset Lancar</th>
                        <th class="text-end">
                            @if(isset($use_rules) && $use_rules && isset($rule_values['aset_lancar']['total']))
                                {{ number_format($rule_values['aset_lancar']['total'], 2, ',', '.') }}
                            @else
                                {{ number_format($total_lancar_display, 2, ',', '.') }}
                            @endif
                        </th>
                    </tr>
                </tbody>
            </table>

            <h6 class="mt-3">B. Aset Tetap</h6>
            <table class="table table-sm">
                <thead><tr><th>Keterangan</th><th class="text-end">Nilai (Rp)</th></tr></thead>
                <tbody>
                @php 
                    // Recalculate untuk tampilan tabel
                    $total_tetap_display = 0; 
                @endphp
                @foreach($accountbased['aset_tetap'] as $kode => $nama)
                    @php 
                        $akun = $aset_tetap->get($kode);
                        // Pastikan $akun ada, jika tidak ada di database, nilai akan 0
                        if (!$akun) {
                            $akun = null;
                        }
                        // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan calculateSaldo
                        if (isset($use_rules) && $use_rules && $akun && isset($akun->rule_value)) {
                            $val = $akun->rule_value;
                        } else {
                            $isCredit = ($kode === '1299');
                            $val = calculateSaldo($akun, $kode, $tanggal_sampai, $isCredit);
                        }
                        $total_tetap_display += $val; 
                    @endphp
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="text-end">{{ number_format($val, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                    <tr class="table-light">
                        <th>Total Aset Tetap</th>
                        <th class="text-end">
                            @if(isset($use_rules) && $use_rules && isset($rule_values['aset_tetap']['total']))
                                {{ number_format($rule_values['aset_tetap']['total'], 2, ',', '.') }}
                            @else
                                {{ number_format($total_tetap_display, 2, ',', '.') }}
                            @endif
                        </th>
                    </tr>
                </tbody>
            </table>

            <h6 class="mt-3">C. Aset Lainnya</h6>
            <table class="table table-sm">
                <thead><tr><th>Keterangan</th><th class="text-end">Nilai (Rp)</th></tr></thead>
                <tbody>
                @php 
                    // Recalculate untuk tampilan tabel
                    $total_lain_display = 0; 
                @endphp
                @foreach($accountbased['aset_lainnya'] as $kode => $nama)
                    @php 
                        $akun = $aset_lainnya->get($kode);
                        // Pastikan $akun ada, jika tidak ada di database, nilai akan 0
                        if (!$akun) {
                            $akun = null;
                        }
                        // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan calculateSaldo
                        if (isset($use_rules) && $use_rules && $akun && isset($akun->rule_value)) {
                            $val = $akun->rule_value;
                        } else {
                            $val = calculateSaldo($akun, $kode, $tanggal_sampai, false);
                        }
                        $total_lain_display += $val; 
                    @endphp
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="text-end">{{ number_format($val, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                    <tr class="table-light">
                        <th>Total Aset Lainnya</th>
                        <th class="text-end">
                            @if(isset($use_rules) && $use_rules && isset($rule_values['aset_lainnya']))
                                {{ number_format($rule_values['aset_lainnya'], 2, ',', '.') }}
                            @else
                                {{ number_format($total_lain_display, 2, ',', '.') }}
                            @endif
                        </th>
                    </tr>
                </tbody>
            </table>

            @php 
                if(isset($use_rules) && $use_rules && isset($rule_values['total_aset']['total'])) {
                    $total_aset = $rule_values['total_aset']['total'];
                } else {
                    $total_aset = $total_lancar + $total_tetap + $total_lain;
                }
            @endphp
            <div class="alert alert-secondary">TOTAL ASET: <strong>Rp {{ number_format($total_aset, 2, ',', '.') }}</strong></div>

            <h6 class="text-muted mt-4">II. LIABILITAS</h6>
            <table class="table table-sm">
                <thead><tr><th>Keterangan</th><th class="text-end">Nilai (Rp)</th></tr></thead>
                <tbody>
                @php 
                    // Recalculate untuk tampilan tabel
                    $total_liab_display = 0; 
                @endphp
                @foreach($accountbased['liabilitas'] as $kode => $nama)
                    @php 
                        $akun = $liabilitas->get($kode);
                        // Pastikan $akun ada, jika tidak ada di database, nilai akan 0
                        if (!$akun) {
                            $akun = null;
                        }
                        // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan calculateSaldo
                        if (isset($use_rules) && $use_rules && $akun && isset($akun->rule_value)) {
                            $val = $akun->rule_value;
                        } else {
                            $val = calculateSaldo($akun, $kode, $tanggal_sampai, true);
                        }
                        $total_liab_display += $val; 
                    @endphp
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="text-end">{{ number_format($val, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                    <tr class="table-light">
                        <th>Total Liabilitas</th>
                        <th class="text-end">
                            @if(isset($use_rules) && $use_rules && isset($rule_values['liabilitas']))
                                {{ number_format($rule_values['liabilitas'], 2, ',', '.') }}
                            @else
                                {{ number_format($total_liab_display, 2, ',', '.') }}
                            @endif
                        </th>
                    </tr>
                </tbody>
            </table>

            <h6 class="text-muted mt-4">III. EKUITAS</h6>
            <table class="table table-sm">
                <thead><tr><th>Keterangan</th><th class="text-end">Nilai (Rp)</th></tr></thead>
                <tbody>
                @php 
                    // Recalculate untuk tampilan tabel
                    $total_eku_display = 0; 
                @endphp
                @foreach($accountbased['ekuitas'] as $kode => $nama)
                    @php 
                        $akun = $ekuitas->get($kode);
                        // Pastikan $akun ada, jika tidak ada di database, nilai akan 0
                        if (!$akun) {
                            $akun = null;
                        }
                        // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan calculateSaldo
                        if (isset($use_rules) && $use_rules && $akun && isset($akun->rule_value)) {
                            $val = $akun->rule_value;
                        } else {
                            $val = calculateSaldo($akun, $kode, $tanggal_sampai, true);
                        }
                        $total_eku_display += $val; 
                    @endphp
                    <tr>
                        <td>{{ $nama }}</td>
                        <td class="text-end">{{ number_format($val, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                    <tr class="table-light">
                        <th>Total Ekuitas</th>
                        <th class="text-end">
                            @if(isset($use_rules) && $use_rules && isset($rule_values['ekuitas']))
                                {{ number_format($rule_values['ekuitas'], 2, ',', '.') }}
                            @else
                                {{ number_format($total_eku_display, 2, ',', '.') }}
                            @endif
                        </th>
                    </tr>
                </tbody>
            </table>

            @php 
                if(isset($use_rules) && $use_rules && isset($rule_values['total_liabilitas_dan_ekuitas'])) {
                    $tle = $rule_values['total_liabilitas_dan_ekuitas'];
                } else {
                    $tle = $total_liab_display + $total_eku_display;
                }
            @endphp
            <div class="alert alert-secondary">TOTAL LIABILITAS DAN EKUITAS: <strong>Rp {{ number_format($tle, 2, ',', '.') }}</strong></div>
            @if(isset($use_rules) && $use_rules && isset($rule_values['is_balanced']))
                @if(!$rule_values['is_balanced'])
                    <div class="alert alert-warning">
                        <strong>⚠ Peringatan:</strong> Neraca tidak seimbang.<br>
                        Total Aset: Rp {{ number_format($rule_values['total_aset']['total'] ?? 0, 2, ',', '.') }}<br>
                        Total Liabilitas & Ekuitas: Rp {{ number_format($rule_values['total_liabilitas_dan_ekuitas'] ?? 0, 2, ',', '.') }}<br>
                        Selisih: Rp {{ number_format($rule_values['selisih'] ?? 0, 2, ',', '.') }}
                        @if(isset($rule_values['balance_warning']))
                            <br><small>{{ $rule_values['balance_warning'] }}</small>
                        @endif
                    </div>
                @else
                    <div class="alert alert-success">
                        <strong>✓ Neraca Seimbang</strong>
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    <strong>ℹ Informasi:</strong> Validasi keseimbangan neraca tidak tersedia. Pastikan data transaksi sudah lengkap dan saldo normal akun sudah dikonfigurasi dengan benar.
                </div>
            @endif
        </div>
    </div>
</div>

@section('scripts')
@vite(['resources/js/pages/ledger-posisi.js'])
@endsection
@endsection

