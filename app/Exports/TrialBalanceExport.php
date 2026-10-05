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

class TrialBalanceExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
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
        $akun = Akun::select(['kode_akun','nama','tipe'])
            ->orderBy('kode_akun')
            ->get();

        $openingDate = date('Y-m-d', strtotime($this->tanggal_dari.' -1 day'));

        // Get transactions for the period
        $allTransactions = Transaksi::select('kode_voucer', 'akun_kode', 'tipe', 'nominal', 'tanggal', 'debit', 'kredit', 'transaksi_type', 'jenis', 'jumlah')
            ->whereBetween('tanggal', [$this->tanggal_dari, $this->tanggal_sampai])
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();
        
        // Get opening balance transactions (before period)
        $openingTransactions = Transaksi::select('kode_voucer', 'akun_kode', 'tipe', 'nominal', 'tanggal', 'debit', 'kredit', 'transaksi_type', 'jenis', 'jumlah')
            ->where('tanggal', '<', $this->tanggal_dari)
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        // Calculate balances for each account
        foreach ($akun as $a) {
            // Opening balance
            $opDebit = 0;
            $opKredit = 0;
            
            // Get opening transactions for this account
            $accountOpening = $openingTransactions->where('akun_kode', $a->kode_akun);
            foreach ($accountOpening as $trans) {
                $jenis = $trans->jenis ?? strtolower($trans->transaksi_type ?? 'debit');
                $jumlah = $trans->jumlah ?? $trans->nominal ?? 0;
                
                if ($jenis === 'debit') {
                    $opDebit += (float)$jumlah;
                } else {
                    $opKredit += (float)$jumlah;
                }
            }
            
            // Add saldo_awal
            if ($a->saldo_awal > 0) {
                if (strtolower($a->saldo_normal ?? 'debit') === 'debit') {
                    $opDebit += (float)$a->saldo_awal;
                } else {
                    $opKredit += (float)$a->saldo_awal;
                }
            }
            
            // Current period transactions
            $chDebit = 0;
            $chKredit = 0;
            
            $accountCurrent = $allTransactions->where('akun_kode', $a->kode_akun);
            foreach ($accountCurrent as $trans) {
                $jenis = $trans->jenis ?? strtolower($trans->transaksi_type ?? 'debit');
                $jumlah = $trans->jumlah ?? $trans->nominal ?? 0;
                
                if ($jenis === 'debit') {
                    $chDebit += (float)$jumlah;
                } else {
                    $chKredit += (float)$jumlah;
                }
            }
            
            // Ending balance
            $endingDebit = $opDebit + $chDebit - $chKredit;
            $endingKredit = $opKredit + $chKredit - $chDebit;
            
            // Store calculated values
            $a->opDebit = max(0, $endingDebit);
            $a->opKredit = max(0, $endingKredit);
            $a->chDebit = $chDebit;
            $a->chKredit = $chKredit;
            $a->endingDebit = max(0, $endingDebit);
            $a->endingKredit = max(0, $endingKredit);
        }

        return $akun;
    }

    public function headings(): array
    {
        return [
            'No. Akun',
            'Nama Akun',
            'Saldo Awal Debit',
            'Saldo Awal Kredit',
            'Debit',
            'Kredit',
            'Saldo Akhir Debit',
            'Saldo Akhir Kredit',
            'Saldo',
        ];
    }

    public function map($akun): array
    {
        $saldo = ($akun->endingDebit ?? 0) - ($akun->endingKredit ?? 0);
        return [
            $akun->kode_akun,
            $akun->nama_akun,
            (float)($akun->opDebit ?? 0),
            (float)($akun->opKredit ?? 0),
            (float)($akun->chDebit ?? 0),
            (float)($akun->chKredit ?? 0),
            (float)($akun->endingDebit ?? 0),
            (float)($akun->endingKredit ?? 0),
            (float)$saldo,
        ];
    }

    public function title(): string
    {
        return 'Neraca Saldo';
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
                $sheet->setCellValue('A2', 'NERACA SALDO');
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
                $sheet->setCellValue('A5', 'No. Dokumen: NS-' . date('YmdHis'));
                $sheet->mergeCells('A5:I5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->tanggal_dari)) . ' s/d ' . date('d M Y', strtotime($this->tanggal_sampai)));
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
                $sheet->getColumnDimension('A')->setWidth(12); // No. Akun
                $sheet->getColumnDimension('B')->setWidth(30); // Nama Akun
                $sheet->getColumnDimension('C')->setWidth(18); // Saldo Awal Debit
                $sheet->getColumnDimension('D')->setWidth(18); // Saldo Awal Kredit
                $sheet->getColumnDimension('E')->setWidth(15); // Debit
                $sheet->getColumnDimension('F')->setWidth(15); // Kredit
                $sheet->getColumnDimension('G')->setWidth(18); // Saldo Akhir Debit
                $sheet->getColumnDimension('H')->setWidth(18); // Saldo Akhir Kredit
                $sheet->getColumnDimension('I')->setWidth(15); // Saldo

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
                $sheet->getStyle('C10:I' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('C10:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Add total row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $sheet->setCellValue('B' . $totalRow, '');
                $sheet->setCellValue('C' . $totalRow, '=SUM(C10:C' . $lastRow . ')');
                $sheet->setCellValue('D' . $totalRow, '=SUM(D10:D' . $lastRow . ')');
                $sheet->setCellValue('E' . $totalRow, '=SUM(E10:E' . $lastRow . ')');
                $sheet->setCellValue('F' . $totalRow, '=SUM(F10:F' . $lastRow . ')');
                $sheet->setCellValue('G' . $totalRow, '=SUM(G10:G' . $lastRow . ')');
                $sheet->setCellValue('H' . $totalRow, '=SUM(H10:H' . $lastRow . ')');
                $sheet->setCellValue('I' . $totalRow, '=SUM(I10:I' . $lastRow . ')');

                // Style total row
                $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'ECECEC',
                        ],
                    ],
                ]);
            },
        ];
    }
}

