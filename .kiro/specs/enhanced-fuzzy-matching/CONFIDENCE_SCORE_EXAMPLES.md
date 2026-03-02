# Confidence Score Calculation Examples

## Overview

This document provides detailed examples of how confidence scores are calculated for different matching scenarios.

## Formula

```
Final Confidence = Base Score (70%) + Discriminator Bonuses - Discriminator Penalties
```

## Example 1: Perfect Match (All Discriminators Match)

**Scenario:**
- Fuzzy name match: 95% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Both have "123 Main Street" (fuzzy match at 90%)
- Barangay: Both have "Barangay 1" (exact match)
- Template fields: None

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
+ Address fuzzy match:         +5%
+ Barangay exact match:        +5%
= Subtotal:                    95%

Penalties:                      0%

Final Confidence:              95%
Status:                        MATCHED
```

**Field Breakdown:**
```php
[
    'name_similarity' => 95,
    'dob_match' => ['matched' => true, 'bonus' => 10, 'penalty' => 0],
    'gender_match' => ['matched' => true, 'bonus' => 5, 'penalty' => 0],
    'address_match' => ['matched' => true, 'bonus' => 10, 'penalty' => 0],
    'template_fields' => ['matched_count' => 0, 'bonus' => 0, 'penalty' => 0],
    'discriminator_adjustment' => 30,
    'base_score' => 70,
    'final_score' => 95,
]
```

## Example 2: Good Match (Some Discriminators Missing)

**Scenario:**
- Fuzzy name match: 88% similarity
- DOB: Uploaded has 1990-05-15, candidate has no DOB
- Gender: Both have M (match)
- Address: Both have "123 Main Street" (exact match)
- Barangay: Uploaded has "Barangay 1", candidate has no barangay
- Template fields: None

**Calculation:**

```
Base Score:                    70%
+ Gender match:                +5%
+ Address exact match:         +5%
- DOB missing from one:        -5%
- Barangay missing from one:   -5%
= Subtotal:                    70%

Final Confidence:              70%
Status:                        POSSIBLE_DUPLICATE
```

**Field Breakdown:**
```php
[
    'name_similarity' => 88,
    'dob_match' => ['matched' => false, 'bonus' => 0, 'penalty' => 5],
    'gender_match' => ['matched' => true, 'bonus' => 5, 'penalty' => 0],
    'address_match' => ['matched' => true, 'bonus' => 5, 'penalty' => 0],
    'template_fields' => ['matched_count' => 0, 'bonus' => 0, 'penalty' => 0],
    'discriminator_adjustment' => 0,
    'base_score' => 70,
    'final_score' => 70,
]
```

## Example 3: Partial Match (Some Discriminators Don't Match)

**Scenario:**
- Fuzzy name match: 87% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Uploaded has M, candidate has F (mismatch - REJECTED)
- Address: Uploaded has "123 Main St", candidate has "456 Oak Ave" (no match)
- Barangay: Both have "Barangay 1" (exact match)
- Template fields: None

**Calculation:**

```
Gender mismatch detected - MATCH REJECTED
Result: null (no match returned)
```

**Note:** Gender mismatch rejection is enabled by default. If disabled:

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Barangay exact match:        +5%
- Address no match:            -5%
= Subtotal:                    80%

Final Confidence:              80%
Status:                        MATCHED
```

## Example 4: Weak Match (Minimal Discriminators)

**Scenario:**
- Fuzzy name match: 85% similarity
- DOB: Uploaded has 1990-05-15, candidate has 1990-05-16 (partial match - same month/year)
- Gender: Uploaded has M, candidate has no gender
- Address: Both missing
- Barangay: Both missing
- Template fields: None

**Calculation:**

```
Base Score:                    70%
- DOB partial match:           -3%
- Gender missing from one:     -3%
= Subtotal:                    64%

Final Confidence:              64%
Status:                        NEW_RECORD
```

**Field Breakdown:**
```php
[
    'name_similarity' => 85,
    'dob_match' => ['matched' => false, 'bonus' => 0, 'penalty' => 3],
    'gender_match' => ['matched' => false, 'bonus' => 0, 'penalty' => 3],
    'address_match' => ['matched' => false, 'bonus' => 0, 'penalty' => 0],
    'template_fields' => ['matched_count' => 0, 'bonus' => 0, 'penalty' => 0],
    'discriminator_adjustment' => -6,
    'base_score' => 70,
    'final_score' => 64,
]
```

## Example 5: Match with Template Fields

**Scenario:**
- Fuzzy name match: 90% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Both have "123 Main Street" (exact match)
- Barangay: Both have "Barangay 1" (exact match)
- Template fields:
  - field_1: "value1" vs "value1" (exact match)
  - field_2: "value2" vs "value2" (exact match)
  - field_3: "value3" vs "value4" (no match)

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
+ Address exact match:         +5%
+ Barangay exact match:        +5%
+ Template field 1 exact:      +2%
+ Template field 2 exact:      +2%
- Template field 3 no match:   -1%
= Subtotal:                    98%

