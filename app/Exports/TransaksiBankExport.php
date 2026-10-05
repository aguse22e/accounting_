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

class TransaksiBankExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $transaksis;

    public function __construct($transaksis)
    {
        $this->transaksis = $transaksis;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->transaksis;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Tanggal',
            'Kode Akun',
            'Nama Akun',
            'Tipe',
            'Debit',
            'Kredit',
            'Saldo',
        ];
    }

    /**
     * @var mixed $transaksi
     */
    public function map($transaksi): array
    {
        $nominal = floatval($transaksi->nominal ?? 0);
        $isDebit = ($transaksi->tipe === 'Debit');

        return [
            $transaksi->kode_transaksi_bank ?? 'N/A',
            \Carbon\Carbon::parse($transaksi->tanggal ?? now())->format('d/m/Y'),
            $transaksi->akun_kode ?? 'N/A',
            $transaksi->akun->nama_akun ?? 'N/A',
            $transaksi->tipe ?? 'Debit',
            $isDebit ? $nominal : 0,
            !$isDebit ? $nominal : 0,
            $transaksi->saldo ?? 0,
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20, // Kode Transaksi
            'B' => 15, // Tanggal
            'C' => 15, // Kode Akun
            'D' => 30, // Nama Akun
            'E' => 12, // Tipe
            'F' => 18, // Debit
            'G' => 18, // Kredit
            'H' => 18, // Saldo
        ];
    }

    /**
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:H1')->applyFromArray([
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
            $sheet->getStyle('A2:H' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Format number columns (Debit, Kredit, Saldo)
            $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');

            // Center align tipe column
            $sheet->getStyle('E2:E' . $lastRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Right align number columns
            $sheet->getStyle('F2:H' . $lastRow)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }
}
