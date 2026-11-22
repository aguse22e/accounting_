<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Buat akun awal sistem keuangan.
     *
     * Kata sandi TIDAK ditulis di kode. Urutan sumbernya:
     *  1. Variabel lingkungan SEED_PASSWORD_<KUNCI> (mis. SEED_PASSWORD_KETUA)
     *  2. Jika tidak ada, dibuat acak 16 karakter dan ditampilkan sekali
     *     di output artisan db:seed — simpan dan ganti segera setelah login.
     *
     * Akun-akun lama yang kata sandinya pernah tertulis di repo harus
     * dirotasi manual di database produksi (kata sandi lama sudah bocor
     * lewat riwayat git dan tidak bisa ditarik kembali dari sana).
     */
    public function run(): void
    {
        $accounts = [
            ['email' => 'ketua@accounting.system', 'User_name' => 'Ketua', 'role' => 'admin', 'env' => 'SEED_PASSWORD_KETUA'],
            ['email' => 'superadmin@accounting.system', 'User_name' => 'Super Admin', 'role' => 'admin', 'env' => 'SEED_PASSWORD_SUPERADMIN'],
            ['email' => 'super@accounting.system', 'User_name' => 'Super', 'role' => 'admin', 'env' => 'SEED_PASSWORD_SUPER'],
            ['email' => 'admin@accounting.system', 'User_name' => 'Admin', 'role' => 'admin', 'env' => 'SEED_PASSWORD_ADMIN'],
            ['email' => 'agusekurnia@gmail.com', 'User_name' => 'Agus Kurnia', 'role' => 'admin', 'env' => 'SEED_PASSWORD_AGUS'],
            ['email' => 'manager@accounting.system', 'User_name' => 'Manager', 'role' => 'manager', 'env' => 'SEED_PASSWORD_MANAGER'],
        ];

        foreach ($accounts as $account) {
            $password = env($account['env']) ?: Str::random(16);

            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'User_name' => $account['User_name'],
                    'password' => $password, // di-hash otomatis oleh cast 'hashed' di model User
                    'role' => $account['role'],
                ]
            );

            if (!env($account['env'])) {
                $this->command->info("Akun {$account['email']} dibuat dengan kata sandi acak: {$password}");
                $this->command->warn('Simpan kata sandi di atas lalu ganti segera setelah login pertama.');
            }
        }
    }
}
