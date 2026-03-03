# PR: Matching Algorithm Improvements, Export Duplicates & UI Theme Update

## Overview

This PR addresses critical matching accuracy issues, adds bulk export functionality for duplicate records, and improves the user interface with a refreshed theme. The changes focus on preventing false-positive exact matches, providing better data analysis tools, and enhancing the overall user experience.

## Changes Summary

### 🎯 Matching Algorithm Improvements

#### 1. Single-Letter Middle Name Fix
**Problem:** Records with single-letter middle names (e.g., "D", "D.", "C", "C.") were incorrectly matching as EXACT (100% confidence) when they should be POSSIBLE DUPLICATE (90% or lower).

**Examples:**
- Anson Agahon (D) vs Anson Agahon (D.) → Was: MATCHED (100%), Now: POSSIBLE DUPLICATE (90%)
- Rosita Aguilar (C) vs Rosita Aguilar (C.) → Was: MATCHED (100%), Now: POSSIBLE DUPLICATE (90%)

**Root Cause:** After normalization, "D." and "D" both become "d", causing them to match exactly. Single-letter middle names are typically initials/abbreviations and don't provide sufficient certainty for exact matches.

**Solution:** Modified `ExactMatchRule` to reject records where either the uploaded or candidate middle name is ≤ 1 character in length.

**Impact:**
- Records with single-letter middle names now fall through to lower-confidence rules:
  - `PartialMatchWithDobRule` (90%) - if first + last + DOB match
  - `FullNameMatchRule` (80%) - if first + last + middle match (no DOB)
  - `FuzzyNameMatchRule` (70%+) - if names are similar

#### 2. Removed Registration Number Matching
**Change:** Deleted `RegsNoMatchRule` from the codebase as per requirement to exclude `regs_no` from matching logic.

**Note:** This rule was not in the active matching chain, so no functional impact on current matching behavior.

### 📊 Export Duplicates Feature

#### Bulk CSV Export for Duplicate Records
**Feature:** Added comprehensive export functionality to download all duplicate records with their matched base records in CSV format.

**Use Case:** Users can now export a detailed report of all flagged duplicates (MATCHED and POSSIBLE DUPLICATE) along with their corresponding base records for external analysis, reporting, or manual review.

**Key Features:**
- **Smart Filtering:** Respects current page filters (batch_id, status)
- **Default Behavior:** Excludes NEW RECORD entries by default (only exports MATCHED and POSSIBLE DUPLICATE)
- **Dynamic Filename:** Includes timestamp, batch ID, and status in filename for easy organization
- **Comprehensive Data:** 19 columns including:
  - Row ID, Batch ID
  - Uploaded record details (first, middle, last name, record ID)
  - Match status and confidence score
  - Matched base record details (first, middle, last name, UID, row ID)
  - Source information (batch ID, file name)
  - Field statistics (matched fields, total fields)
  - Specific field matches (birthday, gender, address)

**UI Integration:**
- Green "Export Duplicates" button next to filter form in results page
- Consistent with new light green theme
- Accessible from main results view

**Example Filename:**
```
duplicates-report-batch123-matched-2026-03-03_143022.csv
duplicates-report-all-batches-duplicates-2026-03-03_143022.csv
```

**Route:** `GET /results/export-duplicates`

### 🎨 UI Theme Update

#### Light Green Theme
Updated the application theme to a clean, professional light green and white color scheme:

**Color Palette:**
- Primary Green: `#10b981` (emerald-500)
- Light Green Background: `#f0fdf4` (green-50)
- Success Green: `#22c55e` (green-500)
- Hover States: `#059669` (emerald-600)

**Status Badge Colors:**
- MATCHED: Green badge (`bg-green-100`, `text-green-800`)
- POSSIBLE DUPLICATE: Yellow badge (`bg-yellow-100`, `text-yellow-800`)
- NEW RECORD: Red badge (`bg-red-100`, `text-red-800`)

**File:** `public/css/green-theme.css`

### 🐛 Bug Fixes

#### 1. Whitespace-Only Middle Name/Birthday Bug
**Previous Fix:** ExactMatchRule was using `empty()` which returns `false` for whitespace-only strings like `' '` or `'  '`, causing false matches.

**Solution:** Replaced `empty()` with `trim() === ''` to properly handle whitespace-only strings.

#### 2. Results Page Row Numbering
**Fix:** Row IDs now start from 1 and maintain correct sequential numbering across pagination and filters using `$results->firstItem() + $index`.

#### 3. DiagnoseMatches Command Column Name
**Fix:** Updated to use correct column name `match_status` instead of `status`.

## Files Changed

### Core Matching Logic
- `app/Services/MatchingRules/ExactMatchRule.php` - Added single-letter middle name validation
- `app/Services/MatchingRules/RegsNoMatchRule.php` - **DELETED**

