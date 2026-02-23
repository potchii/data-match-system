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
    
    public function __construct()
    {
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
     * Returns: ['status' => string, 'confidence' => float, 'matched_id' => ?string]
     */
    public function findMatch(array $uploadedData): array
    {
        $normalized = $this->normalizeRecord($uploadedData);
        
        // Always reload candidates to include records from previous batches
        // and newly inserted records from current batch
        $this->loadCandidates(collect([$normalized]));
        
        return $this->findMatchFromCache($normalized);
    }
    
    /**
     * Find match from cached candidates using rule chain
     */
    protected function findMatchFromCache(array $normalized): array
    {
        foreach ($this->rules as $rule) {
            $result = $rule->match($normalized, $this->candidateCache);
            
            if ($result) {
                return [
                    'status' => $rule->status(),
                    'confidence' => $rule->confidence(),
                    'matched_id' => $result['record']->uid,
                    'rule' => $result['rule'],
                ];
            }
        }
        
        // No match found
        return [
            'status' => 'NEW RECORD',
            'confidence' => 0.0,
            'matched_id' => null,
            'rule' => 'no_match',
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
        
        if ($lastNames->isEmpty() || $firstNames->isEmpty()) {
            $this->candidateCache = collect();
            return;
        }
        
        // Single query to fetch all potential matches
        $this->candidateCache = MainSystem::whereIn('last_name_normalized', $lastNames)
            ->orWhereIn('first_name_normalized', $firstNames)
            ->get();
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
    public function insertNewRecord(array $data): MainSystem
    {
        $data['uid'] = $this->generateUid();
        $data['last_name_normalized'] = $this->normalizeString($data['last_name'] ?? '');
        $data['first_name_normalized'] = $this->normalizeString($data['first_name'] ?? '');
        $data['middle_name_normalized'] = $this->normalizeString($data['middle_name'] ?? '');

        $newRecord = MainSystem::create($data);
        
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
