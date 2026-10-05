<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>General Ledger Detail PDF</title>
    <style>
        @page {
            margin: 5mm 5mm 5mm 5mm;
            size: A4 landscape;
        }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 8px; 
            margin: 0; 
            padding: 5px;
            line-height: 1.2;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            font-size: 8px;
        }
        th, td { 
            border: 1px solid #000; 
            padding: 3px 2px; 
            font-size: 8px;
            line-height: 1.2;
        }
        th { 
            background: #fdebd3; 
            text-align: center; 
            font-size: 8px;
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
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">No. Dokumen: {{ $serial ?? ('GL-' . date('YmdHis')) }}</div>
        <div style="margin-bottom:2px; font-size:9px; line-height:1.2;">Periode: {{ $periodStr ?? (date('d M Y', strtotime($from)) . ' s/d ' . date('d M Y', strtotime($to))) }}</div>
        <div style="margin-bottom:8px; font-size:9px; line-height:1.2;">Tanggal Print: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY HH:mm:ss') }}</div>
    </div>
    <hr style="border:2px solid #444; margin:5px 0;">

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>No. Transaksi</th>
                <th>No. Project</th>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th>Deskripsi</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Kredit</th>
                <th class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>
        @php $lastAccount = null; $running = []; $isFirstRow = []; @endphp
        @foreach($rows as $row)
            @php
                $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? 'N/A';

                if ($lastAccount !== $akunKode) {
                    $running[$akunKode] = 0;
                    $isFirstRow[$akunKode] = true;
                    $lastAccount = $akunKode;
                }

                $debit = 0;
                $kredit = 0;

                if (isset($row->debit) && $row->debit > 0) {
                    $debit = (float)$row->debit;
                } elseif (isset($row->kredit) && $row->kredit > 0) {
                    $kredit = (float)$row->kredit;
                } else {
                    $jenis = $row->jenis ?? strtolower($row->transaksi_type ?? 'debit');
                    $jumlah = $row->jumlah ?? $row->nominal ?? 0;
                    if ($jenis === 'debit') {
                        $debit = (float)$jumlah;
                    } else {
                        $kredit = (float)$jumlah;
                    }
                }

                if ($isFirstRow[$akunKode]) {
                    $running[$akunKode] = $debit - $kredit;
                    $isFirstRow[$akunKode] = false;
                } else {
                    $running[$akunKode] = $running[$akunKode] + $debit - $kredit;
                }

                $akunNama = optional($row->akun)->nama_akun;
                if (!$akunNama && $akunKode && $akunKode !== 'N/A') {
                    $akunFromDb = \App\Models\Akun::where('kode_akun', $akunKode)->first();
                    $akunNama = $akunFromDb ? $akunFromDb->nama_akun : 'N/A';
                } else {
                    $akunNama = $akunNama ?? 'N/A';
                }
            @endphp
            <tr>
                <td>{{ optional($row->tanggal)->format('d M Y') ?? (\Carbon\Carbon::parse($row->tanggal)->format('d M Y')) }}</td>
                <td>{{ $row->kode_voucer ?? '-' }}</td>
                <td>{{ $row->kode_project ?? '-' }}</td>
                <td>{{ $akunKode }}</td>
                <td>{{ $akunNama }}</td>
                <td>{{ $row->deskripsi ?? '-' }}</td>
                <td class="text-end">{{ $debit ? number_format($debit,2,',','.') : '' }}</td>
                <td class="text-end">{{ $kredit ? number_format($kredit,2,',','.') : '' }}</td>
                <td class="text-end">{{ number_format($running[$akunKode],2,',','.') }}</td>
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

