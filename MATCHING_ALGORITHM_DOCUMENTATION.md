# Data Matching Algorithm Documentation

## Overview

This document provides a comprehensive analysis of the data matching system used in this Laravel application. The system matches uploaded records against existing records in the database to identify duplicates, possible duplicates, or new records.

## System Architecture

### Core Components

1. **DataMatchService** - Orchestrates the matching process using a rule chain
2. **MatchingRules** - Individual matching strategies with different confidence levels
3. **ConfidenceScoreService** - Calculates unified confidence scores and field breakdowns
4. **MainSystem Model** - Database model representing person records
5. **FuzzyMatchingConfig** - Configuration for fuzzy matching and discriminators

### Data Flow

```
Upload CSV → RecordImport → DataMatchService → Rule Chain → Match Result
                                    ↓
                            Candidate Loading
                                    ↓
                            Database (main_system)
```

## Database Schema

### main_system Table

The `main_system` table stores all person records with the following structure:

**Core Identity Fields:**
- `uid` - Unique identifier (generated as 'UID-{ULID}')
- `last_name` - Original last name
- `first_name` - Original first name
- `middle_name` - Original middle name
- `last_name_normalized` - Lowercase, trimmed last name for matching
- `first_name_normalized` - Lowercase, trimmed first name for matching
- `middle_name_normalized` - Lowercase, trimmed middle name for matching
- `suffix` - Name suffix (Jr., Sr., III, etc.)
- `birthday` - Date of birth (DATE type)

**Additional Fields:**
- `gender` - Gender (M, F, Other)
- `civil_status` - Marital status
- `address` - Street address
- `barangay` - Barangay/district
- `regs_no` - Registration number
- `registration_date` - Date of registration
- `status` - Record status
- `category` - Record category
- `id_field` - Additional ID field
- `origin_batch_id` - Foreign key to upload_batches table
- `origin_match_result_id` - Foreign key to match_results table

**Relationships:**
- `templateFieldValues()` - HasMany relationship to TemplateFieldValue model
- `originBatch()` - BelongsTo relationship to UploadBatch model

## Matching Process

### 1. Batch Processing Flow

```php
batchFindMatches(Collection $uploadedRecords): Collection
```

**Steps:**
1. Normalize all uploaded records (lowercase, trim, parse dates)
2. Load candidates from database (excluding current batch)
3. Match each record against cached candidates using rule chain
4. Return collection of match results

**Key Behavior:**
- Records within the same batch are NOT matched against each other
- Only existing database records are used as candidates
- Prevents false positives within a single upload

### 2. Candidate Loading

```php
loadCandidatesExcludingBatch(Collection $normalizedRecords): void
```

**Query Strategy:**
- Extract unique normalized last names and first names from uploaded records
- Query database for records matching ANY of these names
- Uses `whereIn` for efficient bulk loading
- Casts a wide net for the matching rules to filter

**Example Query:**
```sql
SELECT * FROM main_system 
WHERE last_name_normalized IN ('smith', 'jones', 'garcia')
   OR first_name_normalized IN ('john', 'maria', 'jose')
```

### 3. Field Normalization

**Name Normalization:**
```php
normalizeString(string $value): string
```
- Converts to lowercase using UTF-8 encoding
- Trims whitespace
- Example: "GARCIA" → "garcia"

**Date Normalization:**
```php
parseDate($date): ?string
```
- Supports multiple formats: Y-m-d, d/m/Y, m/d/Y, d-m-Y, Y/m/d, d.m.Y
- Validates year range (1900 to current year)
- Returns YYYY-MM-DD format or null
- Example: "01/15/1990" → "1990-01-15"

**Field Extraction:**
- DOB: Checks 'dob', 'DOB', 'date_of_birth', 'birthday', etc.
- Gender: Checks 'gender', 'Gender', 'sex', 'Sex', etc.
- Address: Checks 'address', 'Address', 'street_address', etc.
- Barangay: Checks 'barangay', 'Barangay', 'brgy', 'Brgy', etc.

## Matching Rules (Rule Chain)

The system uses a priority-ordered chain of matching rules. Each rule is tried in sequence until a match is found.

### Rule Chain Order

```php
$this->rules = [
    new ExactMatchRule(),           // Priority 1: 100% confidence
    new PartialMatchWithDobRule(),  // Priority 2: 90% confidence
    new FullNameMatchRule(),        // Priority 3: 80% confidence
    new FuzzyNameMatchRule(),       // Priority 4: 70% confidence (base)
];
```

### Rule 1: ExactMatchRule

**Confidence:** 100%  
**Status:** MATCHED  
**Rule Name:** exact_match

