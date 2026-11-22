<?php

namespace App\Imports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;

class ProjectImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;
    
    public $created = 0;
    public $skipped = 0;
    public $errors = [];
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            // Map column names (case-insensitive and flexible)
            $kode_project = $this->getValue($row, ['kode_project', 'kode project', 'kode_project']);
            $nama_project = $this->getValue($row, ['nama_project', 'nama project', 'nama_project']);
            
            // Skip if required fields are missing
            if (empty($kode_project) || empty($nama_project)) {
                $this->skipped++;
                return null;
            }
            
            $deskripsi = $this->getValue($row, ['deskripsi', 'description', 'keterangan']) ?? '';
            $tanggal_mulai = $this->getValue($row, ['tanggal_mulai', 'tanggal mulai', 'tanggal_mulai', 'date', 'tanggal']);
            $tanggal_selesai = $this->getValue($row, ['tanggal_selesai', 'tanggal selesai', 'tanggal_selesai', 'end_date']);
            $status = $this->getValue($row, ['status', 'status']) ?? 'Active';
            $saldo = $this->getValue($row, ['saldo', 'balance']) ?? 0;

            // Parse dates
            $tanggalMulai = $this->parseDate($tanggal_mulai) ?? now();
            $tanggalSelesai = $this->parseDate($tanggal_selesai); // Can be null

            // Check if project already exists
            $existingProject = Project::where('kode_project', $kode_project)->first();
            if ($existingProject) {
                $this->skipped++;
                return null;
            }

            $project = new Project([
                'kode_project' => $kode_project,
                'nama_project' => $nama_project,
                'tanggal' => $tanggalMulai,
                'saldo' => (float) $saldo,
                'kode_akun' => null,
            ]);
            
            $this->created++;
            return $project;
        } catch (\Exception $e) {
            $this->skipped++;
            $this->errors[] = 'Terjadi kesalahan saat memproses baris ini.';
            return null;
        }
    }

    /**
     * Get value from row using multiple possible keys
     */
    private function getValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $normalizedKey = strtolower(str_replace(['_', ' '], ['', ''], $key));
            
            // Try exact match first (case-insensitive)
            foreach ($row as $rowKey => $value) {
                $normalizedRowKey = strtolower(str_replace(['_', ' '], ['', ''], (string) $rowKey));
                if ($normalizedRowKey === $normalizedKey) {
                    return $value === '' || $value === null ? null : $value;
                }
            }
        }
        
        return null;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($date)
    {
        if (empty($date) || $date === null) {
            return null;
        }

        try {
            // Try to parse as Excel date (numeric)
            if (is_numeric($date)) {
                // Excel stores dates as serial numbers
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                return Carbon::instance($excelDate)->format('Y-m-d');
            }
            
            // Try Carbon parsing
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            '*.kode_project' => 'required',
            '*.nama_project' => 'required',
        ];
    }
}
