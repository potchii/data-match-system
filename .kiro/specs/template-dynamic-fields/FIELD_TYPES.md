# Field Types and Validation Rules

## Overview

Template fields support 5 data types with specific validation rules. This document explains each type, their validation rules, acceptable values, and common mistakes to avoid.

## Field Types

### 1. String

**Description:** Accepts any text value without restrictions.

**Validation Rules:**
- Any text value is accepted
- Empty values are allowed (unless field is marked as required)
- No length restrictions
- Special characters, numbers, and spaces are all valid

**Valid Examples:**
```
John Doe
123 Main Street
user@example.com
(555) 123-4567
Mixed123!@#Text
```

**Invalid Examples:**
- None - all text values are valid for string fields

**Common Use Cases:**
- Names (first name, last name, middle name)
- Addresses
- Email addresses
- Phone numbers
- Notes or comments
- Any free-form text

**Edge Cases:**
- Empty string: Valid if field is not required
- Very long text: Accepted (no length limit in validation)
- Unicode characters: Accepted
- Line breaks: Accepted

---

### 2. Integer

**Description:** Accepts whole numbers without decimal points.

**Validation Rules:**
- Must be numeric
- Must NOT contain a decimal point
- Can be positive or negative
- Leading zeros are accepted but treated as integers

**Valid Examples:**
```
0
1
42
-15
1000
999999
```

**Invalid Examples:**
```
3.14          ❌ Contains decimal point
12.0          ❌ Contains decimal point (even if .0)
abc           ❌ Not numeric
1,000         ❌ Contains comma
1.5e2         ❌ Scientific notation not supported
```

**Common Use Cases:**
- Age
- Quantity
- Count
- ID numbers
- Year (e.g., 2024)
- Whole number measurements

**Edge Cases:**
- Zero: Valid
- Negative numbers: Valid
- Leading zeros (e.g., "007"): Valid, treated as 7
- Very large numbers: Valid (no upper limit in validation)

**Common Mistakes:**
- Using decimal values (use 'decimal' type instead)
- Including thousand separators (1,000)
- Using non-numeric characters

---

### 3. Decimal

**Description:** Accepts numeric values including decimals.

**Validation Rules:**
- Must be numeric
- Can contain decimal point
- Can be positive or negative
- Integers are also valid (decimal point is optional)

**Valid Examples:**
```
0
1
3.14
-2.5
0.001
1000.50
42
```

**Invalid Examples:**
```
abc           ❌ Not numeric
12.34.56      ❌ Multiple decimal points
1,234.56      ❌ Contains comma
$99.99        ❌ Contains currency symbol
```

**Common Use Cases:**
- Prices
- Measurements (height, weight)
- Percentages (e.g., 98.6)
- Ratings (e.g., 4.5)
- Scientific measurements
- Financial amounts

**Edge Cases:**
- Integer values: Valid (e.g., "42" is valid for decimal field)
- Zero: Valid
- Negative numbers: Valid
- Very small decimals (e.g., 0.0001): Valid
- Many decimal places: Valid (no precision limit in validation)

**Common Mistakes:**
- Including currency symbols ($, €, etc.)
- Using thousand separators (1,234.56)
- Using multiple decimal points

---

### 4. Date

**Description:** Accepts date values in various formats.

**Validation Rules:**
- Must be parseable as a date by PHP's `strtotime()` function
- Accepts multiple date formats
- Must represent a valid calendar date
- Time components are ignored

**Valid Examples:**
```
2024-01-15
01/15/2024
15-Jan-2024
January 15, 2024
2024/01/15
15.01.2024
```

**Invalid Examples:**
```
2024-13-01    ❌ Invalid month (13)
2024-02-30    ❌ Invalid day (Feb 30)
abc           ❌ Not a date
12345         ❌ Ambiguous number
tomorrow      ❌ Relative dates not recommended
```

**Supported Date Formats:**
- ISO 8601: `2024-01-15`
- US format: `01/15/2024`
- European format: `15/01/2024`
- Month names: `15-Jan-2024`, `January 15, 2024`
- Dots: `15.01.2024`
- Various separators: `-`, `/`, `.`

**Common Use Cases:**
- Birth dates
- Registration dates
- Expiration dates
- Event dates
- Deadlines

**Edge Cases:**
- Leap year dates (Feb 29): Valid only in leap years
- Historical dates: Valid (e.g., 1900-01-01)
- Future dates: Valid
- 1970-01-01: Valid (special handling to avoid Unix epoch confusion)

