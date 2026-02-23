<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

abstract class MatchRule
{
    abstract public function name(): string;
    
    abstract public function confidence(): float;
    
    abstract public function status(): string;
    
    abstract public function match(array $normalizedData, Collection $candidates): ?array;
    
    /**
     * Calculate similarity between two strings (0-100)
     */
    protected function similarity(string $str1, string $str2): float
    {
        similar_text(
            strtolower($str1),
            strtolower($str2),
            $percent
        );
        
        return $percent;
    }
}

