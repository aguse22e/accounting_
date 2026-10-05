<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiBank extends Model
{
    use HasFactory;

    protected $table = 'transaksi_bank';
    protected $primaryKey = 'kode_transaksi_bank';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'kode_transaksi_bank',
        'tanggal',
        'akun_kode',
        'tipe',
        'total',
        'kode_project',
        'deskripsi',
        'reference',
        'nominal',
        'debit',
        'kredit',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total' => 'decimal:2',
        'nominal' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function akun()
    {
        return $this->belongsTo(KodeAkun::class, 'akun_kode', 'kode_akun');
    }



    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getRouteKeyName()
    {
        return 'kode_transaksi_bank';
    }
}
 