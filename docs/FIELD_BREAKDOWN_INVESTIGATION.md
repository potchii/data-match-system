# Field Breakdown Modal - Missing Middle Names Investigation

## Summary
Some middle names are NOT shown in the field breakdown modal because they are **intentionally filtered out** during the data mapping process when they are empty or null.

## Root Cause Analysis

### Data Flow
1. **Upload** → Excel file with columns
2. **RecordImport.collection()** → Processes each row
3. **DataMappingService.mapUploadedData()** → Extracts and normalizes data
4. **ConfidenceScoreService.generateBreakdown()** → Builds field breakdown for modal

### The Issue: Filtering at DataMappingService Level

In `DataMappingService.mapUploadedData()` (lines 26-35):

```php
// Extract middle name
$middleName = $this->extractMiddleName($row);

if ($middleName !== null) {
    $coreFields['middle_name'] = $middleName;  // ← Only added if NOT null
}
```

The `extractMiddleName()` method (lines 217-220) returns `null` if:
- The field is empty
- The field contains null-like values: `'null'`, `'NULL'`, `'N/A'`, `'na'`, `'none'`, `'-'`, etc.

### Consequence: Missing from Field Breakdown

In `ConfidenceScoreService.generateBreakdown()` (lines 60-120):

```php
$coreFields = $uploadedData['core_fields'] ?? [];

// Process core fields
foreach ($coreFields as $field => $uploadedValue) {
    // ... builds field breakdown
}
```

**If `middle_name` was never added to `core_fields` by DataMappingService, it won't appear in the field breakdown.**

## Example Scenario

**Uploaded CSV:**
```
last_name,first_name,middle_name,birthday
Smith,John,
Doe,Jane,Marie,1990-01-15
```

**What happens:**
- Row 1 (John Smith): `middle_name` is empty → NOT added to `core_fields` → **NOT shown in field breakdown**
- Row 2 (Jane Doe): `middle_name` = "Marie" → Added to `core_fields` → **Shown in field breakdown**

## Is This Intentional?

**YES** - This is by design:

1. **Data Quality**: Empty/null fields are filtered out to avoid storing meaningless data
2. **Consistency**: Only fields with actual values are tracked
3. **Performance**: Reduces database bloat with empty records
4. **Matching Logic**: The matching rules only compare fields that have values

## Why This Matters for Field Breakdown

The field breakdown modal shows **only the fields that were actually present in the uploaded data**. This is useful because:

- Users can see exactly what data was uploaded
- Empty fields don't clutter the comparison view
- It's clear which fields were missing from the upload

## Null-Like Values That Are Filtered

The following values are treated as "empty" and filtered out:
- `null` (PHP null)
- `''` (empty string)
- `'null'`, `'NULL'`, `'Null'`
- `'n/a'`, `'N/A'`
- `'na'`, `'NA'`
- `'none'`, `'NONE'`, `'None'`
- `'-'`, `'--'`, `'---'`

## Recommendation

This behavior is **correct and intentional**. The field breakdown modal is working as designed:

- It shows fields that were **actually provided** in the upload
- It doesn't show fields that were **missing or empty**
- This prevents confusion about what data was compared

If you want to see ALL core fields (including empty ones) in the field breakdown, that would require a design change to:
1. Always include all core fields in `core_fields` (even if null)
2. Update the field breakdown modal to display them
3. Update the matching logic to handle null comparisons

**Current behavior is recommended** because it keeps the field breakdown focused and relevant to the actual data being compared.
