<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Trial Balance (Neraca Saldo)</h1>
        <p>Period: {{ \Carbon\Carbon::parse($fromDate)->format('d F Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d F Y') }}</p>
        <p>Generated: {{ now()->format('d F Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trialBalance as $account)
            <tr>
                <td>{{ $account['account_code'] }}</td>
                <td>{{ $account['account_name'] }}</td>
                <td>{{ $account['account_type'] }}</td>
                <td class="text-right">{{ $account['debit'] > 0 ? number_format($account['debit'], 2, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $account['credit'] > 0 ? number_format($account['credit'], 2, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDebit, 2, ',', '.') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalCredit, 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p><strong>Balance Status:</strong> {{ $isBalanced ? '✓ BALANCED' : '✗ NOT BALANCED' }}</p>
        <p><strong>Difference:</strong> {{ number_format(abs($totalDebit - $totalCredit), 2, ',', '.') }}</p>
    </div>
</body>
</html>