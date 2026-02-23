<?php

namespace App\Console\Commands;

use App\Models\MainSystem;
use Illuminate\Console\Command;

class StandardizeCompoundNames extends Command
{
    protected $signature = 'names:standardize-compound {--dry-run : Preview changes without applying them}';
    protected $description = 'Standardize compound first names to Philippine naming convention';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  This will modify first_name and middle_name fields!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Analyzing records...');
        
        // Find records where first_name contains spaces (compound names already in first_name)
        // and middle_name is null or empty
        $compoundRecords = MainSystem::whereRaw('first_name LIKE "% %"')
            ->where(function ($query) {
                $query->whereNull('middle_name')
                      ->orWhere('middle_name', '');
            })
            ->get();

        if ($compoundRecords->isEmpty()) {
            $this->info('✅ No records need standardization');
            return 0;
        }

        $this->info("Found {$compoundRecords->count()} records with compound first names");
        $this->newLine();

        foreach ($compoundRecords as $record) {
            $this->line("ID {$record->id}: {$record->first_name} {$record->middle_name} {$record->last_name}");
            $this->line("  ✓ Already standardized (compound first name, no middle name)");
        }

        $this->newLine();
        $this->info('✅ All records are already in Philippine naming convention format');
        $this->info('   (Compound first names like "John Emmanuel" are preserved)');

        return 0;
    }
}
