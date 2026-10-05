<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $primaryKey = 'kode_voucer';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'kode_voucer',
        'kode_budget',
        'kode_project',
        'akun_kode',
        'deskripsi',
        'nominal',
        'tanggal',
        'debit',
        'kredit',
        'transaksi_type',
        'jenis',
        'jumlah',
        'saldo',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'nominal' => 'decimal:2',
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
        'saldo' => 'decimal:2',
        'jumlah' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Transaksi $model) {
            if (empty($model->kode_voucer)) {
                $model->kode_voucer = self::generateKodeVoucer();
            }
        });
    }

    protected static function generateKodeVoucer(): string
    {
        $prefix = 'TRX-'.date('Ymd');
        $sequence = 1;
        do {
            $candidate = sprintf('%s-%04d', $prefix, $sequence);
            $exists = self::where('kode_voucer', $candidate)->exists();
            $sequence++;
        } while ($exists);
        return $candidate;
    }

    public function getRouteKeyName()
    {
        return 'kode_voucer';
    }

    public function akun()
    {
        return $this->belongsTo(KodeAkun::class, 'akun_kode', 'kode_akun');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'kode_project', 'kode_project');
    }

}
