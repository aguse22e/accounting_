<!DOCTYPE html>
<html>
<head>
    <title>Transaksi PDF</title>
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
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .no-data {
            margin-top: 20px;
            font-style: italic;
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
        <h2>Laporan Transaksi</h2>
        <div class="organization-info">
            Alamat: Jl. Contoh Alamat No. 123, Kota, Provinsi<br>
            Telp: (021) 1234-5678 | Email: info@mahardika.org
        </div>
        <div class="meta">No. Dokumen: MDK-{{ date('YmdHis') }}</div>
        <div class="meta">Periode: {{ isset($from) ? date('d M Y', strtotime($from)) : date('d M Y') }} s/d {{ isset($to) ? date('d M Y', strtotime($to)) : date('d M Y') }}</div>
    </div>

    <div class="location-date">
        <strong>{{ ucfirst(strftime('%A, %d %B %Y', strtotime('now'))) }}</strong>
    </div>

    @if($transaksis->isEmpty())
        <p class="no-data">Belum ada data transaksi untuk periode yang dipilih.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Kode Voucer</th>
                    <th>Akun</th>
                    <th>Project</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Nominal</th>
                    <th>Jenis</th>
                    <th class="text-right">Saldo</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaksis as $transaksi)
                @php
                    $nominal = floatval($transaksi->nominal ?? 0);
                    $isDebit = ($transaksi->transaksi_type === 'Debit' || $transaksi->jenis === 'debit');
                    $nominalFormatted = number_format($nominal, 2, ',', '.');
                @endphp
                <tr>
                    <td>{{ $transaksi->kode_voucer }}</td>
                    <td>{{ optional($transaksi->akun)->kode_akun ?? $transaksi->akun_kode }} - {{ optional($transaksi->akun)->nama_akun ?? 'N/A' }}</td>
                    <td>{{ $transaksi->kode_project }}</td>
                    <td>{{ $transaksi->deskripsi }}</td>
                    <td class="text-right">{{ $nominalFormatted }}</td>
                    <td>{{ $transaksi->transaksi_type ?? $transaksi->jenis }}</td>
                    <td class="text-right"><strong>{{ number_format((float)($transaksi->saldo ?? 0), 2, ',', '.') }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

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
