<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class JurnalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $journalEntries;

    public function __construct($journalEntries)
    {
        $this->journalEntries = $journalEntries;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->journalEntries;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Journal Number',
            'Date',
            'Description',
            'Reference',
            'Project',
            'Total Debit',
            'Total Credit',
        ];
    }

    /**
     * @var mixed $entry
     */
    public function map($entry): array
    {
        return [
            $entry->journal_number ?? 'N/A',
            \Carbon\Carbon::parse($entry->journal_date ?? now())->format('d/m/Y'),
            $entry->description ?? '-',
            $entry->reference ?? '-',
            $entry->kode_project ?? 'N/A',
            $entry->total_debit ?? 0,
            $entry->total_credit ?? 0,
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20, // Journal Number
            'B' => 15, // Date
            'C' => 30, // Description
            'D' => 20, // Reference
            'E' => 15, // Project
            'F' => 18, // Total Debit
            'G' => 18, // Total Credit
        ];
    }

    /**
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '8B5CF6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Get the last row number
        $lastRow = $sheet->getHighestRow();

        // Style data rows
        if ($lastRow > 1) {
            $sheet->getStyle('A2:G' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Format number columns (Total Debit, Total Credit)
            $sheet->getStyle('F2:G' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');

            // Right align number columns
            $sheet->getStyle('F2:G' . $lastRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }
}