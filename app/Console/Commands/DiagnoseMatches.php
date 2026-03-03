<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MatchResult;
use App\Models\MainSystem;

class DiagnoseMatches extends Command
{
    protected $signature = 'diagnose:matches {batch_id}';
    protected $description = 'Diagnose which records matched and why';

    public function handle()
    {
        $batchId = $this->argument('batch_id');
        
        $this->info("Analyzing batch {$batchId}...\n");
        
        // Get all match results for this batch
        $results = MatchResult::where('batch_id', $batchId)
            ->orderBy('id')
            ->get();
        
        $matched = $results->where('match_status', 'MATCHED');
        $possibleDup = $results->where('match_status', 'POSSIBLE DUPLICATE');
        $newRecord = $results->where('match_status', 'NEW RECORD');
        
        $this->info("Summary:");
        $this->info("- MATCHED: " . $matched->count());
        $this->info("- POSSIBLE DUPLICATE: " . $possibleDup->count());
        $this->info("- NEW RECORD: " . $newRecord->count());
        $this->newLine();
        
        // Analyze MATCHED records
        $this->info("=== MATCHED RECORDS (100%) ===\n");
        
        $issueCount = 0;
        foreach ($matched as $result) {
            $uploaded = json_decode($result->uploaded_data, true);
            $matchedRecord = MainSystem::where('uid', $result->matched_system_id)->first();
            
            if (!$matchedRecord) {
                continue;
            }
            
            $this->info("Record #{$result->id}:");
            $this->info("  Uploaded: {$uploaded['first_name']} {$uploaded['last_name']} " . ($uploaded['middle_name'] ?? 'NULL') . " | DOB: " . ($uploaded['birthday'] ?? 'NULL'));
            $this->info("  Matched:  {$matchedRecord->first_name} {$matchedRecord->last_name} " . ($matchedRecord->middle_name ?? 'NULL') . " | DOB: " . ($matchedRecord->birthday ?? 'NULL'));
            $this->info("  Rule: {$result->match_rule}, Confidence: {$result->confidence_score}%");
            
            // Check for potential issues
            $issues = [];
            
            // Check if both DOBs are null
            if (empty($uploaded['birthday']) && empty($matchedRecord->birthday)) {
                $issues[] = "BOTH BIRTHDAYS ARE NULL";
            }
            
            // Check normalized middle names
            $uploadedMiddleNorm = strtolower(trim($uploaded['middle_name_normalized'] ?? ''));
            $matchedMiddleNorm = strtolower(trim($matchedRecord->middle_name_normalized ?? ''));
            
            // Check if middle names are initials vs full
            if (strlen($uploadedMiddleNorm) === 1 && strlen($matchedMiddleNorm) > 1) {
                if (str_starts_with($matchedMiddleNorm, $uploadedMiddleNorm)) {
                    $issues[] = "INITIAL vs FULL NAME (uploaded: '{$uploadedMiddleNorm}' vs matched: '{$matchedMiddleNorm}')";
                }
            } elseif (strlen($matchedMiddleNorm) === 1 && strlen($uploadedMiddleNorm) > 1) {
                if (str_starts_with($uploadedMiddleNorm, $matchedMiddleNorm)) {
                    $issues[] = "INITIAL vs FULL NAME (uploaded: '{$uploadedMiddleNorm}' vs matched: '{$matchedMiddleNorm}')";
                }
            }
            
            // Show issues if any
            if (!empty($issues)) {
                $issueCount++;
                foreach ($issues as $issue) {
                    $this->error("  ⚠️  " . $issue);
                }
            }
            
            $this->newLine();
        }
        
        if ($issueCount === 0) {
            $this->info("✓ All MATCHED records appear correct!");
        } else {
            $this->error("\n{$issueCount} records have issues and should be POSSIBLE DUPLICATE instead of MATCHED");
        }
        
        return 0;
    }
}
