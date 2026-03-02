# Design Document: Enhanced Fuzzy Matching

## Overview

The Enhanced Fuzzy Matching feature extends the existing FuzzyNameMatchRule to incorporate demographic discriminators (DOB, gender, address/barangay) and template fields into the matching algorithm. This reduces false positives and improves match accuracy by distinguishing between records with similar names but different demographic profiles.

### Key Objectives

- Reduce false positive matches by 40-60% through discriminator validation
- Provide granular confidence scoring reflecting match quality
- Maintain backward compatibility with existing matching rules
- Support incomplete data scenarios gracefully
- Optimize performance for large batch processing (1000+ records in <30 seconds)

### Design Principles

1. **Discriminator-Based Validation**: Each discriminator (DOB, gender, address, template fields) validates independently with specific bonuses/penalties
2. **Graceful Degradation**: Missing discriminators don't reject matches; they apply confidence penalties instead
3. **Layered Scoring**: Base score (70%) + discriminator bonuses - discriminator penalties = final confidence
4. **Rule Ordering**: Enhanced fuzzy matching evaluated last, after exact/full name/partial DOB rules
5. **Configuration-Driven**: All thresholds and bonuses configurable via environment variables

## Architecture

### High-Level Data Flow

```
Uploaded Record
    ↓
DataMatchService.findMatch()
    ├─ Normalize record (extract discriminators)
    ├─ Query candidates by normalized name
    ├─ Evaluate matching rules in order:
    │   ├─ ExactMatchRule (100%)
    │   ├─ FullNameMatchRule (80%)
    │   ├─ PartialMatchWithDobRule (90%)
    │   └─ Enhanced FuzzyNameMatchRule (70-80%)
    │       ├─ Validate DOB match
    │       ├─ Validate gender match
    │       ├─ Validate address/barangay match
    │       ├─ Validate template fields
    │       └─ Calculate final confidence
    └─ Return best match with confidence score
```

### Component Interactions

```
┌─────────────────────────────────────────────────────────────┐
│                    DataMatchService                         │
│  - Normalizes records                                       │
│  - Extracts discriminators (DOB, gender, address)           │
│  - Passes data to matching rules                            │
└────────────────┬────────────────────────────────────────────┘
                 │
    ┌────────────┼────────────┐
    ↓            ↓            ↓
┌──────────┐ ┌──────────┐ ┌──────────────────────────┐
│ Existing │ │ Existing │ │ Enhanced                 │
│ Rules    │ │ Rules    │ │ FuzzyNameMatchRule       │
│ (100%,   │ │ (80%,    │ │ - validateDobMatch()     │
│  80%,    │ │  90%)    │ │ - validateGenderMatch()  │
│  90%)    │ │          │ │ - validateAddressMatch() │
└──────────┘ └──────────┘ │ - validateTemplateFields │
                           │ - calculateFinalConfidence
                           └──────────┬───────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    ↓                 ↓                 ↓
            ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
            │ Confidence   │  │ Template     │  │ Configuration
            │ ScoreService │  │ Field        │  │ Manager
            │              │  │ Persistence  │  │
            │              │  │ Service      │  │
            └──────────────┘  └──────────────┘  └──────────────┘
```

## Components and Interfaces

### 1. Enhanced FuzzyNameMatchRule

**Location**: `app/Rules/FuzzyNameMatchRule.php`

**Responsibilities**:
- Perform fuzzy name matching with 85% similarity threshold
- Validate discriminators (DOB, gender, address, template fields)
- Calculate confidence scores with bonuses and penalties
- Log matching process for audit trail

**Key Methods**:

