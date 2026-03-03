# Data Matching Algorithm Documentation

## Overview

This document describes the rule-based matching algorithm used to identify duplicate records in the Data Match System. The algorithm uses a priority-ordered chain of matching rules to determine if an uploaded record matches an existing database record.

## Algorithm Flow

```
Uploaded Record → Normalize Data → Apply Rules (Priority Order) → Match Result
                                          ↓
                                    Rule 1: Exact Match (100%)
                                          ↓ (no match)
                                    Rule 2: Partial Match with DOB (90%)
                                          ↓ (no match)
                                    Rule 3: Full Name Match (80%)
                                          ↓ (no match)
                                    Rule 4: Fuzzy Name Match (70%+)
                                          ↓ (no match)
                                    NEW RECORD (0%)
```

## Matching Rules

### Rule 1: Exact Match Rule
- **Confidence**: 100%
- **Status**: MATCHED
- **Criteria**: 
  - First name matches (normalized, case-insensitive)
  - Last name matches (normalized, case-insensitive)
  - Middle name matches (normalized, case-insensitive) - STRICT equality, no abbreviations
  - Birthday matches (YYYY-MM-DD format)
- **Requirements**:
  - BOTH records MUST have middle names (non-null, non-empty, non-whitespace)
  - BOTH records MUST have birthdays (non-null, non-empty, non-whitespace)
  - Middle names must be EXACTLY equal after normalization (no initial abbreviations)
  - Middle names must be at least 2 characters long (single-letter middle names are rejected)
  - If either record is missing middle name OR birthday, this rule does NOT apply
- **Example**:
  ```
  Uploaded: Juan, Dela Cruz, Pedro, 1990-01-15
  Database: Juan, Dela Cruz, Pedro, 1990-01-15
  Result: MATCHED (100%)
  ```
- **Non-Examples** (will NOT match via this rule):
  ```
  # Missing birthday on both sides
  Uploaded: Juan, Dela Cruz, Pedro, null
  Database: Juan, Dela Cruz, Pedro, null
  Result: Falls through to FullNameMatchRule → POSSIBLE DUPLICATE (80%)
  
  # Initial abbreviation (not exact match)
  Uploaded: Juan, Dela Cruz, S, 1990-01-15
  Database: Juan, Dela Cruz, Santiago, 1990-01-15
  Result: Falls through to FuzzyNameMatchRule → POSSIBLE DUPLICATE (85%+)
  
  # Single-letter middle names (likely initials)
  Uploaded: Anson, Agahon, D, 1990-01-15
  Database: Anson, Agahon, D., 1990-01-15
  Result: Falls through to PartialMatchWithDobRule → POSSIBLE DUPLICATE (90%)
  
  # Whitespace-only middle name
  Uploaded: Juan, Dela Cruz, "  ", 1990-01-15
  Database: Juan, Dela Cruz, Pedro, 1990-01-15
  Result: Falls through to PartialMatchWithDobRule → POSSIBLE DUPLICATE (90%)
  ```

### Rule 2: Partial Match with DOB Rule
- **Confidence**: 90%
- **Status**: POSSIBLE DUPLICATE
- **Criteria**:
  - First name matches (normalized, case-insensitive)
  - Last name matches (normalized, case-insensitive)
  - Birthday matches (YYYY-MM-DD format)
  - Middle name is NOT checked
- **Use Case**: Records where middle name is missing or different
- **Example**:
  ```
  Uploaded: Maria, Santos, null, 1985-03-20
  Database: Maria, Santos, Cruz, 1985-03-20
  Result: POSSIBLE DUPLICATE (90%)
  ```

### Rule 3: Full Name Match Rule
- **Confidence**: 80%
- **Status**: POSSIBLE DUPLICATE
- **Criteria**:
  - First name matches (normalized, case-insensitive)
  - Last name matches (normalized, case-insensitive)
  - Middle name matches (normalized, case-insensitive)
  - Birthday is NOT required
- **Use Case**: Records with matching names but no birthday data
- **Example**:
  ```
  Uploaded: Jose, Reyes, Antonio, null
  Database: Jose, Reyes, Antonio, 1992-07-10
  Result: POSSIBLE DUPLICATE (80%)
  ```

### Rule 4: Fuzzy Name Match Rule
- **Confidence**: 70% - 100% (variable)
- **Status**: POSSIBLE DUPLICATE
- **Criteria**:
  - Fuzzy name similarity ≥ 85% (average of first + last name)
  - Middle names don't conflict (if both present)
  - Gender doesn't conflict (if both present)
- **Discriminators** (adjust confidence):
  - DOB match: +10 points
  - Gender match: +5 points
  - Address/Barangay match: +5 points
  - Template field matches: +1 point each (max +10)
- **Example**:
  ```
  Uploaded: Cristina, Afable, null, 1970-01-01
  Database: Cristita, Afable, null, 1970-01-01
  Name Similarity: 95%
  DOB Match: +10
  Result: POSSIBLE DUPLICATE (85%)
  ```

### Rule 5: No Match
- **Confidence**: 0%
- **Status**: NEW RECORD
- **Criteria**: None of the above rules matched
- **Action**: Record is inserted as new entry in database

## Data Normalization

Before matching, all data is normalized:

### Name Normalization
- Convert to lowercase
- Trim whitespace
- Remove trailing periods from initials (e.g., "D." → "d")
- Handle null-like strings: "Null", "NULL", "N/A", "NA", "None", "-" → empty string

### Birthday Normalization
- Parse multiple date formats (Y-m-d, m/d/Y, d-m-Y, etc.)
- Convert to YYYY-MM-DD format
- Reject future dates
- Reject dates before 1900

