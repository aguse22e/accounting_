<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\GeneralLedger;
use App\Models\Akun;
use Illuminate\Support\Facades\DB;

class JournalPostingService
{
    /**
     * Validate journal entry for double entry compliance
     * @param array $data
     * @param bool $forPosting - if true, enforce strict validation (min 2 lines, must balance)
     * @return array
     */
    public function validateJournalEntry(array $data, bool $forPosting = false): array
    {
        $errors = [];

        // Check if we have at least 1 line (or 2 for posting)
        $minLines = $forPosting ? 2 : 1;
        if (!isset($data['lines']) || count($data['lines']) < $minLines) {
            $errors[] = $forPosting 
                ? 'Jurnal harus memiliki minimal 2 lines (debit dan credit) untuk dapat diposting'
                : 'Jurnal harus memiliki minimal 1 line';
        }

        // Calculate totals
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($data['lines'] as $index => $line) {
            // Validate account exists
            $account = Akun::where('kode_akun', $line['account_code'])->first();
            if (!$account) {
                $errors[] = "Kode akun {$line['account_code']} tidak ditemukan";
            }

            // Validate amount
            if (!is_numeric($line['amount']) || $line['amount'] <= 0) {
                $errors[] = "Line " . ($index + 1) . ": Amount harus berupa angka positif";
            }

            // Sum by type
            if ($line['type'] === 'debit') {
                $totalDebit += floatval($line['amount']);
            } else {
                $totalCredit += floatval($line['amount']);
            }
        }

        // Check balance (allow 0.01 difference for floating point precision)
        $isBalanced = abs($totalDebit - $totalCredit) <= 0.01;
        
        if ($forPosting && !$isBalanced) {
            // Strict validation for posting - must be balanced
            $errors[] = "Jurnal tidak seimbang dan tidak dapat diposting. Total Debit: " . number_format($totalDebit, 2) . ", Total Credit: " . number_format($totalCredit, 2) . ". Selisih: " . number_format(abs($totalDebit - $totalCredit), 2);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => $isBalanced,
            'warning' => !$forPosting && !$isBalanced 
                ? "Jurnal belum seimbang. Total Debit: " . number_format($totalDebit, 2) . ", Total Credit: " . number_format($totalCredit, 2) . ". Selisih: " . number_format(abs($totalDebit - $totalCredit), 2) . ". Jurnal disimpan sebagai draft." 
                : null,
        ];
    }

    /**
     * Create journal entry with lines (allows draft with unbalanced entries)
     */
    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            // Validate for draft (not strict)
            $validation = $this->validateJournalEntry($data, false);
            if (!$validation['valid']) {
                throw new \Exception(implode(', ', $validation['errors']));
            }