**Common Mistakes:**
- Using invalid dates (e.g., Feb 30)
- Ambiguous formats (e.g., 01/02/03 - unclear which is day/month/year)
- Using text that isn't a date
- Inconsistent date formats within the same file

**Best Practices:**
- Use ISO 8601 format (YYYY-MM-DD) for consistency
- Be consistent with date format throughout your file
- Avoid ambiguous formats like MM/DD/YY

---

### 5. Boolean

**Description:** Accepts true/false values in multiple representations.

**Validation Rules:**
- Must be one of the accepted boolean representations
- Case-insensitive
- Whitespace is trimmed

**Valid Examples:**
```
true / false
TRUE / FALSE
True / False
1 / 0
yes / no
YES / NO
Yes / No
y / n
Y / N
```

**Invalid Examples:**
```
2             ❌ Only 1 and 0 are valid numbers
on / off      ❌ Not in accepted list
t / f         ❌ Single letters except y/n
maybe         ❌ Not a boolean value
```

**Accepted Values:**

| True Values | False Values |
|-------------|--------------|
| true        | false        |
| TRUE        | FALSE        |
| True        | False        |
| 1           | 0            |
| yes         | no           |
| YES         | NO           |
| Yes         | No           |
| y           | n            |
| Y           | N            |

**Common Use Cases:**
- Active/inactive status
- Yes/no questions
- Enabled/disabled flags
- Checkbox values
- Binary choices

**Edge Cases:**
- Empty value: Valid if field is not required
- Mixed case (e.g., "YeS"): Valid (case-insensitive)
- Whitespace (e.g., " yes "): Valid (trimmed)

**Common Mistakes:**
- Using "on/off" instead of accepted values
- Using "t/f" instead of "true/false" or "y/n"
- Using numbers other than 0 or 1
- Misspelling (e.g., "ture" instead of "true")

