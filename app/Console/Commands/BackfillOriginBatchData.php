<?php

namespace App\Console\Commands;

use App\Models\MainSystem;
use App\Models\MatchResult;
use Illuminate\Console\Command;

class BackfillOriginBatchData extends Command
{
    protected $signature = 'data:backfill-origin-batch';
    protected $description = 'Backfill origin_batch_id and origin_match_result_id for existing main_system records';

    public function handle()
    {
        $this->info('Starting backfill process...');
        
        // Get all main_system records without origin_batch_id
        $records = MainSystem::whereNull('origin_batch_id')->get();
        
        $this->info("Found {$records->count()} records to process");
        
        $updated = 0;
        
        foreach ($records as $record) {
            // Find the first match result where this record was created (status = NEW RECORD)
            $matchResult = MatchResult::where('matched_system_id', $record->uid)
                ->where('match_status', 'NEW RECORD')
                ->orderBy('created_at', 'asc')
                ->first();
            
            if ($matchResult) {
                $record->update([
                    'origin_batch_id' => $matchResult->batch_id,
                    'origin_match_result_id' => $matchResult->id,
                ]);
                $updated++;
                $this->info("Updated {$record->uid} with batch #{$matchResult->batch_id}");
            }
        }
        
        $this->info("Backfill complete! Updated {$updated} records.");
        
        return 0;
    }
}
