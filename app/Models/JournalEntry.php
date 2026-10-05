<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';

    protected $fillable = [
        'journal_number',
        'journal_date',
        'description',
        'reference',
        'status',
        'total_debit',
        'total_credit',
        'kode_project',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_at',
        'notes',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (JournalEntry $model) {
            if (empty($model->journal_number)) {
                $model->journal_number = self::generateJournalNumber();
            }
            if (empty($model->status)) {
                $model->status = 'draft';
            }
        });
    }

    protected static function generateJournalNumber(): string
    {
        $prefix = 'JNL-' . date('Ymd');
        $sequence = 1;
        do {
            $candidate = sprintf('%s-%04d', $prefix, $sequence);
            $exists = self::where('journal_number', $candidate)->exists();
            $sequence++;
        } while ($exists);
        return $candidate;
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function generalLedgerEntries()
    {
        return $this->hasMany(GeneralLedger::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'Id_User');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by', 'Id_User');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'kode_project', 'kode_project');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function canBePosted(): bool
    {
        return $this->status === 'draft' && $this->isBalanced() && $this->lines()->count() >= 2;
    }

    public function post()
    {
        if (!$this->canBePosted()) {
            throw new \Exception('Journal entry cannot be posted. Must be balanced and have at least 2 lines.');
        }

        $this->update([
            'status' => 'posted',
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        // Post to general ledger
        foreach ($this->lines as $line) {
            GeneralLedger::create([
                'journal_entry_id' => $this->id,
                'journal_line_id' => $line->id,
                'account_code' => $line->account_code,
                'posting_date' => $this->journal_date,
                'debit_amount' => $line->type === 'debit' ? $line->amount : 0,
                'credit_amount' => $line->type === 'credit' ? $line->amount : 0,
                'balance' => $this->calculateBalanceAfter($line),
                'description' => $line->description,
                'reference' => $this->journal_number,
                'kode_project' => $this->kode_project,
            ]);
        }
    }

    private function calculateBalanceAfter(JournalLine $line): float
    {
        $account = Akun::where('kode_akun', $line->account_code)->first();
        if (!$account) {
            return 0;
        }

        $currentBalance = GeneralLedger::where('account_code', $line->account_code)
            ->latest('id')
            ->value('balance') ?? $account->saldo_awal ?? 0;

        if ($account->saldo_normal === 'debit') {
            return $line->type === 'debit' ? $currentBalance + $line->amount : $currentBalance - $line->amount;
        } else {
            return $line->type === 'credit' ? $currentBalance + $line->amount : $currentBalance - $line->amount;
        }
    }

    public function reverse()
    {
        if ($this->status !== 'posted') {
            throw new \Exception('Only posted journal entries can be reversed.');
        }

        // Create reversing journal entry
        $reversingEntry = JournalEntry::create([
            'journal_number' => 'REV-' . $this->journal_number,
            'journal_date' => now(),
            'description' => 'Reversal of: ' . $this->description,
            'reference' => 'REV-' . ($this->reference ?? $this->journal_number),
            'status' => 'posted',
            'total_debit' => $this->total_credit,
            'total_credit' => $this->total_debit,
            'kode_project' => $this->kode_project,
            'created_by' => auth()->id(),
            'posted_by' => auth()->id(),
            'posted_at' => now(),
            'notes' => 'Reversing journal entry #' . $this->id,
        ]);

        // Create reversing lines (swap debit/credit)
        foreach ($this->lines as $line) {
            $reversingLine = JournalLine::create([
                'journal_entry_id' => $reversingEntry->id,
                'account_code' => $line->account_code,
                'type' => $line->type === 'debit' ? 'credit' : 'debit',
                'amount' => $line->amount,
                'description' => 'Reversal: ' . ($line->description ?? ''),
            ]);

            // Post to general ledger
            GeneralLedger::create([
                'journal_entry_id' => $reversingEntry->id,
                'journal_line_id' => $reversingLine->id,
                'account_code' => $reversingLine->account_code,
                'posting_date' => $reversingEntry->journal_date,
                'debit_amount' => $reversingLine->type === 'debit' ? $reversingLine->amount : 0,
                'credit_amount' => $reversingLine->type === 'credit' ? $reversingLine->amount : 0,
                'balance' => $reversingEntry->calculateBalanceAfter($reversingLine),
                'description' => $reversingLine->description,
                'reference' => $reversingEntry->journal_number,
                'kode_project' => $reversingEntry->kode_project,
            ]);
        }

        // Mark original as reversed
        $this->update([
            'status' => 'reversed',
            'reversed_at' => now(),
        ]);

        return $reversingEntry;
    }
}
