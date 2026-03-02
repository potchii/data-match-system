# Enhanced Fuzzy Matching Troubleshooting Guide

## Common Issues and Solutions

### Issue: No Matches Found

**Symptoms:**
- Records that should match are returning NEW_RECORD status
- Confidence scores are very low

**Diagnosis Steps:**

1. Check fuzzy name similarity:
```php
// Enable debug logging
Log::debug('Checking name similarity', [
    'uploaded_first' => $uploadedData['first_name'],
    'uploaded_last' => $uploadedData['last_name'],
    'candidate_first' => $candidate->first_name,
    'candidate_last' => $candidate->last_name,
]);
```

2. Verify name normalization:
```php
// Check normalized names match
$uploadedNormalized = strtolower(trim($uploadedData['first_name']));
$candidateNormalized = strtolower(trim($candidate->first_name));
```

3. Check threshold setting:
```bash
# Default is 85, try lowering to 80
FUZZY_MATCHING_NAME_THRESHOLD=80
```

**Solutions:**

1. Lower name similarity threshold:
```bash
FUZZY_MATCHING_NAME_THRESHOLD=80
```

2. Check for special characters or encoding issues:
```php
// Verify data is properly encoded
$cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
```

3. Verify candidate records exist in database:
```php
// Check if candidates are being loaded
$candidates = MainSystem::where('last_name_normalized', 'LIKE', 'doe%')->get();
```

### Issue: Too Many False Positives

**Symptoms:**
- Records with different people are being matched
- Confidence scores are too high for dissimilar records

**Diagnosis Steps:**

1. Review field_breakdown for matched records:
```php
$result = $dataMatchService->findMatch($uploadedData);
Log::debug('Match breakdown', $result['field_breakdown']);
```

2. Check discriminator values:
```php
// Verify discriminators are being extracted
Log::debug('Discriminators', [
    'dob' => $normalizedData['dob'],
    'gender' => $normalizedData['gender'],
    'address' => $normalizedData['address'],
]);
```

3. Review confidence calculation:
```php
// Check if bonuses are too high
$baseScore = 70;
$totalBonus = 10 + 5 + 5; // DOB + Gender + Address
$finalScore = $baseScore + $totalBonus; // 90
```

**Solutions:**

1. Increase name similarity threshold:
```bash
FUZZY_MATCHING_NAME_THRESHOLD=90
```

2. Increase discriminator bonuses:
```bash
FUZZY_MATCHING_DOB_BONUS_EXACT=15
FUZZY_MATCHING_GENDER_BONUS=10
```

3. Enable gender mismatch rejection:
```bash
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

4. Increase penalties for missing discriminators:
```bash
FUZZY_MATCHING_DOB_PENALTY_MISSING=10
FUZZY_MATCHING_GENDER_PENALTY_MISSING=10
```

### Issue: Gender Mismatch Rejecting Valid Matches

**Symptoms:**
- Records with matching names and other discriminators are rejected
- Gender values are inconsistent in source data

**Diagnosis Steps:**

1. Check gender normalization:
```php
// Verify gender values are normalized correctly
$genders = ['M', 'Male', 'MALE', 'F', 'Female', 'FEMALE'];
foreach ($genders as $gender) {
    Log::debug('Normalized gender', ['input' => $gender, 'output' => normalizeGender($gender)]);
}
```

2. Review gender values in database:
```php
// Check what gender values exist
$genders = MainSystem::distinct()->pluck('gender');
```

3. Check configuration:
```bash
# Verify gender rejection is enabled
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

**Solutions:**

1. Disable gender mismatch rejection:
```bash
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=false
```

2. Clean up gender values in database:
```php
// Normalize all gender values
MainSystem::all()->each(function ($record) {
    $record->gender = normalizeGender($record->gender);
    $record->save();
});
```

3. Add gender normalization to import process:
```php
$uploadedData['gender'] = normalizeGender($uploadedData['gender']);
```

### Issue: DOB Parsing Failures

**Symptoms:**
- DOB values are being treated as missing
- Warnings in logs about DOB parsing

**Diagnosis Steps:**

1. Check DOB format:
```php
// Verify DOB format
Log::debug('DOB format', ['dob' => $uploadedData['dob']]);
```

2. Check supported formats:
```php
$formats = [
    'Y-m-d',      // 2023-01-15
    'd/m/Y',      // 15/01/2023
    'm/d/Y',      // 01/15/2023
    'Y/m/d',      // 2023/01/15
    'd-m-Y',      // 15-01-2023
];
```

3. Check for future dates:
```php
// Verify DOB is not in future
$dob = Carbon::parse($uploadedData['dob']);
if ($dob->isFuture()) {
    Log::warning('DOB is in future', ['dob' => $dob]);
}
```

**Solutions:**

1. Standardize DOB format in import:
```php
// Convert to YYYY-MM-DD format
$dob = Carbon::parse($uploadedData['dob'])->format('Y-m-d');
```

2. Add DOB validation to import process:
```php
if (!Carbon::parse($uploadedData['dob'])->isPast()) {
    throw new ValidationException('DOB must be in the past');
}
```

3. Check for invalid dates:
```php
// Validate date is real (e.g., not 2023-02-30)
try {
    $dob = Carbon::createFromFormat('Y-m-d', $uploadedData['dob'], 'strict');
} catch (InvalidFormatException $e) {
    Log::warning('Invalid DOB format', ['dob' => $uploadedData['dob']]);
}
```

### Issue: Address Fuzzy Matching Not Working

**Symptoms:**
- Address values that are similar are not matching
- Address bonus is not being applied

**Diagnosis Steps:**

1. Check address normalization:
```php
// Verify address normalization
$normalized = strtolower(trim($address));
$normalized = preg_replace('/\s+/', ' ', $normalized);
Log::debug('Normalized address', ['input' => $address, 'output' => $normalized]);
```

