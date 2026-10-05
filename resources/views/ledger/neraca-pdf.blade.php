Do you want to copy it to a new file path, or modify it for UI/UX purposes
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan PDF</title>
    <style>
        @page {
            margin: 5mm 5mm 5mm 5mm;
            size: A4 portrait;
        }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 5px;
            line-height: 1.2;
        }
        .letterhead { 
            text-align: center; 
            margin-bottom: 8px; 
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .letterhead h1 { 
            margin: 0; 
            font-size: 16px; 
            font-weight: bold;
            color: #333;
            line-height: 1.2;
        }
        .letterhead h2 {
            margin: 3px 0;
            font-size: 13px;
            font-weight: normal;
            color: #666;
            line-height: 1.2;
        }
        .letterhead .organization-info {
            font-size: 9px;
            color: #666;
            margin: 3px 0;
            line-height: 1.3;
        }
        .letterhead .meta { 
            font-size: 9px; 
            margin: 2px 0;
            color: #333;
            line-height: 1.2;
        }
        .location-date {
            text-align: right;
            margin-bottom: 8px;
            font-size: 9px;
            line-height: 1.2;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px;
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px 4px; 
            text-align: left;
            line-height: 1.2;
        }
        th { 
            background: #f5f5f5; 
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .signature-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            padding: 5px;
            text-align: center;
            vertical-align: top;
            font-size: 9px;
        }
        .signature-space {
            height: 40px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>ORGANISASI PEREMPUAN MAHARDIKA</h1>
        <h2>{{ $menu ?? 'Laporan Keuangan' }}</h2>
        <div class="organization-info">
            Alamat: Jl. Contoh Alamat No. 123, Kota, Provinsi<br>
            Telp: (021) 1234-5678 | Email: info@mahardika.org
        </div>
        <div class="meta">No. Dokumen: {{ $serial ?? ('MDK-' . date('YmdHis')) }}</div>
        <div class="meta">Periode: {{ $periodStr ?? (date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to))) }}</div>
    </div>

    <div class="location-date">
        <strong>{{ ucfirst(\Carbon\Carbon::now()->locale('id')->isoFormat('dddd, DD MMMM YYYY')) }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Kode Akun</th>
                <th style="width: 50%;">Nama Akun</th>
                <th style="width: 17.5%; text-align:right;">Saldo Debit</th>
                <th style="width: 17.5%; text-align:right;">Saldo Kredit</th>
            </tr>
        </thead>
        <tbody>
        @forelse($akun_data as $akun)
            @php
                $saldoDebit = $akun->getSaldoDebit($from, $to) ?? 0;
                $saldoKredit = $akun->getSaldoKredit($from, $to) ?? 0;
            @endphp
            <tr>
                <td>{{ $akun->kode_akun }}</td>
                <td>{{ $akun->nama_akun }}</td>
                <td style="text-align:right;">{{ $saldoDebit > 0 ? number_format($saldoDebit, 2, ',', '.') : '-' }}</td>
                <td style="text-align:right;">{{ $saldoKredit > 0 ? number_format($saldoKredit, 2, ',', '.') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data</td>
            </tr>
        @endforelse
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

