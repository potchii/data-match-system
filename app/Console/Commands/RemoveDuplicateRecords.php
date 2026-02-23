<?php

namespace App\Console\Commands;

use App\Models\MainSystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateRecords extends Command
{
    protected $signature = 'duplicates:remove {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Remove duplicate records from main_system table, keeping the oldest record';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No records will be deleted');
        } else {
            $this->warn('⚠️  LIVE MODE - Records will be permanently deleted!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Find duplicate groups
        $duplicates = DB::table('main_system')
            ->select('last_name_normalized', 'first_name_normalized', 'middle_name_normalized', 'birthday', DB::raw('COUNT(*) as count'))
            ->whereNotNull('birthday')
            ->whereNotNull('last_name_normalized')
            ->whereNotNull('first_name_normalized')
            ->groupBy('last_name_normalized', 'first_name_normalized', 'middle_name_normalized', 'birthday')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicates found!');
            return 0;
        }

        $this->info("Found {$duplicates->count()} duplicate groups");
        $totalDeleted = 0;

        foreach ($duplicates as $dup) {
            // Get all records in this duplicate group
            $records = MainSystem::where('last_name_normalized', $dup->last_name_normalized)
                ->where('first_name_normalized', $dup->first_name_normalized)
                ->where('middle_name_normalized', $dup->middle_name_normalized)
                ->where('birthday', $dup->birthday)
                ->orderBy('id', 'asc')
                ->get();

            // Keep the first (oldest) record, delete the rest
            $keepRecord = $records->first();
            $deleteRecords = $records->slice(1);

            $this->line("\n📋 {$keepRecord->last_name}, {$keepRecord->first_name} ({$keepRecord->birthday})");
            $this->line("   ✅ Keeping: UID {$keepRecord->uid} (ID: {$keepRecord->id})");

            foreach ($deleteRecords as $record) {
                $this->line("   ❌ Deleting: UID {$record->uid} (ID: {$record->id})");
                $totalDeleted++;
                
                if (!$isDryRun) {
                    $record->delete();
                }
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info("🔍 Would delete {$totalDeleted} duplicate records");
            $this->info('Run without --dry-run to actually delete them');
        } else {
            $this->info("✅ Deleted {$totalDeleted} duplicate records");
        }

        return 0;
    }
}
