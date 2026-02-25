# Template Field Examples

## Introduction

This document provides practical, real-world examples of template configurations for various use cases. Each example includes the template definition, sample data file structure, and common variations.

Use these examples as starting points for creating your own templates.

---

## Example 1: Employee Onboarding

### Use Case
HR department importing new employee data with employee IDs, departments, and hire dates.

### Template Configuration

**Template Name:** Employee Onboarding 2024

**Core Field Mappings:**
- First Name → `first_name`
- Last Name → `last_name`
- Birthday → `date_of_birth`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| employee_id | Integer | Yes | Unique employee identifier |
| department | String | Yes | Department name |
| hire_date | Date | Yes | Date of hire |
| position | String | Yes | Job title |
| is_full_time | Boolean | Yes | Full-time or part-time status |
| salary | Decimal | No | Annual salary (optional) |

### Sample Excel File

```
first_name | last_name | date_of_birth | employee_id | department | hire_date  | position           | is_full_time | salary
-----------|-----------|---------------|-------------|------------|------------|--------------------|--------------|---------
John       | Smith     | 1990-05-15    | 1001        | Sales      | 2024-01-15 | Sales Manager      | yes          | 75000.00
Maria      | Garcia    | 1985-08-22    | 1002        | IT         | 2024-01-20 | Software Engineer  | yes          | 85000.00
David      | Chen      | 1992-03-10    | 1003        | Marketing  | 2024-02-01 | Marketing Coord    | no           |
```

### Validation Rules

- `employee_id`: Must be a whole number (1001, 1002, etc.)
- `department`: Any text accepted
- `hire_date`: Must be valid date (YYYY-MM-DD format recommended)
- `position`: Any text accepted
- `is_full_time`: Must be yes/no, true/false, or 1/0
- `salary`: Optional - can be left empty; if provided, must be numeric

---

## Example 2: School Student Registration

### Use Case
School registrar importing student data with student IDs, grade levels, and parent contact information.

### Template Configuration

**Template Name:** Student Registration Fall 2024

**Core Field Mappings:**
- First Name → `student_first_name`
- Last Name → `student_last_name`
- Birthday → `birth_date`
- Address → `home_address`
- Barangay → `barangay`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| student_id | String | Yes | Student ID number (may contain letters) |
| grade_level | Integer | Yes | Grade level (1-12) |
| section | String | Yes | Class section |
| parent_name | String | Yes | Parent/guardian full name |
| parent_phone | String | Yes | Parent contact number |
| has_special_needs | Boolean | No | Special education requirements |
| enrollment_date | Date | Yes | Date of enrollment |

### Sample Excel File

```
student_first_name | student_last_name | birth_date | home_address      | barangay    | student_id | grade_level | section | parent_name    | parent_phone | has_special_needs | enrollment_date
-------------------|-------------------|------------|-------------------|-------------|------------|-------------|---------|----------------|--------------|-------------------|----------------
Emma               | Johnson           | 2012-06-15 | 123 Main St       | Poblacion   | SY2024-001 | 7           | A       | Robert Johnson | 555-0101     | no                | 2024-08-15
Liam               | Williams          | 2011-09-20 | 456 Oak Ave       | San Jose    | SY2024-002 | 8           | B       | Sarah Williams | 555-0102     | yes               | 2024-08-15
Sophia             | Brown             | 2013-03-10 | 789 Pine Rd       | Santa Cruz  | SY2024-003 | 6           | A       | Michael Brown  | 555-0103     |                   | 2024-08-16
```

### Notes

- `student_id` is String type because it contains letters (SY2024-001)
- `grade_level` is Integer for numeric grades
- `has_special_needs` is optional - can be left blank
- Phone numbers are String type (not Integer) to preserve formatting

---

## Example 3: Healthcare Patient Registration

### Use Case
Clinic importing patient data with medical record numbers, insurance information, and emergency contacts.

### Template Configuration

**Template Name:** Patient Registration System

**Core Field Mappings:**
- First Name → `patient_first_name`
- Last Name → `patient_last_name`
- Middle Name → `patient_middle_name`
- Birthday → `date_of_birth`
- Gender → `gender`
- Address → `street_address`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| medical_record_number | String | Yes | Unique patient MRN |
| blood_type | String | No | Blood type (A+, O-, etc.) |
| has_insurance | Boolean | Yes | Insurance coverage status |
| insurance_provider | String | No | Insurance company name |
| emergency_contact_name | String | Yes | Emergency contact person |
| emergency_contact_phone | String | Yes | Emergency contact number |
| last_visit_date | Date | No | Date of last visit |
| is_active_patient | Boolean | Yes | Active patient status |

