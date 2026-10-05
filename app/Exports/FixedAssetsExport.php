<?php

namespace App\Exports;

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

class FixedAssetsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
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
        $rows = Transaksi::with('akun')
            ->whereBetween('tanggal', [$this->from, $this->to])
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();
        
        // Filter untuk akun 12xx
        $rows = $rows->filter(function($row) {
            $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? '';
            return strpos($akunKode, '12') === 0;
        });
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'No. Transaksi',
            'Kode Akun',
            'Nama Akun',
            'Deskripsi',
            'Debit',
            'Kredit',
            'Nominal',
        ];
    }

    public function map($row): array
    {
        $akunKode = $row->akun_kode ?? optional($row->akun)->kode_akun ?? 'N/A';
        $akunNama = optional($row->akun)->nama_akun ?? 'N/A';
        $debit = $row->debit ?? 0;
        $kredit = $row->kredit ?? 0;
        $nominal = $row->nominal ?? $row->jumlah ?? 0;
        
        return [
            $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-',
            $row->kode_voucer ?? '-',
            $akunKode,
            $akunNama,
            $row->deskripsi ?? '-',
            $debit,
            $kredit,
            $nominal,
        ];
    }

    public function title(): string
    {
        return 'Aset Tetap';
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
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'NERACA KEUANGAN');
                $sheet->mergeCells('A2:H2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Organization info
                $sheet->setCellValue('A3', 'Alamat: Jl. Kedondong I No.39, RT.10/RW.9, Rawamangun, Pulo Gadung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13220, Indonesia');
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->getFont()->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Email: mail@mahardhika.org | Telp: 0813-8872-5150');
                $sheet->mergeCells('A4:H4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Document info
                $sheet->setCellValue('A5', 'No. Dokumen: FA-' . date('YmdHis'));
                $sheet->mergeCells('A5:H5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->from)) . ' s/d ' . date('d M Y', strtotime($this->to)));
                $sheet->mergeCells('A6:H6');
                $sheet->getStyle('A6')->getFont()->setSize(10);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Location and date
                $sheet->setCellValue('A7', 'Tanggal Print: ' . date('d M Y H:i:s'));
                $sheet->mergeCells('A7:H7');
                $sheet->getStyle('A7')->getFont()->setSize(10);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Empty row
                $sheet->setCellValue('A8', '');
                
                // Adjust column widths
                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                
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
                            'rgb' => 'FDEBD3',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Number formatting for currency columns
                $sheet->getStyle('F10:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F10:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}