            // Create journal entry
            $journalEntry = JournalEntry::create([
                'journal_number' => $data['journal_number'] ?? null,
                'journal_date' => $data['journal_date'],
                'description' => $data['description'],
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'total_debit' => $validation['total_debit'],
                'total_credit' => $validation['total_credit'],
                'kode_project' => $data['kode_project'] ?? null,
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Create journal lines
            foreach ($data['lines'] as $line) {
                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_code' => $line['account_code'],
                    'type' => $line['type'],
                    'amount' => $line['amount'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            $journalEntry->load('lines');
            
            // Add warning to session if not balanced
            if ($validation['warning']) {
                session()->flash('warning', $validation['warning']);
            }

            return $journalEntry;
        });
    }

    /**
     * Post journal entry to general ledger (strict validation required)
     */
    public function postToLedger(JournalEntry $journalEntry): void
    {
        // Re-validate with strict rules before posting
        $data = [
            'lines' => $journalEntry->lines->map(function($line) {
                return [
                    'account_code' => $line->account_code,
                    'type' => $line->type,
                    'amount' => $line->amount,
                    'description' => $line->description,
                ];
            })->toArray(),
        ];
        
        $validation = $this->validateJournalEntry($data, true);
        if (!$validation['valid']) {
            throw new \Exception('Jurnal tidak dapat diposting: ' . implode(', ', $validation['errors']));
        }

        DB::transaction(function () use ($journalEntry) {
            // Update journal entry status
            $journalEntry->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Post each line to general ledger
            foreach ($journalEntry->lines as $line) {
                $balance = $this->calculateBalanceAfter($line);

                GeneralLedger::create([
                    'journal_entry_id' => $journalEntry->id,
                    'journal_line_id' => $line->id,
                    'account_code' => $line->account_code,
                    'posting_date' => $journalEntry->journal_date,
                    'debit_amount' => $line->type === 'debit' ? $line->amount : 0,
                    'credit_amount' => $line->type === 'credit' ? $line->amount : 0,
                    'balance' => $balance,
                    'description' => $line->description,
                    'reference' => $journalEntry->journal_number,
                    'kode_project' => $journalEntry->kode_project,
                ]);

                // Update balance_after in journal line
                $line->update(['balance_after' => $balance]);
            }
        });
    }

    /**
     * Calculate balance after a specific journal line
     */
    private function calculateBalanceAfter(JournalLine $line): float
    {
        $account = Akun::where('kode_akun', $line->account_code)->first();
        if (!$account) {
            return 0;
        }

        // Get current balance from general ledger or account's initial balance
        $currentBalance = GeneralLedger::where('account_code', $line->account_code)
            ->latest('id')
            ->value('balance') ?? ($account->saldo_awal ?? 0);

        // Calculate new balance based on account type and transaction type
        if ($account->saldo_normal === 'debit') {
            // Asset & Expense accounts: Debit increases, Credit decreases
            return $line->type === 'debit' 
                ? $currentBalance + $line->amount 
                : $currentBalance - $line->amount;
        } else {
            // Liability, Equity, Revenue accounts: Credit increases, Debit decreases
            return $line->type === 'credit' 
                ? $currentBalance + $line->amount 
                : $currentBalance - $line->amount;
        }
    }

    /**
     * Reverse a posted journal entry
     */
    public function reverseJournalEntry(JournalEntry $journalEntry): JournalEntry
    {
        if ($journalEntry->status !== 'posted') {
            throw new \Exception('Only posted journal entries can be reversed.');
        }

        return DB::transaction(function () use ($journalEntry) {
            // Create reversing journal entry
            $reversingEntry = JournalEntry::create([
                'journal_number' => 'REV-' . $journalEntry->journal_number,
                'journal_date' => now(),
                'description' => 'Reversal of: ' . $journalEntry->description,
                'reference' => 'REV-' . ($journalEntry->reference ?? $journalEntry->journal_number),
                'status' => 'posted',
                'total_debit' => $journalEntry->total_credit,
                'total_credit' => $journalEntry->total_debit,
                'kode_project' => $journalEntry->kode_project,
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'notes' => 'Reversing journal entry #' . $journalEntry->id,
            ]);

            // Create reversing lines (swap debit/credit)
            foreach ($journalEntry->lines as $line) {
                $reversingLine = JournalLine::create([
                    'journal_entry_id' => $reversingEntry->id,
                    'account_code' => $line->account_code,
                    'type' => $line->type === 'debit' ? 'credit' : 'debit',
                    'amount' => $line->amount,
                    'description' => 'Reversal: ' . ($line->description ?? ''),
                ]);

                // Post to general ledger
                $balance = $this->calculateBalanceAfter($reversingLine);

                GeneralLedger::create([
                    'journal_entry_id' => $reversingEntry->id,
                    'journal_line_id' => $reversingLine->id,
                    'account_code' => $reversingLine->account_code,
                    'posting_date' => $reversingEntry->journal_date,
                    'debit_amount' => $reversingLine->type === 'debit' ? $reversingLine->amount : 0,
                    'credit_amount' => $reversingLine->type === 'credit' ? $reversingLine->amount : 0,
                    'balance' => $balance,
                    'description' => $reversingLine->description,
                    'reference' => $reversingEntry->journal_number,
                    'kode_project' => $reversingEntry->kode_project,
                ]);

                $reversingLine->update(['balance_after' => $balance]);
            }

            // Mark original as reversed
            $journalEntry->update([
                'status' => 'reversed',
                'reversed_at' => now(),
            ]);

            return $reversingEntry->load('lines');
        });
    }

    /**
     * Get trial balance for a specific period
     */
    public function getTrialBalance(string $fromDate, string $toDate): array
    {
        // Get general ledger entries for the period
        $generalLedgerEntries = GeneralLedger::whereBetween('posting_date', [$fromDate, $toDate])
            ->whereHas('journalEntry', function($q) {
                $q->where('status', 'posted');
            })
            ->get();

        // Group by account code
        $accountBalances = [];
        foreach ($generalLedgerEntries as $entry) {
            $accountCode = $entry->account_code;
            if (!isset($accountBalances[$accountCode])) {
                $accountBalances[$accountCode] = [
                    'debit' => 0,
                    'credit' => 0,
                ];
            }
            $accountBalances[$accountCode]['debit'] += $entry->debit_amount;
            $accountBalances[$accountCode]['credit'] += $entry->credit_amount;
        }

        // Get all accounts and build trial balance
        $accounts = Akun::all();
        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $accountCode = $account->kode_akun;
            $ledgerData = $accountBalances[$accountCode] ?? ['debit' => 0, 'credit' => 0];

            $totalDebitAmount = $ledgerData['debit'];
            $totalCreditAmount = $ledgerData['credit'];

            // Calculate net balance based on account type
            if ($account->saldo_normal === 'debit') {
                $netBalance = $totalDebitAmount - $totalCreditAmount;
                $debitAmount = $netBalance > 0 ? $netBalance : 0;
                $creditAmount = $netBalance < 0 ? abs($netBalance) : 0;
            } else {
                $netBalance = $totalCreditAmount - $totalDebitAmount;
                $creditAmount = $netBalance > 0 ? $netBalance : 0;
                $debitAmount = $netBalance < 0 ? abs($netBalance) : 0;
            }

            // Add initial balance
            $initialBalance = $account->saldo_awal ?? 0;
            if ($account->saldo_normal === 'debit') {
                $debitAmount += $initialBalance;
            } else {
                $creditAmount += $initialBalance;
            }

            if ($debitAmount > 0 || $creditAmount > 0) {
                $trialBalance[] = [
                    'account_code' => $account->kode_akun,
                    'account_name' => $account->nama_akun,
                    'account_type' => $account->getAccountType(),
                    'debit' => $debitAmount,
                    'credit' => $creditAmount,
                ];

                $totalDebit += $debitAmount;
                $totalCredit += $creditAmount;
            }
        }

        return [
            'accounts' => $trialBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}