**Matching Criteria:**
- `last_name_normalized` must match exactly
- `first_name_normalized` must match exactly
- `middle_name_normalized` must match exactly ✅
- `birthday` must match exactly

**Example:**
```
Uploaded: Ricardo Paredes Aguilar, DOB: 1990-01-15
Database: Ricardo Paredes Aguilar, DOB: 1990-01-15
Result: MATCHED (100% confidence)
```

**Important:** Middle name IS required. This prevents false positives where different people have the same first/last name and birthday but different middle names.

### Rule 2: PartialMatchWithDobRule

**Confidence:** 90%  
**Status:** POSSIBLE DUPLICATE  
**Rule Name:** partial_match_with_dob

**Matching Criteria:**
- `last_name_normalized` must match exactly
- `first_name_normalized` must match exactly
- `birthday` must match exactly
- `middle_name_normalized` NOT required ❌

**Example:**
```
Uploaded: Ricardo Aguilar (no middle name), DOB: 1990-01-15
Database: Ricardo Paredes Aguilar, DOB: 1990-01-15
Result: MATCHED (90% confidence)
```

**Use Case:** Handles cases where middle name is missing in one record but first/last/DOB match.

### Rule 3: FullNameMatchRule

**Confidence:** 80%  
**Status:** POSSIBLE DUPLICATE  
**Rule Name:** full_name_match

**Matching Criteria:**
- `last_name_normalized` must match exactly
- `first_name_normalized` must match exactly
- `middle_name_normalized` must match exactly ✅
- `birthday` NOT required

**Example:**
```
Uploaded: Ricardo Paredes Aguilar (no DOB)
Database: Ricardo Paredes Aguilar, DOB: 1990-01-15
Result: POSSIBLE DUPLICATE (80% confidence)
```

**Use Case:** Matches full names when DOB is missing or unreliable.

### Rule 4: FuzzyNameMatchRule

**Confidence:** 70% (base, adjusted by discriminators)  
**Status:** POSSIBLE DUPLICATE  
**Rule Name:** fuzzy_name_match

**Matching Criteria:**
- Fuzzy name similarity ≥ 88% threshold
- Average of last_name and first_name similarity
- Middle name mismatch rejection: If both middle names are present but don't match, candidate is rejected
- Discriminator validation (DOB, gender, address, template fields)
- Gender mismatch can reject candidate

**Fuzzy Matching Algorithm:**
```php
similarity(string $str1, string $str2): float
```
- Uses Levenshtein distance algorithm
- Calculates percentage similarity
- Example: "Garcia" vs "Garsia" = 83.3% similarity

**Discriminator Validation:**

1. **Middle Name Mismatch Rejection:**
   - Both middle names present but different: REJECT candidate
   - Prevents false positives when middle names clearly differ
   - Example: "Quijano" vs "Delos Santos" → reject immediately
   - Only applies when both values are non-empty

2. **DOB Validation:**
   - Both missing: No adjustment
   - One missing: -5 penalty
   - Exact match: +10 bonus
   - Partial match (same month/year, different day): -5 penalty
   - No match: No adjustment

3. **Gender Validation:**
   - Both missing: No adjustment
   - One missing: -3 penalty
   - Match: +5 bonus
   - Mismatch: REJECT candidate (if reject_on_mismatch=true)

4. **Address/Barangay Validation:**
   - Barangay exact match: +5 bonus
   - Barangay mismatch or one missing: -5 penalty
   - Address fuzzy match (≥80% similarity): +5 bonus
   - Address no match: -5 penalty

5. **Template Fields Validation:**
   - Exact match per field: +2 bonus (max +10 total)
   - Fuzzy match per field (≥80%): +1 bonus
   - No match per field: -1 penalty (max -5 total)

**Final Confidence Calculation:**
```
finalConfidence = baseScore (70) + discriminatorAdjustment
Bounded between: minConfidence (0) and maxConfidence (100)
```

**Example:**
```
Uploaded: Maria Garsia Lopez, DOB: 1985-05-20, Gender: F
Database: Maria Garcia Lopez, DOB: 1985-05-20, Gender: F

Name Similarity: 95% (fuzzy match, above 88% threshold)
DOB: Exact match (+10)
Gender: Match (+5)
Final Confidence: 70 + 15 = 85%
Result: POSSIBLE DUPLICATE (85% confidence)
```

## Confidence Score Calculation

### Unified Scoring System

The `ConfidenceScoreService` provides a unified scoring system that calculates confidence based on field-by-field comparison.

**Formula:**
```
confidence = (matched_fields / total_fields) × 100
```

### Field Breakdown

**Core Fields Tracked:**
- uid, last_name, first_name, middle_name, suffix
- birthday, gender, civil_status
- street_no, street, city, province, barangay

**Template Fields:**
- Dynamic fields defined per template
- Stored in `template_field_values` table