```php
public function match(array $uploadedData, MainSystem $candidate, ?int $templateId = null): ?array
  // Main entry point; returns match result or null

public function validateDobMatch(
  ?string $uploadedDob,
  ?string $candidateDob
): array // Returns ['valid' => bool, 'bonus' => int, 'penalty' => int]

public function validateGenderMatch(
  ?string $uploadedGender,
  ?string $candidateGender
): array // Returns ['valid' => bool, 'bonus' => int, 'penalty' => int]

public function validateAddressMatch(
  ?string $uploadedAddress,
  ?string $uploadedBarangay,
  ?string $candidateAddress,
  ?string $candidateBarangay
): array // Returns ['valid' => bool, 'bonus' => int, 'penalty' => int]

public function validateTemplateFieldMatch(
  array $uploadedFields,
  MainSystem $candidate,
  int $templateId
): array // Returns ['bonus' => int, 'penalty' => int]

public function calculateDiscriminatorScore(
  array $dobResult,
  array $genderResult,
  array $addressResult,
  array $templateResult
): int // Returns total bonus/penalty adjustment

public function calculateFinalConfidence(
  float $baseScore,
  int $discriminatorAdjustment
): int // Returns final confidence (0-100)
```

### 2. DataMatchService Enhancements

**Location**: `app/Services/DataMatchService.php`

**New Responsibilities**:
- Extract discriminator fields during normalization
- Pass discriminator data to FuzzyNameMatchRule
- Handle template field extraction

**Modified Methods**:

```php
protected function normalizeRecord(array $data): array
  // Now extracts: dob, gender, address, barangay, template_fields

protected function findMatchFromCache(
  array $normalized,
  array $uploadedData,
  ?int $templateId = null
): array
  // Passes discriminator data to matching rules
```

### 3. ConfidenceScoreService Enhancements

**Location**: `app/Services/ConfidenceScoreService.php`

**New Responsibilities**:
- Calculate discriminator-based confidence scores
- Return detailed breakdown of discriminator contributions
- Apply bonuses and penalties consistently

**New Methods**:

```php
public function calculateDobScore(
  ?string $uploadedDob,
  ?string $existingDob
): array // Returns ['bonus' => int, 'penalty' => int, 'matched' => bool]

public function calculateGenderScore(
  ?string $uploadedGender,
  ?string $existingGender
): array // Returns ['bonus' => int, 'penalty' => int, 'matched' => bool]

public function calculateAddressScore(
  ?string $uploadedAddress,
  ?string $uploadedBarangay,
  ?string $existingAddress,
  ?string $existingBarangay
): array // Returns ['bonus' => int, 'penalty' => int, 'matched' => bool]

public function calculateTemplateFieldScore(
  array $uploadedFields,
  MainSystem $existingRecord,
  int $templateId
): array // Returns ['bonus' => int, 'penalty' => int, 'matchCount' => int]
```

### 4. Configuration Manager

**Location**: `app/Config/FuzzyMatchingConfig.php`

**Responsibilities**:
- Load and validate configuration
- Provide default values
- Support environment variable overrides

**Configuration Structure**:

```php
[
  'enabled' => true,
  'name_similarity_threshold' => 85,
  'address_similarity_threshold' => 80,
  'discriminators' => [
    'dob' => [
      'enabled' => true,
      'bonus_exact_match' => 10,
      'penalty_missing_one' => 5,
      'penalty_partial_match' => 3,
    ],
    'gender' => [
      'enabled' => true,
      'bonus_match' => 5,
      'penalty_missing_one' => 3,
      'reject_on_mismatch' => true,
    ],
    'address' => [
      'enabled' => true,
      'bonus_exact_barangay' => 5,
      'bonus_fuzzy_address' => 5,
      'penalty_missing_one' => 5,
      'penalty_fuzzy_fail' => 5,
    ],
    'template_fields' => [
      'enabled' => true,
      'bonus_exact_match' => 2,
      'bonus_fuzzy_match' => 1,
      'penalty_no_match' => 1,
      'max_bonus' => 10,
      'max_penalty' => 5,
    ],
  ],
  'base_confidence' => 70,
  'max_confidence' => 100,
  'min_confidence' => 0,
]
```



## Data Models

### Discriminator Data Structure

```php
// Normalized discriminator data passed through matching pipeline
[
  'dob' => '1990-05-15',           // YYYY-MM-DD format
  'gender' => 'M',                 // Normalized: M, F, Other
  'address' => 'barangay road',    // Lowercase, trimmed
  'barangay' => 'barangay name',   // Lowercase, trimmed
  'template_fields' => [           // Custom fields from template
    'field_name_1' => 'value_1',
    'field_name_2' => 'value_2',
  ],
]
```

