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

class TransaksiExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
{
    protected $tanggal_dari;
    protected $tanggal_sampai;

    public function __construct($tanggal_dari, $tanggal_sampai)
    {
        $this->tanggal_dari = $tanggal_dari;
        $this->tanggal_sampai = $tanggal_sampai;
    }

    public function collection()
    {
        return Transaksi::with('akun')
            ->whereBetween('tanggal', [$this->tanggal_dari, $this->tanggal_sampai])
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Voucer',
            'Akun',
            'Project',
            'Deskripsi',
            'Nominal',
            'Jenis',
            'Saldo',
            'Tanggal',
        ];
    }

    public function map($transaksi): array
    {
        static $runningBalance = 0;
        static $firstDebitFound = false;
        
        $nominal = floatval($transaksi->nominal ?? 0);
        
        // Calculate running balance (same logic as in controller)
        if (!$firstDebitFound && ($transaksi->transaksi_type === 'Debit' || $transaksi->jenis === 'debit')) {
            $runningBalance = $nominal;
            $firstDebitFound = true;
        } else {
            if ($transaksi->transaksi_type === 'Kredit' || $transaksi->jenis === 'kredit') {
                $runningBalance -= $nominal;
            } else {
                $runningBalance += $nominal;
            }
        }
        
        // Format nominal based on type
        $nominalFormatted = ($transaksi->transaksi_type === 'Debit' || $transaksi->jenis === 'debit') ? $nominal : -$nominal;
        
        return [
            $transaksi->kode_voucer,
            (optional($transaksi->akun)->kode_akun ?? $transaksi->akun_kode) . ' - ' . (optional($transaksi->akun)->nama_akun ?? 'N/A'),
            $transaksi->kode_project,
            $transaksi->deskripsi,
            $nominalFormatted,
            $transaksi->transaksi_type ?? $transaksi->jenis,
            $runningBalance,
            optional($transaksi->tanggal)->format('d/m/Y') ?? (\Carbon\Carbon::parse($transaksi->tanggal)->format('d/m/Y')),
        ];
    }

    public function title(): string
    {
        return 'Laporan Transaksi';
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
                $sheet->setCellValue('A2', 'Laporan Transaksi');
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
                $sheet->setCellValue('A5', 'No. Dokumen: MDK-' . date('YmdHis'));
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
                $sheet->getColumnDimension('A')->setWidth(15); // Kode Voucer
                $sheet->getColumnDimension('B')->setWidth(25); // Akun
                $sheet->getColumnDimension('C')->setWidth(15); // Project
                $sheet->getColumnDimension('D')->setWidth(30); // Deskripsi
                $sheet->getColumnDimension('E')->setWidth(15); // Nominal
                $sheet->getColumnDimension('F')->setWidth(12); // Jenis
                $sheet->getColumnDimension('G')->setWidth(15); // Saldo
                $sheet->getColumnDimension('H')->setWidth(12); // Tanggal
                
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
                
                // Number formatting for currency columns
                $sheet->getStyle('E10:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('E10:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
