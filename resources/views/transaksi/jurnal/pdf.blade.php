<!DOCTYPE html>
<html>
<head>
    <title>Jurnal Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            background-color: #8B5CF6;
            color: white;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        .table td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-draft {
            background-color: #fcd34d;
            color: #78350f;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .summary-label {
            font-weight: bold;
        }
        .amount {
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Jurnal Report</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</p>
        <p>Generated on: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Journal Number</th>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Project</th>
                <th class="text-right">Total Debit</th>
                <th class="text-right">Total Credit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDebit = 0;
                $totalCredit = 0;
                $countEntries = 0;
            @endphp
            @forelse($journalEntries as $entry)
            @php
                $totalDebit += $entry->total_debit ?? 0;
                $totalCredit += $entry->total_credit ?? 0;
                $countEntries++;
            @endphp
            <tr>
                <td>{{ $entry->journal_number ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($entry->journal_date ?? now())->format('d/m/Y') }}</td>
                <td>{{ $entry->description ?? '-' }}</td>
                <td>{{ $entry->reference ?? '-' }}</td>
                <td>{{ $entry->kode_project ?? 'N/A' }}</td>
                <td class="text-right amount">{{ number_format($entry->total_debit ?? 0, 2, ',', '.') }}</td>
                <td class="text-right amount">{{ number_format($entry->total_credit ?? 0, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No journal entries found for the selected period</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($countEntries > 0)
    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row">
            <span class="summary-label">Total Entries:</span>
            <span>{{ number_format($countEntries) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Debit:</span>
            <span class="amount">{{ number_format($totalDebit, 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Credit:</span>
            <span class="amount">{{ number_format($totalCredit, 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Balance:</span>
            <span class="amount">{{ number_format($totalDebit - $totalCredit, 2, ',', '.') }}</span>
        </div>
    </div>
    @endif
</body>
</html>