### Match Result Structure

```php
[
  'status' => 'MATCHED',           // MATCHED, POSSIBLE_DUPLICATE, NEW_RECORD
  'confidence' => 85,              // 0-100
  'matched_record_id' => 123,      // MainSystem ID or null
  'rule_matched' => 'FuzzyNameMatchRule',
  'field_breakdown' => [
    'name_similarity' => 92,
    'dob_match' => ['matched' => true, 'bonus' => 10],
    'gender_match' => ['matched' => true, 'bonus' => 5],
    'address_match' => ['matched' => false, 'penalty' => 5],
    'template_fields' => ['matched_count' => 2, 'bonus' => 4],
    'discriminator_adjustment' => 14,
    'base_score' => 70,
    'final_score' => 84,
  ],
]
```

### Confidence Score Mapping

```
Score Range    Status              Interpretation
100%           MATCHED             Definite match
90-99%         MATCHED             High confidence match
80-89%         MATCHED             Good confidence match
70-79%         POSSIBLE_DUPLICATE  Requires review
<70%           NEW_RECORD          No match found
```



## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: DOB Exact Match Bonus

For any two records with matching fuzzy names and identical DOB values in YYYY-MM-DD format, the confidence score should increase by exactly 10% from the base score of 70%.

**Validates: Requirements 1.6, 5.3**

### Property 2: DOB Missing Penalty

For any two records where one has a DOB value and the other does not, the confidence score should be reduced by exactly 5% from the base score.

**Validates: Requirements 1.3, 1.4, 5.8**

### Property 3: Gender Mismatch Rejection

For any two records with matching fuzzy names but different gender values (after normalization), the match should be rejected and return null.

**Validates: Requirements 2.2, 2.3**

### Property 4: Gender Match Bonus

For any two records with matching fuzzy names and identical gender values (after normalization), the confidence score should increase by exactly 5% from the base score.

**Validates: Requirements 2.7, 5.4**

### Property 5: Barangay Mismatch Penalty

For any two records with matching fuzzy names but different barangay values, the confidence score should be reduced by exactly 10% instead of rejecting the match.

**Validates: Requirements 3.2, 3.3**

### Property 6: Address Fuzzy Match Bonus

For any two records with matching fuzzy names and address values that fuzzy match at 80% threshold, the confidence score should increase by exactly 5%.

**Validates: Requirements 3.4, 3.5, 5.6**

### Property 7: Confidence Score Formula

For any match result, the final confidence score should equal: base_score (70) + discriminator_bonuses - discriminator_penalties, capped at 100% and floored at 0%.

**Validates: Requirements 5.1, 5.12, 5.13**

### Property 8: Template Field Exact Match Bonus

For any two records with N matching template fields (exact match), the confidence score should increase by 2% per field, with a maximum total bonus of 10%.

**Validates: Requirements 4.4, 5.7**

### Property 9: Template Field Fuzzy Match Bonus

For any two records with N fuzzy-matching template fields (80% threshold), the confidence score should increase by 1% per field, with a maximum total bonus of 5%.

**Validates: Requirements 4.5, 5.7**

### Property 10: Incomplete Data Matching

For any two records with matching fuzzy names but missing discriminator fields, the system should proceed with matching using available discriminators and apply appropriate penalties.

**Validates: Requirements 7.1, 7.2, 7.6**

### Property 11: Rule Evaluation Order

For any uploaded record, matching rules should be evaluated in this deterministic order: ExactMatchRule → FullNameMatchRule → PartialMatchWithDobRule → Enhanced FuzzyNameMatchRule, with evaluation stopping at the first match.

**Validates: Requirements 12.1, 12.2, 12.7**

### Property 12: Backward Compatibility

For any record that matched under existing rules (ExactMatchRule, FullNameMatchRule, PartialMatchWithDobRule), the enhanced fuzzy matching should not be evaluated and existing confidence scores should be preserved.

