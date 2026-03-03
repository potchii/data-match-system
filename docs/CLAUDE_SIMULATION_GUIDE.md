# Matching Algorithm Simulation Guide for Claude Code

## Quick Reference

This guide provides everything needed to simulate the matching algorithm behavior without running the actual Laravel application.

## Core Matching Rules (Priority Order)

### Rule 1: Exact Match (100% - MATCHED)
```
Required Fields (ALL must match):
- first_name (normalized, case-insensitive)
- last_name (normalized, case-insensitive)  
- middle_name (normalized, case-insensitive) - BOTH records MUST have this
- birthday (YYYY-MM-DD format) - BOTH records MUST have this

STRICT EQUALITY ONLY:
- Middle names must be EXACTLY equal (no abbreviations)
- "S" does NOT match "Santiago" in this rule
- Both DOB fields must be non-null

Example:
Seed:  { first: "Juan", last: "Dela Cruz", middle: "Pedro", dob: "1990-01-15" }
Test:  { first: "Juan", last: "Dela Cruz", middle: "Pedro", dob: "1990-01-15" }
Result: MATCHED (100%)

Non-Examples (will NOT match via this rule):
Seed:  { first: "Juan", last: "Cruz", middle: "Pedro", dob: null }
Test:  { first: "Juan", last: "Cruz", middle: "Pedro", dob: null }
Result: Falls to FullNameMatchRule → POSSIBLE DUPLICATE (80%)

Seed:  { first: "Juan", last: "Cruz", middle: "Santiago", dob: "1990-01-15" }
Test:  { first: "Juan", last: "Cruz", middle: "S", dob: "1990-01-15" }
Result: Falls to FuzzyNameMatchRule → POSSIBLE DUPLICATE (85%+)
```

### Rule 2: Partial Match with DOB (90% - POSSIBLE DUPLICATE)
```
Required Fields:
- first_name (normalized, case-insensitive)
- last_name (normalized, case-insensitive)
- birthday (YYYY-MM-DD format)
- middle_name is IGNORED

Example:
Seed:  { first: "Maria", last: "Santos", middle: "Cruz", dob: "1985-03-20" }
Test:  { first: "Maria", last: "Santos", middle: null, dob: "1985-03-20" }
Result: POSSIBLE DUPLICATE (90%)
```

### Rule 3: Full Name Match (80% - POSSIBLE DUPLICATE)
```
Required Fields:
- first_name (normalized, case-insensitive)
- last_name (normalized, case-insensitive)
- middle_name (normalized, case-insensitive)
- birthday is NOT required

Example:
Seed:  { first: "Jose", last: "Reyes", middle: "Antonio", dob: "1992-07-10" }
Test:  { first: "Jose", last: "Reyes", middle: "Antonio", dob: null }
Result: POSSIBLE DUPLICATE (80%)
```

### Rule 4: Fuzzy Name Match (70-100% - POSSIBLE DUPLICATE)
```
Required:
- Name similarity ≥ 85% (average of first + last name using similar_text())
- Middle names don't conflict (if both present)
- Gender doesn't conflict (if both present)

Discriminators (adjust confidence):
- DOB exact match: +10 points
- Gender match: +5 points
- Address/Barangay match: +5 points each
- Template fields: +1 point each (max +10)

Example:
Seed:  { first: "Cristita", last: "Afable", middle: null, dob: "1970-01-01" }
Test:  { first: "Cristina", last: "Afable", middle: null, dob: "1970-01-01" }
Name Similarity: ~95%, DOB Match: +10
Result: POSSIBLE DUPLICATE (85%)
```

### Rule 5: No Match (0% - NEW RECORD)
```
None of the above rules matched
Result: NEW RECORD (0%)
```

## Normalization Rules

### Name Normalization
```javascript
function normalizeName(name) {
  if (!name) return '';
  
  // Convert to lowercase
  let normalized = name.toLowerCase().trim();
  
  // Remove trailing period from single-letter initials
  if (/^[a-z]\.?$/.test(normalized)) {
    normalized = normalized.replace('.', '');
  }
  
  return normalized;
}

Examples:
"Juan" → "juan"
"MARIA" → "maria"
"D." → "d"
"D" → "d"
"  Pedro  " → "pedro"
```

