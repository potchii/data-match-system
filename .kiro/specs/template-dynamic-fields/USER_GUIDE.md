# Template Fields User Guide

## Introduction

Welcome to the Template Fields feature! This guide will help you understand how to create templates with custom fields and upload files that match your template structure.

### What are Template Fields?

Template fields allow you to define additional columns beyond the standard system fields (like first name, last name, birthday, etc.). This is useful when your data files contain extra information specific to your organization, such as:

- Employee ID numbers
- Department names
- Custom reference codes
- Additional dates or status fields
- Any other data unique to your workflow

### Strict Validation

The system uses **strict validation** to ensure data quality. This means:

- Your uploaded file must contain **exactly** the columns expected by your template
- No missing columns allowed
- No extra columns allowed
- Column names must match exactly (case-insensitive)

This strict approach prevents data errors and ensures consistency across all uploads.

---

## Getting Started

### Understanding Field Types

When creating custom fields, you can choose from five data types:

| Field Type | Description | Examples |
|------------|-------------|----------|
| **String** | Any text value | Names, codes, descriptions |
| **Integer** | Whole numbers only | Employee ID: 12345, Count: 100 |
| **Decimal** | Numbers with decimals | Salary: 50000.50, Score: 95.5 |
| **Date** | Date values | 2024-01-15, 01/15/2024 |
| **Boolean** | True/false values | Active: yes/no, Verified: true/false |

### Required vs Optional Fields

- **Required fields**: Must have a value in every row of your file
- **Optional fields**: Can be left empty if no data is available

---

## Creating a Template with Custom Fields

Follow these steps to create a new template with custom fields:

### Step 1: Navigate to Templates

1. Log in to the system
2. Click on **Templates** in the navigation menu
3. Click the **Create New Template** button

### Step 2: Enter Template Details

1. **Template Name**: Give your template a descriptive name (e.g., "Employee Import 2024")
2. **Description**: Add notes about what this template is for (optional)

### Step 3: Map Core Fields

Map the standard system fields to your file's column names:

- **First Name**: The column in your file containing first names
- **Last Name**: The column in your file containing last names
- **Birthday**: The column containing birth dates
- And so on...

**Example:**
- If your Excel file has a column called "Given Name", map it to "First Name"
- If your file has "Surname", map it to "Last Name"

### Step 4: Add Custom Fields

This is where you define additional columns beyond the core fields.

1. Scroll to the **Custom Fields** section
2. Click the **Add Field** button
3. For each custom field, enter:
   - **Field Name**: The exact column name from your file (e.g., "employee_id")
   - **Field Type**: Select the appropriate data type (String, Integer, etc.)
   - **Required**: Check this box if the field must have a value in every row

**Example Custom Fields:**

```
Field Name: employee_id
Field Type: Integer
Required: ✓ (checked)

Field Name: department
Field Type: String
Required: ✓ (checked)

Field Name: hire_date
Field Type: Date
Required: ✗ (unchecked)

Field Name: is_active
Field Type: Boolean
Required: ✓ (checked)
```

### Step 5: Save Your Template

1. Review all your mappings and custom fields
2. Click **Save Template**
3. You'll see a success message confirming your template was created

---

## Editing Existing Templates

### Modifying Template Fields

1. Go to **Templates** in the navigation menu
2. Find your template in the list
3. Click the **Edit** button
4. Make your changes:
   - Update core field mappings
   - Add new custom fields
   - Remove existing custom fields
   - Change field types or required status
5. Click **Save Template**

### Important Notes

- Changing a template affects all future uploads using that template
- Previous uploads are not affected by template changes
- If you remove a custom field, it will no longer be validated in future uploads

---

## Uploading Files with Templates

### Step 1: Prepare Your File

Before uploading, ensure your file matches your template exactly:

1. **Check column names**: They must match your template's expected columns
2. **Include all required fields**: Don't leave out any columns
3. **Don't add extra columns**: Remove any columns not in your template
4. **Use correct data types**: Ensure values match the field type (numbers for integers, dates for date fields, etc.)

### Step 2: Upload Your File

1. Navigate to the **Upload** page
2. Click **Choose File** and select your Excel or CSV file
3. Select your template from the **Template** dropdown
4. Click **Upload**

### Step 3: Review Results

If successful:
- You'll see a confirmation message
- Your data will be processed and available in the system

If validation fails:
- You'll see an error message listing the problems
- Fix the issues in your file and try again

---

## Understanding Validation Errors

