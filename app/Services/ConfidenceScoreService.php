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
     * @return array ['score' => float, 'breakdown' => array]
     */
    public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord): array
    {
        $breakdown = $this->generateBreakdown($uploadedData, $existingRecord);
        
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
     * @return array Field comparison details
     */
    public function generateBreakdown(array $uploadedData, MainSystem $existingRecord): array
    {
        $coreFields = $uploadedData['core_fields'] ?? [];
        
        $totalFields = 0;
        $matchedFields = 0;
        $fieldDetails = [];
        
        // Process core fields
        foreach ($coreFields as $field => $uploadedValue) {
            $totalFields++;
            $existingValue = $existingRecord->$field ?? null;
            
            if ($this->valuesMatch($uploadedValue, $existingValue)) {
                $matchedFields++;
                $fieldDetails[$field] = [
                    'status' => 'match',
                    'uploaded' => $uploadedValue,
                    'existing' => $existingValue,
                    'category' => 'core',
                ];
            } else {
                $fieldDetails[$field] = [
                    'status' => 'mismatch',
                    'uploaded' => $uploadedValue,
                    'existing' => $existingValue,
                    'category' => 'core',
                ];
            }
        }
        
        return [
            'total_fields' => $totalFields,
            'matched_fields' => $matchedFields,
            'fields' => $fieldDetails,
        ];
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
}
