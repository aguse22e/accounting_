<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca Keuangan PDF</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            margin: 0;
            padding: 20px;
        }
        .letterhead { 
            text-align: center; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .letterhead h1 { 
            margin: 0; 
            font-size: 20px; 
            font-weight: bold;
            color: #333;
        }
        .letterhead h2 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
            color: #666;
        }
        .letterhead .organization-info {
            font-size: 11px;
            color: #666;
            margin: 5px 0;
        }
        .letterhead .meta { 
            font-size: 11px; 
            margin: 3px 0;
            color: #333;
        }
        .location-date {
            text-align: right;
            margin-bottom: 15px;
            font-size: 11px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background: #f5f5f5; 
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .category-header {
            background: #d3d3d3;
            font-weight: bold;
            font-size: 13px;
        }
        .subcategory-header {
            background: #f0f0f0;
            font-weight: bold;
        }
        .total-row {
            background: #e6e6e6;
            font-weight: bold;
        }
        .grand-total {
            background: #c0c0c0;
            font-weight: bold;
            font-size: 13px;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            padding: 10px;
            text-align: center;
            vertical-align: top;
        }
        .signature-space {
            height: 60px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>ORGANISASI PEREMPUAN MAHARDIKA</h1>
        <h2>NERACA KEUANGAN</h2>
        <div class="organization-info">
            Alamat: Jl. Contoh Alamat No. 123, Kota, Provinsi<br>
            Telp: (021) 1234-5678 | Email: info@mahardika.org
        </div>
        <div class="meta">No. Dokumen: {{ $serial ?? ('NK-' . date('YmdHis')) }}</div>
        <div class="meta">Periode: {{ $periodStr ?? (date('d M Y', strtotime($tanggal_dari)) . ' s/d ' . date('d M Y', strtotime($tanggal_sampai))) }}</div>
    </div>
    <div class="location-date">
        <strong>{{ ucfirst(\Carbon\Carbon::now()->locale('id')->isoFormat('dddd, DD MMMM YYYY')) }}</strong>
    </div>

    @php
        // Use data passed from controller, or define defaults if not passed
        $accountbased = $accountbased ?? [
            'aset_lancar' => [
                '1101' => 'Kas dan Setara Kas',
                '1102' => 'Piutang Usaha', 
                '1103' => 'Persediaan',
                '1104' => 'Biaya Dibayar Dimuka',
                '1105' => 'Aset Lancar Lainnya'
            ],
            'aset_tetap' => [
                '1201' => 'Tanah',
                '1202' => 'Bangunan',
                '1203' => 'Mesin dan Peralatan',
                '1204' => 'Kendaraan',
                '1299' => 'Akumulasi Penyusutan Aset Tetap'
            ],
            'aset_lainnya' => [
                '1301' => 'Investasi Jangka Panjang',
                '1302' => 'Aset Tak Berwujud (Goodwill, Hak Paten)',
                '1303' => 'Aset Lainnya'
            ],
            'liabilitas' => [
                '2101' => 'Utang Usaha',
                '2102' => 'Utang Pajak',
                '2103' => 'Utang Jangka Panjang',
                '2104' => 'Beban yang Masih Harus Dibayar'
            ],
            'ekuitas' => [
                '3101' => 'Modal Saham',
                '3102' => 'Laba Ditahan',
                '3103' => 'Ekuitas Lainnya'
            ]
        ];
        
        // Use passed collections or query if not provided
        $asetLancar = $aset_lancar ?? collect();
        $asetTetap = $aset_tetap ?? collect();
        $asetLainnya = $aset_lainnya ?? collect();
        $liabilitas = $liabilitas ?? collect();
        $ekuitas = $ekuitas ?? collect();
    @endphp

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-right">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- I. ASET -->
            <tr class="category-header">
                <td colspan="2">I. ASET</td>
            </tr>
            
            <!-- A. Aset Lancar -->
            <tr class="subcategory-header">
                <td>A. Aset Lancar</td>
                <td></td>
            </tr>
            @php 
                $total_lancar = 0;
                $tanggalAkhir = $tanggal_sampai ?? ($tanggal_dari ?? date('Y-m-d'));
            @endphp
            @foreach($accountbased['aset_lancar'] as $kode => $nama)
                @php 
                    $akun_data = $asetLancar->get($kode) ?? $asetLancar->where('kode_akun', $kode)->first();
                    // Gunakan rule_value jika tersedia (dari rules), jika tidak gunakan getFreshBalance
                    if (isset($use_rules) && $use_rules && $akun_data && isset($akun_data->rule_value)) {
                        $val = $akun_data->rule_value;
                    } else {
                        $val = 0;
                        if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                            $val = $akun_data->getFreshBalance('1900-01-01', $tanggalAkhir) ?? 0;
                            if (isset($akun_data->tipe) && $akun_data->tipe === 'Kredit') {
                                $val = abs($val);
                            }
                        }
                        // Ensure positive values for assets
                        $val = $val > 0 ? $val : 0;
                    }
                    $total_lancar += $val; 
                @endphp
                @if($val > 0)
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $nama }}</td>
                    <td class="text-right">{{ number_format($val, 2, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Aset Lancar</strong></td>
                <td class="text-right">
                    <strong>
                        @if(isset($use_rules) && $use_rules && isset($rule_values['aset_lancar']['total']))
                            {{ number_format($rule_values['aset_lancar']['total'], 2, ',', '.') }}
                        @else
                            {{ number_format($total_lancar, 2, ',', '.') }}
                        @endif
                    </strong>
                </td>
            </tr>

            <!-- B. Aset Tetap -->
            <tr class="subcategory-header">
                <td>B. Aset Tetap</td>
                <td></td>
            </tr>
            @php 
                $total_tetap = 0;
            @endphp
            @foreach($accountbased['aset_tetap'] as $kode => $nama)
                @php 
                    $akun_data = $asetTetap->get($kode) ?? $asetTetap->where('kode_akun', $kode)->first();
                    $val = 0;
                    if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                        $val = $akun_data->getFreshBalance('1900-01-01', $tanggalAkhir) ?? 0;
                        if ($kode === '1299' || (isset($akun_data->tipe) && $akun_data->tipe === 'Kredit')) {
                            $val = abs($val);
                        }
                    }
                    // Ensure positive values for assets
                    $val = $val > 0 ? $val : 0;
                    $total_tetap += $val; 
                @endphp
                @if($val > 0)
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $nama }}</td>
                    <td class="text-right">{{ number_format($val, 2, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Aset Tetap</strong></td>
                <td class="text-right"><strong>{{ number_format($total_tetap, 2, ',', '.') }}</strong></td>
            </tr>

            <!-- C. Aset Lainnya -->
            <tr class="subcategory-header">
                <td>C. Aset Lainnya</td>
                <td></td>
            </tr>
            @php 
                $total_lain = 0;
            @endphp
            @foreach($accountbased['aset_lainnya'] as $kode => $nama)
                @php 
                    $akun_data = $asetLainnya->get($kode) ?? $asetLainnya->where('kode_akun', $kode)->first();
                    $val = 0;
                    if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                        $val = $akun_data->getFreshBalance('1900-01-01', $tanggalAkhir) ?? 0;
                        if (isset($akun_data->tipe) && $akun_data->tipe === 'Kredit') {
                            $val = abs($val);
                        }
                    }
                    // Ensure positive values for assets
                    $val = $val > 0 ? $val : 0;
                    $total_lain += $val; 
                @endphp
                @if($val > 0)
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $nama }}</td>
                    <td class="text-right">{{ number_format($val, 2, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Aset Lainnya</strong></td>
                <td class="text-right"><strong>{{ number_format($total_lain, 2, ',', '.') }}</strong></td>
            </tr>

            @php $total_aset = $total_lancar + $total_tetap + $total_lain; @endphp
            <tr class="grand-total">
                <td><strong>TOTAL ASET</strong></td>
                <td class="text-right"><strong>{{ number_format($total_aset, 2, ',', '.') }}</strong></td>
            </tr>

            <!-- II. LIABILITAS -->
            <tr class="category-header">
                <td colspan="2">II. LIABILITAS</td>
            </tr>
            @php 
                $total_liab = 0;
            @endphp
            @foreach($accountbased['liabilitas'] as $kode => $nama)
                @php 
                    $akun_data = $liabilitas->get($kode) ?? $liabilitas->where('kode_akun', $kode)->first();
                    $val = 0;
                    if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                        $val = $akun_data->getFreshBalance('1900-01-01', $tanggalAkhir) ?? 0;
                    }
                    // For liabilities, show absolute value (should be positive)
                    $val = abs($val);
                    $total_liab += $val; 
                @endphp
                @if($val > 0)
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $nama }}</td>
                    <td class="text-right">{{ number_format($val, 2, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Liabilitas</strong></td>
                <td class="text-right"><strong>{{ number_format($total_liab, 2, ',', '.') }}</strong></td>
            </tr>

            <!-- III. EKUITAS -->
            <tr class="category-header">
                <td colspan="2">III. EKUITAS</td>
            </tr>
            @php 
                $total_eku = 0;
            @endphp
            @foreach($accountbased['ekuitas'] as $kode => $nama)
                @php 
                    $akun_data = $ekuitas->get($kode) ?? $ekuitas->where('kode_akun', $kode)->first();
                    $val = 0;
                    if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                        $val = $akun_data->getFreshBalance('1900-01-01', $tanggalAkhir) ?? 0;
                    }
                    // For equity, show absolute value (should be positive)
                    $val = abs($val);
                    $total_eku += $val; 
                @endphp
                @if($val > 0)
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $nama }}</td>
                    <td class="text-right">{{ number_format($val, 2, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
            <tr class="total-row">
                <td><strong>Total Ekuitas</strong></td>
                <td class="text-right"><strong>{{ number_format($total_eku, 2, ',', '.') }}</strong></td>
            </tr>

            @php $tle = $total_liab + $total_eku; @endphp
            <tr class="grand-total">
                <td><strong>TOTAL LIABILITAS DAN EKUITAS</strong></td>
                <td class="text-right"><strong>{{ number_format($tle, 2, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    <div class="signature-space"></div>
                    <strong>KETUA ORGANISASI</strong><br>
                    <small>Perempuan Mahardika</small>
                </td>
                <td style="width: 50%;">
                    <div class="signature-space"></div>
                    <strong>ADMIN KEUANGAN</strong><br>
                    <small>Perempuan Mahardika</small>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