2. Check address similarity:
```php
// Calculate similarity manually
similar_text($address1, $address2, $percent);
Log::debug('Address similarity', ['percent' => $percent]);
```

3. Check threshold:
```bash
# Default is 80, try lowering to 75
FUZZY_MATCHING_ADDRESS_THRESHOLD=75
```

**Solutions:**

1. Lower address similarity threshold:
```bash
FUZZY_MATCHING_ADDRESS_THRESHOLD=75
```

2. Clean up address values:
```php
// Remove punctuation and extra spaces
$address = preg_replace('/[^a-zA-Z0-9\s]/', '', $address);
$address = preg_replace('/\s+/', ' ', $address);
```

3. Increase address bonus:
```bash
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=10
```

### Issue: Template Field Matching Not Working

**Symptoms:**
- Template fields are not being compared
- Template field bonus is not being applied

**Diagnosis Steps:**

1. Check template fields are being extracted:
```php
// Verify template fields are extracted
Log::debug('Template fields', $normalizedData['template_fields']);
```

2. Check template field values exist:
```php
// Verify template field values in database
$values = TemplateFieldValue::where('main_system_id', $candidateId)
    ->where('template_id', $templateId)
    ->get();
```

3. Check template ID is passed:
```php
// Verify template ID is passed to match method
$result = $dataMatchService->findMatch($uploadedData, $templateId);
```

**Solutions:**

1. Verify template ID is passed:
```php
// Ensure template ID is provided
$templateId = 1; // or get from request
$result = $dataMatchService->findMatch($uploadedData, $templateId);
```

2. Check template field names match:
```php
// Verify field names are consistent
$uploadedFields = ['field_1' => 'value1'];
$databaseFields = ['field_1' => 'value1']; // Must match exactly
```

3. Enable template field discriminator:
```bash
FUZZY_MATCHING_TEMPLATE_ENABLED=true
```

### Issue: Performance Problems

**Symptoms:**
- Matching is slow (>100ms per record)
- Batch processing takes too long

**Diagnosis Steps:**

1. Check candidate cache is working:
```php
// Verify cache is being used
Log::debug('Candidate cache', [
    'size' => $this->candidateCache->count(),
    'hit_rate' => $cacheHits / $totalLookups,
]);
```

2. Check database queries:
```php
// Enable query logging
DB::enableQueryLog();
$result = $dataMatchService->findMatch($uploadedData);
Log::debug('Queries', DB::getQueryLog());
```

3. Check performance metrics:
```php
// Review performance metrics in result
Log::debug('Performance', $result['performance']);
```

**Solutions:**

1. Verify candidate caching is enabled:
```php
// Check cache is populated
$this->loadCandidates($normalizedRecords);
```

2. Optimize database queries:
```php
// Use indexed columns for candidate lookup
// Ensure indexes exist on first_name_normalized, last_name_normalized
```

3. Reduce number of candidates:
```bash
# Increase name similarity threshold to reduce candidates
FUZZY_MATCHING_NAME_THRESHOLD=90
```

4. Disable unnecessary discriminators:
```bash
# Disable if not needed
FUZZY_MATCHING_TEMPLATE_ENABLED=false
```

### Issue: Configuration Validation Errors

**Symptoms:**
- Application fails to start
- InvalidArgumentException thrown

**Diagnosis Steps:**

1. Check error message:
```
Configuration error: discriminators.dob.bonus_exact_match must be between 0 and 100, got 150
```

2. Verify environment variables:
```bash
# Check all FUZZY_MATCHING_* variables
env | grep FUZZY_MATCHING
```

3. Check configuration file:
```php
// Verify config file syntax
config('fuzzy-matching');
```

**Solutions:**

1. Fix invalid configuration values:
```bash
# Ensure all values are between 0 and 100
FUZZY_MATCHING_DOB_BONUS_EXACT=10  # Not 150
```

2. Verify boolean values:
```bash
# Use true/false for boolean values
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true  # Not 1
```

3. Check confidence bounds:
```bash
# Ensure min <= base <= max
FUZZY_MATCHING_MIN_CONFIDENCE=0
FUZZY_MATCHING_BASE_CONFIDENCE=70
FUZZY_MATCHING_MAX_CONFIDENCE=100
```

## Debug Mode

Enable debug logging for detailed troubleshooting:

```php
// In your logging configuration
'channels' => [
    'fuzzy_matching' => [
        'driver' => 'single',
        'path' => storage_path('logs/fuzzy-matching.log'),
        'level' => 'debug',
    ],
],
```

Then log to this channel:

```php
Log::channel('fuzzy_matching')->debug('Matching details', [
    'uploaded_data' => $uploadedData,
    'candidates' => $candidates->count(),
    'result' => $result,
]);
```

## Performance Profiling

Profile matching performance:

```php
$startTime = microtime(true);

$result = $dataMatchService->findMatch($uploadedData);

$elapsedTime = (microtime(true) - $startTime) * 1000;
Log::info('Matching performance', [
    'elapsed_ms' => $elapsedTime,
    'confidence' => $result['confidence'],
]);
```

## Monitoring Checklist

- [ ] Monitor false positive rate
- [ ] Monitor false negative rate
- [ ] Monitor average confidence score
- [ ] Monitor matching success rate
- [ ] Monitor processing time per record
- [ ] Monitor database query count
- [ ] Monitor cache hit rate
- [ ] Monitor error rate

## Getting Help

If you encounter issues not covered here:

1. Check the logs for error messages
2. Enable debug logging
3. Review the field_breakdown in match results
4. Check configuration values
5. Verify data format and quality
6. Profile performance metrics
7. Review the implementation guide
