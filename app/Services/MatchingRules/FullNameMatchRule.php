<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

class FullNameMatchRule extends MatchRule
{
    public function name(): string
    {
        return 'full_name_match';
    }
    
    public function confidence(): float
    {
        return 80.0;
    }
    
    public function status(): string
    {
        return 'POSSIBLE DUPLICATE';
    }
    
    public function match(array $normalizedData, Collection $candidates): ?array
    {
        $match = $candidates->first(function ($candidate) use ($normalizedData) {
            return $candidate->last_name_normalized === $normalizedData['last_name_normalized']
                && $candidate->first_name_normalized === $normalizedData['first_name_normalized']
                && $candidate->middle_name_normalized === $normalizedData['middle_name_normalized'];
        });
        
        return $match ? ['record' => $match, 'rule' => $this->name()] : null;
    }
}

