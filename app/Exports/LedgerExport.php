<?php

namespace App\Exports;

use App\Models\Akun;
use App\Models\Transaksi;
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

class LedgerExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
{
    protected $tanggal_dari;
    protected $tanggal_sampai;
    protected $filter_akun;
    protected $menu;

    public function __construct($tanggal_dari, $tanggal_sampai, $filter_akun = 'semua', $menu = 'General Ledger Detail')
    {
        $this->tanggal_dari = $tanggal_dari;
        $this->tanggal_sampai = $tanggal_sampai;
        $this->filter_akun = $filter_akun;
        $this->menu = $menu;
    }

    public function collection()
    {
        $query = Transaksi::with('akun')
            ->whereBetween('tanggal', [$this->tanggal_dari, $this->tanggal_sampai]);
        
        // Filter khusus untuk menu "Aset Tetap" - hanya kode akun 12xx
        if ($this->menu === 'Aset Tetap') {
            $query->where('akun_kode', 'like', '12%');
        }
        // Filter by akun_id if specified
        elseif ($this->filter_akun !== 'semua') {
            $query->where('akun_kode', function($q) {
                $q->select('kode_akun')
                  ->from('akun')
                  ->where('id', $this->filter_akun);
            });
        }
        
        // Note: Kolom akun_kode tidak ada di tabel transaksi
        return $query->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'No. Voucher',
            'Kode Akun',
            'Nama Akun',
            'Deskripsi',
            'Debit',
            'Kredit',
            'Saldo',
        ];
    }

    public function map($transaksi): array
    {
        $accountCode = optional($transaksi->akun)->kode_akun ?? $transaksi->akun_kode;
        
        // Untuk menu "Aset Tetap", gunakan format yang sama dengan tampilan web
        if ($this->menu === 'Aset Tetap') {
            $debit = $transaksi->debit > 0 ? (float)$transaksi->debit : 0;
            $kredit = $transaksi->kredit > 0 ? (float)$transaksi->kredit : 0;
            $nominal = (float)($transaksi->nominal ?? $transaksi->jumlah ?? 0);
            
            return [
                $transaksi->tanggal ? \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y') : '-',
                $transaksi->kode_voucer ?? '-',
                $accountCode,
                optional($transaksi->akun)->nama_akun ?? '-',
                $transaksi->deskripsi ?? '-',
                $debit,
                $kredit,
                $nominal,
            ];
        } else {
            // Untuk menu lain, gunakan logika saldo running
            static $runningBalance = [];
            static $lastAccount = null;
            
            // Reset running balance when account changes
            if ($lastAccount !== $accountCode) {
                $runningBalance[$accountCode] = 0;
                $lastAccount = $accountCode;
            }
            
            $debit = $transaksi->jenis === 'debit' ? (float)($transaksi->jumlah ?? 0) : 0;
            $kredit = $transaksi->jenis === 'kredit' ? (float)($transaksi->jumlah ?? 0) : 0;
            
            // Calculate running balance based on account normal balance
            $normalBalance = optional($transaksi->akun)->saldo_normal ?? 'debit';
            if ($normalBalance === 'debit') {
                $runningBalance[$accountCode] += ($debit - $kredit);
            } else {
                $runningBalance[$accountCode] += ($kredit - $debit);
            }
            
            return [
                optional($transaksi->tanggal)->format('d/m/Y') ?? (\Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y')),
                $transaksi->kode_voucer,
                $accountCode,
                optional($transaksi->akun)->nama_akun ?? 'N/A',
                $transaksi->deskripsi,
                $debit,
                $kredit,
                $runningBalance[$accountCode],
            ];
        }
    }

    public function title(): string
    {
        return $this->menu === 'Aset Tetap' ? 'Daftar Aset Tetap' : 'Laporan Keuangan';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'E6E6E6',
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
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $reportTitle = $this->menu === 'Aset Tetap' ? 'Daftar Aset Tetap' : 'General Ledger Detail';
                $sheet->setCellValue('A2', $reportTitle);
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Organization info
                $sheet->setCellValue('A3', 'Alamat: Jl. Contoh Alamat No. 123, Kota, Provinsi');
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->getFont()->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A4', 'Telp: (021) 1234-5678 | Email: info@mahardika.org');
                $sheet->mergeCells('A4:H4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Document info
                $sheet->setCellValue('A5', 'No. Dokumen: LG-' . date('YmdHis'));
                $sheet->mergeCells('A5:H5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->tanggal_dari)) . ' s/d ' . date('d M Y', strtotime($this->tanggal_sampai)));
                $sheet->mergeCells('A6:H6');
                $sheet->getStyle('A6')->getFont()->setSize(10);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Location and date
                $sheet->setCellValue('A7', 'Tanggal Cetak: ' . date('d M Y H:i'));
                $sheet->mergeCells('A7:H7');
                $sheet->getStyle('A7')->getFont()->setSize(10);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Empty row
                $sheet->setCellValue('A8', '');
                
                // Adjust column widths
                $sheet->getColumnDimension('A')->setWidth(12); // Tanggal
                $sheet->getColumnDimension('B')->setWidth(15); // No. Voucher
                $sheet->getColumnDimension('C')->setWidth(12); // Kode Akun
                $sheet->getColumnDimension('D')->setWidth(25); // Nama Akun
                $sheet->getColumnDimension('E')->setWidth(30); // Deskripsi
                $sheet->getColumnDimension('F')->setWidth(15); // Debit
                $sheet->getColumnDimension('G')->setWidth(15); // Kredit
                $sheet->getColumnDimension('H')->setWidth(15); // Saldo
                
                // Style the data table
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A9:H' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Header row styling
                $sheet->getStyle('A9:H9')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E6E6E6',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Number formatting for currency columns (Debit, Kredit, Saldo)
                $sheet->getStyle('F10:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F10:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}