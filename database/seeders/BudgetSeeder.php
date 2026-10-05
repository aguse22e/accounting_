<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample budget data with basic required columns
        $budgets = [
            [
                'kode_project' => 'PROJ001',
                'kode_akun' => '1101',
                'deskripsi' => 'Budget for Kas dan Setara Kas - Project 001',
                'anggaran' => 1000000.00,
                'tanggal' => '2024-01-01 00:00:00',
            ],
            [
                'kode_project' => 'PROJ001',
                'kode_akun' => '1102',
                'deskripsi' => 'Budget for Piutang Usaha - Project 001',
                'anggaran' => 500000.00,
                'tanggal' => '2024-01-01 00:00:00',
            ],
            [
                'kode_project' => 'PROJ002',
                'kode_akun' => '1101',
                'deskripsi' => 'Budget for Kas dan Setara Kas - Project 002',
                'anggaran' => 750000.00,
                'tanggal' => '2024-01-01 00:00:00',
            ],
            [
                'kode_project' => 'PROJ002',
                'kode_akun' => '1201',
                'deskripsi' => 'Budget for Tanah - Project 002',
                'anggaran' => 2000000.00,
                'tanggal' => '2024-01-01 00:00:00',
            ],
        ];

        foreach ($budgets as $budget) {
            DB::table('budget')->insert($budget);
        }
    }
}
