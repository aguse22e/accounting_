<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    use HasFactory;

    protected $table = 'general_ledger';

    protected $fillable = [
        'journal_entry_id',
        'journal_line_id',
        'account_code',
        'posting_date',
        'debit_amount',
        'credit_amount',
        'balance',
        'description',
        'reference',
        'kode_project',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function journalLine()
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function account()
    {
        return $this->belongsTo(Akun::class, 'account_code', 'kode_akun');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'kode_project', 'kode_project');
    }

    public function scopeForAccount($query, $accountCode)
    {
        return $query->where('account_code', $accountCode);
    }

    public function scopeForPeriod($query, $fromDate, $toDate)
    {
        return $query->whereBetween('posting_date', [$fromDate, $toDate]);
    }

    public function scopeForProject($query, $projectCode)
    {
        return $query->where('kode_project', $projectCode);
    }

    public function getNetChangeAttribute()
    {
        return $this->debit_amount - $this->credit_amount;
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2, ',', '.');
    }

    public function getFormattedDebitAttribute()
    {
        return number_format($this->debit_amount, 2, ',', '.');
    }

    public function getFormattedCreditAttribute()
    {
        return number_format($this->credit_amount, 2, ',', '.');
    }
}