### Null String Sanitization
```javascript
function sanitizeNullStrings(value) {
  const nullLikeStrings = [
    'null', 'NULL', 'Null',
    'n/a', 'N/A', 'NA', 'na',
    'none', 'NONE', 'None',
    '-', '--', '---'
  ];
  
  if (!value || nullLikeStrings.includes(value.trim())) {
    return '';
  }
  
  return value;
}

Examples:
"Null" → ""
"N/A" → ""
"-" → ""
"Pedro" → "Pedro"
```

### Birthday Normalization
```javascript
function normalizeBirthday(dob) {
  if (!dob) return null;
  
  // Try parsing common formats
  const formats = [
    /^\d{4}-\d{2}-\d{2}$/,        // 2023-01-15
    /^\d{2}\/\d{2}\/\d{4}$/,      // 01/15/2023 or 15/01/2023
    /^\d{2}-\d{2}-\d{4}$/,        // 01-15-2023 or 15-01-2023
  ];
  
  // Convert to YYYY-MM-DD
  // Reject dates before 1900 or in the future
  
  return normalizedDate; // YYYY-MM-DD format
}

Examples:
"1990-01-15" → "1990-01-15"
"01/15/1990" → "1990-01-15"
"15-01-1990" → "1990-01-15"
"2030-01-01" → null (future date)
```

## Similarity Calculation

The algorithm uses PHP's `similar_text()` function:

```javascript
// JavaScript equivalent approximation
function calculateSimilarity(str1, str2) {
  str1 = str1.toLowerCase();
  str2 = str2.toLowerCase();
  
  // This is a simplified version
  // PHP's similar_text() uses a different algorithm
  // For accurate simulation, use the actual PHP function
  
  const longer = str1.length > str2.length ? str1 : str2;
  const shorter = str1.length > str2.length ? str2 : str1;
  
  if (longer.length === 0) return 100.0;
  
  const editDistance = levenshteinDistance(longer, shorter);
  const similarity = ((longer.length - editDistance) / longer.length) * 100;
  
  return similarity;
}

// Note: PHP's similar_text() may give different results
// For exact simulation, use PHP or a PHP-compatible library
```

## Simulation Examples

### Example 1: Perfect Exact Match
```json
{
  "seed": {
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "middle_name": "Pedro",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "middle_name": "Pedro",
    "birthday": "1990-01-15"
  },
  "expected": {
    "status": "MATCHED",
    "confidence": 100,
    "rule": "exact_match"
  }
}
```

### Example 2: Missing Middle Name
```json
{
  "seed": {
    "first_name": "Maria",
    "last_name": "Santos",
    "middle_name": "Cruz",
    "birthday": "1985-03-20"
  },
  "test": {
    "first_name": "Maria",
    "last_name": "Santos",
    "middle_name": null,
    "birthday": "1985-03-20"
  },
  "expected": {
    "status": "POSSIBLE DUPLICATE",
    "confidence": 90,
    "rule": "partial_match_with_dob"
  }
}
```

### Example 3: Middle Name Initial Match
```json
{
  "seed": {
    "first_name": "Jose",
    "last_name": "Reyes",
    "middle_name": "Antonio",
    "birthday": "1992-07-10"
  },
  "test": {
    "first_name": "Jose",
    "last_name": "Reyes",
    "middle_name": "A",
    "birthday": "1992-07-10"
  },
  "expected": {
    "status": "POSSIBLE DUPLICATE",
    "confidence": 85,
    "rule": "fuzzy_name_match",
    "note": "Single-letter initial 'A' matches 'Antonio'"
  }
}
```

### Example 4: Fuzzy Name Match with DOB
```json
{
  "seed": {
    "first_name": "Cristita",
    "last_name": "Afable",
    "middle_name": null,
    "birthday": "1970-01-01"
  },
  "test": {
    "first_name": "Cristina",
    "last_name": "Afable",
    "middle_name": null,
    "birthday": "1970-01-01"
  },
  "expected": {
    "status": "POSSIBLE DUPLICATE",
    "confidence": 85,
    "rule": "fuzzy_name_match",
    "calculation": {
      "base_score": 70,
      "name_similarity": 95,
      "dob_bonus": 10,
      "final": 85
    }
  }
}
```