**Validates: Requirements 6.1, 6.2, 6.3, 6.7**

### Property 13: DOB Normalization

For any DOB value in various formats (YYYY-MM-DD, MM/DD/YYYY, DD-MM-YYYY, etc.), the normalization should produce a consistent YYYY-MM-DD format for comparison.

**Validates: Requirements 1.8, 3.10**

### Property 14: Gender Normalization

For any gender value (M, Male, F, Female, Other, etc.), the normalization should produce a consistent standard format (M, F, Other) for comparison.

**Validates: Requirements 2.8, 2.9**

### Property 15: Address Normalization

For any address value with varying cases, extra spaces, or special characters, the normalization should produce a consistent lowercase, trimmed format for comparison.

**Validates: Requirements 3.10**

### Property 16: Confidence Score Rounding

For any calculated confidence score with decimal values, the final score should be rounded to the nearest integer percentage.

**Validates: Requirements 5.14**

### Property 17: Confidence Score Status Mapping

For any confidence score, the status mapping should follow: 100% = MATCHED, 90-99% = MATCHED, 80-89% = MATCHED, 70-79% = POSSIBLE_DUPLICATE, <70% = NEW_RECORD.

**Validates: Requirements 5.15**

### Property 18: Configuration Validation

For any configuration value provided, the system should validate that numeric values are within reasonable ranges (0-100%) and reject invalid configurations.

**Validates: Requirements 11.11**

### Property 19: Graceful Error Handling

For any error during discriminator validation (DOB parsing, gender normalization, address matching, template field lookup), the system should log the error and continue matching without throwing exceptions.

**Validates: Requirements 13.2, 13.3, 13.4, 13.5, 13.6**

### Property 20: Candidate Caching

For any batch of records processed, normalized candidate records should be cached in memory during batch processing to avoid redundant database queries.

**Validates: Requirements 14.2**



## Error Handling

### DOB Validation Errors

- **Invalid Format**: Log warning, treat as missing DOB, apply 5% penalty
- **Unparseable Date**: Log warning, treat as missing DOB, apply 5% penalty
- **Future Date**: Log warning, treat as missing DOB, apply 5% penalty

### Gender Normalization Errors

- **Unknown Gender Value**: Log warning, treat as missing gender, apply 3% penalty
- **Null/Empty Gender**: Treat as missing, no penalty if both records lack gender

### Address Matching Errors

- **Fuzzy Match Failure**: Log warning, apply 5% penalty
- **Null/Empty Address**: Treat as missing, apply 5% penalty if one record has address

### Template Field Lookup Errors

- **Field Not Found**: Log warning, skip field comparison
- **Database Query Failure**: Log warning, continue without template fields
- **Invalid Field Value**: Log warning, skip field comparison

### General Error Handling Strategy

- All errors are caught and logged with context (record IDs, batch IDs)
- No exceptions thrown during matching; graceful degradation applied
- Debug mode available for detailed error logging
- Error logs include discriminator values (without sensitive data)

## Testing Strategy

### Unit Testing Approach

**Discriminator Validation Tests**:
- Test DOB matching with various formats and edge cases
- Test gender normalization with all variations
- Test address fuzzy matching with typos and variations
- Test template field comparison with exact and fuzzy matches
- Test confidence score calculation with all bonus/penalty combinations

**Edge Cases**:
- Null/empty discriminator values
- Special characters in addresses
- Partial DOB matches (same month/year, different day)
- Gender variations (Male vs M vs male)
- Very long address strings
- Unicode characters in names and addresses

**Error Scenarios**:
- Invalid DOB formats
- Unknown gender values
- Database query failures
- Template field lookup failures
- Configuration validation failures

### Property-Based Testing Approach

**Property Test Configuration**:
- Minimum 100 iterations per property test
- Each test references its design document property
- Tag format: `Feature: enhanced-fuzzy-matching, Property {number}: {property_text}`

**Property Tests to Implement**:

1. **DOB Matching Round Trip** (Property 1, 2)
   - Generate random DOB values in various formats
   - Verify normalization produces consistent YYYY-MM-DD format
   - Verify matching DOBs produce 10% bonus

