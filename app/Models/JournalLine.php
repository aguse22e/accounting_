<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    use HasFactory;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_code',
        'type',
        'amount',
        'description',
        'balance_after',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Akun::class, 'account_code', 'kode_akun');
    }

    public function generalLedgerEntry()
    {
        return $this->hasOne(GeneralLedger::class, 'journal_line_id');
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', '.');
    }

    public function getTypeBadgeAttribute()
    {
        return $this->type === 'debit' 
            ? '<span class="badge bg-success">Debit</span>' 
            : '<span class="badge bg-danger">Kredit</span>';
    }
}
