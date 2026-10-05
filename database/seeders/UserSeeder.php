<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun yang diizinkan login ke sistem keuangan
        // Ketua
        User::updateOrCreate(
            ['email' => 'ketua@accounting.system'],
            [
                'User_name' => 'Ketua',
                'password' => 'ikamahardhika',
            ]
        );

        // Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@accounting.system'],
            [
                'User_name' => 'Super Admin',
                'password' => 'dinipassword',
            ]
        );

        // Super
        User::updateOrCreate(
            ['email' => 'super@accounting.system'],
            [
                'User_name' => 'Super',
                'password' => 'aguskurniawan91',
            ]
        );

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@accounting.system'],
            [
                'User_name' => 'Admin',
                'password' => 'surimahardhika',
            ]
        );

        // Akun user: agusekurnia@gmail.com
        User::updateOrCreate(
            ['email' => 'agusekurnia@gmail.com'],
            [
                'User_name' => 'Agus Kurnia',
                'password' => 'fatiho123',
            ]
        );
    }
}