**Field Comparison:**
- Exact match: 100% confidence
- Fuzzy match (strings only): 0-99% confidence using Levenshtein distance
- No match: 0% confidence
- New field (existing value is null): null confidence

**Example Breakdown:**
```json
{
  "total_fields": 10,
  "matched_fields": 8,
  "core_fields": {
    "first_name": {
      "status": "match",
      "uploaded": "Maria",
      "existing": "Maria",
      "confidence": 100.0
    },
    "last_name": {
      "status": "match",
      "uploaded": "Garcia",
      "existing": "Garcia",
      "confidence": 100.0
    },
    "birthday": {
      "status": "mismatch",
      "uploaded": "1990-01-15",
      "existing": "1990-01-16",
      "confidence": 0.0
    }
  },
  "template_fields": {}
}
```

## Match Result Statuses

### MATCHED (100% confidence only)
- Exact match with 100% confidence
- Records represent the same person with certainty
- Safe to merge/update existing record
- Only from ExactMatchRule

### POSSIBLE DUPLICATE (1-99% confidence)
- Any match below 100% confidence
- Requires manual review
- From PartialMatchWithDobRule, FullNameMatchRule, or FuzzyNameMatchRule
- Confidence level indicates likelihood of match

### NEW RECORD (0% confidence)
- No match found in database
- Record should be inserted as new
- All matching rules failed to find a candidate

## Performance Optimizations

### 1. Candidate Caching
- Load candidates once per batch
- Cache in memory for all records in batch
- Prevents N database queries

### 2. Batch Loading
- Use `whereIn` for bulk candidate loading
- Single query instead of per-record queries

### 3. Template Field Optimization
- Batch load template fields for multiple candidates
- Single query with `whereIn` instead of N queries
- Grouped by main_system_id

### 4. Early Termination
- Rule chain stops at first match
- Higher confidence rules tried first
- Reduces unnecessary comparisons

## Configuration

### FuzzyMatchingConfig

**Discriminator Settings:**
```php
'dob' => [
    'enabled' => true,
    'bonus_exact_match' => 10,
    'penalty_missing_one' => 5,
    'penalty_partial_match' => 5,
]

'gender' => [
    'enabled' => true,
    'bonus_match' => 5,
    'penalty_missing_one' => 3,
    'reject_on_mismatch' => true,
]

'address' => [
    'enabled' => true,
    'bonus_exact_barangay' => 5,
    'bonus_fuzzy_address' => 5,
    'penalty_missing_one' => 5,
    'penalty_fuzzy_fail' => 5,
]

'template_fields' => [
    'enabled' => true,
    'bonus_exact_match' => 2,
    'bonus_fuzzy_match' => 1,
    'penalty_no_match' => 1,
    'max_bonus' => 10,
    'max_penalty' => 5,
]
```

**Thresholds:**
- Name similarity threshold: 88%
- Address similarity threshold: 80%
- Min confidence: 0
- Max confidence: 100

## Example Scenarios

### Scenario 1: Exact Match
```
Uploaded Record:
- Name: Juan Dela Cruz Santos
- DOB: 1985-03-15
- Gender: M

Database Record:
- Name: Juan Dela Cruz Santos
- DOB: 1985-03-15
- Gender: M

Result: MATCHED (100% confidence) via ExactMatchRule
```

### Scenario 2: Missing Middle Name
```
Uploaded Record:
- Name: Juan Santos (no middle name)
- DOB: 1985-03-15

Database Record:
- Name: Juan Dela Cruz Santos
- DOB: 1985-03-15

Result: MATCHED (90% confidence) via PartialMatchWithDobRule
```

### Scenario 3: Different Middle Names (Different People)
```
Uploaded Record:
- Name: Ricardo Paredes Aguilar
- DOB: 1990-01-15

Database Record:
- Name: Ricardo Barrera Aguilar
- DOB: 1990-01-15

Result: NEW RECORD (0% confidence)
Reason: ExactMatchRule fails (middle names don't match)
        PartialMatchWithDobRule would match, but ExactMatchRule is tried first
        
IMPORTANT: These are DIFFERENT people, not duplicates!
```

### Scenario 4: Fuzzy Name Match with Discriminators
```
Uploaded Record:
- Name: Maria Garsia Lopez (typo in last name)
- DOB: 1985-05-20
- Gender: F
- Barangay: San Jose

Database Record:
- Name: Maria Garcia Lopez
- DOB: 1985-05-20
- Gender: F
- Barangay: San Jose

Calculation:
- Name similarity: 95% (above 85% threshold)
- DOB exact match: +10
- Gender match: +5
- Barangay exact match: +5
- Final: 70 + 20 = 90%

Result: MATCHED (90% confidence) via FuzzyNameMatchRule
```

