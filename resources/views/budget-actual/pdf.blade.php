<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Budget vs Actual PDF</title>
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
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #d3e5f3;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div style="text-align:center; padding-top:10px; margin-bottom:8px;">
        <span style="letter-spacing:1px; font-weight:900; font-size:16px; color:#333; line-height:1.2;">ORGANISASI PEREMPUAN MAHARDIKA</span>
        <div style="margin: 5px 0 3px 0; color: #757575; font-weight:500; font-size:13px; line-height:1.2;">
            BUDGET VS ACTUAL REPORT
        </div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Alamat: Jl. Kedondong II No. 39, Jakarta</div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Telp: 0813-8872-5150
| Email: info@mahardika.org</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">No. Dokumen: BA-{{ date('YmdHis') }}</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">Periode: {{ $periodStr ?? 'Semua Periode' }}</div>
        <div style="margin-bottom:8px; font-size:9px; line-height:1.2;">Tanggal Print: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm:ss') }}</div>
    </div>
    <hr style="border:2px solid #444; margin:5px 0;">

    <table>
        <thead>
            <tr>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th>Kode Project</th>
                <th>Nama Project</th>
                <th class="text-end">Budget</th>
                <th class="text-end">Actual</th>
                <th class="text-end">Variance</th>
                <th class="text-end">Variance %</th>
            </tr>
        </thead>
        <tbody>
        @if(isset($budgetActualData['data']) && count($budgetActualData['data']) > 0)
            @foreach($budgetActualData['data'] as $account)
                @if(count($account['projects']) > 0)
                    @foreach($account['projects'] as $index => $project)
                        <tr>
                            @if($index === 0)
                                <td rowspan="{{ count($account['projects']) }}">{{ $account['kode_akun'] }}</td>
                                <td rowspan="{{ count($account['projects']) }}">{{ $account['nama_akun'] }}</td>
                            @endif
                            <td>{{ $project['kode_project'] }}</td>
                            <td>{{ $project['nama_project'] }}</td>
                            <td class="text-end">{{ number_format($project['budget'], 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($project['actual'], 2, ',', '.') }}</td>
                            <td class="text-end {{ $project['variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($project['variance'], 2, ',', '.') }}
                            </td>
                            <td class="text-end {{ $project['variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($project['variance_percent'], 2, ',', '.') }}%
                            </td>
                        </tr>
                    @endforeach
                    <!-- Account Total Row -->
                    <tr class="total-row">
                        <td colspan="4" class="text-end">Total {{ $account['nama_akun'] }}:</td>
                        <td class="text-end">{{ number_format($account['total_budget'], 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($account['total_actual'], 2, ',', '.') }}</td>
                        <td class="text-end {{ $account['total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($account['total_variance'], 2, ',', '.') }}
                        </td>
                        <td class="text-end {{ $account['total_variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($account['total_variance_percent'], 2, ',', '.') }}%
                        </td>
                    </tr>
                @endif
            @endforeach
            <!-- Grand Total Row -->
            @if(isset($budgetActualData['summary']))
                <tr class="grand-total-row">
                    <td colspan="4" class="text-end"><strong>GRAND TOTAL:</strong></td>
                    <td class="text-end"><strong>{{ number_format($budgetActualData['summary']['grand_total_budget'], 2, ',', '.') }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($budgetActualData['summary']['grand_total_actual'], 2, ',', '.') }}</strong></td>
                    <td class="text-end {{ $budgetActualData['summary']['grand_total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($budgetActualData['summary']['grand_total_variance'], 2, ',', '.') }}</strong>
                    </td>
                    <td class="text-end {{ $budgetActualData['summary']['grand_total_variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($budgetActualData['summary']['grand_total_variance_percent'], 2, ',', '.') }}%</strong>
                    </td>
                </tr>
            @endif
        @else
            <!-- Show table structure with empty rows when no data -->
            <tr>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00%</td>
            </tr>
            <tr>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00%</td>
            </tr>
            <tr>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00</td>
                <td class="text-end">0,00%</td>
            </tr>
            <!-- Grand Total Row for empty data -->
            <tr class="grand-total-row">
                <td colspan="4" class="text-end"><strong>GRAND TOTAL:</strong></td>
                <td class="text-end"><strong>0,00</strong></td>
                <td class="text-end"><strong>0,00</strong></td>
                <td class="text-end"><strong>0,00</strong></td>
                <td class="text-end"><strong>0,00%</strong></td>
            </tr>
        @endif
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
