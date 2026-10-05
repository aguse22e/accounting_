<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ChartOfAccountsSeeder::class,
            AkunSeeder::class,
            KodeAkunSeeder::class,
            // KunciTransaksiSeeder::class, // Disabled - table doesn't exist
            UserActivitySeeder::class,
        ]);
    }
}


