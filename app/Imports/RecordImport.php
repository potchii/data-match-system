<?php

namespace App\Imports;

use App\Helpers\CoreFieldMappings;
use App\Models\MatchResult;
use App\Services\DataMappingService;
use App\Services\DataMatchService;
use App\Services\ConfidenceScoreService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RecordImport implements ToCollection, WithHeadingRow
{
    protected $batchId;
    protected $template;
    protected $mappingService;
    protected $matchService;
    protected $columnMappingSummary = null;

    public function __construct($batchId, $template = null)
    {
        $this->batchId = $batchId;
        $this->template = $template;
        $this->mappingService = new DataMappingService();
        $this->matchService = new DataMatchService(new ConfidenceScoreService());
    }

    /**
     * Get column mapping summary from the first row
     *
     * @return array|null
     */
    public function getColumnMappingSummary(): ?array
    {
        return $this->columnMappingSummary;
    }

    /**
     * Process each row from the Excel file
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        $isFirstRow = true;
        $allOriginalColumns = [];
        $processedCount = 0;
        $skippedCount = 0;
        $newRecordCount = 0;
        $matchedRecordCount = 0;
        
        Log::info('Starting record import', [
            'batch_id' => $this->batchId,
            'template' => $this->template ? $this->template->name : 'none',
            'row_count' => $rows->count(),
        ]);
        
        foreach ($rows as $index => $row) {
            // Convert row to array (handle both object and array inputs)
            $rowData = is_array($row) ? $row : $row->toArray();
            
            // Apply template if provided
            if ($this->template) {
                $rowData = $this->template->applyTo($rowData);
            }
            
            // Capture original column names from first row for mapping summary
            if ($isFirstRow) {
                $allOriginalColumns = array_keys($rowData);
                $isFirstRow = false;
            }
            
            // Map uploaded data to system format (returns structured data)
            $mappedData = $this->mappingService->mapUploadedData($rowData);
            
            // Generate column mapping summary from first row
            if ($this->columnMappingSummary === null) {
                $this->columnMappingSummary = $this->generateMappingSummary($allOriginalColumns, $mappedData);
            }
            
            // Extract core fields for validation
            $coreFields = $mappedData['core_fields'];
            
            // Skip if essential data is missing
            if (empty($coreFields['last_name']) || empty($coreFields['first_name'])) {
                $skippedCount++;
                Log::warning('Skipping row with missing required fields', [
                    'batch_id' => $this->batchId,
                    'row_index' => $index + 1,
                    'has_last_name' => !empty($coreFields['last_name']),
                    'has_first_name' => !empty($coreFields['first_name']),
                ]);
                continue;
            }
            
            // Find match in main system (pass structured data)
            $matchResult = $this->matchService->findMatch($mappedData);
            
            // If NEW RECORD, insert into main system
            if ($matchResult['status'] === 'NEW RECORD') {
                $coreFields['origin_batch_id'] = $this->batchId;
                
                $newRecord = $this->matchService->insertNewRecord(['core_fields' => $coreFields]);
                $matchResult['matched_id'] = $newRecord->uid;
                
                $newRecordCount++;
                
                Log::info('New record created', [
                    'batch_id' => $this->batchId,
                    'record_id' => $newRecord->uid,
                    'last_name' => $coreFields['last_name'],
                    'first_name' => $coreFields['first_name'],
                ]);
                
                // Create match result record first
                $matchResultRecord = MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $coreFields['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $coreFields['last_name'],
                    'uploaded_first_name' => $coreFields['first_name'],
                    'uploaded_middle_name' => $coreFields['middle_name'] ?? null,
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                ]);
                
                // Update the main system record with the match result ID
                $newRecord->update(['origin_match_result_id' => $matchResultRecord->id]);
            } else {
                // Extract field breakdown from match result
                $fieldBreakdown = $matchResult['field_breakdown'] ?? null;
                
                $matchedRecordCount++;
                
                // Create match result record for existing matches
                MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $coreFields['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $coreFields['last_name'],
                    'uploaded_first_name' => $coreFields['first_name'],
                    'uploaded_middle_name' => $coreFields['middle_name'] ?? null,
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                    'field_breakdown' => $fieldBreakdown,
                ]);
            }
            
            $processedCount++;
        }
        
        Log::info('Record import completed', [
            'batch_id' => $this->batchId,
            'total_rows' => $rows->count(),
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'new_records' => $newRecordCount,
            'matched_records' => $matchedRecordCount,
        ]);
    }

    /**
     * Generate column mapping summary from first row analysis
     *
     * @param array $originalColumns
     * @param array $mappedData
     * @return array
     */
    protected function generateMappingSummary(array $originalColumns, array $mappedData): array
    {
        $coreFieldsMapped = [];
        $skippedColumns = [];
        
        // Get the reverse mapping to find original column names
        $coreFields = $mappedData['core_fields'];
        
        // Track which original columns were mapped
        $mappedOriginalColumns = [];
        
        // Identify core fields mapped
        foreach ($coreFields as $systemField => $value) {
            // Find original column name that mapped to this system field
            $originalColumn = $this->findOriginalColumnForCoreField($originalColumns, $systemField);
            if ($originalColumn) {
                $coreFieldsMapped[] = $originalColumn;
                $mappedOriginalColumns[] = $originalColumn;
            }
        }
        
        // Identify skipped columns (empty or processed as compound names)
        foreach ($originalColumns as $column) {
            if (!in_array($column, $mappedOriginalColumns)) {
                $skippedColumns[] = $column;
            }
        }
        
        return [
            'core_fields_mapped' => $coreFieldsMapped,
            'skipped_columns' => $skippedColumns,
        ];
    }
    
    /**
     * Find original column name that mapped to a core field
     *
     * @param array $originalColumns
     * @param string $systemField
     * @return string|null
     */
    protected function findOriginalColumnForCoreField(array $originalColumns, string $systemField): ?string
    {
        $variations = CoreFieldMappings::getVariations($systemField);
        
        foreach ($variations as $variation) {
            if (in_array($variation, $originalColumns)) {
                return $variation;
            }
        }
        
        return null;
    }
}
