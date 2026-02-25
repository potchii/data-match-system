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
            new ExactMatchRule(),           // 100% - Full name + DOB
            new PartialMatchWithDobRule(),  // 90%  - First + Last + DOB
            new FullNameMatchRule(),        // 80%  - Full name only (no DOB)
            new FuzzyNameMatchRule(),       // 70%  - Similar name (fuzzy)
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
        
        // Load all potential candidates in one query
        $this->loadCandidates($normalizedRecords);
        
        // Match each record against cached candidates
        return $normalizedRecords->map(function ($normalized) {
            return $this->findMatchFromCache($normalized);
        });
    }
    
    /**
     * Find match for single uploaded record (legacy support)
     * Returns: ['status' => string, 'confidence' => float, 'matched_id' => ?string, 'field_breakdown' => ?array]
     */
    public function findMatch(array $uploadedData): array
    {
        // Support both old format (flat array) and new format (structured)
        if (isset($uploadedData['core_fields'])) {
            $coreData = $uploadedData['core_fields'];
        } else {
            // Backward compatibility: treat entire array as core data
            $coreData = $uploadedData;
        }

        $normalized = $this->normalizeRecord($coreData);

        // Reload candidates from database to include cross-batch records
        // but preserve newly inserted records from current batch
        $this->refreshCandidates(collect([$normalized]));

        return $this->findMatchFromCache($normalized, $uploadedData);
    }
    
    /**
     * Find match from cached candidates using rule chain
     * Now uses unified scoring with field breakdown
     */
    protected function findMatchFromCache(array $normalized, array $uploadedData): array
    {
        foreach ($this->rules as $rule) {
            $result = $rule->match($normalized, $this->candidateCache);
            
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
                
                $scoreResult = $this->confidenceScoreService->calculateUnifiedScore($scoringData, $matchedRecord);
                
                return [
                    'status' => $rule->status(),
                    'confidence' => $scoreResult['score'],
                    'matched_id' => $matchedRecord->uid,
                    'rule' => $result['rule'],
                    'field_breakdown' => $scoreResult['breakdown'],
                ];
            }
        }
        
        // No match found
        return [
            'status' => 'NEW RECORD',
            'confidence' => 0.0,
            'matched_id' => null,
            'rule' => 'no_match',
            'field_breakdown' => null,
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
        
        // Fetch fresh records from database
        $dbCandidates = MainSystem::where(function ($query) use ($lastNames, $firstNames) {
            if ($lastNames->isNotEmpty()) {
                $query->whereIn('last_name_normalized', $lastNames);
            }
            if ($firstNames->isNotEmpty()) {
                $query->orWhereIn('first_name_normalized', $firstNames);
            }
        })->get();
        
        // Merge with existing cache, removing duplicates by UID
        $this->candidateCache = $this->candidateCache
            ->merge($dbCandidates)
            ->unique('uid')
            ->values();
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
            'original_data' => $data,
        ];
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
