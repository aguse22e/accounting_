@extends('layouts.app')

@section('title', 'Statement of Activity')

@section('styles')
@vite(['resources/css/pages/statement-of-activity.css'])
@endsection

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="bi bi-file-text me-2"></i>Statement of Activity</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-danger btn-sm" onclick="exportPdf()">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('ledger.statement-of-activity') }}" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('ledger.statement-of-activity') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Statement of Activity Report -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th>{{ date('d M', strtotime($fromDate)) . ' - ' . date('d M Y', strtotime($toDate)) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Operating Revenue / Pendapatan Operasi -->
                        <tr>
                            <td colspan="2">PENDAPATAN OPERASI</td>
                        </tr>
                        @foreach($data['operating_revenue'] as $key => $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ number_format($item['total'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td>Total PENDAPATAN OPERASI</td>
                            <td>{{ number_format($data['totals']['operating_revenue'], 2, ',', '.') }}</td>
                        </tr>

                        <!-- Cost of Goods Sold / Harga Pokok Penjualan -->
                        <tr>
                            <td colspan="2">Harga Pokok Penjualan</td>
                        </tr>
                        @foreach($data['cost_of_goods_sold'] as $key => $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ number_format($item['total'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td>Total Harga Pokok Penjualan</td>
                            <td>{{ number_format($data['totals']['cost_of_goods_sold'], 2, ',', '.') }}</td>
                        </tr>

                        <!-- Gross Profit / Laba Kotor -->
                        <tr>
                            <td>LABA KOTOR</td>
                            <td>{{ number_format($data['totals']['gross_profit'], 2, ',', '.') }}</td>
                        </tr>

                        <!-- Operating Expenses / Beban Operasi -->
                        <tr>
                            <td colspan="2">Beban Operasi</td>
                        </tr>
                        @foreach($data['operating_expenses'] as $key => $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ number_format($item['total'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td>Total Beban Operasi</td>
                            <td>{{ number_format($data['totals']['operating_expenses'], 2, ',', '.') }}</td>
                        </tr>

                        <!-- Income from Operation / Pendapatan dari Operasi -->
                        <tr>
                            <td>PENDAPATAN DARI OPERASI</td>
                            <td>{{ number_format($data['totals']['income_from_operation'], 2, ',', '.') }}</td>
                        </tr>

                        <!-- Other Income and Expenses / Pendapatan dan Beban Lain-lain -->
                        <tr>
                            <td colspan="2">Pendapatan dan Beban Lain-lain</td>
                        </tr>
                        @if(count($data['other_income']) > 0)
                        <tr>
                            <td>Pendapatan Lain-lain</td>
                            <td></td>
                        </tr>
                        @foreach($data['other_income'] as $key => $item)
                        <tr>
                            <td>{{ $item['name'] ?? $key }}</td>
                            <td>{{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        @endif
                        <tr>
                            <td>Total Pendapatan Lain-lain</td>
                            <td>{{ number_format($data['totals']['other_income'], 2, ',', '.') }}</td>
                        </tr>
                        @if(count($data['other_expenses']) > 0)
                        <tr>
                            <td>Beban Lain-lain</td>
                            <td></td>
                        </tr>
                        @foreach($data['other_expenses'] as $key => $item)
                        <tr>
                            <td>{{ $item['name'] ?? $key }}</td>
                            <td>{{ number_format($item['total'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        @endif
                        <tr>
                            <td>Total Beban Lain-lain</td>
                            <td>{{ number_format($data['totals']['other_expenses'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Total Pendapatan dan Beban Lain-lain</td>
                            <td>{{ number_format($data['totals']['other_income'] - $data['totals']['other_expenses'], 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
window.exportPdfRoute = '{{ route("ledger.statement-of-activity.export-pdf") }}';
</script>
@vite(['resources/js/pages/statement-of-activity.js'])
@endsection