2. **Gender Normalization Round Trip** (Property 14)
   - Generate random gender variations
   - Verify normalization produces M, F, or Other
   - Verify case-insensitive comparison works

3. **Address Fuzzy Matching** (Property 6)
   - Generate random address pairs with 80%+ similarity
   - Verify fuzzy match succeeds and applies 5% bonus
   - Verify non-matching addresses apply 5% penalty

4. **Confidence Score Bounds** (Property 7, 12, 13)
   - Generate random bonus/penalty combinations
   - Verify final score never exceeds 100%
   - Verify final score never goes below 0%
   - Verify score rounds to nearest integer

5. **Template Field Matching** (Property 8, 9)
   - Generate random template field pairs
   - Verify exact matches apply 2% bonus per field (max 10%)
   - Verify fuzzy matches apply 1% bonus per field (max 5%)

6. **Incomplete Data Handling** (Property 10)
   - Generate records with missing discriminators
   - Verify matching proceeds with available fields
   - Verify appropriate penalties applied

7. **Rule Evaluation Order** (Property 11)
   - Generate records matching different rules
   - Verify rules evaluated in correct order
   - Verify evaluation stops at first match

8. **Backward Compatibility** (Property 12)
   - Generate records matching existing rules
   - Verify enhanced fuzzy matching not evaluated
   - Verify existing confidence scores preserved

### Integration Testing Approach

**DataMatchService Integration**:
- Test discriminator extraction during normalization
- Test discriminator data passed to FuzzyNameMatchRule
- Test template field extraction and lookup
- Test batch processing with caching

**ConfidenceScoreService Integration**:
- Test discriminator score calculation
- Test field_breakdown generation
- Test unified score calculation
- Test score mapping to status

**End-to-End Scenarios**:
- Test complete matching flow with all discriminators
- Test matching with incomplete data
- Test batch processing performance
- Test configuration variations

### Test Coverage Goals

- Minimum 80% code coverage for matching logic
- 100% coverage for confidence score calculation
- 100% coverage for discriminator validation methods
- All acceptance criteria covered by at least one test



## Performance Optimization

### Candidate Lookup Optimization

**Indexed Queries**:
- Use existing index on `first_name_normalized` and `last_name_normalized`
- Query candidates by normalized name before fuzzy matching
- Limit candidate set to top 100 by relevance

**Query Strategy**:
```sql
SELECT * FROM main_system
WHERE first_name_normalized LIKE CONCAT(?, '%')
   OR last_name_normalized LIKE CONCAT(?, '%')
LIMIT 100
```

### Caching Strategy

**In-Memory Candidate Cache**:
- Cache normalized candidate records during batch processing
- Keyed by normalized first/last name combination
- Cleared after batch completion
- Reduces database queries by 70-80%

**Template Field Caching**:
- Batch load template fields for all candidates at once
- Cache template field definitions by template_id
- Lazy-load only when template fields enabled

**Normalization Caching**:
- Cache normalized values to avoid redundant string operations
- Cache DOB parsing results
- Cache gender normalization results

### Query Optimization

**Batch Template Field Lookup**:
```php
// Instead of N queries (one per candidate)
// Use single query with IN clause
TemplateFieldValue::whereIn('main_system_id', $candidateIds)
  ->with('templateField')
  ->get()
```

**Avoid Redundant Comparisons**:
- Skip discriminator comparison if already rejected by previous discriminator
- Skip template field comparison if no template fields available
- Cache fuzzy match results to avoid recalculation

### Performance Metrics

**Target Performance**:
- Single record matching: <100ms
- Batch of 1000 records: <30 seconds
- Memory usage: <500MB for 1000 record batch

**Profiling Points**:
- Candidate lookup time
- Fuzzy matching time
- Discriminator validation time
- Template field lookup time
- Total matching time per record

## Database Query Optimization

### Candidate Lookup Query

```php
// Optimized candidate lookup
$candidates = MainSystem::where(function ($query) use ($firstName, $lastName) {
    $query->where('first_name_normalized', 'LIKE', $firstName . '%')
          ->orWhere('last_name_normalized', 'LIKE', $lastName . '%');
})
->limit(100)
->get();
```

