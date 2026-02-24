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
    protected $columnMappingSummary = null;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
        $this->mappingService = new DataMappingService();
        $this->matchService = new DataMatchService();
    }

    /**
     * Get column mapping summary from the first row
     */
    public function getColumnMappingSummary(): ?array
    {
        return $this->columnMappingSummary;
    }

    /**
     * Process each row from the Excel file
     */
    public function collection(Collection $rows)
    {
        $isFirstRow = true;
        $allOriginalColumns = [];
        
        foreach ($rows as $index => $row) {
            // Convert row to array (handle both object and array inputs)
            $rowData = is_array($row) ? $row : $row->toArray();
            
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
                continue;
            }
            
            // Find match in main system (pass structured data)
            $matchResult = $this->matchService->findMatch($mappedData);
            
            // If NEW RECORD, insert into main system
            if ($matchResult['status'] === 'NEW RECORD') {
                $coreFields['origin_batch_id'] = $this->batchId;
                
                // Reconstruct full data for insertion
                $insertData = [
                    'core_fields' => $coreFields,
                    'dynamic_fields' => $mappedData['dynamic_fields'] ?? [],
                ];
                
                $newRecord = $this->matchService->insertNewRecord($insertData);
                $matchResult['matched_id'] = $newRecord->uid;
                
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
                ]);
            }
        }
    }

    /**
     * Generate column mapping summary from first row analysis
     */
    protected function generateMappingSummary(array $originalColumns, array $mappedData): array
    {
        $coreFieldsMapped = [];
        $dynamicFieldsCaptured = [];
        $skippedColumns = [];
        
        // Get the reverse mapping to find original column names
        $coreFields = $mappedData['core_fields'];
        $dynamicFields = $mappedData['dynamic_fields'];
        
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
        
        // Identify dynamic fields captured
        foreach ($dynamicFields as $normalizedKey => $value) {
            // Find original column name that became this dynamic field
            $originalColumn = $this->findOriginalColumnForDynamicField($originalColumns, $normalizedKey, $mappedOriginalColumns);
            if ($originalColumn) {
                $dynamicFieldsCaptured[] = $originalColumn;
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
            'dynamic_fields_captured' => $dynamicFieldsCaptured,
            'skipped_columns' => $skippedColumns,
        ];
    }
    
    /**
     * Find original column name that mapped to a core field
     */
    protected function findOriginalColumnForCoreField(array $originalColumns, string $systemField): ?string
    {
        // Get core field mappings from DataMappingService
        $mappings = [
            'uid' => ['regsno', 'RegsNo', 'regsnumber', 'registration_no'],
            'last_name' => ['surname', 'Surname', 'lastname', 'LastName', 'last_name'],
            'first_name' => ['firstname', 'FirstName', 'first_name', 'fname'],
            'middle_name' => ['middlename', 'MiddleName', 'middle_name', 'mname'],
            'suffix' => ['extension', 'Extension', 'suffix', 'Suffix', 'ext'],
            'birthday' => ['dob', 'DOB', 'birthday', 'Birthday', 'birthdate', 'BirthDate', 'birth_date', 'date_of_birth', 'DateOfBirth', 'dateofbirth'],
            'gender' => ['sex', 'Sex', 'gender', 'Gender'],
            'civil_status' => ['status', 'Status', 'civilstatus', 'CivilStatus', 'civil_status'],
            'street' => ['address', 'Address', 'street', 'Street'],
            'city' => ['city', 'City'],
            'barangay' => ['brgydescription', 'BrgyDescription', 'barangay', 'Barangay'],
        ];
        
        if (!isset($mappings[$systemField])) {
            return null;
        }
        
        foreach ($mappings[$systemField] as $variation) {
            if (in_array($variation, $originalColumns)) {
                return $variation;
            }
        }
        
        return null;
    }
    
    /**
     * Find original column name that became a dynamic field
     */
    protected function findOriginalColumnForDynamicField(array $originalColumns, string $normalizedKey, array $alreadyMapped): ?string
    {
        // Try to find a column that normalizes to this key
        foreach ($originalColumns as $column) {
            if (in_array($column, $alreadyMapped)) {
                continue;
            }
            
            // Normalize the column name the same way DataMappingService does
            $normalized = $this->normalizeDynamicKey($column);
            if ($normalized === $normalizedKey) {
                return $column;
            }
        }
        
        return null;
    }
    
    /**
     * Normalize dynamic key (same logic as DataMappingService)
     */
    protected function normalizeDynamicKey(string $key): string
    {
        $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key);
        $normalized = strtolower($key);
        $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);
        return trim($normalized, '_');
    }
}
