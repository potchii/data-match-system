<?php

namespace App\Services\MatchingRules;

use Illuminate\Support\Collection;

class RegsNoMatchRule extends MatchRule
{
    public function name(): string
    {
        return 'regs_no_match';
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
        // Skip if uploaded record doesn't have regs_no
        if (empty($normalizedData['regs_no'])) {
            return null;
        }
        
        $match = $candidates->first(function ($candidate) use ($normalizedData) {
            // Skip if candidate doesn't have regs_no
            if (empty($candidate->regs_no)) {
                return false;
            }
            
            // Match by registration number (case-insensitive)
            return strtolower(trim($candidate->regs_no)) === strtolower(trim($normalizedData['regs_no']));
        });
        
        return $match ? [
            'record' => $match,
            'rule' => $this->name(),
            'confidence' => $this->confidence(),
        ] : null;
    }
}
