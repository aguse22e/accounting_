# Perbaikan Keamanan accounting_ — 23 November 2026

Audit: `SECURITY-AUDIT` (cek repo lain, 23 Nov 2026). Injeksi SQL: **aman** —
semua query mentah memakai teks tetap, tidak ada input pengguna yang
disisipkan, tidak ada `$_POST`/`$_GET`. Perbaikan di bawah ini dikerjakan
di salinan lokal `repo-study/accounting_` (HEAD remote `7d91eee`) dan
**belum di-upload ke GitHub**.

## 1. KRITIS — Kredensial hardcode dihapus (LoginController + UserSeeder)

- `app/Http/Controllers/Auth/LoginController.php`: blok `$bootstrapUsers`
  (6 akun admin/manager dengan kata sandi tertulis di kode) **dihapus total**.
  Login sekarang hanya lewat `Auth::attempt` + fallback migrasi kata sandi
  plaintext lama (tetap dipertahankan, karena database lama mungkin masih
  menyimpan kata sandi plaintext; fallback ini langsung meng-hash ulang).
- `database/seeders/UserSeeder.php`: ditulis ulang. Kata sandi tidak lagi
  tertulis di kode — dibaca dari variabel lingkungan
  `SEED_PASSWORD_<KUNCI>` (mis. `SEED_PASSWORD_KETUA`), atau dibuat acak
  16 karakter dan ditampilkan sekali di output `artisan db:seed`.
  Akun manager (`manager@accounting.system`, peran `manager`) kini ikut
  dibuat seeder — sebelumnya akun ini hanya tercipta lewat blok bootstrap.

> **WAJIB dilakukan di database produksi:** kata sandi lama keenam akun itu
> sudah pernah tertulis di riwayat git dan tidak bisa ditarik kembali dari
> sana. Ganti kata sandi keenam akun di database yang sedang berjalan
> (lewat halaman pengaturan profil masing-masing akun, atau langsung
> `Hash::make()` lewat tinker). Sampai dirotasi, siapa pun yang pernah
> membaca repo masih bisa masuk dengan kata sandi lama. Pertimbangkan juga
> menulis ulang riwayat git (mis. `git filter-repo`) lalu force-push —
> langkah ini destruktif dan harus diputuskan terpisah.

## 2. KRITIS — Rute auto-login tanpa kata sandi dihapus

- `routes/web.php`: rute publik `GET /quick-login` (masuk otomatis sebagai
  superadmin, tanpa autentikasi apa pun) dan `GET /test-simple` (rute debug)
  **dihapus**.

## 3. TINGGI — Login dibatasi percobaannya

- `POST /login` kini memakai middleware `throttle:5,1` — 5 percobaan gagal
  per menit per IP, setelah itu 429. (Sebelumnya tanpa batas sama sekali.)

## 4. SEDANG — Celah peran manager ditutup (ManagerAccess)

Middleware `ManagerAccess` sebenarnya sudah ada, tapi bisa dilewati: ia
hanya memeriksa **nama rute**, dan 20 rute tidak bernama (nama `null`)
lolos begitu saja — termasuk `POST/PUT/DELETE /api/akun`,
`POST/PUT/DELETE /api/project`, `POST/PUT/DELETE /api/budget`, dan
impor master. Seorang manager bisa menulis data master lewat API padahal
tampilannya menyembunyikan menu itu.

- Semua 16 rute API + `master.akuntansi` kini **diberi nama**
  (`api.akun.*`, `api.project.*`, `api.budget.*`, `api.management-data`).
- Rute baca saja (`api.akun.index/show/by-kode`, `api.management-data`,
  `api.project.index`, `api.budget.index`) ditambahkan ke allowlist
  manager agar halaman ledger tetap berfungsi; rute tulis tetap diblokir.
- Middleware diubah menjadi **deny-by-default**: rute tanpa nama pun kini
  ditolak untuk manager (sebelumnya lolos). Semua 23 nama di allowlist
  sudah diverifikasi ada di `routes/web.php`.

## 5. SEDANG — XSS tersimpan di halaman budget-actual

- `resources/views/budget-actual/index.blade.php`: dua `{!! json_encode(...) !!}`
  diganti `@json(...)`. Nama akun/proyek (diisi pengguna) sebelumnya
  dimasukkan mentah ke dalam `<script>` — nama berisi `</script>` bisa
  menjalankan kode di browser siapa pun yang membuka halaman itu.

## 6. RENDAH — Pesan error database disamarkan (18 tempat)

- `AkunController` (3), `JurnalController` (9), `LedgerController` (5),
  `ProjectImport` (1): pesan yang ditampilkan ke pengguna diganti
  `'Terjadi kesalahan.'` (atau padanan Indonesianya), dengan `report($e);`
  agar rincian tetap masuk log server. Sisa 4 `getMessage()` di
  `LedgerController` hanya ada di dalam `Log::error()` (log server, aman).

## 7. RENDAH — Lain-lain

- `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`
  (sebelumnya `local` / `true`).
- `app/Exceptions/Handler.php`: blok debug "agent log" yang menulis
  `debug-ddb0e0.log` dihapus.

## Berkas yang diubah (11)

`.env.example`, `app/Exceptions/Handler.php`,
`app/Http/Controllers/AkunController.php`,
`app/Http/Controllers/Auth/LoginController.php`,
`app/Http/Controllers/JurnalController.php`,
`app/Http/Controllers/LedgerController.php`,
`app/Http/Middleware/ManagerAccess.php`,
`app/Imports/ProjectImport.php`,
`database/seeders/UserSeeder.php`,
`resources/views/budget-actual/index.blade.php`, `routes/web.php`.

## Yang harus dijalankan di mesin Laravel sebelum deploy

1. `php -l` untuk 11 berkas di atas (mesin ini tidak punya PHP).
2. Uji login: kata sandi salah 6x berturut-turut harus kena 429.
3. `GET /quick-login` harus 404.
4. Login sebagai manager, lalu `POST /api/akun` dan `DELETE /api/akun/1`
   harus dialihkan ke ledger (bukan berhasil).
5. Buka halaman budget-actual dengan nama akun berisi `</script>` —
   harus tampil sebagai teks, bukan menjalankan kode.
6. Jalankan `php artisan db:seed --class=UserSeeder` di database baru,
   catat kata sandi acak yang tampil, lalu ganti setelah login pertama.
   Di database lama, JANGAN jalankan seeder ini sebelum merotasi kata
   sandi (poin 1 di atas) — seeder memakai `updateOrCreate` dan akan
   menimpa kata sandi akun yang sudah ada dengan kata sandi acak.