### Example 5: Conflicting Middle Names
```json
{
  "seed": {
    "first_name": "Pedro",
    "last_name": "Garcia",
    "middle_name": "Luis",
    "birthday": "1988-05-12"
  },
  "test": {
    "first_name": "Pedro",
    "last_name": "Garcia",
    "middle_name": "Carlos",
    "birthday": "1988-05-12"
  },
  "expected": {
    "status": "NEW RECORD",
    "confidence": 0,
    "rule": "no_match",
    "note": "Both have different middle names (not initials), fuzzy rule rejects"
  }
}
```

### Example 6: Gender Mismatch
```json
{
  "seed": {
    "first_name": "Alex",
    "last_name": "Cruz",
    "middle_name": null,
    "birthday": "1995-08-20",
    "gender": "M"
  },
  "test": {
    "first_name": "Alex",
    "last_name": "Cruz",
    "middle_name": null,
    "birthday": "1995-08-20",
    "gender": "F"
  },
  "expected": {
    "status": "NEW RECORD",
    "confidence": 0,
    "rule": "no_match",
    "note": "Gender mismatch causes fuzzy rule to reject"
  }
}
```

### Example 7: Completely Different Records
```json
{
  "seed": {
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "middle_name": "Pedro",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "Maria",
    "last_name": "Santos",
    "middle_name": "Cruz",
    "birthday": "1985-03-20"
  },
  "expected": {
    "status": "NEW RECORD",
    "confidence": 0,
    "rule": "no_match"
  }
}
```

## Edge Cases

### Case 1: Null-like Strings
```json
{
  "seed": {
    "first_name": "Juan",
    "last_name": "Cruz",
    "middle_name": "Null",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "Juan",
    "last_name": "Cruz",
    "middle_name": null,
    "birthday": "1990-01-15"
  },
  "expected": {
    "status": "POSSIBLE DUPLICATE",
    "confidence": 90,
    "rule": "partial_match_with_dob",
    "note": "'Null' string is sanitized to empty, treated as missing middle name"
  }
}
```

### Case 2: Trailing Periods in Initials
```json
{
  "seed": {
    "first_name": "Juan",
    "last_name": "Cruz",
    "middle_name": "D.",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "Juan",
    "last_name": "Cruz",
    "middle_name": "D",
    "birthday": "1990-01-15"
  },
  "expected": {
    "status": "MATCHED",
    "confidence": 100,
    "rule": "exact_match",
    "note": "'D.' normalized to 'd', matches 'D' normalized to 'd'"
  }
}
```

### Case 3: Case Sensitivity
```json
{
  "seed": {
    "first_name": "JUAN",
    "last_name": "DELA CRUZ",
    "middle_name": "PEDRO",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "juan",
    "last_name": "dela cruz",
    "middle_name": "pedro",
    "birthday": "1990-01-15"
  },
  "expected": {
    "status": "MATCHED",
    "confidence": 100,
    "rule": "exact_match",
    "note": "All names normalized to lowercase before comparison"
  }
}
```

### Case 4: Whitespace Handling
```json
{
  "seed": {
    "first_name": "  Juan  ",
    "last_name": " Dela Cruz ",
    "middle_name": "Pedro",
    "birthday": "1990-01-15"
  },
  "test": {
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "middle_name": "Pedro",
    "birthday": "1990-01-15"
  },
  "expected": {
    "status": "MATCHED",
    "confidence": 100,
    "rule": "exact_match",
    "note": "Whitespace trimmed during normalization"
  }
}
```

## Test Data Structure for Simulations

### Expected Distribution: 20 Exact, 15 Fuzzy, 25 New