### Template Field Batch Lookup

```php
// Batch load template fields for all candidates
$candidateIds = $candidates->pluck('id')->toArray();
$templateFields = TemplateFieldValue::whereIn('main_system_id', $candidateIds)
  ->where('template_id', $templateId)
  ->with('templateField')
  ->get()
  ->groupBy('main_system_id');
```

### Index Requirements

**Required Indexes**:
- `main_system(first_name_normalized)` - for candidate lookup
- `main_system(last_name_normalized)` - for candidate lookup
- `template_field_values(main_system_id, template_id)` - for batch lookup
- `template_field_values(template_id)` - for template field queries

## Backward Compatibility

### Existing Rule Preservation

- ExactMatchRule: Unchanged, 100% confidence
- FullNameMatchRule: Unchanged, 80% confidence
- PartialMatchWithDobRule: Unchanged, 90% confidence
- Enhanced FuzzyNameMatchRule: New rule, evaluated last

### Configuration Flag

```php
'enhanced_fuzzy_matching' => [
  'enabled' => env('ENHANCED_FUZZY_MATCHING_ENABLED', true),
]
```

When disabled, system uses existing matching rules only.

### Match Result Compatibility

- Existing match results structure preserved
- New discriminator scores added to field_breakdown
- Existing field comparisons maintained
- No breaking changes to API responses

### Migration Path

1. Deploy enhanced fuzzy matching with flag disabled
2. Run property-based tests to verify correctness
3. Enable for subset of batches (10%)
4. Monitor false positive reduction
5. Gradually increase to 100%
6. Keep flag available for rollback



## Diagrams

### Discriminator Processing Flow

```
Uploaded Record
    ↓
Extract Discriminators
    ├─ DOB → Normalize to YYYY-MM-DD
    ├─ Gender → Normalize to M/F/Other
    ├─ Address → Lowercase, trim, remove extra spaces
    ├─ Barangay → Lowercase, trim
    └─ Template Fields → Lowercase, trim
    ↓
Query Candidates by Normalized Name
    ↓
For Each Candidate:
    ├─ Fuzzy Name Match (85% threshold)
    │   ├─ No Match → Continue to next candidate
    │   └─ Match → Validate Discriminators
    │       ├─ Validate DOB
    │       │   ├─ Both present & match → +10% bonus
    │       │   ├─ Both present & no match → Reject (gender check first)
    │       │   ├─ One missing → -5% penalty
    │       │   └─ Both missing → No adjustment
    │       ├─ Validate Gender
    │       │   ├─ Both present & match → +5% bonus
    │       │   ├─ Both present & no match → Reject match
    │       │   ├─ One missing → -3% penalty
    │       │   └─ Both missing → No adjustment
    │       ├─ Validate Address/Barangay
    │       │   ├─ Barangay exact match → +5% bonus
    │       │   ├─ Barangay no match → -10% penalty
    │       │   ├─ Address fuzzy match (80%) → +5% bonus
    │       │   ├─ Address no match → -5% penalty
    │       │   └─ One missing → -5% penalty
    │       └─ Validate Template Fields
    │           ├─ Exact match per field → +2% (max 10%)
    │           ├─ Fuzzy match per field → +1% (max 5%)
    │           └─ No match per field → -1% (max 5%)
    │       ↓
    │       Calculate Final Confidence
    │       = Base (70%) + Bonuses - Penalties
    │       = Capped at 100%, floored at 0%
    │       = Rounded to nearest integer
    │       ↓
    │       Return Match Result
    └─ Return Best Match or NEW_RECORD
```

### Confidence Score Calculation