### Export Feature
- `app/Http/Controllers/ResultsController.php` - Added `exportDuplicates()` and `generateDuplicatesCSV()` methods
- `routes/web.php` - Added `GET /results/export-duplicates` route
- `resources/views/pages/results.blade.php` - Added "Export Duplicates" button

### Tests
- `tests/Unit/ExactMatchRuleSingleLetterTest.php` - **NEW** - 5 comprehensive tests
- `tests/Unit/ExactMatchRuleBugTest.php` - Existing tests still passing
- `tests/Feature/ExportDuplicatesTest.php` - **NEW** - 7 comprehensive tests

### UI/Theme
- `public/css/green-theme.css` - **NEW** - Light green theme
- `resources/views/pages/results.blade.php` - Row numbering fix + export button

### Database
- `database/factories/MainSystemFactory.php` - Fixed `suffix` default to empty string instead of null

### Documentation
- `docs/SINGLE_LETTER_MIDDLE_NAME_FIX.md` - **NEW** - Detailed fix documentation
- `docs/MATCHING_ALGORITHM.md` - Updated with new requirements
- `docs/EXACT_MATCH_BUG_FIX.md` - Previous whitespace fix documentation

### Utilities
- `app/Console/Commands/DiagnoseMatches.php` - Column name fix

## Testing

### Unit Tests
All tests passing:
```bash
php artisan test --filter=ExactMatchRuleSingleLetterTest
# 5 passed (9 assertions)

php artisan test --filter=ExactMatchRuleBugTest
# 5 passed (7 assertions)

php artisan test --filter=ExportDuplicatesTest
# 7 passed (20+ assertions)
```

### Test Coverage

#### Matching Rules
- ✅ Rejects single-letter middle names in uploaded data
- ✅ Rejects single-letter middle names in candidate data
- ✅ Rejects when both have single-letter middle names
- ✅ Accepts two-letter middle names (2+ characters)
- ✅ Accepts full middle names
- ✅ Handles whitespace-only middle names
- ✅ Handles null birthdays correctly

#### Export Duplicates
- ✅ Exports duplicates as CSV with correct headers
- ✅ Filters export by batch_id
- ✅ Filters export by status
- ✅ Excludes NEW RECORD entries by default
- ✅ Includes field breakdown details (matched fields, birthday, gender, address)
- ✅ Requires authentication
- ✅ Generates dynamic filename with filters and timestamp

### Manual Testing Required
1. Purge existing data: `php artisan data:purge --keep-users --keep-templates`
2. Upload seed data (156 records)
3. Upload test dataset (60 records)
4. Verify expected results: 20 exact matches, 15 fuzzy matches, 25 new records
5. Test export functionality:
   - Click "Export Duplicates" button
   - Verify CSV downloads with correct filename
   - Check CSV contains all expected columns
   - Apply filters (batch_id, status) and verify filtered export

## ExactMatchRule Requirements (Updated)

For a record to match via ExactMatchRule (100% confidence), ALL of the following must be true:

1. ✅ First name matches (normalized, case-insensitive)
2. ✅ Last name matches (normalized, case-insensitive)
3. ✅ Middle name matches (normalized, case-insensitive, EXACT equality)
4. ✅ Birthday matches (YYYY-MM-DD format)
5. ✅ BOTH records have non-null, non-empty, non-whitespace middle names
6. ✅ BOTH records have non-null, non-empty, non-whitespace birthdays
7. ✅ **NEW:** BOTH middle names are at least 2 characters long

## Migration Notes

- No database migrations required
- This is a business logic change only
- Existing match results are not affected
- Only new uploads will use the updated matching logic

## Breaking Changes

None. This is a bug fix that improves matching accuracy.

## Rollback Plan

If issues arise:
1. Revert `ExactMatchRule.php` changes
2. Existing tests will still pass (they test the old behavior)
3. No database rollback needed

## Related Issues

- Fixes: Single-letter middle names matching as exact (100%)
- Fixes: Whitespace-only middle names/birthdays causing false matches
- Fixes: Results page row numbering across filters
- Removes: Unused `regs_no` matching logic

## Checklist

- [x] Code follows project coding standards
- [x] All existing tests pass
- [x] New tests added for new functionality
- [x] Documentation updated
- [x] No breaking changes
- [x] Manual testing instructions provided
- [x] Theme changes are consistent across all pages

## Screenshots

### Before
- Anson Agahon (D) vs Anson Agahon (D.) → MATCHED (100%)
- Rosita Aguilar (C) vs Rosita Aguilar (C.) → MATCHED (100%)

### After
- Anson Agahon (D) vs Anson Agahon (D.) → POSSIBLE DUPLICATE (90%)
- Rosita Aguilar (C) vs Rosita Aguilar (C.) → POSSIBLE DUPLICATE (90%)

### Theme Update
- Clean light green and white color scheme
- Professional status badges (Green/Yellow/Red)
- Improved visual hierarchy and readability