```json
{
  "seed_data": [
    // 20 records with complete data (first, last, middle, DOB)
    {
      "id": 1,
      "first_name": "Juan",
      "last_name": "Dela Cruz",
      "middle_name": "Pedro",
      "birthday": "1990-01-15"
    },
    // ... 19 more
    
    // 15 records with complete data (will match partially in test)
    {
      "id": 21,
      "first_name": "Maria",
      "last_name": "Santos",
      "middle_name": "Cruz",
      "birthday": "1985-03-20"
    },
    // ... 14 more
    
    // 25 unique records (won't match anything)
    {
      "id": 36,
      "first_name": "Carlos",
      "last_name": "Mendoza",
      "middle_name": "Luis",
      "birthday": "1992-07-10"
    }
    // ... 24 more
  ],
  
  "test_data": [
    // 20 exact matches (same first, last, middle, DOB)
    {
      "first_name": "Juan",
      "last_name": "Dela Cruz",
      "middle_name": "Pedro",
      "birthday": "1990-01-15"
    },
    // ... 19 more
    
    // 15 partial matches (same first, last, DOB, but missing/different middle)
    {
      "first_name": "Maria",
      "last_name": "Santos",
      "middle_name": null,  // <-- Missing middle name
      "birthday": "1985-03-20"
    },
    // ... 14 more
    
    // 25 completely different records
    {
      "first_name": "Roberto",
      "last_name": "Fernandez",
      "middle_name": "Jose",
      "birthday": "1988-11-25"
    }
    // ... 24 more
  ]
}
```

## Common Simulation Mistakes

### Mistake 1: Null Birthday Comparison
```
Problem: Records with matching names but NO birthday on either side get MATCHED (100%)
Expected: POSSIBLE DUPLICATE (80%) via FullNameMatchRule
Actual: MATCHED (100%) via ExactMatchRule

Cause: ExactMatchRule was comparing null == null as true

Example:
Seed: { first: "Librada", last: "Aguilar", middle: "I", dob: null }
Test: { first: "Librada", last: "Aguilar", middle: "I", dob: null }
WRONG: MATCHED (100%)
CORRECT: POSSIBLE DUPLICATE (80%) via full_name_match

Fix: ExactMatchRule now requires BOTH birthdays to be non-null
```

### Mistake 2: Initial Abbreviations in Exact Match
```
Problem: Single-letter initials matching full names get MATCHED (100%)
Expected: POSSIBLE DUPLICATE (90%) via PartialMatchWithDobRule or FuzzyNameMatchRule
Actual: MATCHED (100%) via ExactMatchRule

Cause: Abbreviation logic was leaking into ExactMatchRule

Example:
Seed: { first: "Rodil", last: "Aguilar", middle: "Santiago", dob: "1990-01-15" }
Test: { first: "Rodil", last: "Aguilar", middle: "S", dob: "1990-01-15" }
WRONG: MATCHED (100%)
CORRECT: POSSIBLE DUPLICATE (90%) via partial_match_with_dob

Fix: ExactMatchRule now uses STRICT equality only (no abbreviation checks)
Abbreviation matching only happens in FuzzyNameMatchRule
```

### Mistake 3: Middle Names in Test Data
```
Problem: Test data has middle names when expecting partial matches
Expected: 15 partial matches (90%)
Actual: 19 exact matches (100%), 11 partial matches (90%)

Cause: 4 records in test data have middle names matching seed data

Solution: Verify test data middle_name field is null or different
```

### Mistake 4: Name Similarity Below Threshold
```
Problem: Fuzzy matches not triggering
Expected: Fuzzy match
Actual: NEW RECORD

Cause: Name similarity < 85%

Example:
"Cristita" vs "Christina" = ~80% (below 85% threshold)
"Cristita" vs "Cristina" = ~95% (above 85% threshold)
```

### Mistake 5: Gender Conflicts
```
Problem: Expected fuzzy match, got NEW RECORD
Cause: Gender mismatch causes rejection

Example:
Seed: { first: "Alex", last: "Cruz", gender: "M" }
Test: { first: "Alex", last: "Cruz", gender: "F" }
Result: NEW RECORD (gender mismatch rejects)
```

### Mistake 6: Date Format Issues
```
Problem: Expected exact match, got partial or no match
Cause: Birthday not normalized to YYYY-MM-DD

Example:
Seed: "1990-01-15"
Test: "01/15/1990"
After normalization: Both become "1990-01-15" → Match
```