```
Base Score: 70%
    ↓
Add Discriminator Bonuses:
    ├─ DOB exact match: +10%
    ├─ Gender match: +5%
    ├─ Barangay exact match: +5%
    ├─ Address fuzzy match: +5%
    └─ Template fields: +2% per exact (max 10%), +1% per fuzzy (max 5%)
    ↓
Subtract Discriminator Penalties:
    ├─ DOB missing from one: -5%
    ├─ Gender missing from one: -3%
    ├─ Address/Barangay missing from one: -5%
    ├─ Barangay mismatch: -10%
    ├─ Address fuzzy fail: -5%
    └─ Template field no match: -1% per field (max 5%)
    ↓
Apply Bounds:
    ├─ Cap at 100%
    ├─ Floor at 0%
    └─ Round to nearest integer
    ↓
Final Confidence Score (0-100%)
    ↓
Map to Status:
    ├─ 100%: MATCHED
    ├─ 90-99%: MATCHED
    ├─ 80-89%: MATCHED
    ├─ 70-79%: POSSIBLE_DUPLICATE
    └─ <70%: NEW_RECORD
```

### Class Diagram

```
┌─────────────────────────────────────────┐
│         MatchRule (Abstract)            │
├─────────────────────────────────────────┤
│ + match(array, MainSystem): ?array      │
│ + similarity(string, string): float     │
└─────────────────────────────────────────┘
         ↑
         │ extends
         │
┌─────────────────────────────────────────────────────────────┐
│      FuzzyNameMatchRule (Enhanced)                          │
├─────────────────────────────────────────────────────────────┤
│ - confidenceScoreService: ConfidenceScoreService            │
│ - templateFieldService: TemplateFieldPersistenceService     │
│ - config: FuzzyMatchingConfig                               │
├─────────────────────────────────────────────────────────────┤
│ + match(array, MainSystem, ?int): ?array                    │
│ + validateDobMatch(?string, ?string): array                 │
│ + validateGenderMatch(?string, ?string): array              │
│ + validateAddressMatch(?string, ?string, ...): array        │
│ + validateTemplateFieldMatch(array, MainSystem, int): array │
│ + calculateDiscriminatorScore(array...): int                │
│ + calculateFinalConfidence(float, int): int                 │
│ - normalizeDob(?string): ?string                            │
│ - normalizeGender(?string): ?string                         │
│ - normalizeAddress(?string): ?string                        │
│ - fuzzyMatchAddresses(string, string): bool                 │
└─────────────────────────────────────────────────────────────┘
         │ uses
         ├─────────────────────────────────────────┐
         │                                         │
         ↓                                         ↓
┌──────────────────────────────┐    ┌──────────────────────────────┐
│  ConfidenceScoreService      │    │ TemplateFieldPersistenceServ │
├──────────────────────────────┤    ├──────────────────────────────┤
│ + calculateDobScore(...): arr│    │ + getTemplateFields(...): ar │
│ + calculateGenderScore(...): │    │ + getFieldValue(...): ?string│
│ + calculateAddressScore(...) │    │ + getAllFields(...): array   │
│ + calculateTemplateScore(..) │    └──────────────────────────────┘
└──────────────────────────────┘
```

### Data Flow Diagram

```
┌──────────────────┐
│ Uploaded Record  │
└────────┬─────────┘
         │
         ↓
┌──────────────────────────────────────┐
│   DataMatchService.findMatch()       │
├──────────────────────────────────────┤
│ 1. Normalize record                  │
│    - Extract DOB, gender, address    │
│    - Extract template fields         │
│    - Normalize names                 │
│ 2. Query candidates                  │
│ 3. Evaluate matching rules           │
└────────┬─────────────────────────────┘
         │
         ├─────────────────────────────────────────┐
         │                                         │
         ↓                                         ↓
┌──────────────────────┐              ┌──────────────────────┐
│ Existing Rules       │              │ Enhanced Fuzzy Rule  │
│ (100%, 80%, 90%)     │              │ (70-80%)             │
│                      │              │                      │
│ Match found?         │              │ Fuzzy name match?    │
│ ├─ Yes → Return      │              │ ├─ No → Continue     │
│ └─ No → Continue     │              │ └─ Yes → Validate    │
└──────────────────────┘              │    discriminators    │
                                      │    ├─ DOB            │
                                      │    ├─ Gender         │
                                      │    ├─ Address        │
                                      │    └─ Template       │
                                      │    ↓                 │
                                      │    Calculate score   │
                                      │    ↓                 │
                                      │    Return result     │
                                      └──────────────────────┘
         │
         ↓
┌──────────────────────────────────────┐
│   Match Result                       │
├──────────────────────────────────────┤
│ {                                    │
│   status: MATCHED|POSSIBLE|NEW,      │
│   confidence: 0-100,                 │
│   matched_record_id: ?int,           │
│   rule_matched: string,              │
│   field_breakdown: {...}             │
│ }                                    │
└──────────────────────────────────────┘
```