### Sample Excel File

```
patient_first_name | patient_last_name | patient_middle_name | date_of_birth | gender | street_address    | medical_record_number | blood_type | has_insurance | insurance_provider | emergency_contact_name | emergency_contact_phone | last_visit_date | is_active_patient
-------------------|-------------------|---------------------|---------------|--------|-------------------|-----------------------|------------|---------------|--------------------|-----------------------|-------------------------|-----------------|------------------
James              | Anderson          | Michael             | 1975-04-12    | Male   | 321 Elm Street    | MRN-2024-0001         | A+         | yes           | HealthCare Plus    | Mary Anderson         | 555-1001                | 2024-01-10      | true
Patricia           | Martinez          | Ann                 | 1988-11-30    | Female | 654 Maple Drive   | MRN-2024-0002         | O-         | yes           | MediCare           | Carlos Martinez       | 555-1002                | 2023-12-15      | true
Robert             | Taylor            |                     | 1992-07-22    | Male   | 987 Cedar Lane    | MRN-2024-0003         |            | no            |                    | Jennifer Taylor       | 555-1003                |                 | true
```

### Notes

- `medical_record_number` is String to accommodate various formats
- `blood_type` is optional - not all patients may have this recorded
- `insurance_provider` is only needed if `has_insurance` is yes
- `last_visit_date` is optional for new patients
- Empty middle name is acceptable (optional core field)

---

## Example 4: Membership Organization

### Use Case
Association importing member data with membership numbers, renewal dates, and payment status.

### Template Configuration

**Template Name:** Annual Membership Import

**Core Field Mappings:**
- First Name → `first_name`
- Last Name → `last_name`
- Birthday → `birth_date`
- Address → `mailing_address`
- Civil Status → `marital_status`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| membership_number | String | Yes | Unique membership ID |
| membership_type | String | Yes | Type of membership (Regular, Premium, etc.) |
| join_date | Date | Yes | Date joined organization |
| renewal_date | Date | Yes | Next renewal date |
| is_paid | Boolean | Yes | Payment status for current period |
| payment_amount | Decimal | No | Amount paid |
| years_member | Integer | No | Years of membership |
| is_board_member | Boolean | No | Board member status |

### Sample Excel File

```
first_name | last_name | birth_date | mailing_address     | marital_status | membership_number | membership_type | join_date  | renewal_date | is_paid | payment_amount | years_member | is_board_member
-----------|-----------|------------|---------------------|----------------|-------------------|-----------------|------------|--------------|---------|----------------|--------------|----------------
Alice      | Cooper    | 1980-03-15 | 111 First Ave       | Married        | MEM-2020-001      | Premium         | 2020-01-15 | 2025-01-15   | yes     | 150.00         | 4            | yes
Bob        | Davis     | 1975-08-20 | 222 Second St       | Single         | MEM-2022-045      | Regular         | 2022-06-01 | 2025-06-01   | yes     | 75.00          | 2            | no
Carol      | Evans     | 1990-12-10 | 333 Third Blvd      | Married        | MEM-2024-089      | Regular         | 2024-01-10 | 2025-01-10   | no      |                | 0            |
```

### Notes

- `membership_number` includes year prefix for easy identification
- `payment_amount` is optional - may be empty if not paid
- `years_member` can be 0 for new members
- `is_board_member` is optional - defaults to no if empty

---

## Example 5: Event Registration

### Use Case
Conference organizer importing attendee registrations with ticket types and dietary preferences.

### Template Configuration

**Template Name:** Tech Conference 2024 Registration

**Core Field Mappings:**
- First Name → `attendee_first_name`
- Last Name → `attendee_last_name`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| registration_id | String | Yes | Unique registration code |
| email | String | Yes | Contact email |
| company | String | No | Company/organization name |
| ticket_type | String | Yes | Ticket category |
| registration_date | Date | Yes | Date registered |
| has_dietary_restrictions | Boolean | No | Dietary restrictions flag |
| dietary_notes | String | No | Specific dietary needs |
| num_guests | Integer | No | Number of additional guests |
| total_paid | Decimal | Yes | Total amount paid |
| is_checked_in | Boolean | No | Check-in status |

