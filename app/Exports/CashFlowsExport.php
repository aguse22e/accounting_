<?php

namespace App\Exports;

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

class CashFlowsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
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
        // Return collection dengan data cash flows
        $data = collect([
            (object)['keterangan' => 'AKTIVITAS OPERASIONAL', 'jumlah' => $this->getOperating()],
            (object)['keterangan' => 'AKTIVITAS INVESTASI', 'jumlah' => $this->getInvesting()],
            (object)['keterangan' => 'AKTIVITAS PENDANAAN', 'jumlah' => $this->getFinancing()],
            (object)['keterangan' => 'NET CASH FLOW', 'jumlah' => $this->getNetCashFlow()],
            (object)['keterangan' => 'Saldo Kas Awal', 'jumlah' => $this->getCashBeginning()],
            (object)['keterangan' => 'SALDO KAS AKHIR', 'jumlah' => $this->getCashEnding()],
        ]);
        
        return $data;
    }

    protected function getOperating()
    {
        $jurnalTableExists = Schema::hasTable('jurnal');
        $operating = 0.0;
        
        if ($jurnalTableExists) {
            $operating += (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where(function($q) {
                    $q->where('kode', 'like', '4%')
                      ->orWhere('kode', 'like', '5%')
                      ->orWhere('lawan', 'like', '4%')
                      ->orWhere('lawan', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        if (Schema::hasTable('transaksi_bank')) {
            $operating += (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '4%')
                      ->orWhere('akun_kode', 'like', '5%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        return $operating;
    }

    protected function getInvesting()
    {
        $jurnalTableExists = Schema::hasTable('jurnal');
        $investing = 0.0;
        
        if ($jurnalTableExists) {
            $investing += (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where(function($q) {
                    $q->where('kode', 'like', '12%')
                      ->orWhere('lawan', 'like', '12%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        if (Schema::hasTable('transaksi_bank')) {
            $investing += (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where('akun_kode', 'like', '12%')
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        return $investing;
    }

    protected function getFinancing()
    {
        $jurnalTableExists = Schema::hasTable('jurnal');
        $financing = 0.0;
        
        if ($jurnalTableExists) {
            $financing += (float) DB::table('jurnal')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where(function($q) {
                    $q->where('kode', 'like', '2%')
                      ->orWhere('kode', 'like', '3%')
                      ->orWhere('lawan', 'like', '2%')
                      ->orWhere('lawan', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(nominal, 0) ELSE -COALESCE(nominal, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        if (Schema::hasTable('transaksi_bank')) {
            $financing += (float) DB::table('transaksi_bank')
                ->whereNull('deleted_at')
                ->whereDate('tanggal', '>=', $this->from)
                ->whereDate('tanggal', '<=', $this->to)
                ->where(function($q) {
                    $q->where('akun_kode', 'like', '2%')
                      ->orWhere('akun_kode', 'like', '3%');
                })
                ->selectRaw('SUM(CASE WHEN LOWER(COALESCE(tipe, \'\')) = \'debit\' THEN COALESCE(total, 0) ELSE -COALESCE(total, 0) END) AS total')
                ->value('total') ?? 0.0;
        }
        
        return $financing;
    }

    protected function getNetCashFlow()
    {
        return $this->getOperating() + $this->getInvesting() + $this->getFinancing();
    }

    protected function getCashBeginning()
    {
        $openingDate = date('Y-m-d', strtotime($this->from.' -1 day'));
        $cashAccounts = Akun::where('kode_akun', 'like', '11%')->get();
        $cashBeginning = 0.0;
        foreach ($cashAccounts as $acc) {
            $cashBeginning += $acc->getBalance('1900-01-01', $openingDate);
        }
        return $cashBeginning;
    }

    protected function getCashEnding()
    {
        return $this->getCashBeginning() + $this->getNetCashFlow();
    }

    public function headings(): array
    {
        return [
            'Keterangan',
            'Jumlah',
        ];
    }

    public function map($row): array
    {
        return [
            $row->keterangan,
            (float)$row->jumlah,
        ];
    }

    public function title(): string
    {
        return 'Arus Kas';
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
                $sheet->mergeCells('A1:B1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'NERACA KEUANGAN');
                $sheet->mergeCells('A2:B2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Organization info
                $sheet->setCellValue('A3', 'Alamat: Jl. Kedondong I No.39, RT.10/RW.9, Rawamangun, Pulo Gadung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13220, Indonesia');
                $sheet->mergeCells('A3:B3');
                $sheet->getStyle('A3')->getFont()->setSize(10);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A4', 'Email: mail@mahardhika.org | Telp: 0813-8872-5150');
                $sheet->mergeCells('A4:B4');
                $sheet->getStyle('A4')->getFont()->setSize(10);
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Document info
                $sheet->setCellValue('A5', 'No. Dokumen: CF-' . date('YmdHis'));
                $sheet->mergeCells('A5:B5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $sheet->setCellValue('A6', 'Periode: ' . date('d M Y', strtotime($this->from)) . ' s/d ' . date('d M Y', strtotime($this->to)));
                $sheet->mergeCells('A6:B6');
                $sheet->getStyle('A6')->getFont()->setSize(10);
                $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Location and date
                $sheet->setCellValue('A7', 'Tanggal Print: ' . date('d M Y H:i:s'));
                $sheet->mergeCells('A7:B7');
                $sheet->getStyle('A7')->getFont()->setSize(10);
                $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Empty row
                $sheet->setCellValue('A8', '');
                
                // Adjust column widths
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(20);
                
                // Style the data table
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A9:B' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Header row styling
                $sheet->getStyle('A9:B9')->applyFromArray([
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
                
                // Number formatting for currency column
                $sheet->getStyle('B10:B' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('B10:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Style section titles and totals
                $sheet->getStyle('A10:A12')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECECEC'],
                    ],
                ]);
                $sheet->getStyle('A13:A13')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECECEC'],
                    ],
                ]);
                $sheet->getStyle('A15:A15')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECECEC'],
                    ],
                ]);
            },
        ];
    }
}

