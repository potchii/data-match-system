# Troubleshooting Guide: Validation Errors

## Introduction

This guide helps you diagnose and fix validation errors when uploading files. The system uses strict validation to ensure data quality, which means your file must match the expected structure exactly.

**Quick Navigation:**
- [Understanding Validation Errors](#understanding-validation-errors)
- [Column Structure Errors](#column-structure-errors)
- [Field Type Validation Errors](#field-type-validation-errors)
- [Common Scenarios](#common-scenarios)
- [Step-by-Step Solutions](#step-by-step-solutions)
- [Prevention Tips](#prevention-tips)

---

## Understanding Validation Errors

### What is Strict Validation?

Strict validation means:
- Your file must contain **exactly** the expected columns
- No missing columns allowed
- No extra columns allowed
- Column names must match (case-insensitive)
- Data values must match field types

### When Validation Happens

Validation occurs **before** your data is processed. If validation fails:
- No data is imported
- You'll see detailed error messages
- Your file remains unchanged
- You can fix the issues and try again

### Reading Error Messages

Error messages follow this format:

```
File Validation Failed

The uploaded file does not match the expected column structure:

• Missing required column: employee_id
• Unexpected column: extra_notes
• Field 'hire_date' must be a valid date

Expected columns: first_name, last_name, birthday, employee_id, department
Found columns: first_name, last_name, birthday, department, extra_notes
```

**Key sections:**
- **Error list**: Specific problems found
- **Expected columns**: What the system expects
- **Found columns**: What your file contains

---

## Column Structure Errors

### Error: "Missing required column"

**Full Error Example:**
```
Missing required column: first_name
```

**What it means:**
Your file is missing a column that the system requires.

**Common causes:**
1. Column was deleted from your file
2. Column name is misspelled
3. Using wrong template
4. Column header is empty

**How to fix:**

**Step 1:** Check if the column exists with a different name
```
Your file has: "FirstName"
System expects: "first_name" or "FirstName" or "firstname" or "fname"
```
The system accepts variations, so check the expected variations list.

**Step 2:** Add the missing column
1. Open your file in Excel or your spreadsheet program
2. Insert a new column
3. Name it exactly as shown in the error (or use an accepted variation)
4. Fill in the data for that column
5. Save the file

**Step 3:** Verify and re-upload
1. Check that the column header matches expected names
2. Ensure the column has data (if required)
3. Upload again

**Example fix:**

Before (❌ Error):
```
| LastName | Birthday   | Department |
|----------|------------|------------|
| Smith    | 1990-01-15 | Sales      |
```

After (✅ Fixed):
```
| FirstName | LastName | Birthday   | Department |
|-----------|----------|------------|------------|
| John      | Smith    | 1990-01-15 | Sales      |
```

---

### Error: "Unexpected column"

**Full Error Example:**
```
Unexpected column: extra_notes
```

**What it means:**
Your file contains a column that's not defined in the template or core fields.

**Common causes:**
1. Extra column added to file
2. Using wrong template
3. Template doesn't include this custom field
4. Empty columns in Excel

**How to fix:**

**Option 1: Remove the extra column**
1. Open your file
2. Delete the column mentioned in the error
3. Save and upload again

**Option 2: Add the column to your template**
1. Go to Templates → Edit your template
2. Scroll to "Custom Fields" section
3. Click "Add Field"
4. Enter the field name exactly as it appears in your file
5. Select appropriate field type
6. Mark as required if needed
7. Save template
8. Upload your file again

**Option 3: Check for empty columns**
Excel sometimes adds empty columns. To remove:
1. Select all columns to the right of your data
2. Right-click → Delete
3. Save the file
4. Upload again

**Example fix:**

Before (❌ Error):
```
| FirstName | LastName | Notes      | Department |
|-----------|----------|------------|------------|
| John      | Smith    | New hire   | Sales      |
```

After Option 1 (✅ Fixed - removed column):
```
| FirstName | LastName | Department |
|-----------|----------|------------|
| John      | Smith    | Sales      |
```

After Option 2 (✅ Fixed - added to template):
```
Template now includes:
- Custom field: "Notes" (type: string, optional)

File remains the same, but now validates successfully.
```

---

### Error: "Unknown column"

**Full Error Example:**
```
Unknown column: frist_name
```

**What it means:**
The column name doesn't match any expected column (likely a typo).

**Common causes:**
1. Misspelled column name
2. Extra spaces in column name
3. Special characters in column name
4. Wrong language/translation

**How to fix:**

**Step 1:** Identify the correct spelling
Look at the "Expected columns" list in the error message.

**Step 2:** Rename the column
1. Open your file
2. Find the misspelled column header
3. Correct the spelling
4. Save the file

**Step 3:** Check for hidden characters
1. Copy the column name from your file
2. Paste into a text editor
3. Look for extra spaces or special characters
4. Clean up and save

**Common misspellings:**

| Wrong ❌ | Correct ✅ |
|---------|-----------|
| frist_name | first_name |
| lst_name | last_name |
| brithday | birthday |
| adress | address |
| employe_id | employee_id |

**Example fix:**

Before (❌ Error):
```
| frist_name | LastName | Birthday   |
|------------|----------|------------|
| John       | Smith    | 1990-01-15 |
```

After (✅ Fixed):
```
| first_name | LastName | Birthday   |
|------------|----------|------------|
| John       | Smith    | 1990-01-15 |
```

---

## Field Type Validation Errors

### Error: "Field must be an integer"

**Full Error Example:**
```
Field 'employee_id' must be an integer
```

**What it means:**
The field expects whole numbers only, but found decimals or text.

**Common causes:**
1. Decimal point in value (e.g., 123.0)
2. Text mixed with numbers (e.g., "ID-123")
3. Thousand separators (e.g., 1,000)
4. Empty cells in required field
5. Excel formatting issues

**How to fix:**

**Step 1:** Identify the problem values
Check the column for:
- Decimal points: `123.0` → `123`
- Text: `ID-123` → `123`
- Commas: `1,000` → `1000`
- Spaces: `123 ` → `123`

**Step 2:** Clean the data
1. Open your file
2. Select the entire column
3. Remove decimals, text, and formatting
4. Ensure all values are whole numbers
5. Save the file

**Step 3:** Fix Excel formatting
1. Select the column
2. Right-click → Format Cells
3. Choose "Number" category
4. Set decimal places to 0
5. Uncheck "Use 1000 separator"
6. Click OK

**Valid vs Invalid values:**

| Invalid ❌ | Valid ✅ | Reason |
|-----------|---------|---------|
| 123.45 | 123 | No decimals allowed |
| 123.0 | 123 | Even .0 is not allowed |
| ID-123 | 123 | Numbers only |
| 1,000 | 1000 | No thousand separators |
| (empty) | 0 or any number | Required field |

**Example fix:**

Before (❌ Error):
```
| FirstName | employee_id |
|-----------|-------------|
| John      | 1,234.0     |
| Jane      | ID-5678     |
```

After (✅ Fixed):
```
| FirstName | employee_id |
|-----------|-------------|
| John      | 1234        |
| Jane      | 5678        |
```

---

### Error: "Field must be a number"

**Full Error Example:**
```
Field 'salary' must be a number
```

**What it means:**
The field expects numeric values (integers or decimals), but found text.

**Common causes:**
1. Currency symbols ($, €, £)
2. Text values ("N/A", "TBD")
3. Thousand separators (1,000.50)
4. Percentage signs (95%)
5. Spaces or special characters

**How to fix:**

**Step 1:** Remove non-numeric characters
- Currency symbols: `$50,000.00` → `50000.00`
- Percentage signs: `95%` → `95` or `0.95`
- Text: `N/A` → leave empty or use `0`
- Commas: `1,234.56` → `1234.56`

**Step 2:** Clean the column
1. Open your file
2. Find and replace:
   - Replace `$` with nothing
   - Replace `,` with nothing
   - Replace `%` with nothing
3. Verify all values are numeric
4. Save the file

**Step 3:** Handle missing values
For optional fields:
- Leave cell empty, OR
- Use `0` if appropriate

For required fields:
- Must provide a numeric value
- Cannot leave empty

**Valid vs Invalid values:**

| Invalid ❌ | Valid ✅ | Reason |
|-----------|---------|---------|
| $50,000.50 | 50000.50 | No currency symbols |
| 1,234.56 | 1234.56 | No thousand separators |
| 95% | 95 or 0.95 | No percentage signs |
| N/A | 0 or (empty) | Must be numeric |
| 3.14.15 | 3.14 | Only one decimal point |

**Example fix:**

Before (❌ Error):
```
| FirstName | salary      |
|-----------|-------------|
| John      | $50,000.00  |
| Jane      | N/A         |
```

After (✅ Fixed):
```
| FirstName | salary   |
|-----------|----------|
| John      | 50000.00 |
| Jane      | 0        |
```

---

### Error: "Field must be a valid date"

**Full Error Example:**
```
Field 'hire_date' must be a valid date
```

**What it means:**
The field expects a date, but the value cannot be parsed as a valid date.

**Common causes:**
1. Invalid dates (Feb 30, Month 13)
2. Ambiguous formats (01/02/03)
3. Text values ("TBD", "Unknown")
4. Wrong date format
5. Excel date serial numbers

**How to fix:**

**Step 1:** Use a standard date format
Recommended format: `YYYY-MM-DD` (e.g., 2024-01-15)

Other accepted formats:
- `MM/DD/YYYY` (e.g., 01/15/2024)
- `DD-MMM-YYYY` (e.g., 15-Jan-2024)
- `Month DD, YYYY` (e.g., January 15, 2024)

**Step 2:** Fix invalid dates
Check for:
- Month > 12: `2024-13-01` → Invalid
- Day > 31: `2024-02-30` → Invalid (Feb only has 28/29 days)
- Year format: `24` → `2024` (use 4 digits)

**Step 3:** Format in Excel
1. Select the date column
2. Right-click → Format Cells
3. Choose "Date" category
4. Select format: `YYYY-MM-DD` or `MM/DD/YYYY`
5. Click OK

**Step 4:** Handle missing dates
For optional fields:
- Leave cell empty

For required fields:
- Must provide a valid date
- Cannot use "TBD" or "Unknown"

**Valid vs Invalid values:**

| Invalid ❌ | Valid ✅ | Reason |
|-----------|---------|---------|
| 2024-13-01 | 2024-01-01 | Month 13 doesn't exist |
| 2024-02-30 | 2024-02-28 | Feb 30 doesn't exist |
| 01/02/03 | 2003-01-02 | Ambiguous, use 4-digit year |
| TBD | (empty) | Must be valid date or empty |
| 44927 | 2024-01-15 | Excel serial number |

**Example fix:**

Before (❌ Error):
```
| FirstName | hire_date  |
|-----------|------------|
| John      | 2024-13-01 |
| Jane      | TBD        |
| Bob       | 01/02/03   |
```

After (✅ Fixed):
```
| FirstName | hire_date  |
|-----------|------------|
| John      | 2024-01-01 |
| Jane      |            |
| Bob       | 2003-01-02 |
```

---

### Error: "Field must be true/false, yes/no, or 1/0"

**Full Error Example:**
```
Field 'is_active' must be true/false, yes/no, or 1/0
```

**What it means:**
The field expects a boolean value, but found something else.

**Common causes:**
1. Using "on/off" instead of accepted values
2. Using "t/f" instead of "true/false"
3. Using numbers other than 0 or 1
4. Misspellings ("ture" instead of "true")
5. Text values ("active", "inactive")

**How to fix:**

**Step 1:** Use accepted values only

**Accepted values:**

| For TRUE | For FALSE |
|----------|-----------|
| true | false |
| TRUE | FALSE |
| True | False |
| 1 | 0 |
| yes | no |
| YES | NO |
| Yes | No |
| y | n |
| Y | N |

**Step 2:** Replace invalid values
1. Open your file
2. Find and replace:
   - "on" → "yes" or "1"
   - "off" → "no" or "0"
   - "active" → "yes" or "1"
   - "inactive" → "no" or "0"
3. Save the file

**Step 3:** Be consistent
Pick one format and use it throughout:
- Recommended: Use `1` for true, `0` for false (simplest)
- Alternative: Use `yes` for true, `no` for false

**Valid vs Invalid values:**

| Invalid ❌ | Valid ✅ | Reason |
|-----------|---------|---------|
| on | 1 or yes | Use accepted values |
| off | 0 or no | Use accepted values |
| active | 1 or yes | Use accepted values |
| t | true or y | Single letter except y/n |
| 2 | 1 or 0 | Only 1 and 0 are valid numbers |
| maybe | 1 or 0 | Must be binary choice |

**Example fix:**

Before (❌ Error):
```
| FirstName | is_active |
|-----------|-----------|
| John      | on        |
| Jane      | off       |
| Bob       | active    |
```

After (✅ Fixed):
```
| FirstName | is_active |
|-----------|-----------|
| John      | 1         |
| Jane      | 0         |
| Bob       | 1         |
```

---

### Error: "Field is required"

**Full Error Example:**
```
Field 'department' is required
```

**What it means:**
A required field has an empty or null value.

**Common causes:**
1. Empty cells in required column
2. Spaces only (looks filled but isn't)
3. Formula errors showing as blank
4. Hidden characters

**How to fix:**

**Step 1:** Find empty cells
1. Open your file
2. Select the column mentioned in the error
3. Use Find & Replace (Ctrl+H)
4. Find: (leave empty)
5. Replace: (nothing)
6. This highlights empty cells

**Step 2:** Fill in missing values
1. For each empty cell, add appropriate data
2. Ensure no cells are left blank
3. Check for cells with only spaces

**Step 3:** Verify data completeness
1. Sort the column to group empty values
2. Fill in all empty cells
3. Save the file

**Example fix:**

Before (❌ Error):
```
| FirstName | department |
|-----------|------------|
| John      | Sales      |
| Jane      |            |  ← Empty (required field)
| Bob       |            |  ← Empty (required field)
```

After (✅ Fixed):
```
| FirstName | department |
|-----------|------------|
| John      | Sales      |
| Jane      | Marketing  |
| Bob       | IT         |
```

---

## Common Scenarios

### Scenario 1: "Multiple errors in my file"

**Problem:**
```
File Validation Failed

• Missing required column: first_name
• Unexpected column: extra_field
• Field 'age' must be an integer
```

**Solution approach:**
Fix errors in this order:

1. **Column structure first** (missing/extra columns)
   - Add missing columns
   - Remove or add extra columns to template
   
2. **Then fix data types** (field validation)
   - Clean up integer fields
   - Format date fields
   - Fix boolean values

**Why this order?**
Column structure errors prevent the file from being read. Fix these first, then address data quality issues.

---

### Scenario 2: "File worked before, now it doesn't"

**Possible causes:**
1. Template was modified
2. Using different template
3. File structure changed
4. New required fields added

**Solution:**
1. Check which template you're using
2. Compare with previous successful upload
3. Review template's expected columns
4. Check if template was recently edited

---

### Scenario 3: "Excel shows dates correctly, but validation fails"

**Problem:**
Excel displays: `1/15/2024`
Validation error: `Field 'birthday' must be a valid date`

**Cause:**
Excel might be storing dates as serial numbers or in a format the system can't parse.

**Solution:**
1. Select the date column
2. Format as Text first
3. Manually enter dates in YYYY-MM-DD format
4. Or use Excel formula: `=TEXT(A2,"YYYY-MM-DD")`
5. Copy and paste values (not formulas)
6. Save as CSV or XLSX

---

### Scenario 4: "Template has too many fields"

**Problem:**
Your file only has core fields, but template expects many custom fields.

**Solution:**

**Option 1:** Upload without template
- If you only have core fields (first_name, last_name, etc.)
- Don't select a template when uploading
- System validates against core fields only

**Option 2:** Create a simpler template
- Create a new template with fewer custom fields
- Only include fields your file actually has
- Use this template for uploads

**Option 3:** Add columns to your file
- Add the missing custom field columns
- Fill with appropriate data
- Upload with the template

---

### Scenario 5: "Can't find which row has the error"

**Problem:**
Error says: `Field 'age' must be an integer`
But doesn't specify which row.

**Solution:**

**Step 1:** Sort the column
1. Open your file
2. Select the problem column
3. Sort A-Z
4. Look for non-numeric values grouped together

**Step 2:** Use Excel filtering
1. Select the column header
2. Click Filter button
3. Look for text values in a number column
4. Or use "Text Filters" → "Contains" to find letters

**Step 3:** Use Find & Replace
1. Press Ctrl+H
2. Find: `[a-zA-Z]` (enable regex if available)
3. This finds cells with letters
4. Fix those cells

---

## Step-by-Step Solutions

### Solution 1: Starting from scratch

**When to use:** You're creating a new file for upload

**Steps:**

1. **Get the expected columns**
   - If using template: View template details to see expected columns
   - If no template: Use core fields (first_name, last_name, birthday, etc.)

2. **Create your file**
   - Open Excel or Google Sheets
   - Add column headers in first row
   - Use exact names from expected columns list
   - Fill in your data

3. **Validate data types**
   - Integer columns: Whole numbers only
   - Decimal columns: Numbers with or without decimals
   - Date columns: Use YYYY-MM-DD format
   - Boolean columns: Use 1/0 or yes/no
   - String columns: Any text

4. **Check for required fields**
   - Ensure all required columns have values
   - No empty cells in required columns

5. **Save and upload**
   - Save as .xlsx or .csv
   - Upload with appropriate template (or no template)

---

### Solution 2: Fixing an existing file

**When to use:** You have a file that's failing validation

**Steps:**

1. **Read the error message carefully**
   - Note all errors listed
   - Check "Expected columns" vs "Found columns"

2. **Fix column structure issues**
   - Add any missing columns
   - Remove or handle extra columns
   - Fix misspelled column names

3. **Fix data type issues**
   - Clean integer columns (remove decimals, text)
   - Format date columns (use YYYY-MM-DD)
   - Fix boolean columns (use 1/0 or yes/no)
   - Remove currency symbols from number columns

4. **Fill required fields**
   - Find empty cells in required columns
   - Add appropriate values

5. **Save and re-upload**
   - Save your changes
   - Upload again
   - Check for any remaining errors

---

### Solution 3: Creating a matching template

**When to use:** You have a file and want to create a template for it

**Steps:**

1. **List your file's columns**
   - Open your file
   - Write down all column names

2. **Identify core vs custom fields**
   - Core fields: first_name, last_name, birthday, address, etc.
   - Custom fields: Everything else

3. **Create the template**
   - Go to Templates → Create New Template
   - Enter template name and description
   - Map core fields to your column names

4. **Add custom fields**
   - For each custom column:
     - Click "Add Field"
     - Enter exact column name from your file
     - Select appropriate field type
     - Mark as required if needed

5. **Save and test**
   - Save the template
   - Upload your file using this template
   - Fix any validation errors

---

## Prevention Tips

### Tip 1: Use a template file

Create an empty template file with just the headers:

```
| first_name | last_name | birthday   | employee_id | department |
|------------|-----------|------------|-------------|------------|
|            |           |            |             |            |
```

Save this as "Upload_Template.xlsx" and use it for all future uploads.

**Benefits:**
- Ensures correct column names
- Prevents typos
- Consistent structure
- Easy to share with team

---

### Tip 2: Validate before uploading

**Quick checks:**
1. ✓ All required columns present
2. ✓ No extra columns (unless in template)
3. ✓ Column names spelled correctly
4. ✓ No empty cells in required fields
5. ✓ Dates in consistent format
6. ✓ Numbers without formatting (no $, commas)
7. ✓ Boolean values use 1/0 or yes/no

---

### Tip 3: Clean data in Excel first

**Before saving:**
1. Remove empty rows and columns
2. Trim whitespace (use TRIM function)
3. Format dates consistently
4. Remove currency symbols from numbers
5. Check for hidden characters
6. Verify required fields are filled

---

### Tip 4: Test with small files first

**Best practice:**
1. Create a test file with 2-3 rows
2. Upload to verify structure
3. If successful, upload full file
4. If errors, fix in small file first
5. Apply fixes to full file

**Benefits:**
- Faster error detection
- Easier to debug
- Less time wasted on large files

---

### Tip 5: Document your templates

**For each template, document:**
- Template name and purpose
- Expected columns and types
- Required vs optional fields
- Date format to use
- Boolean format to use (1/0 or yes/no)
- Example file

**Share with your team** so everyone uses the same format.

---

### Tip 6: Use consistent naming

**Column naming best practices:**
- Use lowercase with underscores: `employee_id`
- Be descriptive: `hire_date` not `date1`
- Avoid special characters
- Avoid spaces (use underscores instead)
- Be consistent across all files

---

### Tip 7: Save as XLSX, not XLS

**Recommended format:** `.xlsx` (Excel 2007+)

**Why:**
- Better compatibility
- Handles larger files
- More reliable date handling
- Better Unicode support

**Avoid:** `.xls` (older Excel format)

---

## Quick Reference

### Error Types Summary

| Error Type | Quick Fix |
|------------|-----------|
| Missing required column | Add the column to your file |
| Unexpected column | Remove it or add to template |
| Unknown column | Fix spelling/typo |
| Must be an integer | Remove decimals and text |
| Must be a number | Remove $, commas, text |
| Must be a valid date | Use YYYY-MM-DD format |
| Must be true/false | Use 1/0 or yes/no |
| Field is required | Fill in empty cells |

### Accepted Field Values

| Field Type | Valid Examples | Invalid Examples |
|------------|----------------|------------------|
| String | Any text | (none - all valid) |
| Integer | 42, -10, 0 | 3.14, "abc", 1,000 |
| Decimal | 3.14, 100, -5.5 | "abc", $99.99 |
| Date | 2024-01-15, 01/15/2024 | 2024-13-01, "TBD" |
| Boolean | 1, 0, yes, no, true, false | on, off, maybe |

### Core Field Variations

| Field | Accepted Column Names |
|-------|----------------------|
| First Name | firstname, FirstName, first_name, fname |
| Last Name | surname, Surname, lastname, LastName, last_name |
| Middle Name | middlename, MiddleName, middle_name, mname |
| Birthday | dob, DOB, birthday, Birthday, birthdate, birth_date |
| Gender | sex, Sex, gender, Gender |
| Address | address, Address, street, Street |
| Barangay | barangay, Barangay, brgydescription, BrgyDescription |

---

## Still Having Issues?

### Check the logs

If you have access to application logs:
1. Look for detailed validation errors
2. Check for file reading errors
3. Review template configuration

### Contact support

When contacting support, provide:
1. The complete error message
2. Template name (if using one)
3. Sample of your file (first few rows, remove sensitive data)
4. File format (.xlsx, .csv, etc.)
5. What you've already tried

### Additional resources

- **User Guide**: See `USER_GUIDE.md` for complete template usage
- **Field Types**: See `FIELD_TYPES.md` for detailed field type documentation
- **API Documentation**: See `API_DOCUMENTATION.md` for programmatic access
- **Requirements**: See `requirements.md` for system requirements

---

## Summary

**Key takeaways:**

1. **Read error messages carefully** - they tell you exactly what's wrong
2. **Fix column structure first** - then fix data types
3. **Use standard formats** - YYYY-MM-DD for dates, 1/0 for booleans
4. **Test with small files** - catch errors early
5. **Create template files** - prevent future errors
6. **Be consistent** - use same formats across all uploads

**Remember:** Strict validation is your friend! It catches errors early and ensures your data is clean and reliable.

Good luck with your uploads! 🚀