### Sample Excel File

```
attendee_first_name | attendee_last_name | registration_id | email                  | company        | ticket_type | registration_date | has_dietary_restrictions | dietary_notes      | num_guests | total_paid | is_checked_in
--------------------|--------------------|-----------------|-----------------------|----------------|-------------|-------------------|--------------------------|--------------------|-----------|-----------|--------------
Michael             | Zhang              | REG-001-2024    | mzhang@email.com      | Tech Corp      | VIP         | 2024-01-05        | yes                      | Vegetarian         | 1          | 500.00    | no
Jennifer            | Lee                | REG-002-2024    | jlee@email.com        | StartUp Inc    | Regular     | 2024-01-08        | no                       |                    | 0          | 250.00    | no
Thomas              | Wilson             | REG-003-2024    | twilson@email.com     |                | Student     | 2024-01-10        | yes                      | Gluten-free        |            | 100.00    | no
```

### Notes

- `email` is String type (not a special email type)
- `company` is optional for individual attendees
- `dietary_notes` only needed if `has_dietary_restrictions` is yes
- `num_guests` can be empty (defaults to 0)
- `is_checked_in` is optional - used during event

---

## Example 6: Inventory Management

### Use Case
Warehouse importing product inventory with SKUs, quantities, and reorder information.

### Template Configuration

**Template Name:** Product Inventory Import

**Core Field Mappings:**
- (No core field mappings - this is a product import, not person data)

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| sku | String | Yes | Stock keeping unit code |
| product_name | String | Yes | Product name |
| category | String | Yes | Product category |
| quantity_on_hand | Integer | Yes | Current stock quantity |
| reorder_point | Integer | Yes | Minimum quantity before reorder |
| unit_price | Decimal | Yes | Price per unit |
| supplier_name | String | No | Supplier name |
| last_restock_date | Date | No | Date of last restock |
| is_active | Boolean | Yes | Active product status |
| is_perishable | Boolean | No | Perishable item flag |

### Sample Excel File

```
sku        | product_name          | category    | quantity_on_hand | reorder_point | unit_price | supplier_name    | last_restock_date | is_active | is_perishable
-----------|-----------------------|-------------|------------------|---------------|------------|------------------|-------------------|-----------|---------------
SKU-001-A  | Wireless Mouse        | Electronics | 150              | 50            | 25.99      | Tech Supplies Co | 2024-01-15        | yes       | no
SKU-002-B  | Office Chair          | Furniture   | 45               | 20            | 199.99     | Furniture Plus   | 2024-01-10        | yes       | no
SKU-003-C  | Printer Paper (Ream)  | Office      | 500              | 100           | 5.50       | Paper World      | 2024-02-01        | yes       | no
SKU-004-D  | Desk Lamp             | Electronics | 0                | 25            | 35.00      | Lighting Inc     | 2023-12-20        | no        | no
```

### Notes

- This template doesn't use person-related core fields
- `quantity_on_hand` can be 0 (out of stock)
- `is_active` set to no for discontinued products
- `supplier_name` is optional for generic items
- All prices are Decimal type for accuracy

---

## Example 7: Volunteer Management

### Use Case
Non-profit organization importing volunteer data with skills, availability, and background check status.

### Template Configuration

**Template Name:** Volunteer Database 2024

**Core Field Mappings:**
- First Name → `first_name`
- Last Name → `last_name`
- Birthday → `date_of_birth`
- Address → `home_address`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| volunteer_id | String | Yes | Unique volunteer identifier |
| email | String | Yes | Contact email |
| phone | String | Yes | Contact phone number |
| skills | String | No | Volunteer skills/expertise |
| availability | String | No | Available days/times |
| background_check_completed | Boolean | Yes | Background check status |
| background_check_date | Date | No | Date of background check |
| hours_volunteered | Integer | No | Total volunteer hours |
| is_active | Boolean | Yes | Active volunteer status |
| preferred_role | String | No | Preferred volunteer role |

### Sample Excel File

