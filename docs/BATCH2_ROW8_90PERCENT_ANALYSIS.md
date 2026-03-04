# Batch 2, Row 8 - 90% Confidence Score Analysis

## The Issue

Batch 2, Row 8 shows a **90% confidence score** despite having:
- **7 matched fields** (87.5%)
- **1 mismatched field** (regs_no with 0.0% confidence)
- **Total: 8 fields**

This seems high for a record with a clear mismatch in regs_no.

## Root Cause: Flawed Confidence Calculation

The problem is in `ConfidenceScoreService.calculateUnifiedScore()`:

```php
public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord, ?int $templateId = null): array
{
    $breakdown = $this->generateBreakdown($uploadedData, $existingRecord, $templateId);
    
    $totalFields = $breakdown['total_fields'];
    $matchedFields = $breakdown['matched_fields'];
    
    // Calculate percentage: (matching fields / total fields) × 100
    $score = $totalFields > 0 ? ($matchedFields / $totalFields) * 100 : 0;
    
    return [
        'score' => round($score, 2),
        'breakdown' => $breakdown,
    ];
}
```

### The Calculation

For Batch 2, Row 8:
- Matched Fields: 7
- Total Fields: 8
- Score: (7 / 8) × 100 = **87.5%**

But the UI shows **90%**. This discrepancy suggests:

1. **The field count might be different** - Perhaps the regs_no field is not being counted in the total
2. **The calculation is rounding up** - 87.5% rounds to 88%, not 90%
3. **There's a different scoring algorithm** being used somewhere

## What Should Happen

The current algorithm only counts **matching vs total fields**. It doesn't consider:

1. **Field importance** - Some fields (like name) should matter more than others (like regs_no)
2. **Confidence scores** - Individual field confidences are calculated but not used in the final score
3. **Mismatch severity** - A complete mismatch (0.0%) should impact the score more

## The Real Problem

Looking at the field breakdown:
- **regs_no**: `U8lxjpuiys6j8gax` vs `Ehe2atn2twdl9zs1` = **0.0% confidence** (complete mismatch)
- **middle_name**: `I.` vs `I.` = **100.0% confidence** (exact match)

The current algorithm treats these equally:
- regs_no mismatch = -1 point
- middle_name match = +1 point

But regs_no is a registration number - a complete mismatch here is a **red flag** that should significantly lower the confidence score.

## Why This Is a Bug

1. **Misleading confidence** - 90% suggests a very likely match, but there's a clear mismatch in regs_no
2. **Ignores field importance** - All fields weighted equally regardless of significance
3. **Doesn't use calculated confidences** - Individual field confidences (0.0%, 100.0%) are calculated but ignored
4. **False positives** - Records with critical field mismatches get high scores

## Recommended Fix

The confidence score should be calculated as a **weighted average** of individual field confidences:

```php
public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord, ?int $templateId = null): array
{
    $breakdown = $this->generateBreakdown($uploadedData, $existingRecord, $templateId);
    
    $coreFields = $breakdown['core_fields'] ?? [];
    $templateFields = $breakdown['template_fields'] ?? [];
    
    $totalConfidence = 0;
    $fieldCount = 0;
    
    // Weight core fields more heavily (e.g., 1.0x)
    foreach ($coreFields as $field => $data) {
        $totalConfidence += $data['confidence'] ?? 0;
        $fieldCount++;
    }
    
    // Weight template fields less (e.g., 0.5x)
    foreach ($templateFields as $field => $data) {
        $totalConfidence += ($data['confidence'] ?? 0) * 0.5;
        $fieldCount += 0.5;
    }
    
    $score = $fieldCount > 0 ? ($totalConfidence / $fieldCount) : 0;
    
    return [
        'score' => round($score, 2),
        'breakdown' => $breakdown,
    ];
}
```

## Current Behavior vs Expected

**Current (Batch 2, Row 8):**
- 7 matched / 8 total = 87.5% → displayed as 90%
- Ignores that regs_no has 0.0% confidence

**Expected:**
- Average of all field confidences: (100 + 100 + 0 + 100 + 100 + 100 + 100 + 100) / 8 = **87.5%**
- Or with weighting: Could be lower if regs_no is weighted more heavily

## Conclusion

The 90% score is **misleading** because:
1. It only counts matching vs total fields
2. It ignores the severity of mismatches (0.0% confidence on regs_no)
3. It doesn't use the individual field confidence scores that are already calculated

This is a **scoring algorithm bug** that should be fixed to use weighted field confidences instead of simple field counts.
