<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    // Use DB-based project table
    protected $table = 'projects';
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'kode_project',
        'nama_project',
        'saldo',
        'tanggal',
        'kode_akun',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'saldo' => 'decimal:2',
    ];

    /**
     * Get all projects including a "No Project" option
     */
    public static function getAllWithNoProject()
    {
        $projects = self::orderBy('kode_project')->get();

        // Virtual option for UI selectors
        $noProject = new self([
            'kode_project' => 'NO_PROJECT',
            'nama_project' => 'No Project',
            'saldo' => 0,
            'tanggal' => null,
            'kode_akun' => null,
        ]);

        return $projects->prepend($noProject);
    }

    /**
     * Check if this is the "No Project" option
     */
    public function isNoProject()
    {
        return $this->kode_project === 'NO_PROJECT';
    }

    /**
     * Get display name for the project
     */
    public function getDisplayNameAttribute()
    {
        if ($this->isNoProject()) {
            return 'No Project';
        }
        return $this->kode_project . ' - ' . $this->nama_project;
    }

    /**
     * Get all laporan keuangan for this project
     */
    public function laporanKeuangan()
    {
        return $this->hasMany(LaporanKeuangan::class, 'Kode_Project', 'kode_project');
    }

    /**
     * Get all budgets for this project
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class, 'kode_project', 'kode_project');
    }

    /**
     * Get all transactions for this project
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'kode_project', 'kode_project');
    }

    /**
     * Get total budget for this project
     */
    public function getTotalBudgetAttribute()
    {
        return $this->budgets()->sum('anggaran');
    }

    /**
     * Get total actual spending for this project
     */
    public function getTotalActualAttribute()
    {
        return $this->laporanKeuangan()->sum('saldo');
    }

    /**
     * Get budget variance for this project
     */
    public function getBudgetVarianceAttribute()
    {
        return $this->total_budget - $this->total_actual;
    }

    /**
     * Check if project is over budget
     */
    public function isOverBudget()
    {
        return $this->total_actual > $this->total_budget;
    }
}