```
first_name | last_name | date_of_birth | home_address      | volunteer_id | email                | phone      | skills                  | availability      | background_check_completed | background_check_date | hours_volunteered | is_active | preferred_role
-----------|-----------|---------------|-------------------|--------------|----------------------|------------|-------------------------|-------------------|---------------------------|-----------------------|-------------------|-----------|----------------
Sarah      | Johnson   | 1985-05-20    | 100 Volunteer Ln  | VOL-2024-001 | sjohnson@email.com   | 555-2001   | Teaching, Mentoring     | Weekends          | yes                        | 2024-01-05            | 120               | yes       | Tutor
Mark       | Thompson  | 1990-09-15    | 200 Helper St     | VOL-2024-002 | mthompson@email.com  | 555-2002   | Construction, Carpentry | Weekday evenings  | yes                        | 2024-01-08            | 45                | yes       | Builder
Lisa       | Rodriguez | 1978-12-03    | 300 Service Ave   | VOL-2024-003 | lrodriguez@email.com | 555-2003   | Cooking                 | Flexible          | no                         |                       | 0                 | yes       | Kitchen Help
```

### Notes

- `skills` and `availability` are free-text fields
- `background_check_date` only needed if check is completed
- `hours_volunteered` can be 0 for new volunteers
- `preferred_role` is optional - volunteers may be flexible

---

## Example 8: Real Estate Property Listings

### Use Case
Real estate agency importing property listings with prices, features, and availability.

### Template Configuration

**Template Name:** Property Listings Q1 2024

**Core Field Mappings:**
- Address → `property_address`
- Barangay → `barangay`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| listing_id | String | Yes | Unique listing identifier |
| property_type | String | Yes | Type (House, Condo, Lot, etc.) |
| bedrooms | Integer | No | Number of bedrooms |
| bathrooms | Integer | No | Number of bathrooms |
| square_meters | Decimal | Yes | Property size in sqm |
| price | Decimal | Yes | Listing price |
| is_available | Boolean | Yes | Availability status |
| listing_date | Date | Yes | Date listed |
| has_parking | Boolean | No | Parking availability |
| is_furnished | Boolean | No | Furnished status |
| agent_name | String | Yes | Listing agent name |

### Sample Excel File

```
property_address           | barangay    | listing_id | property_type | bedrooms | bathrooms | square_meters | price        | is_available | listing_date | has_parking | is_furnished | agent_name
---------------------------|-------------|------------|---------------|----------|-----------|---------------|--------------|--------------|--------------|-------------|--------------|-------------
123 Sunset Boulevard       | Poblacion   | PROP-001   | House         | 3        | 2         | 150.50        | 5500000.00   | yes          | 2024-01-15   | yes         | no           | Maria Santos
456 Ocean View Drive       | San Roque   | PROP-002   | Condo         | 2        | 1         | 75.00         | 3200000.00   | yes          | 2024-01-20   | yes         | yes          | Juan Cruz
789 Mountain Road          | Santa Maria | PROP-003   | Lot           |          |           | 500.00        | 2000000.00   | no           | 2024-01-10   |             |              | Maria Santos
```

### Notes

- `bedrooms` and `bathrooms` are empty for lot listings
- `has_parking` and `is_furnished` are optional
- `price` uses Decimal for exact amounts
- `is_available` tracks if property is still on market
- Empty values for lot properties (no bedrooms/bathrooms)

---

## Example 9: Simple Contact List

### Use Case
Basic contact import with minimal custom fields.

### Template Configuration

**Template Name:** Basic Contact Import

**Core Field Mappings:**
- First Name → `first_name`
- Last Name → `last_name`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| email | String | Yes | Email address |
| phone | String | No | Phone number |
| company | String | No | Company name |
| notes | String | No | Additional notes |

### Sample Excel File

```
first_name | last_name | email                | phone      | company      | notes
-----------|-----------|----------------------|------------|--------------|------------------
John       | Doe       | jdoe@email.com       | 555-0001   | ABC Corp     | Met at conference
Jane       | Smith     | jsmith@email.com     | 555-0002   | XYZ Inc      |
Bob        | Jones     | bjones@email.com     |            |              | Follow up next week
```

### Notes

- Minimal template for simple contact management
- Only email is required beyond core fields
- All other fields are optional
- Good starting point for basic imports

---

## Example 10: Training Course Enrollment

### Use Case
Training center importing course enrollment data with course codes and completion status.

### Template Configuration

**Template Name:** Course Enrollment Spring 2024

**Core Field Mappings:**
- First Name → `student_first_name`
- Last Name → `student_last_name`

**Custom Fields:**

