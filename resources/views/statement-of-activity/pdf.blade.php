<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Activity - MAHARDIKA</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        .header .subtitle {
            font-size: 12px;
            margin: 5px 0;
        }
        .header .contact-info {
            font-size: 10px;
            margin: 3px 0;
            color: #555;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 20px;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            width: 200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: none;
            padding: 5px 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        td.text-end {
            text-align: right;
        }
        .section-header {
            background: #e9ecef;
            font-weight: bold;
            padding: 8px;
        }
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }
        .indent-3 { padding-left: 60px; }
        .total-row {
            font-weight: bold;
            background: #f8f9fa;
        }
        .profit-row {
            font-weight: bold;
            background: #d1ecf1;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MAHARDIKA</h1>
        <div class="contact-info">Jl. Kedondong - Rawamangun</div>
        <div class="contact-info">Telp: 081319155058 | Email: voice@mahardika</div>
        <div class="subtitle">Laporan Laba Rugi</div>
        <div class="subtitle">Periode: {{ date('d M Y', strtotime($fromDate)) }} s/d {{ date('d M Y', strtotime($toDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60%;">Deskripsi</th>
                <th class="text-end" style="width: 40%;">{{ date('d M', strtotime($fromDate)) }}-{{ date('d M Y', strtotime($toDate)) }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Operating Revenue / Pendapatan Operasi -->
            <tr>
                <td colspan="2" class="section-header">PENDAPATAN OPERASI</td>
            </tr>
            @foreach($data['operating_revenue'] as $key => $item)
            <tr>
                <td class="indent-1">{{ $item['name'] }}</td>
                <td class="text-end">{{ number_format($item['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Total PENDAPATAN OPERASI</td>
                <td class="text-end">{{ number_format($data['totals']['operating_revenue'], 2, ',', '.') }}</td>
            </tr>

            <!-- Cost of Goods Sold / Harga Pokok Penjualan -->
            <tr>
                <td colspan="2" class="section-header">Harga Pokok Penjualan</td>
            </tr>
            @foreach($data['cost_of_goods_sold'] as $key => $item)
            <tr>
                <td class="indent-1">{{ $item['name'] }}</td>
                <td class="text-end">{{ number_format($item['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Harga Pokok Penjualan</td>
                <td class="text-end">{{ number_format($data['totals']['cost_of_goods_sold'], 2, ',', '.') }}</td>
            </tr>

            <!-- Gross Profit / Laba Kotor -->
            <tr class="profit-row">
                <td>LABA KOTOR</td>
                <td class="text-end">{{ number_format($data['totals']['gross_profit'], 2, ',', '.') }}</td>
            </tr>

            <!-- Operating Expenses / Beban Operasi -->
            <tr>
                <td colspan="2" class="section-header">Beban Operasi</td>
            </tr>
            @foreach($data['operating_expenses'] as $key => $item)
            <tr>
                <td class="indent-1">{{ $item['name'] }}</td>
                <td class="text-end">{{ number_format($item['total'], 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Beban Operasi</td>
                <td class="text-end">{{ number_format($data['totals']['operating_expenses'], 2, ',', '.') }}</td>
            </tr>

            <!-- Income from Operation / Pendapatan dari Operasi -->
            <tr class="profit-row">
                <td>PENDAPATAN DARI OPERASI</td>
                <td class="text-end">{{ number_format($data['totals']['income_from_operation'], 2, ',', '.') }}</td>
            </tr>

            <!-- Other Income and Expenses / Pendapatan dan Beban Lain-lain -->
            <tr>
                <td colspan="2" class="section-header">Pendapatan dan Beban Lain-lain</td>
            </tr>
            @if(count($data['other_income']) > 0)
            <tr>
                <td class="indent-1">Pendapatan Lain-lain</td>
                <td></td>
            </tr>
            @foreach($data['other_income'] as $key => $item)
            <tr>
                <td class="indent-2">{{ $item['name'] ?? $key }}</td>
                <td class="text-end">{{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            @endif
            <tr class="total-row">
                <td class="indent-1">Total Pendapatan Lain-lain</td>
                <td class="text-end">{{ number_format($data['totals']['other_income'], 2, ',', '.') }}</td>
            </tr>
            @if(count($data['other_expenses']) > 0)
            <tr>
                <td class="indent-1">Beban Lain-lain</td>
                <td></td>
            </tr>
            @foreach($data['other_expenses'] as $key => $item)
            <tr>
                <td class="indent-2">{{ $item['name'] ?? $key }}</td>
                <td class="text-end">{{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            @endif
            <tr class="total-row">
                <td class="indent-1">Total Beban Lain-lain</td>
                <td class="text-end">{{ number_format($data['totals']['other_expenses'], 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td class="indent-1">Total Pendapatan dan Beban Lain-lain</td>
                <td class="text-end">{{ number_format($data['totals']['other_income'] - $data['totals']['other_expenses'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box" style="text-align: left;">
            <div style="margin-bottom: 60px;">Mengetahui,</div>
            <div style="margin-bottom: 5px;">Ketua</div>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box" style="text-align: left;">
            <div style="margin-bottom: 60px;">Jakarta, {{ date('d M Y') }}</div>
            <div style="margin-bottom: 5px;">Admin</div>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="footer">
        Dicetak pada {{ date('d M Y') }}<br>
        Halaman 1 dari 1
    </div>
</body>
</html>