## Simulation Algorithm (Pseudocode)

```python
def simulate_match(seed_record, test_record):
    # Step 1: Normalize data
    seed_normalized = normalize_record(seed_record)
    test_normalized = normalize_record(test_record)
    
    # Step 2: Apply rules in priority order
    
    # Rule 1: Exact Match
    if (seed_normalized.first == test_normalized.first and
        seed_normalized.last == test_normalized.last and
        seed_normalized.middle and test_normalized.middle and
        seed_normalized.middle == test_normalized.middle and
        seed_normalized.birthday and test_normalized.birthday and
        seed_normalized.birthday == test_normalized.birthday):
        return {"status": "MATCHED", "confidence": 100, "rule": "exact_match"}
    
    # Rule 2: Partial Match with DOB
    if (seed_normalized.first == test_normalized.first and
        seed_normalized.last == test_normalized.last and
        seed_normalized.birthday and test_normalized.birthday and
        seed_normalized.birthday == test_normalized.birthday):
        return {"status": "POSSIBLE DUPLICATE", "confidence": 90, "rule": "partial_match_with_dob"}
    
    # Rule 3: Full Name Match
    if (seed_normalized.first == test_normalized.first and
        seed_normalized.last == test_normalized.last and
        seed_normalized.middle and test_normalized.middle and
        seed_normalized.middle == test_normalized.middle):
        return {"status": "POSSIBLE DUPLICATE", "confidence": 80, "rule": "full_name_match"}
    
    # Rule 4: Fuzzy Name Match
    first_similarity = calculate_similarity(seed_normalized.first, test_normalized.first)
    last_similarity = calculate_similarity(seed_normalized.last, test_normalized.last)
    avg_similarity = (first_similarity + last_similarity) / 2
    
    if avg_similarity >= 85:
        # Check for conflicts
        if middle_names_conflict(seed_normalized.middle, test_normalized.middle):
            return {"status": "NEW RECORD", "confidence": 0, "rule": "no_match"}
        
        if gender_mismatch(seed_normalized.gender, test_normalized.gender):
            return {"status": "NEW RECORD", "confidence": 0, "rule": "no_match"}
        
        # Calculate discriminators
        base_score = 70
        adjustment = 0
        
        if seed_normalized.birthday == test_normalized.birthday:
            adjustment += 10
        
        if seed_normalized.gender == test_normalized.gender:
            adjustment += 5
        
        # ... more discriminators
        
        final_confidence = max(0, min(100, base_score + adjustment))
        
        return {"status": "POSSIBLE DUPLICATE", "confidence": final_confidence, "rule": "fuzzy_name_match"}
    
    # Rule 5: No Match
    return {"status": "NEW RECORD", "confidence": 0, "rule": "no_match"}
```

## Debugging Checklist

When simulation results don't match expectations:

1. ✓ Verify middle name presence in BOTH seed and test data
2. ✓ Check for null-like strings ("Null", "N/A", "-")
3. ✓ Confirm birthday format is YYYY-MM-DD after normalization
4. ✓ Verify name similarity calculation (use PHP's similar_text if possible)
5. ✓ Check for gender conflicts
6. ✓ Ensure case-insensitive comparison
7. ✓ Verify whitespace is trimmed
8. ✓ Check for trailing periods in initials
9. ✓ Confirm rule priority order is followed
10. ✓ Verify discriminator bonuses/penalties are applied correctly

## Quick Validation

To validate your simulation matches the actual system:

```json
{
  "test_case": {
    "seed": { "first": "Juan", "last": "Cruz", "middle": "P", "dob": "1990-01-15" },
    "test": { "first": "Juan", "last": "Cruz", "middle": "Pedro", "dob": "1990-01-15" }
  },
  "expected_result": {
    "status": "POSSIBLE DUPLICATE",
    "confidence": 85,
    "rule": "fuzzy_name_match",
    "reason": "Single-letter 'P' matches 'Pedro', DOB bonus +10"
  }
}
```

If your simulation produces different results, review the normalization and rule application steps.