| Field Name | Type | Required | Description |
|------------|------|----------|-------------|
| enrollment_id | String | Yes | Unique enrollment ID |
| course_code | String | Yes | Course identifier |
| course_name | String | Yes | Course title |
| enrollment_date | Date | Yes | Date enrolled |
| start_date | Date | Yes | Course start date |
| end_date | Date | Yes | Course end date |
| is_completed | Boolean | No | Completion status |
| completion_date | Date | No | Date completed |
| score | Decimal | No | Final score/grade |
| is_certified | Boolean | No | Certification issued |

### Sample Excel File

```
student_first_name | student_last_name | enrollment_id | course_code | course_name              | enrollment_date | start_date | end_date   | is_completed | completion_date | score | is_certified
-------------------|-------------------|---------------|-------------|--------------------------|-----------------|------------|------------|--------------|-----------------|-------|-------------
Alex               | Turner            | ENR-2024-001  | CS101       | Introduction to Python   | 2024-01-05      | 2024-01-15 | 2024-03-15 | yes          | 2024-03-10      | 92.5  | yes
Emily              | Parker            | ENR-2024-002  | WD201       | Web Development Advanced | 2024-01-08      | 2024-01-15 | 2024-03-15 | no           |                 |       | no
Chris              | Morgan            | ENR-2024-003  | DB301       | Database Design          | 2024-01-10      | 2024-02-01 | 2024-04-01 | no           |                 |       | no
```

### Notes

- `is_completed` is optional - defaults to no for active courses
- `completion_date` and `score` only filled when course is completed
- `is_certified` indicates if certificate was issued
- `score` is Decimal to allow for grades like 92.5

---

## Common Patterns and Tips

### Pattern 1: ID Fields

Use **String** type for ID fields that may contain:
- Letters and numbers: `EMP-2024-001`
- Leading zeros: `00123`
- Special characters: `REG/2024/001`

Use **Integer** type only for purely numeric IDs: `12345`

### Pattern 2: Money/Currency Fields

Always use **Decimal** type for money:
- Prices: `99.99`
- Salaries: `50000.00`
- Payments: `1500.50`

Never include currency symbols in the data.

### Pattern 3: Phone Numbers

Use **String** type for phone numbers to preserve:
- Formatting: `(555) 123-4567`
- Leading zeros: `0912-345-6789`
- International codes: `+63-912-345-6789`

### Pattern 4: Yes/No Questions

Use **Boolean** type for binary choices:
- Active/Inactive status
- Completed/Not completed
- Has/Doesn't have something

Accepted values: yes/no, true/false, 1/0, y/n

### Pattern 5: Dates

Use **Date** type for all date fields:
- Birth dates
- Registration dates
- Deadlines
- Timestamps (date portion only)

Recommended format: `YYYY-MM-DD` (2024-01-15)

### Pattern 6: Optional vs Required

Make fields **Required** when:
- Data is essential for the record
- Missing data would cause problems
- You need the data for processing

Make fields **Optional** when:
- Data may not always be available
- Field is for additional information only
- Users should be able to leave it blank

### Pattern 7: Free Text Fields

Use **String** type for free-text fields:
- Notes
- Comments
- Descriptions
- Addresses
- Names

These accept any text without validation.

---

## Choosing the Right Template Structure

### Minimal Template (3-5 custom fields)
Best for: Simple imports, basic contact lists
Example: Name, email, phone, company

### Standard Template (6-10 custom fields)
Best for: Most common use cases, balanced detail
Example: Employee data, student registration

### Comprehensive Template (11+ custom fields)
Best for: Detailed records, complex requirements
Example: Healthcare records, property listings

### Tips for Template Design

1. **Start simple**: Begin with required fields only
2. **Add gradually**: Add optional fields as needed
3. **Test first**: Test with sample data before full import
4. **Document**: Use clear field names and descriptions
5. **Be consistent**: Use same naming conventions throughout
6. **Plan ahead**: Consider future needs when designing

---

## Summary

These examples demonstrate common template configurations for various industries and use cases. Key takeaways:

- Choose appropriate field types for your data
- Mark fields as required only when necessary
- Use String type for IDs with letters or special formatting
- Use Decimal type for all monetary values
- Use Boolean type for yes/no questions
- Test your template with sample data first

For more information:
- **Field Types**: See `FIELD_TYPES.md` for detailed type specifications
- **User Guide**: See `USER_GUIDE.md` for step-by-step instructions
- **Requirements**: See `requirements.md` for system requirements
- **Design**: See `design.md` for technical implementation details
