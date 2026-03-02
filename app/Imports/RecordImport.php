<?php

namespace App\Imports;

use App\Helpers\CoreFieldMappings;
use App\Models\MatchResult;
use App\Services\DataMappingService;
use App\Services\DataMatchService;
use App\Services\ConfidenceScoreService;
use App\Services\TemplateFieldPersistenceService;
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
    protected $persistenceService;
    protected $columnMappingSummary = null;

    public function __construct($batchId, $template = null)
    {
        $this->batchId = $batchId;
        $this->template = $template;
        $this->mappingService = new DataMappingService();
        $this->matchService = new DataMatchService(new ConfidenceScoreService());
        $this->persistenceService = new TemplateFieldPersistenceService();
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
            
            // Get template field names if template is provided
            $templateFieldNames = $this->template ? $this->template->fields->pluck('field_name')->toArray() : null;
            
            // Map uploaded data to system format (returns structured data with core and template fields)
            $mappedData = $this->mappingService->mapUploadedData($rowData, $templateFieldNames);
            
            // Generate column mapping summary from first row
            if ($this->columnMappingSummary === null) {
                $this->columnMappingSummary = $this->generateMappingSummary($allOriginalColumns, $mappedData);
            }
            
            // Extract core fields for validation
            $coreFields = $mappedData['core_fields'];
            $templateFields = $mappedData['template_fields'] ?? [];
            
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
            
            // Validate required template fields (pass raw row data to check all fields)
            if ($this->template) {
                $requiredFieldErrors = $this->validateRequiredTemplateFields($rowData, $index + 1);
                if (!empty($requiredFieldErrors)) {
                    throw new \Exception($requiredFieldErrors);
                }
            }
            
            // Find match in main system (pass structured data)
            $matchResult = $this->matchService->findMatch($mappedData, $this->template?->id);
            
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
                
                // Extract field breakdown from match result
                $fieldBreakdown = $matchResult['field_breakdown'] ?? null;
                
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
                    'field_breakdown' => $fieldBreakdown,
                ]);
                
                // Update the main system record with the match result ID
                $newRecord->update(['origin_match_result_id' => $matchResultRecord->id]);
                
                // Persist template fields after MainSystem record is saved
                if (!empty($templateFields) && $this->template) {
                    try {
                        $this->persistenceService->persistTemplateFields(
                            (int) $newRecord->id,
                            $templateFields,
                            $this->batchId,
                            $matchResult['confidence'],
                            $this->template->id
                        );
                    } catch (\Exception $e) {
                        Log::error('Template field persistence failed', [
                            'batch_id' => $this->batchId,
                            'row_index' => $index + 1,
                            'main_system_id' => $newRecord->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                // Extract field breakdown from match result
                $fieldBreakdown = $matchResult['field_breakdown'] ?? null;
                
                $matchedRecordCount++;
                
                // Create match result record for existing matches
                $matchResultRecord = MatchResult::create([
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
                
                // Persist template fields after MainSystem record is saved
                if (!empty($templateFields) && $this->template) {
                    try {
                        $this->persistenceService->persistTemplateFields(
                            (int) $matchResult['matched_id'],
                            $templateFields,
                            $this->batchId,
                            $matchResult['confidence'],
                            $this->template->id
                        );
                    } catch (\Exception $e) {
                        Log::error('Template field persistence failed', [
                            'batch_id' => $this->batchId,
                            'row_index' => $index + 1,
                            'main_system_id' => $matchResult['matched_id'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
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
        $dynamicFieldsCaptured = [];
        $skippedColumns = [];
        
        // Get the mapped data
        $coreFields = $mappedData['core_fields'];
        $templateFields = $mappedData['template_fields'] ?? [];
        $dynamicFields = $mappedData['dynamic_fields'] ?? [];
        
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
        
        // Identify template fields captured (these are in original columns)
        foreach ($templateFields as $fieldName => $value) {
            if (in_array($fieldName, $originalColumns)) {
                $dynamicFieldsCaptured[] = $fieldName;
                $mappedOriginalColumns[] = $fieldName;
            }
        }
        
        // Identify dynamic fields captured (these are unknown fields normalized to snake_case)
        foreach ($dynamicFields as $snakeCaseKey => $value) {
            // Find the original column name that maps to this snake_case key
            foreach ($originalColumns as $originalColumn) {
                if ($this->toSnakeCase($originalColumn) === $snakeCaseKey && !in_array($originalColumn, $mappedOriginalColumns)) {
                    $dynamicFieldsCaptured[] = $originalColumn;
                    $mappedOriginalColumns[] = $originalColumn;
                    break;
                }
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

    /**
     * Convert string to snake_case
     */
    protected function toSnakeCase(string $str): string
    {
        // Replace spaces and hyphens with underscores
        $str = str_replace([' ', '-'], '_', $str);
        
        // Insert underscores before uppercase letters (camelCase to snake_case)
        $str = preg_replace('/([a-z])([A-Z])/', '$1_$2', $str);
        
        // Convert to lowercase
        return strtolower($str);
    }

    /**
     * Validate required template fields
     *
     * @param array $templateFields Template field values from the row
     * @param int $rowNumber The row number (1-indexed) for error reporting
     * @return string Empty string if valid, error message if invalid
     */
    protected function validateRequiredTemplateFields(array $rowData, int $rowNumber): string
    {
        if (!$this->template) {
            return '';
        }

        $missingFields = [];

        foreach ($this->template->fields as $field) {
            if (!$field->is_required) {
                continue;
            }

            $fieldValue = $rowData[$field->field_name] ?? null;

            if ($fieldValue === null || $fieldValue === '') {
                $missingFields[] = $field->field_name;
            }
        }

        if (empty($missingFields)) {
            return '';
        }

        $fieldList = implode("', '", $missingFields);
        $fieldCount = count($missingFields);
        $fieldWord = $fieldCount === 1 ? 'field' : 'fields';

        return "Row {$rowNumber}: Required custom {$fieldWord} cannot be empty. " .
               "Please provide values for: '{$fieldList}'. " .
               "All required fields marked in the template must have data in every row.";
    }
}
