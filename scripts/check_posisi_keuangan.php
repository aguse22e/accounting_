<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\KodeAkun;
use App\Models\Transaksi;
use App\Services\NeracaRule;

$codes = ['1101','1102','1103','1104','1105','1201','1202','1203','1204','1299','1301','1302','1303','2101','2102','2103','2104','3101','3102','3103'];

echo "=== Checking KodeAkun ===\n";
$found = 0;
foreach($codes as $code) {
    $akun = KodeAkun::where('kode_akun', $code)->first();
    if ($akun) {
        echo "✓ {$code}: {$akun->nama}\n";
        $found++;
    } else {
        echo "✗ {$code}: NOT FOUND\n";
    }
}
echo "\nFound: {$found}/" . count($codes) . " accounts\n\n";

echo "=== Checking Transactions ===\n";
$trans = Transaksi::whereIn('akun_kode', $codes)->get();
echo "Total transaksi untuk kode akun tersebut: " . $trans->count() . "\n";
if ($trans->count() > 0) {
    foreach($trans as $t) {
        echo "  - {$t->akun_kode}: {$t->transaksi_type} - Rp " . number_format($t->nominal ?? 0, 0, ',', '.') . "\n";
    }
}
echo "\n";

echo "=== Testing NeracaRule Calculation ===\n";
$tanggal_dari = '2025-10-01';
$tanggal_sampai = '2025-10-29';

$service = app(NeracaRule::class);
$result = $service->generateNeracaKeuangan($tanggal_dari, $tanggal_sampai);

echo "Aset Lancar:\n";
foreach($result['aset_lancar'] as $key => $value) {
    if ($key !== 'total') {
        echo "  {$key}: Rp " . number_format($value, 2, ',', '.') . "\n";
    }
}
echo "  Total: Rp " . number_format($result['aset_lancar']['total'], 2, ',', '.') . "\n";

echo "\nAset Tetap Total: Rp " . number_format($result['aset_tetap']['total'], 2, ',', '.') . "\n";
echo "Aset Lainnya: Rp " . number_format($result['aset_lainnya'], 2, ',', '.') . "\n";
echo "Liabilitas: Rp " . number_format($result['liabilitas'], 2, ',', '.') . "\n";
echo "Ekuitas: Rp " . number_format($result['ekuitas'], 2, ',', '.') . "\n";

echo "\n=== Checking specific account balances ===\n";
foreach(['1101','1102','1000','1100'] as $code) {
    $akun = KodeAkun::where('kode_akun', $code)->first();
    if ($akun) {
        $balance = $akun->getFreshBalance('1900-01-01', $tanggal_sampai);
        echo "{$code}: Rp " . number_format($balance ?? 0, 2, ',', '.') . "\n";
    } else {
        echo "{$code}: NOT FOUND\n";
    }
}