**Best Practices:**
- Be consistent throughout your file
- Use "1/0" for simplicity and clarity
- Avoid mixing representations (don't use both "yes/no" and "1/0" in same file)

---

## Required vs Optional Fields

### Required Fields

When a field is marked as **required**:
- Empty values are NOT allowed
- Null values are NOT allowed
- Blank cells will cause validation errors
- Error message: "Field '{field_name}' is required"

### Optional Fields

When a field is **optional** (not required):
- Empty values are allowed
- Null values are allowed
- Blank cells are valid
- No validation is performed on empty values

---

## Field Name Rules

Field names must follow these rules:

**Valid Characters:**
- Lowercase letters (a-z)
- Uppercase letters (A-Z)
- Numbers (0-9)
- Underscores (_)

**Valid Examples:**
```
employee_id
firstName
age
address_line_1
custom_field_123
```

**Invalid Examples:**
```
employee-id       ❌ Contains hyphen
first name        ❌ Contains space
email@address     ❌ Contains special character
field#1           ❌ Contains hash symbol
```

**Best Practices:**
- Use snake_case (employee_id) or camelCase (employeeId)
- Be descriptive but concise
- Avoid abbreviations that aren't obvious
- Use consistent naming throughout your templates

---

## Validation Error Messages

When validation fails, you'll see specific error messages:

### Column Structure Errors

```
Missing required column: first_name
```
The file is missing a required column. Add the column to your file.

```
Unexpected column: extra_field
```
The file contains a column not defined in the template. Remove it or add it to the template.

```
Unknown column: frist_name
```
The file contains a misspelled or unrecognized column name. Check spelling.

### Type Validation Errors

```
Field 'age' must be an integer
```
The value in the 'age' field contains a decimal point or non-numeric characters.

```
Field 'price' must be a number
```
The value in the 'price' field is not numeric.

```
Field 'birth_date' must be a valid date
```
The value in the 'birth_date' field cannot be parsed as a date.

```
Field 'active' must be true/false, yes/no, or 1/0
```
The value in the 'active' field is not a recognized boolean value.

```
Field 'employee_id' is required
```
A required field has an empty or null value.

---

## Common Validation Scenarios

### Scenario 1: Age Field

**Field Type:** Integer  
**Required:** Yes

**Valid Values:**
```
18
25
42
0
```

**Invalid Values:**
```
25.5          ❌ Use integer type, not decimal
twenty-five   ❌ Must be numeric
(empty)       ❌ Required field cannot be empty
```

---

### Scenario 2: Price Field

**Field Type:** Decimal  
**Required:** Yes

**Valid Values:**
```
19.99
100
0.50
1000.00
```

**Invalid Values:**
```
$19.99        ❌ Remove currency symbol
19,99         ❌ Use period for decimal, not comma
1,000.00      ❌ Remove thousand separator
free          ❌ Must be numeric
```

---

### Scenario 3: Birth Date Field

**Field Type:** Date  
**Required:** Yes

**Valid Values:**
```
1990-05-15
05/15/1990
15-May-1990
May 15, 1990
```

**Invalid Values:**
```
1990-13-15    ❌ Invalid month
1990-02-30    ❌ Invalid day
15/05/90      ❌ Ambiguous year (use 4 digits)
(empty)       ❌ Required field cannot be empty
```

---

### Scenario 4: Active Status Field

**Field Type:** Boolean  
**Required:** No

**Valid Values:**
```
1
0
yes
no
true
false
(empty)       ✓ Optional field can be empty
```

**Invalid Values:**
```
active        ❌ Use yes/no or 1/0
2             ❌ Only 1 or 0 are valid numbers
on            ❌ Use yes/no or 1/0
```

---

## Troubleshooting Guide

### Problem: "Missing required column" error

**Cause:** Your file doesn't have all the required columns.

**Solution:**
1. Check the template definition to see which columns are required
2. Add the missing column to your file
3. Ensure column names match exactly (case-sensitive)

---

### Problem: "Unexpected column" error

**Cause:** Your file has columns not defined in the template.

**Solution:**
1. Remove the extra column from your file, OR
2. Add the column to your template definition
3. Ensure you're using the correct template

---

### Problem: Integer validation fails

**Cause:** The value contains a decimal point or non-numeric characters.

**Solution:**
1. Remove decimal points (use 42, not 42.0)
2. Remove non-numeric characters (use 1000, not 1,000)
3. If you need decimals, change the field type to 'decimal'

---

### Problem: Date validation fails

**Cause:** The date format isn't recognized or the date is invalid.

**Solution:**
1. Use a standard format like YYYY-MM-DD (2024-01-15)
2. Check for invalid dates (Feb 30, month 13, etc.)
3. Use 4-digit years (2024, not 24)
4. Be consistent with date format throughout the file

---

### Problem: Boolean validation fails

**Cause:** The value isn't one of the accepted boolean representations.

**Solution:**
1. Use one of: true/false, yes/no, y/n, 1/0
2. Check for typos (ture instead of true)
3. Don't use on/off or other variations

---

## Best Practices

### 1. Choose the Right Type

- Use **string** for text that doesn't need validation
- Use **integer** for whole numbers (age, count, year)
- Use **decimal** for numbers with decimals (price, measurements)
- Use **date** for calendar dates
- Use **boolean** for yes/no, true/false values

### 2. Be Consistent

- Use the same date format throughout your file
- Use the same boolean representation (don't mix yes/no with 1/0)
- Use consistent field naming conventions

### 3. Validate Before Upload

- Check your file has all required columns
- Check column names match exactly
- Verify data types match field definitions
- Test with a small sample file first

### 4. Handle Empty Values

- Mark fields as required only if they must have a value
- Use optional fields for data that might be missing
- Empty values in optional fields are valid

### 5. Document Your Templates

- Add clear descriptions to your templates
- Document expected formats (especially for dates)
- Provide examples of valid values
- Share templates with your team

---

## Quick Reference Table

| Type    | Accepts                          | Example Valid Values           | Common Mistakes                |
|---------|----------------------------------|--------------------------------|--------------------------------|
| string  | Any text                         | "John Doe", "123 Main St"      | None - all text is valid       |
| integer | Whole numbers                    | 42, -15, 0                     | Using decimals (3.14)          |
| decimal | Numbers with/without decimals    | 3.14, 42, -2.5                 | Currency symbols ($99.99)      |
| date    | Valid dates in various formats   | 2024-01-15, 01/15/2024         | Invalid dates (Feb 30)         |
| boolean | true/false, yes/no, 1/0, y/n     | 1, yes, true                   | Using on/off or other values   |

---

## Additional Resources

- **User Guide:** See `USER_GUIDE.md` for complete template usage instructions
- **API Documentation:** See task 22.4 for API endpoint documentation
- **Requirements:** See `requirements.md` for detailed system requirements
- **Design:** See `design.md` for technical implementation details

---

## Support

If you encounter validation errors that aren't covered in this guide:

1. Check the error message for specific details
2. Review the field type definitions above
3. Verify your data matches the expected format
4. Check the template definition for field requirements
5. Test with a small sample file to isolate the issue

For persistent issues, check the application logs for detailed validation information.
