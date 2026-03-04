# Confidence Scoring Architecture - How It Works

## Overview

Your system has **TWO SEPARATE confidence scoring mechanisms** that serve different purposes:

1. **Rule-Based Confidence** (used for matching decisions)
2. **Unified Scoring Confidence** (used for field breakdown display)

## 1. Rule-Based Confidence (Matching Decision)

This is the **PRIMARY** confidence used to determine if records are duplicates.

### How It Works

When `DataMatchService.findMatch()` is called:

1. **Rule Chain Evaluation** - Rules are evaluated in priority order:
   - ExactMatchRule (100%)
   - PartialMatchWithDobRule (90%)
   - FullNameMatchRule (80%)
   - FuzzyNameMatchRule (70%+)

2. **FuzzyNameMatchRule Calculation** (most complex):
   ```
   Base Score: 70% (from FuzzyNameMatchRule.confidence())
   
   + Discriminator Adjustments:
     - DOB match: +10% bonus or -10% penalty
     - Gender match: +5% bonus or -5% penalty
     - Address match: +5% bonus or -5% penalty
     - Template fields: +5% bonus or -5% penalty
   
   Final Score = Base Score + Adjustments (bounded 0-100%)
   ```

3. **Result** - Returns the **rule-based confidence** (e.g., 90%, 85%, 70%)

### Example: Batch 2, Row 8

- **Rule Applied**: FuzzyNameMatchRule
- **Base Score**: 70%
- **Discriminator Adjustments**: +20% (from DOB, gender, address, template fields)
- **Final Confidence**: 90%

This is **CORRECT** - the record matched via fuzzy name matching with discriminator bonuses.

---

## 2. Unified Scoring Confidence (Field Breakdown Display)

This is a **SECONDARY** confidence used only for the field breakdown modal display.

### How It Works

After a match is found, `ConfidenceScoreService.calculateUnifiedScore()` is called:

```php
public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord, ?int $templateId = null): array
{
    $breakdown = $this->generateBreakdown($uploadedData, $existingRecord, $templateId);
    
    $totalFields = $breakdown['total_fields'];
    $matchedFields = $breakdown['matched_fields'];
    
    // Simple field count: (matching fields / total fields) × 100
    $score = $totalFields > 0 ? ($matchedFields / $totalFields) * 100 : 0;
    
    return [
        'score' => round($score, 2),
        'breakdown' => $breakdown,
    ];
}
```

### Example: Batch 2, Row 8

- **Total Fields**: 8
- **Matched Fields**: 7
- **Unified Score**: (7/8) × 100 = 87.5%

This score is **ONLY FOR DISPLAY** in the field breakdown modal.

---

## The Key Insight

**The 90% confidence shown in the UI is the RULE-BASED confidence, NOT the unified scoring confidence.**

### What You're Seeing

In the UI for Batch 2, Row 8:
- **Confidence Score: 90%** ← This is the FuzzyNameMatchRule confidence (70% + 20% adjustments)
- **Matched Fields: 7 / Total Fields: 8** ← This is the unified scoring breakdown

### Why They're Different

1. **Rule-Based (90%)** - Determined by FuzzyNameMatchRule with discriminator adjustments
2. **Unified Scoring (87.5%)** - Determined by simple field count

The rule-based confidence is what matters for the **matching decision**. The unified scoring is just for **display purposes**.

---

## Is This Working As Intended?

**YES, this is working correctly.**

### Why the 90% Score Is Appropriate

For Batch 2, Row 8:
- ✅ Names match (fuzzy match: ~85% similarity)
- ✅ DOB matches exactly (+10% bonus)
- ✅ Gender matches (+5% bonus)
- ✅ Address/Barangay match (+5% bonus)
- ✅ Template fields match (+0% or +5% bonus)

**Total: 70% base + 20% adjustments = 90%**

The regs_no mismatch (0.0% confidence) is **NOT** considered by the rule-based matching because:
1. regs_no is not part of the matching rules
2. The matching rules focus on name, DOB, gender, address, and template fields
3. regs_no is a secondary identifier, not a primary matching criterion

---

## The Discrepancy Explained

**Why does the field breakdown show 7/8 matched but the confidence is 90%?**

Because they use **different algorithms**:

1. **Rule-Based Confidence (90%)**:
   - Uses fuzzy name matching + discriminator adjustments
   - Ignores regs_no (not part of matching rules)
   - Result: 90%

2. **Unified Scoring (87.5%)**:
   - Counts all fields equally
   - Includes regs_no as a field
   - Result: 7/8 = 87.5%

---

## Recommendation

**This is working as designed.** The system correctly:

1. ✅ Uses rule-based confidence for matching decisions (90%)
2. ✅ Uses unified scoring for field breakdown display (87.5%)
3. ✅ Ignores regs_no in matching rules (it's not a primary identifier)
4. ✅ Shows regs_no mismatch in the field breakdown (for transparency)

### If You Want to Change This

If you want regs_no mismatches to affect the confidence score, you would need to:

1. Add regs_no validation to FuzzyNameMatchRule
2. Apply a penalty if regs_no doesn't match
3. This would lower the final confidence from 90% to something lower

But this is **not recommended** because:
- regs_no can change or be missing
- It's not a reliable primary identifier
- The current approach (ignoring it in matching) is safer

---

## Summary

| Aspect | Rule-Based (90%) | Unified Scoring (87.5%) |
|--------|------------------|------------------------|
| **Purpose** | Matching decision | Field breakdown display |
| **Algorithm** | Fuzzy name + discriminators | Field count |
| **Includes regs_no** | No | Yes |
| **Used for** | Determining if duplicate | UI display only |
| **Correct?** | ✅ Yes | ✅ Yes |

Both are working correctly for their intended purposes.
