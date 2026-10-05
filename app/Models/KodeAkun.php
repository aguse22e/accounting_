<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class KodeAkun extends Model
{
    use HasFactory;

    protected $table = 'kode_akun';
    protected $primaryKey = 'kode_akun';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kode_akun',
        'nama',
        'tipe',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];


    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function transaksiBank()
    {
        return $this->hasMany(TransaksiBank::class, 'akun_kode', 'kode_akun');
    }
    
    public function getSaldoDebit($tanggal_dari, $tanggal_sampai)
    {
        $totalDebit = 0;
        
        // 1. Ambil dari tabel transaksi menggunakan kolom tipe dan nominal
        // Note: Tabel transaksi tidak memiliki kolom akun_kode, jadi skip untuk sekarang
        $transaksiDebit = 0;
        $totalDebit += (float)$transaksiDebit;
        
        // 3. Ambil dari tabel transaksi_bank (akun bank utama)
        $transaksiBankDebit = DB::table('transaksi_bank')
            ->where('akun_kode', $this->kode_akun)
            ->whereDate('tanggal', '>=', $tanggal_dari)
            ->whereDate('tanggal', '<=', $tanggal_sampai)
            ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'debit')
            ->whereNull('deleted_at')
            ->sum(DB::raw('COALESCE(total, 0)'));
        
        $transaksiBankDebit = $transaksiBankDebit ?? 0;
        $totalDebit += (float)$transaksiBankDebit;
        
        // 4. transaksi_bank_detail removed - kode_lawan column no longer exists
        
        return $totalDebit;
    }
    
    public function getSaldoKredit($tanggal_dari, $tanggal_sampai)
    {
        $totalKredit = 0;
        
        // 1. Ambil dari tabel transaksi menggunakan kolom tipe dan nominal
        // Note: Tabel transaksi tidak memiliki kolom akun_kode, jadi skip untuk sekarang
        $transaksiKredit = 0;
        $totalKredit += (float)$transaksiKredit;
        
        // 3. Ambil dari tabel transaksi_bank (akun bank utama)
        $transaksiBankKredit = DB::table('transaksi_bank')
            ->where('akun_kode', $this->kode_akun)
            ->whereDate('tanggal', '>=', $tanggal_dari)
            ->whereDate('tanggal', '<=', $tanggal_sampai)
            ->where(DB::raw('LOWER(COALESCE(tipe, ""))'), 'kredit')
            ->whereNull('deleted_at')
            ->sum(DB::raw('COALESCE(total, 0)'));
        
        $transaksiBankKredit = $transaksiBankKredit ?? 0;
        $totalKredit += (float)$transaksiBankKredit;
        
        // 4. transaksi_bank_detail removed - kode_lawan column no longer exists
        
        return $totalKredit;
    }

    public function getBalance($tanggal_dari, $tanggal_sampai)
    {
        $debit = $this->getSaldoDebit($tanggal_dari, $tanggal_sampai) ?? 0;
        $kredit = $this->getSaldoKredit($tanggal_dari, $tanggal_sampai) ?? 0;

        // PERBAIKAN: Gunakan tipe akun yang benar dari master data
        // Jika tipe tidak di-set atau null, default ke Debit (aset)
        $tipe = $this->tipe ?? 'Debit';

        if ($tipe === 'Debit') {
            // Aset & Beban: Debit (+), Kredit (-)
            return $debit - $kredit;
        }
        // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
        return $kredit - $debit;
    }

    public function getFreshBalance($tanggal_dari, $tanggal_sampai)
    {
        // Hitung saldo langsung tanpa reload model
        $debit = $this->getSaldoDebit($tanggal_dari, $tanggal_sampai) ?? 0;
        $kredit = $this->getSaldoKredit($tanggal_dari, $tanggal_sampai) ?? 0;

        // PERBAIKAN: Gunakan tipe akun yang benar dari master data
        $tipe = $this->tipe ?? 'Debit';

        if ($tipe === 'Debit') {
            // Aset & Beban: Debit (+), Kredit (-)
            return $debit - $kredit;
        }
        // Liabilitas, Ekuitas, Pendapatan: Kredit (+), Debit (-)
        return $kredit - $debit;
    }


}
