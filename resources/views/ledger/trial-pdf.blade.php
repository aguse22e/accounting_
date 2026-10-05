<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Neraca Saldo (Trial Balance) PDF</title>
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
        tfoot th { 
            background: #ececec; 
            font-weight: bold; 
        }
    </style>
</head>
<body>
    <div style="text-align:center; padding-top:10px; margin-bottom:8px;">
        <span style="letter-spacing:1px; font-weight:900; font-size:16px; color:#333; line-height:1.2;">ORGANISASI PEREMPUAN MAHARDIKA</span>
        <div style="margin: 5px 0 3px 0; color: #757575; font-weight:500; font-size:13px; line-height:1.2;">
            NERACA SALDO
        </div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Alamat: Jl. Kedondong II No. 123, Jakarta</div>
        <div style="color:#888; margin-bottom:2px; font-size:9px; line-height:1.3;">Telp: (021) 1234-5678 | Email: info@mahardika.org</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">No. Dokumen: {{ $serial ?? ('NS-' . date('YmdHis')) }}</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">Periode: {{ $periodStr ?? (date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to))) }}</div>
        <div style="margin-bottom:8px; font-size:9px; line-height:1.2;">Tanggal Print: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm:ss') }}</div>
    </div>
    <hr style="border:2px solid #444; margin:5px 0;">

    <table>
        <thead>
            <tr>
                <th>No. Akun</th>
                <th>Nama Akun</th>
                <th class="text-end">Saldo Awal Debit</th>
                <th class="text-end">Saldo Awal Kredit</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Kredit</th>
                <th class="text-end">Saldo Akhir Debit</th>
                <th class="text-end">Saldo Akhir Kredit</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
        @foreach($akun as $a)
            @php
                $saldo = ($a->netBalance ?? 0);
                $saldoClass = $saldo >= 0 ? 'text-success' : 'text-danger';
            @endphp
            <tr>
                <td>{{ $a->kode_akun }}</td>
                <td>{{ $a->nama_akun }}</td>
                <td class="text-end">{{ number_format($a->opDebit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($a->opKredit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($a->chDebit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($a->chKredit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($a->endingDebit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($a->endingKredit ?? 0, 2, ',', '.') }}</td>
                <td class="text-end {{ $saldoClass }}">{{ number_format($saldo, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">TOTAL</th>
                <th class="text-end">{{ number_format($akun->sum('opDebit'), 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($akun->sum('opKredit'), 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($akun->sum('chDebit'), 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($akun->sum('chKredit'), 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($akun->sum('endingDebit'), 2, ',', '.') }}</th>
                <th class="text-end">{{ number_format($akun->sum('endingKredit'), 2, ',', '.') }}</th>
                <th class="text-end">
                    @php
                        $totalSaldo = $akun->sum(function($a) { return ($a->endingDebit ?? 0) - ($a->endingKredit ?? 0); });
                        $totalSaldoClass = $totalSaldo >= 0 ? 'text-success' : 'text-danger';
                    @endphp
                    <span class="{{ $totalSaldoClass }}">{{ number_format($totalSaldo, 2, ',', '.') }}</span>
                </th>
            </tr>
        </tfoot>
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