When your file doesn't match the template, you'll see detailed error messages. Here's how to interpret and fix them:

### Missing Required Column

**Error:** `Missing required column: employee_id`

**What it means:** Your file is missing a column that the template expects.

**How to fix:**
1. Open your file in Excel or your spreadsheet program
2. Add a column with the exact name shown in the error
3. Fill in the data for that column
4. Save and upload again

### Unexpected Column

**Error:** `Unexpected column: extra_field`

**What it means:** Your file has a column that's not defined in the template.

**How to fix:**
1. Either remove the extra column from your file, OR
2. Edit your template to add this column as a custom field
3. Save and upload again

### Type Mismatch Errors

**Error:** `Field 'employee_id' must be an integer`

**What it means:** The data in that column doesn't match the expected type.

**How to fix:**
1. Check the values in that column
2. For integers: Remove any decimal points or text
3. For dates: Use a valid date format (YYYY-MM-DD or MM/DD/YYYY)
4. For booleans: Use yes/no, true/false, or 1/0
5. Save and upload again

### Example Error Display

```
File Validation Failed

The uploaded file does not match the expected column structure:

• Missing required column: employee_id
• Unexpected column: extra_notes
• Field 'hire_date' must be a valid date

Expected columns: first_name, last_name, birthday, employee_id, department, hire_date, is_active
Found columns: first_name, last_name, birthday, department, hire_date, is_active, extra_notes
```

---

## Common Use Cases

### Use Case 1: Employee Import with Custom Fields

**Scenario:** You need to import employee data with employee IDs and department information.

**Solution:**
1. Create a template named "Employee Import"
2. Map core fields: first_name, last_name, birthday
3. Add custom fields:
   - `employee_id` (Integer, Required)
   - `department` (String, Required)
   - `hire_date` (Date, Optional)
4. Prepare your Excel file with these exact columns
5. Upload using the "Employee Import" template

### Use Case 2: Membership Data with Status Flags

**Scenario:** You're importing member data with active/inactive status and membership numbers.

**Solution:**
1. Create a template named "Membership Import"
2. Map core fields as needed
3. Add custom fields:
   - `membership_number` (String, Required)
   - `is_active` (Boolean, Required)
   - `renewal_date` (Date, Optional)
4. In your file, use yes/no or true/false for the is_active column
5. Upload using the "Membership Import" template

### Use Case 3: Basic Import Without Custom Fields

**Scenario:** You only need to import standard information (names, birthdays, addresses).

**Solution:**
1. You don't need to create a template!
2. Simply prepare your file with the core system columns
3. Upload without selecting a template
4. The system will validate against core fields only

---

## Best Practices

### File Preparation

1. **Use consistent column names**: Stick to lowercase with underscores (e.g., `employee_id`, not `Employee ID`)
2. **Clean your data first**: Remove empty rows and columns before uploading
3. **Test with a small file**: Upload a file with just a few rows first to verify your template works
4. **Keep a template file**: Save an empty Excel file with just the headers as a template for future uploads

### Template Management

1. **Use descriptive names**: Name templates clearly (e.g., "Q1 2024 Employee Import" instead of "Template1")
2. **Document your templates**: Use the description field to note what each template is for
3. **Version your templates**: If you need to change a template significantly, create a new one instead of editing the old one
4. **Test after changes**: After editing a template, test it with a sample file

### Data Quality

1. **Validate dates**: Ensure dates are in a consistent format (YYYY-MM-DD is recommended)
2. **Check number formats**: Remove currency symbols, commas, or other formatting from number fields
3. **Standardize boolean values**: Pick one format (yes/no, true/false, or 1/0) and use it consistently
4. **Trim whitespace**: Remove extra spaces before and after values

---

## Troubleshooting Common Issues

### Problem: "File validation failed" but I can't see what's wrong

**Solution:**
1. Check the detailed error message - it lists all issues
2. Compare your file's column headers with the expected columns shown in the error
3. Look for typos in column names (even a single character difference matters)
4. Check for extra spaces in column names

### Problem: Template shows different columns than my file

**Solution:**
1. You may be using the wrong template
2. Verify you selected the correct template from the dropdown
3. Or create a new template that matches your file structure

### Problem: Date fields keep failing validation

**Solution:**
1. Dates must be in a recognizable format
2. Try using YYYY-MM-DD format (e.g., 2024-01-15)
3. Ensure Excel hasn't auto-formatted your dates strangely
4. Check that the column is actually set as "Date" type in your template

### Problem: Boolean fields not accepting my values