## Implementation Considerations

### DOB Handling

**Supported Formats**:
- YYYY-MM-DD (ISO 8601)
- MM/DD/YYYY (US format)
- DD-MM-YYYY (European format)
- YYYY/MM/DD
- DD/MM/YYYY

**Normalization Logic**:
```php
// Parse various formats and convert to YYYY-MM-DD
// Handle partial dates (e.g., YYYY-MM only)
// Validate date is not in future
// Return null if unparseable
```

**Partial Match Handling**:
- Same year and month, different day: -3% penalty
- Same year, different month: Treat as no match, -5% penalty
- Different year: Treat as no match, -5% penalty

### Gender Normalization

**Mapping**:
- M, Male, male, MALE → M
- F, Female, female, FEMALE → F
- Other, other, OTHER → Other
- Unknown values → Treat as missing

### Address Fuzzy Matching

**Algorithm**: Levenshtein distance with 80% threshold
**Normalization**: Lowercase, trim, remove extra spaces, remove punctuation

### Template Field Matching

**Prioritization**:
- Fields marked as "matching_field" in template definition get priority
- Compare in order of priority
- Stop at max bonus/penalty caps

### Configuration Loading

**Priority Order**:
1. Environment variables (FUZZY_MATCHING_*)
2. Config file (.env or config/fuzzy-matching.php)
3. Hardcoded defaults

**Validation**:
- All numeric values must be 0-100
- Bonuses and penalties must be positive
- Thresholds must be 0-100
- Enabled flags must be boolean

## Security Considerations

### Data Privacy

- Never log sensitive discriminator values (full DOB, full address)
- Log only hashed or masked values for audit trail
- Sanitize all user inputs before comparison
- Use parameterized queries for database access

### Input Validation

- Validate all discriminator values before processing
- Reject values with invalid characters
- Limit string lengths to prevent DoS
- Validate date ranges

### Error Handling

- Don't expose internal error details to users
- Log full errors internally for debugging
- Return generic error messages to API consumers
- Implement rate limiting for matching requests

## Monitoring and Observability

### Metrics to Track

- Matching success rate (% of records matched)
- Average confidence score
- False positive rate (if known)
- Processing time per record
- Cache hit rate
- Database query count per batch

### Logging Strategy

**Info Level**:
- Rule matched for each record
- Batch processing start/completion
- Configuration loaded

**Debug Level**:
- Discriminator values (masked)
- Confidence calculation details
- Cache hits/misses
- Database query details

**Error Level**:
- Parsing failures
- Database errors
- Configuration validation failures
- Unexpected exceptions

### Alerting

- Alert if matching success rate drops below 80%
- Alert if average processing time exceeds 100ms per record
- Alert if database query count exceeds threshold
- Alert on configuration validation failures

## Future Enhancements

### Potential Improvements

1. **Machine Learning**: Train model on historical matches to learn optimal weights
2. **Phonetic Matching**: Add soundex/metaphone for name variations
3. **Fuzzy DOB Matching**: Allow configurable tolerance for date differences
4. **Custom Discriminators**: Allow users to define custom discriminator fields
5. **Weighted Discriminators**: Allow different weights for different discriminators
6. **Batch Optimization**: Parallel processing of records
7. **Incremental Matching**: Cache results for faster re-matching
8. **Analytics Dashboard**: Visualize matching metrics and trends

### Extensibility Points

- Abstract DiscriminatorValidator interface for custom validators
- Plugin system for custom matching rules
- Configurable scoring algorithms
- Custom normalization strategies

