<?php

namespace App\Exports;

use App\Models\Akun;
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

class NeracaExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
{
    protected $tanggal_dari;
    protected $tanggal_sampai;
    protected $filter_akun;

    public function __construct($tanggal_dari, $tanggal_sampai, $filter_akun = 'semua')
    {
        $this->tanggal_dari = $tanggal_dari;
        $this->tanggal_sampai = $tanggal_sampai;
        $this->filter_akun = $filter_akun;
    }

    public function collection()
    {
        $query = Akun::select(['kode_akun', 'nama', 'tipe']);
        
        if ($this->filter_akun !== 'semua') {
            $query->where('id', $this->filter_akun);
        }
        
        return $query->orderBy('kode_akun')->get();
    }

    public function headings(): array
    {
        return [
            'Kode Akun',
            'Nama Akun',
            'Saldo Debit',
            'Saldo Kredit',
        ];
    }

    public function map($akun): array
    {
        $saldoDebit = $akun->getSaldoDebit($this->tanggal_dari, $this->tanggal_sampai) ?? 0;
        $saldoKredit = $akun->getSaldoKredit($this->tanggal_dari, $this->tanggal_sampai) ?? 0;
        
        return [
            $akun->kode_akun,
            $akun->nama_akun,
            (float)$saldoDebit,
            (float)$saldoKredit,
        ];
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
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
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'Laporan Keuangan');
                $sheet->mergeCells('A2:D2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Organization info
                $sheet->setCellValue('A3', 'Alamat: Jl. Contoh Alamat No. 123, Kota, Provinsi');
                $sheet->mergeCells('A3:D3');
                $sheet->getStyle('A3')->getFont()->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A4', 'Telp: (021) 1234-5678 | Email: info@mahardika.org');
                $sheet->mergeCells('A4:D4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Document info
                $sheet->setCellValue('A5', 'No. Dokumen: MDK-' . date('YmdHis'));
                $sheet->mergeCells('A5:D5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->tanggal_dari)) . ' s/d ' . date('d M Y', strtotime($this->tanggal_sampai)));
                $sheet->mergeCells('A6:D6');
                $sheet->getStyle('A6')->getFont()->setSize(10);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Location and date
                $sheet->setCellValue('A7', 'Tanggal Cetak: ' . date('d M Y H:i'));
                $sheet->mergeCells('A7:D7');
                $sheet->getStyle('A7')->getFont()->setSize(10);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Empty row
                $sheet->setCellValue('A8', '');
                
                // Adjust column widths
                $sheet->getColumnDimension('A')->setWidth(15); // Kode Akun
                $sheet->getColumnDimension('B')->setWidth(40); // Nama Akun
                $sheet->getColumnDimension('C')->setWidth(18); // Saldo Debit
                $sheet->getColumnDimension('D')->setWidth(18); // Saldo Kredit
                
                // Style the data table
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A9:D' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Header row styling
                $sheet->getStyle('A9:D9')->applyFromArray([
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
                
                // Number formatting for currency columns (Saldo Debit, Saldo Kredit)
                $sheet->getStyle('C10:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('C10:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}

