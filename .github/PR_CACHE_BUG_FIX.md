# Fix: Prevent Duplicate Records in Same Batch Upload

## Type
`fix(matching): prevent duplicate records within same batch upload`

## Summary
Fixed a critical bug where uploading a file with duplicate rows (same name + DOB) would create multiple records in the database instead of matching them together. The issue was caused by a stale candidate cache that wasn't updated after inserting new records during batch processing.

## Problem Description

### The Bug
When processing an Excel file with duplicate rows:
1. **Row 1:** "John Doe, 1990-01-15" → No match found → Inserted as NEW RECORD
2. **Row 2:** "John Doe, 1990-01-15" → **Should match Row 1, but didn't** → Inserted as duplicate

### Root Cause
The `DataMatchService` loaded candidate records once at the start of processing and cached them. When a new record was inserted during processing, the cache was never updated, causing subsequent identical rows to fail matching and create duplicates.

```php
// Before: Cache loaded once, never refreshed
public function findMatch(array $uploadedData): array
{
    if ($this->candidateCache->isEmpty()) {
        $this->loadCandidates(collect([$normalized]));
    }
    // ❌ New records inserted here don't update cache
}
```

## Changes Made

### 1. Fixed Stale Cache Bug
**File:** `app/Services/DataMatchService.php`

Added cache update after inserting new records:

```php
public function insertNewRecord(array $data): MainSystem
{
    // ... create record ...
    $newRecord = MainSystem::create($data);
    
    // ✅ Add to cache immediately
    $this->candidateCache->push($newRecord);
    
    return $newRecord;
}
```

**Impact:** Duplicate rows in the same upload batch now correctly match against each other.

### 2. Created Duplicate Cleanup Command
**File:** `app/Console/Commands/RemoveDuplicateRecords.php`

New Artisan command to identify and remove existing duplicates:

```bash
# Preview duplicates
php artisan duplicates:remove --dry-run

# Remove duplicates (keeps oldest record)
php artisan duplicates:remove
```

**Features:**
- Finds duplicate groups by name + DOB
- Keeps oldest record (lowest ID)
- Dry-run mode for safe preview
- Detailed output showing what will be deleted

### 3. Created Database Purge Command
**File:** `app/Console/Commands/PurgeData.php`

New Artisan command for testing and development:

```bash
# Purge all data
php artisan data:purge

# Keep user accounts
php artisan data:purge --keep-users
```

**Features:**
- Truncates all data tables
- Optional user account preservation
- Safe foreign key handling
- Confirmation prompt

### 4. Documentation Updates
**File:** `README.md`

Added comprehensive documentation for new commands:
- Usage examples
- What each command does
- When to use them
- Safety features

### 5. GitHub Issues Created
**Files:** 
- `.github/ISSUE_SECURE_FILE_STORAGE.md` - Proposed secure file storage implementation
- `.github/ISSUE_DYNAMIC_SCHEMA_SUPPORT.md` - Dynamic schema mapping feature

## Testing

### Reproduction Steps (Before Fix)
1. Create Excel file with 2 identical rows: "Test User, 1990-01-01"
2. Upload file
3. **Result:** 2 records created in database ❌

### Verification Steps (After Fix)
1. Create Excel file with 2 identical rows: "Test User, 1990-01-01"
2. Upload file
3. **Result:** 
   - Row 1: NEW RECORD (inserted)
   - Row 2: MATCHED (100% confidence, linked to Row 1) ✅

### Cleanup Verification
```bash
# Check for duplicates
php artisan duplicates:remove --dry-run

# Remove them
php artisan duplicates:remove
```

## Impact

### Before
- ❌ Duplicate rows in uploads created duplicate database records
- ❌ No way to clean up existing duplicates
- ❌ Data integrity issues
- ❌ Inflated record counts

### After
- ✅ Duplicate rows correctly match within same batch
- ✅ Command to identify and remove existing duplicates
- ✅ Maintained data integrity
- ✅ Accurate record counts

## Breaking Changes
None. This is a bug fix that improves existing functionality.

## Migration Required
No new migrations. Existing duplicates can be cleaned up using:
```bash
php artisan duplicates:remove
```

## Related Issues
- Fixes duplicate record creation bug
- Improves data quality
- Adds data management tooling

## Checklist
- [x] Bug identified and reproduced
- [x] Root cause analyzed
- [x] Fix implemented and tested
- [x] Cleanup commands created
- [x] Documentation updated
- [x] No breaking changes
- [x] Ready for review

## Commit Message
```
fix(matching): prevent duplicate records within same batch upload

- Update candidate cache after inserting new records
- Add duplicates:remove command for cleanup
- Add data:purge command for testing
- Update README with new commands
- Create GitHub issues for future enhancements

Fixes bug where identical rows in uploaded file created duplicate
database records instead of matching together.
```
