<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

class FuzzyNameMatchRule extends MatchRule
{
    protected float $threshold = 85.0;
    
    public function name(): string
    {
        return 'fuzzy_name_match';
    }
    
    public function confidence(): float
    {
        return 70.0; // Adjusted to 60-75% range
    }
    
    public function status(): string
    {
        return 'POSSIBLE DUPLICATE';
    }
    
    public function match(array $normalizedData, Collection $candidates): ?array
    {
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($candidates as $candidate) {
            $lastNameSimilarity = $this->similarity(
                $normalizedData['last_name_normalized'],
                $candidate->last_name_normalized
            );
            
            $firstNameSimilarity = $this->similarity(
                $normalizedData['first_name_normalized'],
                $candidate->first_name_normalized
            );
            
            $avgScore = ($lastNameSimilarity + $firstNameSimilarity) / 2;
            
            if ($avgScore >= $this->threshold && $avgScore > $bestScore) {
                $bestScore = $avgScore;
                $bestMatch = $candidate;
            }
        }
        
        return $bestMatch ? ['record' => $bestMatch, 'rule' => $this->name()] : null;
    }
}

