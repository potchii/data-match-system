<?php

namespace App\Imports;

use App\Models\MatchResult;
use App\Services\DataMappingService;
use App\Services\DataMatchService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RecordImport implements ToCollection, WithHeadingRow
{
    protected $batchId;
    protected $mappingService;
    protected $matchService;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
        $this->mappingService = new DataMappingService();
        $this->matchService = new DataMatchService();
    }

    /**
     * Process each row from the Excel file
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Convert row to array
            $rowData = $row->toArray();
            
            // Map uploaded data to system format
            $mappedData = $this->mappingService->mapUploadedData($rowData);
            
            // Skip if essential data is missing
            if (empty($mappedData['last_name']) || empty($mappedData['first_name'])) {
                continue;
            }
            
            // Find match in main system
            $matchResult = $this->matchService->findMatch($mappedData);
            
            // If NEW RECORD, insert into main system
            if ($matchResult['status'] === 'NEW RECORD') {
                $mappedData['origin_batch_id'] = $this->batchId;
                $newRecord = $this->matchService->insertNewRecord($mappedData);
                $matchResult['matched_id'] = $newRecord->uid;
                
                // Create match result record first
                $matchResultRecord = MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $mappedData['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $mappedData['last_name'],
                    'uploaded_first_name' => $mappedData['first_name'],
                    'uploaded_middle_name' => $mappedData['middle_name'],
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                ]);
                
                // Update the main system record with the match result ID
                $newRecord->update(['origin_match_result_id' => $matchResultRecord->id]);
            } else {
                // Create match result record for existing matches
                MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $mappedData['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $mappedData['last_name'],
                    'uploaded_first_name' => $mappedData['first_name'],
                    'uploaded_middle_name' => $mappedData['middle_name'],
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                ]);
            }
        }
    }
}
