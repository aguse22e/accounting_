<?php

namespace App\Exports;

use App\Models\KodeAkun;
use App\Services\NeracaRule;
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

class PosisiKeuanganExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithStyles
{
    protected $tanggal_dari;
    protected $tanggal_sampai;

    public function __construct($tanggal_dari, $tanggal_sampai = null)
    {
        $this->tanggal_dari = $tanggal_dari;
        // Jika hanya ada satu parameter, gunakan sebagai kedua tanggal
        $this->tanggal_sampai = $tanggal_sampai ?? $tanggal_dari;
    }

    public function collection()
    {
        // Gunakan NeracaRule untuk perhitungan berdasarkan rules
        $mappingService = app(NeracaRule::class);
        $ruleValues = $mappingService->generateNeracaKeuangan($this->tanggal_dari, $this->tanggal_sampai);
        
        // Define account structure based on the provided chart of accounts
        $accountbased = [
            'aset_lancar' => [
                '1101' => 'Kas dan Setara Kas',
                '1102' => 'Piutang Usaha', 
                '1103' => 'Persediaan',
                '1104' => 'Biaya Dibayar Dimuka',
                '1105' => 'Aset Lancar Lainnya'
            ],
            'aset_tetap' => [
                '1201' => 'Tanah',
                '1202' => 'Bangunan',
                '1203' => 'Mesin dan Peralatan',
                '1204' => 'Kendaraan',
                '1299' => 'Akumulasi Penyusutan Aset Tetap'
            ],
            'aset_lainnya' => [
                '1301' => 'Investasi Jangka Panjang',
                '1302' => 'Aset Tak Berwujud (Goodwill, Hak Paten)',
                '1303' => 'Aset Lainnya'
            ],
            'liabilitas' => [
                '2101' => 'Utang Usaha',
                '2102' => 'Utang Pajak',
                '2103' => 'Utang Jangka Panjang',
                '2104' => 'Beban yang Masih Harus Dibayar'
            ],
            'ekuitas' => [
                '3101' => 'Modal Saham',
                '3102' => 'Laba Ditahan',
                '3103' => 'Ekuitas Lainnya'
            ]
        ];

        $akun = KodeAkun::orderBy('kode_akun')->get();
        $collection = collect();

        // Add ASET section
        $collection->push(['category' => 'I. ASET', 'subcategory' => '', 'account_name' => '', 'balance' => '']);
        
        // Aset Lancar - gunakan nilai dari rules
        $collection->push(['category' => '', 'subcategory' => 'A. Aset Lancar', 'account_name' => '', 'balance' => '']);
        $mappingLancar = [
            '1101' => 'kas_dan_setara_kas',
            '1102' => 'piutang_usaha',
            '1103' => 'persediaan',
            '1104' => 'biaya_dibayar_dimuka',
            '1105' => 'aset_lancar_lainnya',
        ];
        foreach ($accountbased['aset_lancar'] as $kode => $nama) {
            $val = $ruleValues['aset_lancar'][$mappingLancar[$kode]] ?? 0;
            $collection->push(['category' => '', 'subcategory' => '', 'account_name' => $nama, 'balance' => $val]);
        }
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'Total Aset Lancar', 'balance' => $ruleValues['aset_lancar']['total'] ?? 0]);

        // Aset Tetap - gunakan nilai dari rules
        $collection->push(['category' => '', 'subcategory' => 'B. Aset Tetap', 'account_name' => '', 'balance' => '']);
        foreach ($accountbased['aset_tetap'] as $kode => $nama) {
            $val = 0;
            if ($kode === '1299') {
                $val = $ruleValues['aset_tetap']['akumulasi_penyusutan'] ?? 0;
            } else {
                // Untuk aset tetap lainnya, gunakan calculate langsung dari akun
                $akun_data = $akun->where('kode_akun', $kode)->first();
                if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                    $val = $akun_data->getFreshBalance('1900-01-01', $this->tanggal_sampai) ?? 0;
                    if (isset($akun_data->tipe) && $akun_data->tipe === 'Kredit') {
                        $val = abs($val);
                    }
                }
            }
            // Ensure positive values for assets
            $val = $val > 0 ? $val : 0;
            $collection->push(['category' => '', 'subcategory' => '', 'account_name' => $nama, 'balance' => $val]);
        }
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'Total Aset Tetap', 'balance' => $ruleValues['aset_tetap']['total'] ?? 0]);

        // Aset Lainnya - gunakan nilai dari rules
        $collection->push(['category' => '', 'subcategory' => 'C. Aset Lainnya', 'account_name' => '', 'balance' => '']);
        foreach ($accountbased['aset_lainnya'] as $kode => $nama) {
            $akun_data = $akun->where('kode_akun', $kode)->first();
            $val = 0;
            if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                $val = $akun_data->getFreshBalance('1900-01-01', $this->tanggal_sampai) ?? 0;
                if (isset($akun_data->tipe) && $akun_data->tipe === 'Kredit') {
                    $val = abs($val);
                }
            }
            // Ensure positive values for assets
            $val = $val > 0 ? $val : 0;
            $collection->push(['category' => '', 'subcategory' => '', 'account_name' => $nama, 'balance' => $val]);
        }
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'Total Aset Lainnya', 'balance' => $ruleValues['aset_lainnya'] ?? 0]);
        
        // TOTAL ASET dari rules
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'TOTAL ASET', 'balance' => $ruleValues['total_aset']['total'] ?? 0]);

        // Add LIABILITAS section - gunakan total dari rules
        $collection->push(['category' => 'II. LIABILITAS', 'subcategory' => '', 'account_name' => '', 'balance' => '']);
        foreach ($accountbased['liabilitas'] as $kode => $nama) {
            $akun_data = $akun->where('kode_akun', $kode)->first();
            $val = 0;
            if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                $val = $akun_data->getFreshBalance('1900-01-01', $this->tanggal_sampai) ?? 0;
            }
            // For liabilities, show absolute value (should be positive)
            $val = abs($val);
            $collection->push(['category' => '', 'subcategory' => '', 'account_name' => $nama, 'balance' => $val]);
        }
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'Total Liabilitas', 'balance' => $ruleValues['liabilitas'] ?? 0]);

        // Add EKUITAS section - gunakan total dari rules
        $collection->push(['category' => 'III. EKUITAS', 'subcategory' => '', 'account_name' => '', 'balance' => '']);
        foreach ($accountbased['ekuitas'] as $kode => $nama) {
            $akun_data = $akun->where('kode_akun', $kode)->first();
            $val = 0;
            if ($akun_data && method_exists($akun_data, 'getFreshBalance')) {
                $val = $akun_data->getFreshBalance('1900-01-01', $this->tanggal_sampai) ?? 0;
            }
            // For equity, show absolute value (should be positive)
            $val = abs($val);
            $collection->push(['category' => '', 'subcategory' => '', 'account_name' => $nama, 'balance' => $val]);
        }
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'Total Ekuitas', 'balance' => $ruleValues['ekuitas'] ?? 0]);
        
        // TOTAL LIABILITAS DAN EKUITAS dari rules
        $collection->push(['category' => '', 'subcategory' => '', 'account_name' => 'TOTAL LIABILITAS DAN EKUITAS', 'balance' => $ruleValues['total_liabilitas_dan_ekuitas'] ?? 0]);

        return $collection;
    }

    public function headings(): array
    {
        return [
            'Kategori',
            'Subkategori', 
            'Keterangan',
            'Nilai (Rp)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['category'],
            $row['subcategory'],
            $row['account_name'],
            $row['balance'],
        ];
    }

    public function title(): string
    {
        return 'Neraca Keuangan';
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
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Report title
                $sheet->setCellValue('A2', 'NERACA KEUANGAN');
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
                $sheet->setCellValue('A5', 'No. Dokumen: NK-' . date('YmdHis'));
                $sheet->mergeCells('A5:D5');
                $sheet->getStyle('A5')->getFont()->setSize(10);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $tanggalDariObj = \Carbon\Carbon::parse($this->tanggal_dari);
                $tanggalSampaiObj = \Carbon\Carbon::parse($this->tanggal_sampai);
                
                if ($this->tanggal_dari === $this->tanggal_sampai) {
                    $sheet->setCellValue('A6', 'Per Tanggal: ' . $tanggalDariObj->format('d F Y'));
                } else {
                    $sheet->setCellValue('A6', 'Periode: ' . $tanggalDariObj->format('d F Y') . ' - ' . $tanggalSampaiObj->format('d F Y'));
                }
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
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(40);
                $sheet->getColumnDimension('D')->setWidth(20);
                
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
                
                // Number formatting for currency column
                $sheet->getStyle('D10:D' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('D10:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Style category headers
                for ($row = 10; $row <= $lastRow; $row++) {
                    $category = $sheet->getCell('A' . $row)->getValue();
                    $subcategory = $sheet->getCell('B' . $row)->getValue();
                    $accountName = $sheet->getCell('C' . $row)->getValue();
                    
                    if (!empty($category)) {
                        // Main category styling
                        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 12,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => 'D3D3D3',
                                ],
                            ],
                        ]);
                    } elseif (!empty($subcategory)) {
                        // Subcategory styling
                        $sheet->getStyle('B' . $row . ':D' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => 'F0F0F0',
                                ],
                            ],
                        ]);
                    } elseif (strpos($accountName, 'Total') !== false || strpos($accountName, 'TOTAL') !== false) {
                        // Total row styling
                        $sheet->getStyle('C' . $row . ':D' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => 'E6E6E6',
                                ],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
