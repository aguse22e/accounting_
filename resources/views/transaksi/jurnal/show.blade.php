@extends('layouts.app')

@section('title', 'View Jurnal')

@section('content')
<div class="content-wrapper">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Detail Jurnal</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('transaksi.jurnal.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
                <a href="{{ route('transaksi.jurnal.edit', $journalEntry) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>
                <form action="{{ route('transaksi.jurnal.post', $journalEntry) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this journal entry to general ledger?')">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send me-1"></i> Post to Ledger
                    </button>
                </form>
                <form action="{{ route('transaksi.jurnal.reverse', $journalEntry) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this journal entry?')">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Journal Number:</th>
                            <td><strong>{{ $journalEntry->journal_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>Journal Date:</th>
                            <td>{{ \Carbon\Carbon::parse($journalEntry->journal_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Reference:</th>
                            <td>{{ $journalEntry->reference ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Project:</th>
                            <td>{{ optional($journalEntry->project)->display_name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Total Debit:</th>
                            <td class="text-success fw-bold">{{ number_format($journalEntry->total_debit, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Credit:</th>
                            <td class="text-danger fw-bold">{{ number_format($journalEntry->total_credit, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Balance:</th>
                            <td>
                                @if($journalEntry->isBalanced())
                                    <span class="badge bg-success">Balanced</span>
                                @else
                                    <span class="badge bg-danger">Not Balanced</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>{{ optional($journalEntry->creator)->User_name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h5>Description</h5>
                    <p>{{ $journalEntry->description }}</p>
                    @if($journalEntry->notes)
                        <div class="alert alert-info">
                            <strong>Notes:</strong> {{ $journalEntry->notes }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h5>Journal Lines</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th>Description</th>
                                    <th class="text-end">Balance After</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($journalEntry->lines as $line)
                                <tr>
                                    <td><strong>{{ $line->account_code }}</strong></td>
                                    <td>{{ optional($line->account)->nama_akun ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $line->type === 'debit' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($line->type) }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($line->amount, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $line->description ?? '-' }}</td>
                                    <td class="text-end">
                                        {{ $line->balance_after ? number_format($line->balance_after, 2, ',', '.') : '-' }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('transaksi.jurnal.editLine', [$journalEntry, $line]) }}" class="btn btn-sm btn-warning" title="Edit Line">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('transaksi.jurnal.destroyLine', [$journalEntry, $line]) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin ingin menghapus line ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Line">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>TOTAL</strong></td>
                                    <td class="text-end text-success fw-bold">
                                        {{ number_format($journalEntry->total_debit, 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format($journalEntry->total_credit, 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
