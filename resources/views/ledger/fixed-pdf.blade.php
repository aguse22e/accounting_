<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aset Tetap PDF</title>
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
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            font-size: 9px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 4px 3px; 
            font-size: 9px;
            line-height: 1.2;
        }
        th { 
            background: #fdebd3; 
            text-align: center; 
            font-size: 9px;
            font-weight: bold;
        }
        td.text-end { 
            text-align: right; 
        }
    </style>
</head>
<body>
    <div style="text-align:center; padding-top:10px; margin-bottom:8px;">
        <span style="letter-spacing:1px; font-weight:900; font-size:16px; color:#333; line-height:1.2;">ORGANISASI PEREMPUAN MAHARDIKA</span>
        <div style="margin: 5px 0 3px 0; color: #757575; font-weight:500; font-size:13px; line-height:1.2;">
            NERACA KEUANGAN
        </div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Alamat: Jl. Kedondong II No. 123, Jakarta</div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Telp: (021) 1234-5678 | Email: info@mahardika.org</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">No. Dokumen: {{ $serial ?? ('FA-' . date('YmdHis')) }}</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">Periode: {{ $periodStr ?? (date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to))) }}</div>
        <div style="margin-bottom:8px; font-size:9px; line-height:1.2;">Tanggal Print: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm:ss') }}</div>
    </div>
    <hr style="border:2px solid #444; margin:5px 0;">

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th>Deskripsi</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Kredit</th>
                <th class="text-end">Nominal</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            @php
                $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? 'N/A';
                $akunNama = optional($row->akun)->nama_akun ?? 'N/A';
                $debit = $row->debit ?? 0;
                $kredit = $row->kredit ?? 0;
                $nominal = $row->nominal ?? $row->jumlah ?? 0;
            @endphp
            <tr>
                <td>{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-' }}</td>
                <td>{{ $row->kode_voucer ?? '-' }}</td>
                <td><strong>{{ $akunKode }}</strong></td>
                <td>{{ $akunNama }}</td>
                <td>{{ $row->deskripsi ?? '-' }}</td>
                <td class="text-end">{{ $debit > 0 ? number_format($debit, 2, ',', '.') : '-' }}</td>
                <td class="text-end">{{ $kredit > 0 ? number_format($kredit, 2, ',', '.') : '-' }}</td>
                <td class="text-end"><strong>{{ number_format($nominal, 2, ',', '.') }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px; page-break-inside: avoid;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; border: none; padding: 10px; text-align: center; vertical-align: top; font-size: 9px;">
                    <div style="height: 40px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                    <strong>KETUA ORGANISASI</strong><br>
                    Perempuan Mahardika
                </td>
                <td style="width: 50%; border: none; padding: 10px; text-align: center; vertical-align: top; font-size: 9px;">
                    <div style="height: 40px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                    <strong>ADMIN KEUANGAN</strong><br>
                    Perempuan Mahardika
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

