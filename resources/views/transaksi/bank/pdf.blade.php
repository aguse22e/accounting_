<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transaksi Bank Report</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #8B5CF6;
        }

        .header h1 {
            font-size: 20pt;
            color: #8B5CF6;
            margin-bottom: 5px;
        }

        .header .period {
            font-size: 11pt;
            color: #666;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table thead {
            background-color: #8B5CF6;
            color: white;
        }

        table thead th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #7C3AED;
        }

        table thead th.text-center {
            text-align: center;
        }

        table thead th.text-right {
            text-align: right;
        }

        table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table tbody tr:hover {
            background-color: #f3f4f6;
        }

        table tbody td {
            padding: 8px;
            font-size: 9pt;
            border: 1px solid #e5e7eb;
        }

        table tbody td.text-center {
            text-align: center;
        }

        table tbody td.text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .badge-debit {
            background-color: #bbf7d0;
            color: #065f46;
        }

        .badge-kredit {
            background-color: #fecaca;
            color: #991b1b;
        }

        .amount-debit {
            color: #059669;
            font-weight: bold;
        }

        .amount-kredit {
            color: #dc2626;
            font-weight: bold;
        }

        .amount-saldo {
            font-weight: bold;
        }

        .amount-saldo.positive {
            color: #059669;
        }

        .amount-saldo.negative {
            color: #dc2626;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .summary-row .label {
            font-weight: bold;
            color: #666;
        }

        .summary-row .value {
            font-weight: bold;
            color: #333;
        }

        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #999;
            font-size: 12pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TRANSAKSI BANK REPORT</h1>
        <div class="period">
            Period: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
        <div class="period">
            Generated: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    @if($transaksis->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Tanggal</th>
                    <th>Akun</th>
                    <th class="text-center">Tipe</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDebit = 0;
                    $totalKredit = 0;
                @endphp
                @foreach($transaksis as $transaksi)
                    @php
                        $nominal = floatval($transaksi->nominal ?? 0);
                        $isDebit = ($transaksi->tipe === 'Debit');
                        if ($isDebit) {
                            $totalDebit += $nominal;
                        } else {
                            $totalKredit += $nominal;
                        }
                    @endphp
                    <tr>
                        <td>{{ $transaksi->kode_transaksi_bank ?? 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($transaksi->tanggal ?? now())->format('d/m/Y') }}</td>
                        <td>{{ $transaksi->akun_kode ?? 'N/A' }} - {{ $transaksi->akun->nama_akun ?? 'N/A' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $isDebit ? 'badge-debit' : 'badge-kredit' }}">
                                {{ $transaksi->tipe ?? 'Debit' }}
                            </span>
                        </td>
                        <td class="text-right amount-debit">
                            {{ $isDebit ? number_format($nominal, 2, ',', '.') : '-' }}
                        </td>
                        <td class="text-right amount-kredit">
                            {{ !$isDebit ? number_format($nominal, 2, ',', '.') : '-' }}
                        </td>
                        <td class="text-right amount-saldo {{ ($transaksi->saldo ?? 0) >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($transaksi->saldo ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span class="label">Total Transaksi:</span>
                <span class="value">{{ $transaksis->count() }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Total Debit:</span>
                <span class="value amount-debit">Rp {{ number_format($totalDebit, 2, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Total Kredit:</span>
                <span class="value amount-kredit">Rp {{ number_format($totalKredit, 2, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Saldo Akhir:</span>
                <span class="value amount-saldo {{ ($transaksis->last()->saldo ?? 0) >= 0 ? 'positive' : 'negative' }}">
                    Rp {{ number_format($transaksis->last()->saldo ?? 0, 2, ',', '.') }}
                </span>
            </div>
        </div>
    @else
        <div class="no-data">
            <p>Tidak ada data transaksi bank pada periode ini.</p>
        </div>
    @endif

    <div class="footer">
        <p>Transaksi Bank Report | Generated by Accounting System</p>
    </div>
</body>
</html>