Final Confidence:              98%
Status:                        MATCHED
```

**Field Breakdown:**
```php
[
    'name_similarity' => 90,
    'dob_match' => ['matched' => true, 'bonus' => 10, 'penalty' => 0],
    'gender_match' => ['matched' => true, 'bonus' => 5, 'penalty' => 0],
    'address_match' => ['matched' => true, 'bonus' => 10, 'penalty' => 0],
    'template_fields' => ['matched_count' => 2, 'bonus' => 4, 'penalty' => 1],
    'discriminator_adjustment' => 28,
    'base_score' => 70,
    'final_score' => 98,
]
```

## Example 6: Fuzzy Template Field Match

**Scenario:**
- Fuzzy name match: 88% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Both missing
- Barangay: Both missing
- Template fields:
  - field_1: "John Smith" vs "Jon Smith" (fuzzy match at 85%)
  - field_2: "123 Main St" vs "456 Oak Ave" (no match at 20%)

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
+ Template field 1 fuzzy:      +1%
- Template field 2 no match:   -1%
= Subtotal:                    85%

Final Confidence:              85%
Status:                        MATCHED
```

**Field Breakdown:**
```php
[
    'name_similarity' => 88,
    'dob_match' => ['matched' => true, 'bonus' => 10, 'penalty' => 0],
    'gender_match' => ['matched' => true, 'bonus' => 5, 'penalty' => 0],
    'address_match' => ['matched' => false, 'bonus' => 0, 'penalty' => 0],
    'template_fields' => ['matched_count' => 1, 'bonus' => 1, 'penalty' => 1],
    'discriminator_adjustment' => 15,
    'base_score' => 70,
    'final_score' => 85,
]
```

## Example 7: No Match (Below Threshold)

**Scenario:**
- Fuzzy name match: 82% similarity (below 85% threshold)
- DOB: Different years
- Gender: Different
- Address: Different
- Barangay: Different
- Template fields: None

**Calculation:**

```
Fuzzy name match (82%) is below threshold (85%)
Result: null (no match returned)
Status: NEW_RECORD
```

## Example 8: Bonus Capping (Template Fields)

**Scenario:**
- Fuzzy name match: 92% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Both have "123 Main Street" (exact match)
- Barangay: Both have "Barangay 1" (exact match)
- Template fields:
  - field_1: exact match (+2%)
  - field_2: exact match (+2%)
  - field_3: exact match (+2%)
  - field_4: exact match (+2%)
  - field_5: exact match (+2%)
  - field_6: exact match (+2%)

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
+ Address exact match:         +5%
+ Barangay exact match:        +5%
+ Template fields (capped):   +10% (max, not 12%)
= Subtotal:                   105%

Apply max confidence cap:     100%

Final Confidence:             100%
Status:                       MATCHED
```

**Note:** Template field bonus is capped at 10% maximum.

## Example 9: Penalty Capping (Template Fields)

**Scenario:**
- Fuzzy name match: 86% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Both missing
- Barangay: Both missing
- Template fields:
  - field_1: no match (-1%)
  - field_2: no match (-1%)
  - field_3: no match (-1%)
  - field_4: no match (-1%)
  - field_5: no match (-1%)
  - field_6: no match (-1%)

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
- Template fields (capped):    -5% (max, not -6%)
= Subtotal:                    80%

Final Confidence:              80%
Status:                        MATCHED
```

**Note:** Template field penalty is capped at 5% maximum.

## Example 10: Confidence Score Rounding

**Scenario:**
- Fuzzy name match: 87% similarity
- DOB: Both have 1990-05-15 (exact match)
- Gender: Both have M (match)
- Address: Fuzzy match at 82% (+5%)
- Barangay: Both have "Barangay 1" (exact match)
- Template fields: None

**Calculation:**

```
Base Score:                    70%
+ DOB exact match:            +10%
+ Gender match:                +5%
+ Address fuzzy match:         +5%
+ Barangay exact match:        +5%
= Subtotal:                    95%

Final Confidence:              95%
Status:                        MATCHED
```

## Confidence Score Ranges

| Score Range | Status | Interpretation | Action |
|---|---|---|---|
| 100% | MATCHED | Definite match | Accept |
| 90-99% | MATCHED | High confidence | Accept |
| 80-89% | MATCHED | Good confidence | Accept |
| 70-79% | POSSIBLE_DUPLICATE | Requires review | Review manually |
| <70% | NEW_RECORD | No match | Create new record |

## Tips for Interpreting Scores

1. **High scores (90%+):** All or most discriminators match
2. **Medium scores (80-89%):** Most discriminators match, some missing
3. **Low scores (70-79%):** Name matches but discriminators don't align well
4. **Very low scores (<70%):** Insufficient evidence of match

## Adjusting Scores

To increase matching:
- Lower name similarity threshold
- Reduce penalty values
- Increase bonus values

To decrease false positives:
- Raise name similarity threshold
- Increase penalty values
- Reduce bonus values
- Enable gender mismatch rejection

See CONFIGURATION_GUIDE.md for detailed tuning instructions.
