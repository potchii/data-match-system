<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

class ExactMatchRule extends MatchRule
{
    public function name(): string
    {
        return 'exact_match';
    }
    
    public function confidence(): float
    {
        return 100.0;
    }
    
    public function status(): string
    {
        return 'MATCHED';
    }
    
    public function match(array $normalizedData, Collection $candidates): ?array
    {
        // Require BOTH middle name AND birthday to be present for exact match
        // Use trim() to handle whitespace-only strings that empty() would miss
        $uploadedMiddle = trim($normalizedData['middle_name_normalized'] ?? '');
        $uploadedBirthday = trim($normalizedData['birthday'] ?? '');
        
        if ($uploadedMiddle === '' || $uploadedBirthday === '') {
            return null;
        }
        
        // Reject single-letter middle names (likely abbreviations/initials)
        // These should be handled by fuzzy matching rules instead
        if (strlen($uploadedMiddle) <= 1) {
            return null;
        }
        
        $match = $candidates->first(function ($candidate) use ($normalizedData, $uploadedMiddle, $uploadedBirthday) {
            // Convert birthday to string for comparison if it's a Carbon instance
            $candidateBirthday = ($candidate->birthday instanceof \Carbon\Carbon || $candidate->birthday instanceof \Carbon\CarbonImmutable)
                ? $candidate->birthday->format('Y-m-d') 
                : $candidate->birthday;
            
            // Require candidate to also have BOTH middle name AND birthday
            // Use trim() to handle whitespace-only strings
            $candidateMiddle = trim($candidate->middle_name_normalized ?? '');
            $candidateBirthdayStr = trim($candidateBirthday ?? '');
            
            if ($candidateMiddle === '' || $candidateBirthdayStr === '') {
                return false;
            }
            
            // Also reject single-letter middle names for candidate
            if (strlen($candidateMiddle) <= 1) {
                return false;
            }
            
            // All four fields must match EXACTLY (no abbreviations, no fuzzy matching)
            return $candidate->last_name_normalized === $normalizedData['last_name_normalized']
                && $candidate->first_name_normalized === $normalizedData['first_name_normalized']
                && $candidateMiddle === $uploadedMiddle
                && $candidateBirthdayStr === $uploadedBirthday;
        });
        
        return $match ? [
            'record' => $match,
            'rule' => $this->name(),
            'confidence' => $this->confidence(),
        ] : null;
    }
}