**Solution:**
Boolean fields accept these values:
- **True values**: true, 1, yes, y (case-insensitive)
- **False values**: false, 0, no, n (case-insensitive)

Use one of these formats consistently in your file.

### Problem: Integer field failing but values look correct

**Solution:**
1. Check for decimal points (integers can't have decimals)
2. Remove any commas or formatting (use 1000, not 1,000)
3. Ensure there's no text mixed with numbers
4. Check for hidden characters or spaces

### Problem: Can't upload file - keeps saying "extra column"

**Solution:**
1. Your file has a column not defined in the template
2. Either remove that column from your file, OR
3. Edit your template to add that column as a custom field
4. Make sure you're not including empty columns (Excel sometimes adds blank columns)

---

## Field Type Reference

### String Fields

- **Accepts:** Any text, numbers, symbols
- **Examples:** "John Doe", "ABC-123", "Department A"
- **Tips:** Most flexible type, use when data doesn't fit other types

### Integer Fields

- **Accepts:** Whole numbers only (no decimals)
- **Examples:** 123, 4567, 0, -10
- **Rejects:** 123.45, "abc", 1,000 (with comma)
- **Tips:** Use for IDs, counts, quantities

### Decimal Fields

- **Accepts:** Numbers with or without decimals
- **Examples:** 123.45, 100, 0.5, -10.25
- **Rejects:** "abc", 1,000.50 (with comma)
- **Tips:** Use for prices, scores, measurements

### Date Fields

- **Accepts:** Valid date formats
- **Examples:** 2024-01-15, 01/15/2024, 15-Jan-2024
- **Rejects:** "not a date", 99/99/9999, empty string (if required)
- **Tips:** YYYY-MM-DD format is most reliable

### Boolean Fields

- **Accepts:** 
  - True: true, 1, yes, y
  - False: false, 0, no, n
- **Case insensitive:** "YES", "Yes", "yes" all work
- **Rejects:** maybe, unknown, blank (if required)
- **Tips:** Pick one format and use it consistently

---

## Frequently Asked Questions

### Can I change a template after I've used it to upload files?

Yes, you can edit templates at any time. Changes only affect future uploads, not data already imported.

### What happens if I delete a template?

The template and all its custom field definitions are deleted. However, data previously uploaded using that template remains in the system.

### Can I use the same template for different files?

Yes! That's the purpose of templates. Create one template and reuse it for all files with the same structure.

### Do I need a template for every upload?

No. If your file only contains core system fields (first name, last name, birthday, etc.), you can upload without a template.

### Can I have multiple templates with the same custom field names?

Yes. Each template is independent. You can have "employee_id" in multiple templates.

### What's the maximum number of custom fields I can add?

There's no strict limit, but keep it reasonable. Too many fields can make files difficult to manage.

### Can I export a template to share with others?

Currently, templates are user-specific. Other users need to create their own templates, but they can use the same field definitions.

### Why is the system so strict about column matching?

Strict validation prevents data errors, ensures consistency, and makes it clear when something is wrong with your file. This protects data quality.

---

## Getting Help

If you encounter issues not covered in this guide:

1. **Check the error message carefully** - it usually tells you exactly what's wrong
2. **Review your template settings** - ensure fields are configured correctly
3. **Verify your file format** - compare column names and data types
4. **Contact support** - provide the error message and a sample of your file (with sensitive data removed)

---

## Quick Reference Card

### Creating a Template
1. Templates → Create New Template
2. Enter name and description
3. Map core fields
4. Add custom fields (name, type, required)
5. Save

### Uploading with a Template
1. Upload page → Choose File
2. Select template from dropdown
3. Click Upload
4. Review results

### Fixing Validation Errors
- **Missing column**: Add it to your file
- **Extra column**: Remove it or add to template
- **Type mismatch**: Fix data format in that column
- **Misnamed column**: Correct the spelling/capitalization

### Field Types Quick Guide
- **String**: Any text
- **Integer**: Whole numbers (123)
- **Decimal**: Numbers with decimals (123.45)
- **Date**: Date values (2024-01-15)
- **Boolean**: yes/no, true/false, 1/0

---

## Summary

Template fields give you the flexibility to import custom data while maintaining strict validation for data quality. By following this guide, you can:

- Create templates with custom fields tailored to your needs
- Upload files confidently knowing they'll be validated
- Quickly identify and fix any issues with your data
- Maintain consistent data quality across all imports

Remember: The strict validation is your friend - it catches errors early and ensures your data is clean and reliable!
