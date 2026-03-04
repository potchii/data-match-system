# Discriminators in Your Matching System

## Overview

Discriminators are secondary validation fields used by the **FuzzyNameMatchRule** to adjust the confidence score after a fuzzy name match is found. They provide bonuses or penalties to fine-tune the matching confidence.

## Your Four Discriminators

### 1. Date of Birth (DOB)

**Validation Method**: `validateDobMatch()`

**Scoring**:
- **+10% bonus**: Exact DOB match
- **+5% bonus**: Partial DOB match (year and month match, day differs)
- **-10% penalty**: DOB mismatch (both have DOB but they don't match)
- **No adjustment**: One or both DOB values are missing

**Logic**:
- Exact match: `YYYY-MM-DD` format must be identical
- Partial match: Year and month match, but day differs (e.g., 1990-01-15 vs 1990-01-20)
- Mismatch: Both have DOB but they're different

### 2. Gender

**Validation Method**: `validateGenderMatch()`

**Scoring**:
- **+5% bonus**: Gender match (both Male or both Female)
- **-5% penalty**: Gender mismatch (one Male, one Female)
- **No adjustment**: One or both gender values are missing

**Logic**:
- Normalizes gender values (M/Male → Male, F/Female → Female)
- Rejects candidate if gender mismatch (hard rejection)
- Applies bonus/penalty if both have gender values

### 3. Address/Barangay

**Validation Method**: `validateAddressMatch()`

**Scoring**:
- **+5% bonus**: Address match (fuzzy match on address AND barangay)
- **-5% penalty**: Address mismatch (both have address but they don't match)
- **No adjustment**: One or both address values are missing

**Logic**:
- Uses fuzzy matching on address strings (not exact match)
- Checks both address AND barangay fields
- Considers it a match if both address and barangay fuzzy match

### 4. Template Fields

**Validation Method**: `validateTemplateFieldMatch()`

**Scoring**:
- **+5% bonus**: Template fields match
- **-5% penalty**: Template fields mismatch
- **No adjustment**: No template fields or template ID not provided

**Logic**:
- Only applies if a template ID is provided
- Compares custom template fields between records
- Uses fuzzy matching for template field values

## Scoring Formula

```
Final Confidence = Base Score (70%) + Discriminator Adjustment

Discriminator Adjustment = Total Bonus - Total Penalty

Total Bonus = DOB bonus + Gender bonus + Address bonus + Template bonus
Total Penalty = DOB penalty + Gender penalty + Address penalty + Template penalty
```

## Example: Batch 2, Row 8

**Base Score**: 70% (FuzzyNameMatchRule)

**Discriminator Adjustments**:
- DOB: +10% (exact match)
- Gender: +5% (both NULL, no adjustment)
- Address: +5% (fuzzy match)
- Template: +0% (no template fields)

**Total Adjustment**: +10% + 0% + 5% + 0% = +15%

**Final Confidence**: 70% + 15% = 85%

*Note: The UI showed 90%, which suggests there may be additional bonuses or the calculation differs slightly.*

## Bounds

The final confidence score is bounded:
- **Minimum**: 0%
- **Maximum**: 100%

Any calculated score outside these bounds is clamped to the range.

## When Discriminators Apply

Discriminators are **only used by FuzzyNameMatchRule**. Other rules don't use them:

| Rule | Uses Discriminators? |
|------|----------------------|
| ExactMatchRule | ❌ No (100% if exact match) |
| PartialMatchWithDobRule | ❌ No (90% if DOB matches) |
| FullNameMatchRule | ❌ No (80% if names match) |
| FuzzyNameMatchRule | ✅ Yes (70% + adjustments) |

## Configuration

Discriminator bonuses/penalties are hardcoded in the validation methods:

- **DOB**: ±10% (exact), +5% (partial)
- **Gender**: ±5%
- **Address**: ±5%
- **Template**: ±5%

These values are not configurable via `FuzzyMatchingConfig`.

## Key Points

1. **Discriminators refine fuzzy matches** - They adjust the base 70% score up or down
2. **They're optional** - Missing values don't penalize, they just don't contribute
3. **Gender is a hard rejection** - Mismatched gender rejects the candidate entirely
4. **Address uses fuzzy matching** - Not exact string comparison
5. **Template fields are optional** - Only apply if template ID is provided
6. **Maximum adjustment** - Can add up to +20% (all bonuses) or subtract up to -20% (all penalties)

## Possible Confidence Ranges

| Scenario | Confidence |
|----------|------------|
| All discriminators match | 70% + 20% = 90% |
| All discriminators mismatch | 70% - 20% = 50% |
| Mixed (2 match, 2 mismatch) | 70% + 10% - 10% = 70% |
| No discriminators (all missing) | 70% + 0% = 70% |
| DOB exact + Address match | 70% + 10% + 5% = 85% |

## Summary

Your discriminators are:
1. **DOB** - Most important (±10%)
2. **Gender** - Hard rejection if mismatch
3. **Address/Barangay** - Fuzzy matching (±5%)
4. **Template Fields** - Custom fields (±5%)

They work together to fine-tune the fuzzy name matching confidence score, providing a more nuanced assessment than just name similarity alone.
