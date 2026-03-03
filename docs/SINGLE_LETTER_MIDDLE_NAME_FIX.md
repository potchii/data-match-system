# Single-Letter Middle Name Fix

## Problem

Records with single-letter middle names (e.g., "D", "D.", "C", "C.") were matching as EXACT (100% confidence) when they should be POSSIBLE DUPLICATE (90% confidence).

### Examples from User Report
- Anson Agahon with middle name "D" vs "D." → Incorrectly matched as EXACT (100%)
- Rosita Aguilar with middle name "C" vs "C." → Incorrectly matched as EXACT (100%)

### Root Cause

After normalization:
- "D." becomes "d" (period removed, lowercased)
- "D" becomes "d" (lowercased)
- Both match exactly in ExactMatchRule

Single-letter middle names are typically abbreviations or initials and don't provide enough certainty for an exact match.

## Solution

Modified `ExactMatchRule` to reject records where either the uploaded or candidate middle name is a single letter (length ≤ 1).

### Changes Made

**File:** `app/Services/MatchingRules/ExactMatchRule.php`

Added length validation for middle names:

```php
// Reject single-letter middle names (likely abbreviations/initials)
// These should be handled by fuzzy matching rules instead
if (strlen($uploadedMiddle) <= 1) {
    return null;
}

// Also reject single-letter middle names for candidate
if (strlen($candidateMiddle) <= 1) {
    return false;
}
```

### Behavior After Fix

- Single-letter middle names (1 character) → Rejected by ExactMatchRule
- Two-letter middle names (2+ characters) → Accepted by ExactMatchRule
- Records with single-letter middle names will fall through to lower-confidence rules:
  - `PartialMatchWithDobRule` (100%) - if first + last + DOB match
  - `FullNameMatchRule` (80%) - if first + last + middle match (no DOB required)
  - `FuzzyNameMatchRule` (70%) - if names are similar

## Testing

Created comprehensive test suite in `tests/Unit/ExactMatchRuleSingleLetterTest.php`:

1. ✅ Rejects single-letter middle names in uploaded data
2. ✅ Rejects single-letter middle names in candidate data
3. ✅ Rejects when both have single-letter middle names
4. ✅ Accepts two-letter middle names
5. ✅ Accepts full middle names

All tests passing.

## Related Changes

Also removed `RegsNoMatchRule` as per user request to exclude `regs_no` from matching logic.

**File Deleted:** `app/Services/MatchingRules/RegsNoMatchRule.php`

Note: RegsNoMatchRule was not in the active rule chain in `DataMatchService`, so no changes were needed there.

## Expected Impact

Records like "Anson Agahon (D)" vs "Anson Agahon (D.)" will now:
- NOT match as EXACT (100%)
- Fall through to PartialMatchWithDobRule or FullNameMatchRule
- Result in POSSIBLE DUPLICATE (90%) or lower confidence

This should help achieve the expected test results of 20 exact matches, 15 fuzzy matches, and 25 new records.

## Migration Notes

No database migration required. This is a business logic change only.

Existing match results are not affected. Only new uploads will use the updated matching logic.
