<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ledger PDF</title>
    <style>
        @page {
            margin: 5mm 5mm 5mm 5mm;
            size: A4 landscape;
        }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 9px; 
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
            font-size: 9px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 4px 3px; 
            text-align: left;
            line-height: 1.2;
        }
        th { 
            background: #f5f5f5; 
            font-weight: bold;
            text-align: center;
            font-size: 9px;
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

    @php 
        $lastAccount = null; 
        $running = 0; 
        $accountBalances = [];
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 12%;">No. Transaksi</th>
                <th style="width: 10%;">Kode Akun</th>
                <th style="width: 20%;">Nama Akun</th>
                <th style="width: 23%;">Deskripsi</th>
                <th style="width: 10%; text-align:right;">Debit</th>
                <th style="width: 10%; text-align:right;">Kredit</th>
                <th style="width: 10%; text-align:right;">Saldo</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            @php
                $accountCode = optional($row->akun)->kode_akun ?? $row->akun_kode;
                
                // Untuk menu "Aset Tetap", gunakan format yang sama dengan tampilan web
                if ($menu === 'Aset Tetap') {
                    $debit = $row->debit > 0 ? (float)$row->debit : 0;
                    $kredit = $row->kredit > 0 ? (float)$row->kredit : 0;
                    $nominal = (float)($row->nominal ?? $row->jumlah ?? 0);
                } else {
                    // Untuk menu lain, gunakan logika saldo running
                    $accountId = optional($row->akun)->id ?? null;
                    if ($lastAccount !== $accountId) { 
                        $running = 0; 
                        $lastAccount = $accountId; 
                    }
                    $jenis = $row->jenis ?? strtolower($row->transaksi_type ?? 'debit');
                    $jumlah = $row->jumlah ?? $row->nominal ?? 0;
                    $debit = $jenis === 'debit' ? (float)$jumlah : 0;
                    $kredit = $jenis === 'kredit' ? (float)$jumlah : 0;
                    $running += $debit - $kredit;
                }
            @endphp
            <tr>
                <td>{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-' }}</td>
                <td>{{ $row->kode_voucer ?? '-' }}</td>
                <td>{{ $accountCode }}</td>
                <td>{{ $row->akun ? $row->akun->nama_akun : '-' }}</td>
                <td>{{ $row->deskripsi ?? '-' }}</td>
                <td style="text-align:right;">{{ $debit > 0 ? number_format($debit, 2, ',', '.') : '-' }}</td>
                <td style="text-align:right;">{{ $kredit > 0 ? number_format($kredit, 2, ',', '.') : '-' }}</td>
                <td style="text-align:right;">{{ $menu === 'Aset Tetap' ? number_format($nominal, 2, ',', '.') : number_format($running, 2, ',', '.') }}</td>
            </tr>
        @endforeach
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


