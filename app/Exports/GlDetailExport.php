<?php

namespace App\Exports;

use App\Models\Transaksi;
use App\Models\Akun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GlDetailExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
{
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $rows = collect();
        
        // 1. Ambil dari tabel transaksi
        $transaksiRows = Transaksi::with('akun')
            ->whereBetween('tanggal', [$this->from, $this->to])
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($row) {
                $row->source = 'transaksi';
                $row->akun_kode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? null;
                return $row;
            });
        
        // 2. Ambil dari tabel jurnal
        if (Schema::hasTable('jurnal')) {
            $jurnalRows = DB::table('jurnal')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->orderBy('tanggal', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function($row) {
                    $obj = (object) [
                        'tanggal' => $row->tanggal,
                        'kode_voucer' => $row->kode_transaksi,
                        'kode_project' => null,
                        'akun_kode' => $row->kode,
                        'deskripsi' => $row->keterangan,
                        'tipe' => $row->tipe,
                        'nominal' => $row->nominal,
                        'debit' => strtolower($row->tipe) === 'debit' ? $row->nominal : 0,
                        'kredit' => strtolower($row->tipe) === 'kredit' ? $row->nominal : 0,
                        'jenis' => strtolower($row->tipe),
                        'jumlah' => $row->nominal,
                        'transaksi_type' => $row->tipe,
                        'source' => 'jurnal',
                        'akun' => null
                    ];
                    return $obj;
                });
            $rows = $rows->merge($jurnalRows);
        }
        
        // 3. Ambil dari tabel transaksi_bank
        if (Schema::hasTable('transaksi_bank')) {
            $bankRows = DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->orderBy('tanggal', 'asc')
                ->orderBy('kode_transaksi_bank', 'asc')
                ->get()
                ->map(function($row) {
                    $obj = (object) [
                        'tanggal' => $row->tanggal,
                        'kode_voucer' => $row->kode_transaksi_bank,
                        'kode_project' => null,
                        'akun_kode' => $row->akun_kode,
                        'deskripsi' => null,
                        'tipe' => $row->tipe,
                        'nominal' => $row->total,
                        'debit' => strtolower($row->tipe) === 'debit' ? $row->total : 0,
                        'kredit' => strtolower($row->tipe) === 'kredit' ? $row->total : 0,
                        'jenis' => strtolower($row->tipe),
                        'jumlah' => $row->total,
                        'transaksi_type' => $row->tipe,
                        'source' => 'transaksi_bank',
                        'akun' => null
                    ];
                    return $obj;
                });
            $rows = $rows->merge($bankRows);
        }
        
        // Gabungkan semua data
        $rows = $rows->merge($transaksiRows);
        
        // Urutkan berdasarkan akun_kode, lalu tanggal, lalu waktu
        $rows = $rows->sortBy(function($row) {
            $akunKode = $row->akun_kode ?? 'ZZZ';
            $tanggal = $row->tanggal ?? '9999-12-31';
            $id = $row->id ?? $row->kode_voucer ?? '';
            return $akunKode . '_' . $tanggal . '_' . $id;
        })->values();
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Date',
            'No. Transaksi',
            'No. Project',
            'Kode Akun',
            'Nama Akun',
            'Deskripsi',
            'Debit',
            'Kredit',
            'Saldo',
        ];
    }

    public function map($row): array
    {
        static $lastAccount = null;
        static $running = [];
        static $isFirstRow = [];

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
            $akunFromDb = Akun::where('kode_akun', $akunKode)->first();
            $akunNama = $akunFromDb ? $akunFromDb->nama_akun : 'N/A';
        } else {
            $akunNama = $akunNama ?? 'N/A';
        }

        return [
            $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d M Y') : '-',
            $row->kode_voucer ?? '-',
            $row->kode_project ?? '-',
            $akunKode,
            $akunNama,
            $row->deskripsi ?? '-',
            $debit,
            $kredit,
            $running[$akunKode],
        ];
    }

    public function title(): string
    {
        return 'General Ledger Detail';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FDEBD3',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Add letterhead
                $sheet->insertNewRowBefore(1, 8);
                
                // Organization header
                $sheet->setCellValue('A1', 'ORGANISASI PEREMPUAN MAHARDIKA');
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'NERACA KEUANGAN');
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Organization info
                $sheet->setCellValue('A3', 'Alamat: Jl. Kedondong II No. 123, Jakarta');
                $sheet->mergeCells('A3:I3');
                $sheet->getStyle('A3')->getFont()->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A4', 'Telp: (021) 1234-5678 | Email: info@mahardika.org');
                $sheet->mergeCells('A4:I4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Document info
                $sheet->setCellValue('A5', 'No. Dokumen: GL-' . date('YmdHis'));
                $sheet->mergeCells('A5:I5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->from)) . ' s/d ' . date('d M Y', strtotime($this->to)));
                $sheet->mergeCells('A6:I6');
                $sheet->getStyle('A6')->getFont()->setSize(10);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Location and date
                $sheet->setCellValue('A7', 'Tanggal Print: ' . date('d M Y H:i:s'));
                $sheet->mergeCells('A7:I7');
                $sheet->getStyle('A7')->getFont()->setSize(10);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Empty row
                $sheet->setCellValue('A8', '');
                
                // Adjust column widths
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                
                // Style the data table
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A9:I' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Header row styling
                $sheet->getStyle('A9:I9')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FDEBD3',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Number formatting for currency columns
                $sheet->getStyle('G10:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('G10:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}

