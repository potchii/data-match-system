# Field Breakdown Modal - Null Middle Name Fix

## Problem

The field breakdown modal was not showing the `middle_name` field when the uploaded value was null/empty, even if the existing record had a middle name value.

### Root Cause

In `ConfidenceScoreService.generateBreakdown()`, the method only iterated through fields that existed in `$coreFields`:

```php
// OLD CODE - Only processes fields that exist in uploaded data
foreach ($coreFields as $field => $uploadedValue) {
    // ...
}
```

Since `DataMappingService.mapUploadedData()` only adds fields to `core_fields` if they have values, null/empty middle names were never added to the array, so they never appeared in the breakdown.

## Solution

Modified `generateBreakdown()` to iterate through all known core fields and include any field that has a value in either the uploaded data OR the existing record:

```php
// NEW CODE - Processes all core fields
$allCoreFields = ['uid', 'last_name', 'first_name', 'middle_name', 'suffix', 'birthday', 'gender', 'civil_status', 'address', 'barangay', 'regs_no', 'id_type', 'status', 'category', 'registration_date'];

foreach ($allCoreFields as $field) {
    $uploadedValue = $coreFields[$field] ?? null;
    $existingValue = $existingRecord->$field ?? null;
    
    // Skip if field wasn't uploaded and doesn't exist in existing record
    if ($uploadedValue === null && $existingValue === null) {
        continue;
    }
    
    // Process field...
}
```

## Changes Made

**File**: `app/Services/ConfidenceScoreService.php`

**Method**: `generateBreakdown()`

**Changes**:
1. Added `$allCoreFields` array with all known core field names
2. Changed loop to iterate through `$allCoreFields` instead of `$coreFields`
3. Added logic to skip fields only if BOTH uploaded and existing values are null
4. This ensures middle_name appears in breakdown even when uploaded value is null

## Behavior

### Before Fix
- Uploaded: `middle_name = null`
- Existing: `middle_name = "Michael"`
- **Result**: middle_name NOT shown in breakdown

### After Fix
- Uploaded: `middle_name = null`
- Existing: `middle_name = "Michael"`
- **Result**: middle_name shown as "mismatch" (null vs "Michael")

## Test Coverage

Created `tests/Unit/ConfidenceScoreServiceNullMiddleNameTest.php` with three test cases:

1. **test_breakdown_includes_middle_name_when_uploaded_value_is_null**
   - Verifies middle_name appears when uploaded is null but existing has value
   - Checks status is "mismatch"

2. **test_breakdown_includes_middle_name_when_both_values_are_null**
   - Verifies middle_name is NOT included when both are null
   - Prevents unnecessary fields in breakdown

3. **test_breakdown_includes_middle_name_when_uploaded_has_value**
   - Verifies middle_name appears when uploaded has value
   - Checks status is "match" when values are identical

## Impact

- ✅ Field breakdown modal now shows all relevant fields
- ✅ Users can see when uploaded data is missing a field that exists in the database
- ✅ Transparency: mismatches are now visible even for null values
- ✅ No breaking changes: only adds fields that were previously hidden

## Files Modified

- `app/Services/ConfidenceScoreService.php` - Updated `generateBreakdown()` method
- `tests/Unit/ConfidenceScoreServiceNullMiddleNameTest.php` - New test file
