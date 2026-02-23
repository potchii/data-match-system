<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

class PartialMatchWithDobRule extends MatchRule
{
    public function name(): string
    {
        return 'partial_match_with_dob';
    }
    
    public function confidence(): float
    {
        return 90.0;
    }
    
    public function status(): string
    {
        return 'MATCHED';
    }
    
    public function match(array $normalizedData, Collection $candidates): ?array
    {
        if (empty($normalizedData['birthday'])) {
            return null;
        }
        
        $match = $candidates->first(function ($candidate) use ($normalizedData) {
            // Convert birthday to string for comparison if it's a Carbon instance
            $candidateBirthday = ($candidate->birthday instanceof \Carbon\Carbon || $candidate->birthday instanceof \Carbon\CarbonImmutable)
                ? $candidate->birthday->format('Y-m-d') 
                : $candidate->birthday;
            
            return $candidate->last_name_normalized === $normalizedData['last_name_normalized']
                && $candidate->first_name_normalized === $normalizedData['first_name_normalized']
                && $candidateBirthday === $normalizedData['birthday'];
        });
        
        return $match ? ['record' => $match, 'rule' => $this->name()] : null;
    }
}

