<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Akun extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'kode_akun';
    protected $primaryKey = 'kode_akun';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'kode_akun',
        'nama',
        'tipe',
    ];
    
    // Accessors to maintain backward compatibility with old column names
    public function getNamaAkunAttribute()
    {
        return $this->nama;
    }
    
    public function getSaldoNormalAttribute()
    {
        return $this->tipe;
    }
    
    public function getSaldoAwalAttribute()
    {
        return 0; // Default to 0 as column doesn't exist in kode_akun table
    }
    
    // Note: Relationship removed karena tabel transaksi tidak memiliki kolom akun_kode
    // public function transaksi()
    // {
    //     return $this->hasMany(Transaksi::class, 'akun_kode', 'kode_akun');
    // }
    
    public function getSaldoDebit($tanggal_dari, $tanggal_sampai)
    {
        $debit = 0;

        // 1. Ambil dari tabel transaksi
        $transaksiDebit = DB::table('transaksi')
            ->where('akun_kode', $this->kode_akun)
            ->whereDate('tanggal', '>=', $tanggal_dari)
            ->whereDate('tanggal', '<=', $tanggal_sampai)
            ->where(DB::raw('LOWER(COALESCE(transaksi_type, ""))'), 'debit')
            ->sum(DB::raw('COALESCE(jumlah, COALESCE(nominal, 0))'));

        $debit += (float)$transaksiDebit;

        // 2. Ambil dari tabel jurnal
        if (Schema::hasTable('jurnal')) {
            $jurnalDebit = DB::table('jurnal')
                ->where('kode', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
                ->sum(DB::raw('COALESCE(nominal, 0)'));

            $debit += (float)$jurnalDebit;

            // Juga dari kolom lawan
            $jurnalLawanDebit = DB::table('jurnal')
                ->where('lawan', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
                ->sum(DB::raw('COALESCE(nominal, 0)'));

            $debit += (float)$jurnalLawanDebit;
        }

        // 3. Ambil dari tabel transaksi_bank
        if (Schema::hasTable('transaksi_bank')) {
            $bankDebit = DB::table('transaksi_bank')
                ->where('akun_kode', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
                ->whereNull('deleted_at')
                ->sum(DB::raw('COALESCE(total, 0)'));

            $debit += (float)$bankDebit;
        }

        return (float)$debit;
    }

    public function getSaldoKredit($tanggal_dari, $tanggal_sampai)
    {
        $kredit = 0;

        // 1. Ambil dari tabel transaksi
        $transaksiKredit = DB::table('transaksi')
            ->where('akun_kode', $this->kode_akun)
            ->whereDate('tanggal', '>=', $tanggal_dari)
            ->whereDate('tanggal', '<=', $tanggal_sampai)
            ->where(DB::raw('LOWER(COALESCE(transaksi_type, ""))'), 'kredit')
            ->sum(DB::raw('COALESCE(jumlah, COALESCE(nominal, 0))'));

        $kredit += (float)$transaksiKredit;

        // 2. Ambil dari tabel jurnal
        if (Schema::hasTable('jurnal')) {
            $jurnalKredit = DB::table('jurnal')
                ->where('kode', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
                ->sum(DB::raw('COALESCE(nominal, 0)'));

            $kredit += (float)$jurnalKredit;

            // Juga dari kolom lawan
            $jurnalLawanKredit = DB::table('jurnal')
                ->where('lawan', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
                  ->sum(DB::raw('COALESCE(nominal, 0)'));

            $kredit += (float)$jurnalLawanKredit;
        }

        // 3. Ambil dari tabel transaksi_bank
        if (Schema::hasTable('transaksi_bank')) {
            $bankKredit = DB::table('transaksi_bank')
                ->where('akun_kode', $this->kode_akun)
                ->whereDate('tanggal', '>=', $tanggal_dari)
                ->whereDate('tanggal', '<=', $tanggal_sampai)
                ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
                ->whereNull('deleted_at')
                ->sum(DB::raw('COALESCE(total, 0)'));

            $kredit += (float)$bankKredit;
        }

        return (float)$kredit;
    }
 
    public function getBalance($tanggal_dari, $tanggal_sampai)
    {
        $debit = $this->getSaldoDebit($tanggal_dari, $tanggal_sampai) ?? 0;
        $kredit = $this->getSaldoKredit($tanggal_dari, $tanggal_sampai) ?? 0;

        // Default to debit if tipe is not set
        $tipe = $this->tipe ?? 'Debit';

        if (strtolower($tipe) === 'debit') {
            return $debit - $kredit;
        }
        return $kredit - $debit;
    }

    /**
     * Get fresh balance without any caching - ensures real-time data
     */
    public function getFreshBalance($tanggal_dari, $tanggal_sampai)
    {
        // Force fresh query by using fresh() method
        $freshModel = $this->fresh();
        return $freshModel->getBalance($tanggal_dari, $tanggal_sampai);
    }

    /**
     * Get saldo awal (initial balance)
     */
    public function getSaldoAwal()
    {
        return (float) ($this->saldo_awal ?? 0);
    }

    /**
     * Get current balance including initial balance
     */
    public function getCurrentBalance($tanggal_dari = null, $tanggal_sampai = null)
    {
        if (!$tanggal_dari) {
            $tanggal_dari = '1900-01-01';
        }
        if (!$tanggal_sampai) {
            $tanggal_sampai = now()->format('Y-m-d');
        }
        
        $saldo_awal = $this->getSaldoAwal();
        $saldo_periode = $this->getBalance($tanggal_dari, $tanggal_sampai);
        
        return $saldo_awal + $saldo_periode;
    }

    /**
     * Get account type based on kode_akun
     */
    public function getAccountType()
    {
        $kode = (int) $this->kode_akun;
        
        if ($kode >= 1000 && $kode < 2000) {
            return 'Aset';
        } elseif ($kode >= 2000 && $kode < 3000) {
            return 'Kewajiban';
        } elseif ($kode >= 3000 && $kode < 4000) {
            return 'Ekuitas';
        } elseif ($kode >= 4000 && $kode < 5000) {
            return 'Pendapatan';
        } elseif ($kode >= 5000 && $kode < 6000) {
            return 'Beban';
        } else {
            return 'Lainnya';
        }
    }

    /**
     * Check if account is active (has transactions or balance)
     */
    public function isActive()
    {
        // Note: Relationship transaksi() tidak ada, jadi hanya cek saldo awal
        return $this->getSaldoAwal() != 0;
    }

    /**
     * Get formatted account code with name
     */
    public function getFormattedName()
    {
        return $this->kode_akun . ' - ' . $this->nama;
    }

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        // Note: Relationship transaksi() tidak ada, jadi hanya return query
        return $query;
    }

    /**
     * Scope for accounts by type
     */
    public function scopeByType($query, $type)
    {
        $ranges = [
            'aset' => [1000, 1999],
            'kewajiban' => [2000, 2999],
            'ekuitas' => [3000, 3999],
            'pendapatan' => [4000, 4999],
            'beban' => [5000, 5999]
        ];

        if (isset($ranges[$type])) {
            return $query->whereBetween('kode_akun', $ranges[$type]);
        }

        return $query;
    }
}
