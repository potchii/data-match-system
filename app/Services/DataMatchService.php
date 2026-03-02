<?php

namespace App\Services;

use App\Models\MainSystem;
use App\Services\MatchingRules\ExactMatchRule;
use App\Services\MatchingRules\PartialMatchWithDobRule;
use App\Services\MatchingRules\FullNameMatchRule;
use App\Services\MatchingRules\FuzzyNameMatchRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DataMatchService
{
    protected array $rules;
    protected Collection $candidateCache;
    protected ConfidenceScoreService $confidenceScoreService;
    
    public function __construct(ConfidenceScoreService $confidenceScoreService)
    {
        $this->confidenceScoreService = $confidenceScoreService;
        
        // Initialize matching rules in priority order
        $this->rules = [
            new ExactMatchRule(),           // 100% - First + Last + Middle + DOB
            new PartialMatchWithDobRule(),  // 90%  - First + Last + DOB (no middle name)
            new FullNameMatchRule(),        // 80%  - First + Last + Middle (no DOB required)
            new FuzzyNameMatchRule(),       // 70%  - Similar name (fuzzy matching)
        ];
        
        $this->candidateCache = collect();
    }
    
    /**
     * Batch process multiple records for matching
     * Returns: Collection of match results
     */
    public function batchFindMatches(Collection $uploadedRecords): Collection
    {
        // Normalize all uploaded records
        $normalizedRecords = $uploadedRecords->map(function ($record) {
            return $this->normalizeRecord($record);
        });
        
        // Load candidates from database only (exclude current batch)
        $this->loadCandidatesExcludingBatch($normalizedRecords);
        
        // Match each record against cached candidates (database records only)
        return $normalizedRecords->map(function ($normalized, $index) use ($uploadedRecords) {
            // Get the original uploaded data for this record
            $uploadedData = $uploadedRecords->get($index);
            return $this->findMatchFromCache($normalized, $uploadedData);
        });
    }
    
    /**
     * Find match for single uploaded record (legacy support)
     * Returns: ['status' => string, 'confidence' => float, 'matched_id' => ?string, 'field_breakdown' => ?array]
     * 
     * NOTE: This method is deprecated. Use batchFindMatches() instead for batch processing.
     * This method does NOT refresh candidates - it uses the current cache state.
     */
    public function findMatch(array $uploadedData, ?int $templateId = null): array
    {
        // Support both old format (flat array) and new format (structured)
        if (isset($uploadedData['core_fields'])) {
            $coreData = $uploadedData['core_fields'];
        } else {
            // Backward compatibility: treat entire array as core data
            $coreData = $uploadedData;
        }

        $normalized = $this->normalizeRecord($coreData);

        // Use current cache state without refreshing
        // This prevents matching records within the same batch against each other
        return $this->findMatchFromCache($normalized, $uploadedData, $templateId);
    }
    
    /**
     * Find match from cached candidates using rule chain
     * Now uses unified scoring with field breakdown
     */
    protected function findMatchFromCache(array $normalized, array $uploadedData = [], ?int $templateId = null): array
    {
        foreach ($this->rules as $rule) {
            // Pass template ID to FuzzyNameMatchRule
            if ($rule instanceof FuzzyNameMatchRule) {
                $result = $rule->match($normalized, $this->candidateCache, $templateId);
            } else {
                $result = $rule->match($normalized, $this->candidateCache);
            }
            
            if ($result) {
                // Use unified confidence score calculation
                $matchedRecord = $result['record'];
                
                // Ensure uploadedData is in the correct format for scoring
                $scoringData = $uploadedData;
                if (!isset($uploadedData['core_fields'])) {
                    // Convert flat format to structured format for scoring
                    $scoringData = [
                        'core_fields' => $uploadedData,
                    ];
                }
                
                $scoreResult = $this->confidenceScoreService->calculateUnifiedScore($scoringData, $matchedRecord, $templateId);
                
                return [
                    'status' => $rule->status(),
                    'confidence' => $scoreResult['score'],
                    'matched_id' => $matchedRecord->uid,
                    'rule' => $result['rule'],
                    'field_breakdown' => $scoreResult['breakdown'],
                ];
            }
        }
        
        // No match found - generate field breakdown for NEW RECORD
        $scoringData = $uploadedData;
        if (!isset($uploadedData['core_fields'])) {
            $scoringData = [
                'core_fields' => $uploadedData,
            ];
        }
        
        // Create a temporary MainSystem record to generate breakdown
        $tempRecord = new MainSystem();
        $breakdown = $this->confidenceScoreService->generateBreakdown($scoringData, $tempRecord, $templateId);
        
        return [
            'status' => 'NEW RECORD',
            'confidence' => 0.0,
            'matched_id' => null,
            'rule' => 'no_match',
            'field_breakdown' => $breakdown,
        ];
    }
    
    /**
     * Load all potential candidate records in one query
     */
    protected function loadCandidates(Collection $normalizedRecords): void
    {
        // Extract unique normalized names for efficient querying
        $lastNames = $normalizedRecords->pluck('last_name_normalized')->unique()->filter();
        $firstNames = $normalizedRecords->pluck('first_name_normalized')->unique()->filter();
        
        if ($lastNames->isEmpty() && $firstNames->isEmpty()) {
            $this->candidateCache = collect();
            return;
        }
        
        // Fetch all records that match any of the last names or first names
        // This casts a wide net for the matching rules to filter
        $this->candidateCache = MainSystem::where(function ($query) use ($lastNames, $firstNames) {
            if ($lastNames->isNotEmpty()) {
                $query->whereIn('last_name_normalized', $lastNames);
            }
            if ($firstNames->isNotEmpty()) {
                $query->orWhereIn('first_name_normalized', $firstNames);
            }
        })->get();
    }

    /**
     * Load candidates from database only, excluding records from current batch.
     * This prevents matching records within the same Excel file against each other.
     */
    protected function loadCandidatesExcludingBatch(Collection $normalizedRecords): void
    {
        // Extract unique normalized names for efficient querying
        $lastNames = $normalizedRecords->pluck('last_name_normalized')->unique()->filter();
        $firstNames = $normalizedRecords->pluck('first_name_normalized')->unique()->filter();
        
        if ($lastNames->isEmpty() && $firstNames->isEmpty()) {
            $this->candidateCache = collect();
            return;
        }
        
        // Fetch records from database only (not from current batch)
        // This ensures we only match against existing database records
        $this->candidateCache = MainSystem::where(function ($query) use ($lastNames, $firstNames) {
            if ($lastNames->isNotEmpty()) {
                $query->whereIn('last_name_normalized', $lastNames);
            }
            if ($firstNames->isNotEmpty()) {
                $query->orWhereIn('first_name_normalized', $firstNames);
            }
        })->get();
    }
    
    /**
     * Refresh candidates from database while preserving newly inserted records
     * This ensures cross-batch matching works while keeping within-batch inserts
     */
    protected function refreshCandidates(Collection $normalizedRecords): void
    {
        // Extract unique normalized names for efficient querying
        $lastNames = $normalizedRecords->pluck('last_name_normalized')->unique()->filter();
        $firstNames = $normalizedRecords->pluck('first_name_normalized')->unique()->filter();
        
        if ($lastNames->isEmpty() && $firstNames->isEmpty()) {
            return;
        }
        
        // Fetch fresh records from database only (not from current batch)
        // This prevents records inserted during this batch from being matched against later records
        $this->candidateCache = MainSystem::where(function ($query) use ($lastNames, $firstNames) {
            if ($lastNames->isNotEmpty()) {
                $query->whereIn('last_name_normalized', $lastNames);
            }
            if ($firstNames->isNotEmpty()) {
                $query->orWhereIn('first_name_normalized', $firstNames);
            }
        })->get();
    }
    
    /**
     * Normalize record for matching
     */
    protected function normalizeRecord(array $data): array
    {
        return [
            'last_name' => $data['last_name'] ?? '',
            'first_name' => $data['first_name'] ?? '',
            'middle_name' => $data['middle_name'] ?? '',
            'last_name_normalized' => $this->normalizeString($data['last_name'] ?? ''),
            'first_name_normalized' => $this->normalizeString($data['first_name'] ?? ''),
            'middle_name_normalized' => $this->normalizeString($data['middle_name'] ?? ''),
            'birthday' => $this->extractAndNormalizeBirthday($data),
            'dob' => $this->extractDob($data),
            'gender' => $this->extractGender($data),
            'address' => $this->extractAddress($data),
            'barangay' => $this->extractBarangay($data),
            'template_fields' => $this->extractTemplateFields($data),
            'original_data' => $data,
        ];
    }

    /**
     * Extract DOB from various field names
     */
    private function extractDob(array $data): ?string
    {
        $dobFields = [
            'dob', 'DOB', 'date_of_birth', 'dateOfBirth',
            'DateOfBirth', 'dateofbirth', 'birthdate', 'BirthDate',
            'birth_date', 'Birthday', 'Birthdate', 'birthday',
        ];

        foreach ($dobFields as $field) {
            if (!empty($data[$field])) {
                return (string) $data[$field];
            }
        }

        return null;
    }

    /**
     * Extract gender from various field names
     */
    private function extractGender(array $data): ?string
    {
        $genderFields = [
            'gender', 'Gender', 'sex', 'Sex', 'gender_code', 'genderCode',
            'gender_id', 'genderId', 'gender_type', 'genderType',
        ];

        foreach ($genderFields as $field) {
            if (!empty($data[$field])) {
                return (string) $data[$field];
            }
        }

        return null;
    }

    /**
     * Extract address from various field names
     */
    private function extractAddress(array $data): ?string
    {
        $addressFields = [
            'address', 'Address', 'street_address', 'streetAddress',
            'street', 'Street', 'location', 'Location',
        ];

        foreach ($addressFields as $field) {
            if (!empty($data[$field])) {
                return (string) $data[$field];
            }
        }

        return null;
    }

    /**
     * Extract barangay from various field names
     */
    private function extractBarangay(array $data): ?string
    {
        $barangayFields = [
            'barangay', 'Barangay', 'barangay_name', 'barangayName',
            'barangay_code', 'barangayCode', 'brgy', 'Brgy',
        ];

        foreach ($barangayFields as $field) {
            if (!empty($data[$field])) {
                return (string) $data[$field];
            }
        }

        return null;
    }

    /**
     * Extract template fields from data
     * Template fields are typically prefixed or in a specific structure
     */
    private function extractTemplateFields(array $data): array
    {
        $templateFields = [];

        // Look for fields prefixed with 'template_' or in a 'template_fields' array
        foreach ($data as $key => $value) {
            if (strpos($key, 'template_') === 0) {
                $fieldName = substr($key, strlen('template_'));
                $templateFields[$fieldName] = $value;
            }
        }

        // Also check for a 'template_fields' array
        if (isset($data['template_fields']) && is_array($data['template_fields'])) {
            $templateFields = array_merge($templateFields, $data['template_fields']);
        }

        return $templateFields;
    }
    
    /**
     * Normalize string for case-insensitive comparison
     */
    protected function normalizeString(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
    
    /**
     * Extract and normalize birthday with robust parsing
     */
    protected function extractAndNormalizeBirthday(array $data): ?string
    {
        $birthdayFields = [
            'birthday', 'dob', 'DOB', 'date_of_birth', 'dateOfBirth',
            'DateOfBirth', 'dateofbirth', 'birthdate', 'BirthDate',
            'birth_date', 'Birthday', 'Birthdate',
        ];

        foreach ($birthdayFields as $field) {
            if (!empty($data[$field])) {
                $normalized = $this->parseDate($data[$field]);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return null;
    }
    
    /**
     * Robust date parsing with multiple format support
     */
    protected function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        // Try common formats explicitly
        $formats = [
            'Y-m-d',      // 2023-01-15
            'd/m/Y',      // 15/01/2023
            'm/d/Y',      // 01/15/2023
            'd-m-Y',      // 15-01-2023
            'm-d-Y',      // 01-15-2023
            'Y/m/d',      // 2023/01/15
            'd.m.Y',      // 15.01.2023
        ];
        
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $date);
                if ($parsed && $parsed->year >= 1900 && $parsed->year <= now()->year) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Fallback to Carbon's flexible parsing
        try {
            $parsed = Carbon::parse($date);
            if ($parsed->year >= 1900 && $parsed->year <= now()->year) {
                return $parsed->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }
    
    /**
     * Insert new record into main system
     */
    /**
     * Insert new record into main system
     */
    public function insertNewRecord(array $data): MainSystem
    {
        // Support both old format and new format
        if (isset($data['core_fields'])) {
            $coreFields = $data['core_fields'];
        } else {
            // Backward compatibility
            $coreFields = $data;
        }

        $coreFields['uid'] = $this->generateUid();
        $coreFields['last_name_normalized'] = $this->normalizeString($coreFields['last_name'] ?? '');
        $coreFields['first_name_normalized'] = $this->normalizeString($coreFields['first_name'] ?? '');
        $coreFields['middle_name_normalized'] = $this->normalizeString($coreFields['middle_name'] ?? '');

        $newRecord = MainSystem::create($coreFields);

        // Add newly created record to cache to prevent duplicates within same batch
        $this->candidateCache->push($newRecord);

        return $newRecord;
    }
    
    /**
     * Generate unique UID using UUID
     */
    protected function generateUid(): string
    {
        return 'UID-' . strtoupper(Str::ulid());
    }
}