### Scenario 5: Gender Mismatch Rejection
```
Uploaded Record:
- Name: Maria Garcia (fuzzy match)
- Gender: F

Database Record:
- Name: Mario Garcia
- Gender: M

Result: NEW RECORD (0% confidence)
Reason: FuzzyNameMatchRule rejects due to gender mismatch
```

## Important Design Decisions

### 1. Middle Name Requirement
**Decision:** Middle names ARE required for ExactMatchRule and FullNameMatchRule

**Rationale:**
- Prevents false positives where different people have same first/last/DOB
- Example: Ricardo Paredes Aguilar ≠ Ricardo Barrera Aguilar
- PartialMatchWithDobRule handles cases where middle name is missing

### 2. Within-Batch Isolation
**Decision:** Records in the same batch are NOT matched against each other

**Rationale:**
- Prevents false positives within a single upload
- Only existing database records are used as candidates
- Ensures data integrity during batch processing

### 3. Rule Chain Priority
**Decision:** Higher confidence rules are tried first

**Rationale:**
- Exact matches (100%) are most reliable
- Stops at first match for performance
- Reduces unnecessary fuzzy matching

### 4. Gender Mismatch Rejection
**Decision:** Gender mismatch rejects candidate in FuzzyNameMatchRule

**Rationale:**
- Strong indicator that records are different people
- Prevents false positives from name typos
- Configurable via reject_on_mismatch setting

### 5. Fuzzy Matching Threshold
**Decision:** Fuzzy name similarity threshold set to 88%

**Rationale:**
- Prevents false positives from minor name variations
- Requires higher confidence in fuzzy matches
- Filters out marginal matches like "Aguila" vs "Aguilar" (87% similarity)
- Maintains legitimate typo matching for closer similarities
- Tested against dataset: reduces false positives while preserving valid matches

## Logging and Debugging

### Log Levels

**Debug:**
- DOB comparisons
- Gender comparisons
- Address comparisons
- Template field comparisons
- Discriminator score calculations

**Info:**
- Fuzzy name match found
- Candidate evaluation metrics
- Performance timing

**Warning:**
- Failed to parse DOB
- Unknown gender value
- Template field comparison errors

### Performance Metrics

**Tracked Metrics:**
- Elapsed time (milliseconds)
- Candidates evaluated
- Field breakdown details

**Example Log:**
```
Fuzzy name match found
- candidate_id: 12345
- confidence: 85
- candidates_evaluated: 150
- elapsed_time_ms: 45.23
```

## Testing Considerations

### Test Coverage Areas

1. **Rule Matching:**
   - Test each rule independently
   - Test rule chain priority
   - Test edge cases (null values, empty strings)

2. **Field Normalization:**
   - Test date parsing with various formats
   - Test name normalization (case, whitespace)
   - Test field extraction from different column names

3. **Discriminator Validation:**
   - Test DOB matching (exact, partial, missing)
   - Test gender matching (match, mismatch, missing)
   - Test address fuzzy matching
   - Test template field matching

4. **Confidence Scoring:**
   - Test unified score calculation
   - Test field breakdown generation
   - Test fuzzy match scoring

5. **Performance:**
   - Test batch processing with large datasets
   - Test candidate loading efficiency
   - Test memory usage

## Future Enhancements

### Potential Improvements

1. **Machine Learning:**
   - Train model on historical match decisions
   - Improve fuzzy matching accuracy
   - Adaptive confidence thresholds

2. **Phonetic Matching:**
   - Add Soundex or Metaphone algorithms
   - Handle name variations (Jose/Joseph, Maria/Mary)

3. **Address Standardization:**
   - Integrate with address validation API
   - Normalize street abbreviations
   - Handle address variations

4. **Template Field Storage:**
   - Currently template fields are not fully stored
   - Implement complete template field persistence
   - Enable template field matching in all rules

5. **Performance:**
   - Add database indexes on normalized fields
   - Implement caching layer
   - Optimize fuzzy matching algorithm

## Conclusion

This matching algorithm provides a robust, multi-layered approach to identifying duplicate records while minimizing false positives. The rule chain design allows for flexibility and extensibility, while the discriminator system provides fine-grained control over matching confidence.

**Key Strengths:**
- Multiple matching strategies with clear priorities
- Comprehensive field normalization
- Discriminator-based confidence adjustment
- Performance optimizations for batch processing
- Detailed logging and debugging support

**Key Considerations:**
- Middle names are required for exact matches (prevents false positives)
- Within-batch isolation prevents duplicate detection in same upload
- Gender mismatch rejection in fuzzy matching
- Template field matching requires full implementation

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-02  
**Author:** Kiro AI Assistant