### Gender Normalization
- Map variations to standard: M, F, Other
- "Male", "MALE", "m" → "M"
- "Female", "FEMALE", "f" → "F"

## Confidence Score Mapping

The confidence score determines the final match status:

| Confidence | Status | Description |
|------------|--------|-------------|
| 100% | MATCHED | Exact match with all required fields |
| 90% | POSSIBLE DUPLICATE | High confidence but missing some data |
| 80% | POSSIBLE DUPLICATE | Name match without DOB |
| 70-99% | POSSIBLE DUPLICATE | Fuzzy match with discriminators |
| 1-69% | Not used | (Reserved for future rules) |
| 0% | NEW RECORD | No match found |

## Special Cases

### Middle Name Handling
- **Single-letter initials**: "D" matches "David", "D." matches "David"
- **Missing vs Present**: If one record has middle name and other doesn't, use PartialMatchWithDobRule (90%)
- **Both missing**: Can match via PartialMatchWithDobRule if first + last + DOB match
- **Conflict**: If both have different middle names (not initials), fuzzy rule may reject

### Null String Sanitization
These strings are treated as empty/null:
- "Null", "NULL"
- "N/A", "NA", "n/a"
- "None", "NONE"
- "-", "--"
- Empty string or whitespace

### Birthday Comparison
- Must be exact match (YYYY-MM-DD)
- Partial matches (same month/year, different day) are NOT considered matches
- Missing birthday in one or both records: rule skips to next

## Testing Scenarios

### Scenario 1: Exact Match
```
Seed Data:    Juan, Dela Cruz, Pedro, 1990-01-15
Test Data:    Juan, Dela Cruz, Pedro, 1990-01-15
Expected:     MATCHED (100%)
```

### Scenario 2: Missing Middle Name
```
Seed Data:    Maria, Santos, Cruz, 1985-03-20
Test Data:    Maria, Santos, null, 1985-03-20
Expected:     POSSIBLE DUPLICATE (90%)
```

### Scenario 3: Fuzzy Name Match
```
Seed Data:    Cristita, Afable, null, 1970-01-01
Test Data:    Cristina, Afable, null, 1970-01-01
Expected:     POSSIBLE DUPLICATE (85%+)
```

### Scenario 4: No Match
```
Seed Data:    Juan, Dela Cruz, Pedro, 1990-01-15
Test Data:    Maria, Santos, Cruz, 1985-03-20
Expected:     NEW RECORD (0%)
```

## Implementation Details

### File Locations
- **Main Service**: `app/Services/DataMatchService.php`
- **Exact Match**: `app/Services/MatchingRules/ExactMatchRule.php`
- **Partial Match**: `app/Services/MatchingRules/PartialMatchWithDobRule.php`
- **Full Name**: `app/Services/MatchingRules/FullNameMatchRule.php`
- **Fuzzy Match**: `app/Services/MatchingRules/FuzzyNameMatchRule.php`
- **Config**: `app/Config/FuzzyMatchingConfig.php`

### Rule Priority
Rules are applied in strict order. Once a rule matches, subsequent rules are NOT evaluated:
1. ExactMatchRule (highest priority)
2. PartialMatchWithDobRule
3. FullNameMatchRule
4. FuzzyNameMatchRule (lowest priority)

### Performance Optimization
- Candidate records are pre-filtered by last name and first name
- Single database query loads all potential matches
- Fuzzy matching only runs if exact/partial rules don't match
- Results are cached to prevent duplicate processing

## Simulation Guidelines

### Creating Test Data

For accurate simulations, structure your test data as follows:

**Seed Data (Excel File 1)**:
- 20 records with complete data (first, last, middle, DOB)
- 15 records with complete data (first, last, middle, DOB)
- 25 unique records (won't match anything)

**Test Data (Excel File 2)**:
- 20 records matching seed data exactly (same first, last, middle, DOB)
- 15 records matching seed data partially (same first, last, DOB, but different/missing middle)
- 25 completely different records

**Expected Results**:
- 20 MATCHED (100%)
- 15 POSSIBLE DUPLICATE (90%)
- 25 NEW RECORD (0%)

### Common Issues

1. **Too many exact matches**: Test data has middle names when you expected them to be missing
2. **Too few fuzzy matches**: Records that should partially match have conflicting data
3. **Unexpected new records**: Names don't match due to typos or normalization issues

### Debugging Tips

1. Check field breakdown modal to see which fields matched/mismatched
2. Verify middle name presence in both seed and test data
3. Ensure birthday format is consistent (YYYY-MM-DD)
4. Check for null-like strings ("Null", "N/A") that get sanitized
5. Review normalized values in field breakdown

## Configuration

### Fuzzy Matching Thresholds
Located in `app/Config/FuzzyMatchingConfig.php`:

```php
'name_similarity_threshold' => 85.0,  // Minimum % for fuzzy name match
'address_similarity_threshold' => 80.0,  // Minimum % for address match

'discriminators' => [
    'dob' => [
        'enabled' => true,
        'bonus_exact_match' => 10,
        'penalty_missing_one' => 5,
    ],
    'gender' => [
        'enabled' => true,
        'bonus_match' => 5,
        'reject_on_mismatch' => true,
    ],
    // ... more discriminators
]
```

## Status Badge Colors

In the UI, match statuses are color-coded:
- **MATCHED** (100%): Green badge
- **POSSIBLE DUPLICATE** (70-99%): Yellow badge
- **NEW RECORD** (0%): Red badge
