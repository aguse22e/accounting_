<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Arus Kas PDF</title>
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
            line-height: 1.3;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px 4px; 
            font-size: 10px;
            line-height: 1.3;
        }
        th { 
            background: #fdebd3; 
            text-align: center; 
            font-size: 10px;
            font-weight: bold;
        }
        td.text-end { 
            text-align: right; 
        }
        .section-title {
            background: #ececec;
            font-weight: bold;
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
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">No. Dokumen: {{ $serial ?? ('CF-' . date('YmdHis')) }}</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">Periode: {{ $periodStr ?? (date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to))) }}</div>
        <div style="margin-bottom:8px; font-size:9px; line-height:1.2;">Tanggal Print: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm:ss') }}</div>
    </div>
    <hr style="border:2px solid #444; margin:5px 0;">

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-end">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-title">
                <td><strong>AKTIVITAS OPERASIONAL</strong></td>
                <td class="text-end">{{ number_format($operating ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="section-title">
                <td><strong>AKTIVITAS INVESTASI</strong></td>
                <td class="text-end">{{ number_format($investing ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="section-title">
                <td><strong>AKTIVITAS PENDANAAN</strong></td>
                <td class="text-end">{{ number_format($financing ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr style="background: #ececec; font-weight: bold;">
                <td><strong>NET CASH FLOW</strong></td>
                <td class="text-end"><strong>{{ number_format($netCashFlow ?? 0, 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Saldo Kas Awal</td>
                <td class="text-end">{{ number_format($cashBeginning ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr style="background: #ececec; font-weight: bold;">
                <td><strong>SALDO KAS AKHIR</strong></td>
                <td class="text-end"><strong>{{ number_format($cashEnding ?? 0, 2, ',', '.') }}</strong></td>
            </tr>
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

