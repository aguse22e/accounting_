<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateExcelTemplates extends Command
{
    protected $signature = 'templates:generate-excel';
    protected $description = 'Generate .xlsx templates from existing CSV templates in storage/app/imports/templates';

    public function handle(): int
    {
        $basePath = storage_path('app/imports/templates');
        if (!is_dir($basePath)) {
            $this->error('Folder not found: ' . $basePath);
            return self::FAILURE;
        }

        $files = [
            'Project_import_valid',
            'Project_import_invalid',
            'Budget_import_valid',
            'Budget_import_invalid',
            'Transactions_import_valid',
            'Transactions_import_invalid',
            'BlackBox_Checklist',
        ];

        $generated = 0; $skipped = 0;
        foreach ($files as $name) {
            $csvPath = $basePath . DIRECTORY_SEPARATOR . $name . '.csv';
            if (!file_exists($csvPath)) { $this->warn("Skip missing: {$name}.csv"); $skipped++; continue; }

            $rows = $this->readCsv($csvPath);
            if (empty($rows)) { $this->warn("Skip empty: {$name}.csv"); $skipped++; continue; }

            $xlsxPath = $basePath . DIRECTORY_SEPARATOR . $name . '.xlsx';
            $this->writeXlsx($rows, $xlsxPath);
            $this->info('Written: ' . $xlsxPath);
            $generated++;
        }

        $this->line("Done. Generated: {$generated}, Skipped: {$skipped}");
        return self::SUCCESS;
    }

    /** @return array<int, array<int, string>> */
    private function readCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = array_map(static fn($v) => (string) $v, $data);
            }
            fclose($handle);
        }
        return $rows;
    }

    /** @param array<int, array<int, string>> $rows */
    private function writeXlsx(array $rows, string $targetPath): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $rIdx => $cols) {
            foreach ($cols as $cIdx => $val) {
                $sheet->setCellValueByColumnAndRow($cIdx + 1, $rIdx + 1, $val);
            }
        }
        // Basic autosize for first 12 columns
        for ($i = 1; $i <= 12; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($targetPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}


