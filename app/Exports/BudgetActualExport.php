<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BudgetActualExport implements FromArray, WithHeadings, WithTitle, WithEvents, WithStyles
{
    protected $data;
    protected $fromDate;
    protected $toDate;

    public function __construct($data, $fromDate = null, $toDate = null)
    {
        $this->data = $data;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function array(): array
    {
        $exportData = [];

        if (!isset($this->data['data'])) {
            return $exportData;
        }

        // Export data rows - data is already flat
        foreach ($this->data['data'] as $item) {
            $exportData[] = [
                $item['kode_akun'],
                $item['nama_akun'],
                $item['kode_project'],
                $item['nama_project'],
                $item['budget'], // Raw number for Excel
                $item['actual'], // Raw number for Excel
                $item['variance'], // Raw number for Excel
                $item['variance_percent'], // Raw number for Excel
            ];
        }

        // Add grand total row if summary exists
        if (isset($this->data['summary'])) {
            $summary = $this->data['summary'];
            $exportData[] = [
                '', // Empty Kode Akun
                '', // Empty Nama Akun
                '', // Empty Kode Project
                'GRAND TOTAL:', // Nama Project as label
                $summary['grand_total_budget'],
                $summary['grand_total_actual'],
                $summary['grand_total_variance'],
                $summary['grand_total_variance_percent'],
            ];
        }

        return $exportData;
    }

    public function headings(): array
    {
        // 8 columns  view table structure
        return [
            'Kode Akun',
            'Nama Akun',
            'Kode Project',
            'Nama Project',
            'Budget',
            'Actual',
            'Variance',
            'Variance %',
        ];
    }

    public function title(): string
    {
        return 'Budget vs Actual';
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
                
                //  header
                $sheet->setCellValue('A1', 'ORGANISASI PEREMPUAN MAHARDIKA');
                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'Budget vs Actual Report');
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
                $sheet->setCellValue('A5', 'No. Dokumen: BA-' . date('YmdHis'));
                $sheet->mergeCells('A5:H5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Period info
                $periodText = 'Periode: ';
                if ($this->fromDate && $this->toDate) {
                    $periodText .= date('d M Y', strtotime($this->fromDate)) . ' s/d ' . date('d M Y', strtotime($this->toDate));
                } else {
                    $periodText .= 'Semua Periode';
                }
                $sheet->setCellValue('A6', $periodText);
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
                
                // Adjust column widths to match view
                $sheet->getColumnDimension('A')->setWidth(15); // Kode Akun
                $sheet->getColumnDimension('B')->setWidth(30); // Nama Akun
                $sheet->getColumnDimension('C')->setWidth(15); // Kode Project
                $sheet->getColumnDimension('D')->setWidth(30); // Nama Project
                $sheet->getColumnDimension('E')->setWidth(18); // Budget
                $sheet->getColumnDimension('F')->setWidth(18); // Actual
                $sheet->getColumnDimension('G')->setWidth(18); // Variance
                $sheet->getColumnDimension('H')->setWidth(15); // Variance %
                
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
                
                // Number formatting for currency columns (Budget, Actual, Variance)
                $sheet->getStyle('E10:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('E10:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Percentage formatting for Variance %
                $sheet->getStyle('H10:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00%');
                $sheet->getStyle('H10:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Style total rows (find rows containing "Total" or "GRAND TOTAL")
                for ($row = 10; $row <= $lastRow; $row++) {
                    $cellD = $sheet->getCell('D' . $row)->getValue();
                    if (is_string($cellD) && (stripos($cellD, 'Total') !== false || stripos($cellD, 'GRAND TOTAL') !== false)) {
                        // Style total rows
                        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => stripos($cellD, 'GRAND TOTAL') !== false ? 'D3E5F3' : 'F0F0F0',
                                ],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}

