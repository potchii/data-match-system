<?php

namespace App\Services;

use App\Models\MainSystem;

class ConfidenceScoreService
{
    /**
     * Core fields in the MainSystem model (excluding metadata fields)
     */
    protected const CORE_FIELDS = [
        'uid',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'birthday',
        'gender',
        'civil_status',
        'street_no',
        'street',
        'city',
        'province',
        'barangay',
    ];

    /**
     * Calculate unified confidence score
     * 
     * @param array $uploadedData All fields from upload
     * @param MainSystem $existingRecord Database record to compare against
     * @param int|null $templateId Optional template ID for template fields
     * @return array ['score' => float, 'breakdown' => array]
     */
    public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord, ?int $templateId = null): array
    {
        $breakdown = $this->generateBreakdown($uploadedData, $existingRecord, $templateId);
        
        $totalFields = $breakdown['total_fields'];
        $matchedFields = $breakdown['matched_fields'];
        
        // Calculate percentage: (matching fields / total fields) × 100
        $score = $totalFields > 0 ? ($matchedFields / $totalFields) * 100 : 0;
        
        return [
            'score' => round($score, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Generate field-by-field comparison breakdown
     * 
     * @param array $uploadedData
     * @param MainSystem $existingRecord
     * @param int|null $templateId Optional template ID for template fields
     * @return array Field comparison details
     */
    public function generateBreakdown(array $uploadedData, MainSystem $existingRecord, ?int $templateId = null): array
    {
        $coreFields = $uploadedData['core_fields'] ?? [];
        $templateFields = $uploadedData['template_fields'] ?? [];
        
        $totalFields = 0;
        $matchedFields = 0;
        $coreFieldDetails = [];
        $templateFieldDetails = [];
        
        // Process core fields
        foreach ($coreFields as $field => $uploadedValue) {
            $totalFields++;
            $existingValue = $existingRecord->$field ?? null;
            
            $fieldData = [
                'status' => $this->valuesMatch($uploadedValue, $existingValue) ? 'match' : 'mismatch',
                'uploaded' => $uploadedValue,
                'existing' => $existingValue,
                'category' => 'core',
            ];
            
            // Add normalized values if available for name fields
            if (in_array($field, ['last_name', 'first_name', 'middle_name'])) {
                $normalizedField = $field . '_normalized';
                
                // Get uploaded normalized value from uploadedData if available
                $uploadedNormalized = $uploadedData['core_fields'][$normalizedField] ?? null;
                
                // Get existing normalized value from database
                $existingNormalized = $existingRecord->$normalizedField ?? null;
                
                if ($uploadedNormalized !== null) {
                    $fieldData['uploaded_normalized'] = $uploadedNormalized;
                }
                
                if ($existingNormalized !== null) {
                    $fieldData['existing_normalized'] = $existingNormalized;
                }
            }
            
            // Calculate confidence score
            $fieldType = $this->getCoreFieldType($field);
            $fieldData['confidence'] = $this->calculateFieldConfidence($uploadedValue, $existingValue, $fieldType);
            
            if ($fieldData['status'] === 'match') {
                $matchedFields++;
            }
            
            $coreFieldDetails[$field] = $fieldData;
        }
        
        // Process template fields if template ID is provided
        if ($templateId && !empty($templateFields)) {
            $templateFieldModels = $this->getTemplateFields($templateId);
            
            foreach ($templateFields as $field => $uploadedValue) {
                $totalFields++;
                
                // Get existing value - for now, template field values are not stored in database
                // This will be implemented when template field storage is added
                $existingValue = null;
                
                // Find field type from template
                $fieldType = $this->getFieldType($field, $templateFieldModels);
                
                $isMatch = $this->valuesMatch($uploadedValue, $existingValue);
                $status = ($existingValue === null || $existingValue === '') ? 'new' : ($isMatch ? 'match' : 'mismatch');
                
                if ($status === 'match') {
                    $matchedFields++;
                }
                
                $templateFieldDetails[$field] = [
                    'status' => $status,
                    'uploaded' => $uploadedValue,
                    'existing' => $existingValue,
                    'category' => 'template',
                    'field_type' => $fieldType,
                    'confidence' => $this->calculateFieldConfidence($uploadedValue, $existingValue, $fieldType),
                ];
            }
        }
        
        return [
            'total_fields' => $totalFields,
            'matched_fields' => $matchedFields,
            'core_fields' => $coreFieldDetails,
            'template_fields' => $templateFieldDetails,
            'fields' => array_merge($coreFieldDetails, $templateFieldDetails), // For backward compatibility
        ];
    }
    
    /**
     * Get template fields from database
     * 
     * @param int $templateId
     * @return array
     */
    protected function getTemplateFields(int $templateId): array
    {
        $template = \App\Models\ColumnMappingTemplate::with('fields')->find($templateId);
        
        if (!$template) {
            \Log::warning('Template not found for field breakdown', ['template_id' => $templateId]);
            return [];
        }
        
        return $template->fields->toArray();
    }
    
    /**
     * Get field type for a template field
     * 
     * @param string $fieldName
     * @param array $templateFields
     * @return string
     */
    protected function getFieldType(string $fieldName, array $templateFields): string
    {
        foreach ($templateFields as $field) {
            if ($field['field_name'] === $fieldName) {
                return $field['field_type'];
            }
        }
        
        return 'string'; // Default to string if not found
    }
    
    /**
     * Get field type for core fields
     * 
     * @param string $fieldName
     * @return string
     */
    protected function getCoreFieldType(string $fieldName): string
    {
        return match($fieldName) {
            'birthday' => 'date',
            'uid', 'street_no' => 'string',
            default => 'string',
        };
    }

    /**
     * Compare two field values for equality
     * Treats null and empty string as equivalent
     * Handles Carbon date objects
     * 
     * @param mixed $value1
     * @param mixed $value2
     * @return bool
     */
    protected function valuesMatch($value1, $value2): bool
    {
        // Treat null and empty string as equivalent
        $normalizedValue1 = ($value1 === null || $value1 === '') ? null : $value1;
        $normalizedValue2 = ($value2 === null || $value2 === '') ? null : $value2;
        
        // Handle Carbon date objects
        if ($normalizedValue2 instanceof \Carbon\Carbon || $normalizedValue2 instanceof \Carbon\CarbonImmutable) {
            $normalizedValue2 = $normalizedValue2->format('Y-m-d');
        }
        if ($normalizedValue1 instanceof \Carbon\Carbon || $normalizedValue1 instanceof \Carbon\CarbonImmutable) {
            $normalizedValue1 = $normalizedValue1->format('Y-m-d');
        }
        
        return $normalizedValue1 === $normalizedValue2;
    }
    
    /**
     * Calculate field-level confidence score
     * 
     * @param mixed $uploadedValue
     * @param mixed $existingValue
     * @param string $fieldType Field type (string, date, integer, decimal, boolean)
     * @return float|null Confidence percentage (0-100) or null if existing value is null
     */
    protected function calculateFieldConfidence($uploadedValue, $existingValue, string $fieldType = 'string'): ?float
    {
        // If existing value is null or empty, return null (new field)
        if ($existingValue === null || $existingValue === '') {
            return null;
        }
        
        // Normalize values for comparison
        $normalizedUploaded = $this->normalizeValueForComparison($uploadedValue, $fieldType);
        $normalizedExisting = $this->normalizeValueForComparison($existingValue, $fieldType);
        
        // Exact match = 100%
        if ($normalizedUploaded === $normalizedExisting) {
            return 100.0;
        }
        
        // No match for non-string types = 0%
        if ($fieldType !== 'string') {
            return 0.0;
        }
        
        // For string types, calculate fuzzy match score
        return $this->calculateFuzzyMatchScore($normalizedUploaded, $normalizedExisting);
    }
    
    /**
     * Normalize value for comparison based on field type
     * 
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    protected function normalizeValueForComparison($value, string $fieldType)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Handle Carbon date objects
        if ($value instanceof \Carbon\Carbon || $value instanceof \Carbon\CarbonImmutable) {
            return $value->format('Y-m-d');
        }
        
        return match($fieldType) {
            'string' => strtolower(trim((string) $value)),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'date' => $value,
            'boolean' => in_array(strtolower(trim((string) $value)), ['true', '1', 'yes', 'y']) ? 1 : 0,
            default => strtolower(trim((string) $value)),
        };
    }
    
    /**
     * Calculate fuzzy match score for string values
     * Uses Levenshtein distance for similarity calculation
     * 
     * @param string|null $value1
     * @param string|null $value2
     * @return float Score between 0 and 99
     */
    protected function calculateFuzzyMatchScore(?string $value1, ?string $value2): float
    {
        if ($value1 === null || $value2 === null) {
            return 0.0;
        }
        
        $value1 = (string) $value1;
        $value2 = (string) $value2;
        
        // If either string is empty, return 0
        if ($value1 === '' || $value2 === '') {
            return 0.0;
        }
        
        // Calculate Levenshtein distance
        $distance = levenshtein($value1, $value2);
        $maxLength = max(strlen($value1), strlen($value2));
        
        // Avoid division by zero
        if ($maxLength === 0) {
            return 100.0;
        }
        
        // Calculate similarity percentage (0-99 for fuzzy matches)
        $similarity = (1 - ($distance / $maxLength)) * 100;
        
        // Cap at 99% for fuzzy matches (100% is reserved for exact matches)
        return min(99.0, max(0.0, $similarity));
    }
}
