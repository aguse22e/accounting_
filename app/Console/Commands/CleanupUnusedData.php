<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupUnusedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:cleanup 
                            {--days=90 : Number of days to keep old data}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unused and old data from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Starting database cleanup (keeping data from last {$days} days)...");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be deleted");
        }
        
        $totalDeleted = 0;
        
        // 1. Clean up old sessions
        $deleted = $this->cleanupOldSessions($days, $dryRun);
        $totalDeleted += $deleted;
        $this->info("Cleaned up {$deleted} old session records");
        
        // 2. Clean up old activity logs (optional - keep for audit)
        // Uncomment if you want to clean activity logs
        // $deleted = $this->cleanupOldActivityLogs($days, $dryRun);
        // $totalDeleted += $deleted;
        // $this->info("Cleaned up {$deleted} old activity log records");
        
        // 3. Clean up orphaned records
        $deleted = $this->cleanupOrphanedRecords($dryRun);
        $totalDeleted += $deleted;
        $this->info("Cleaned up {$deleted} orphaned records");
        
        $this->info("Cleanup completed! Total records affected: {$totalDeleted}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Clean up old session records
     */
    private function cleanupOldSessions(int $days, bool $dryRun): int
    {
        if (!DB::getSchemaBuilder()->hasTable('sessions')) {
            return 0;
        }
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        if ($dryRun) {
            $count = DB::table('sessions')
                ->where('last_activity', '<', $cutoffDate->timestamp)
                ->count();
            $this->line("  Would delete {$count} old session records");
            return 0;
        }
        
        return DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->delete();
    }
    
    /**
     * Clean up old activity logs (optional)
     */
    private function cleanupOldActivityLogs(int $days, bool $dryRun): int
    {
        if (!DB::getSchemaBuilder()->hasTable('activity_users')) {
            return 0;
        }
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        if ($dryRun) {
            $count = DB::table('activity_users')
                ->where('created_at', '<', $cutoffDate)
                ->count();
            $this->line("  Would delete {$count} old activity log records");
            return 0;
        }
        
        return DB::table('activity_users')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Clean up orphaned records (records with invalid foreign keys)
     */
    private function cleanupOrphanedRecords(bool $dryRun): int
    {
        $total = 0;
        
        // Clean up transaksi_bank with invalid akun_kode
        if (DB::getSchemaBuilder()->hasTable('transaksi_bank') && 
            DB::getSchemaBuilder()->hasTable('kode_akun')) {
            
            $orphaned = DB::table('transaksi_bank as tb')
                ->leftJoin('kode_akun as ka', 'tb.akun_kode', '=', 'ka.kode_akun')
                ->whereNull('ka.kode_akun')
                ->whereNotNull('tb.akun_kode')
                ->select('tb.kode_transaksi_bank')
                ->get();
            
            if ($dryRun) {
                $count = $orphaned->count();
                if ($count > 0) {
                    $this->line("  Would delete {$count} transaksi_bank records with invalid akun_kode");
                }
            } else {
                $ids = $orphaned->pluck('kode_transaksi_bank')->toArray();
                if (!empty($ids)) {
                    $deleted = DB::table('transaksi_bank')
                        ->whereIn('kode_transaksi_bank', $ids)
                        ->delete();
                    $total += $deleted;
                }
            }
        }
        
        // Clean up transaksi with invalid kode_project
        if (DB::getSchemaBuilder()->hasTable('transaksi') && 
            DB::getSchemaBuilder()->hasTable('projects')) {
            
            $orphaned = DB::table('transaksi as t')
                ->leftJoin('projects as p', 't.kode_project', '=', 'p.kode_project')
                ->whereNull('p.kode_project')
                ->whereNotNull('t.kode_project')
                ->select('t.kode_voucer')
                ->get();
            
            if ($dryRun) {
                $count = $orphaned->count();
                if ($count > 0) {
                    $this->line("  Would delete {$count} transaksi records with invalid kode_project");
                }
            } else {
                $ids = $orphaned->pluck('kode_voucer')->toArray();
                if (!empty($ids)) {
                    $deleted = DB::table('transaksi')
                        ->whereIn('kode_voucer', $ids)
                        ->delete();
                    $total += $deleted;
                }
            }
        }
        
        // Clean up budget with invalid kode_project
        if (DB::getSchemaBuilder()->hasTable('budget') && 
            DB::getSchemaBuilder()->hasTable('projects')) {
            
            $orphaned = DB::table('budget as b')
                ->leftJoin('projects as p', 'b.kode_project', '=', 'p.kode_project')
                ->whereNull('p.kode_project')
                ->whereNotNull('b.kode_project')
                ->select('b.kode_budget')
                ->get();
            
            if ($dryRun) {
                $count = $orphaned->count();
                if ($count > 0) {
                    $this->line("  Would delete {$count} budget records with invalid kode_project");
                }
            } else {
                $ids = $orphaned->pluck('kode_budget')->toArray();
                if (!empty($ids)) {
                    $deleted = DB::table('budget')
                        ->whereIn('kode_budget', $ids)
                        ->delete();
                    $total += $deleted;
                }
            }
        }
        
        return $total;
    }
}
