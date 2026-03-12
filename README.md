# Data Match System

A Laravel-based system for matching uploaded records (Excel/CSV) with existing database records. Unmatched records are automatically inserted as new entries with intelligent duplicate detection and confidence scoring.

## Overview

The Data Match System provides:
- **File Upload Processing**: Support for Excel and CSV formats with flexible column mapping
- **Intelligent Matching**: Multi-stage matching algorithm with confidence scoring
- **Template Support**: Define custom fields and validation rules for different data sources
- **Batch Tracking**: Complete audit trail of all imported data with origin tracking
- **Duplicate Detection**: Automatic identification of potential duplicate records
- **Admin Dashboard**: User-friendly interface for managing records and viewing results

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: AdminLTE 3
- **Database**: MySQL/MariaDB
- **File Processing**: Laravel Excel (Maatwebsite)
- **Authentication**: Laravel Fortify

## Installation

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (for frontend assets)

### Setup

1. Clone the repository
```bash
git clone <repository-url>
cd data-match-system
```

2. Install dependencies
```bash
composer install
npm install
```

3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Setup database
```bash
php artisan migrate
php artisan db:seed
```

5. Build assets
```bash
npm run build
```

6. Start development server
```bash
php artisan serve
```

## Core Features

### File Upload & Processing
- Upload Excel (.xlsx, .xls) or CSV files
- Automatic column mapping with flexible naming conventions
- Support for core fields and custom template fields
- File size limit: 10MB
- Batch tracking with upload history

### Matching Algorithm
The system uses a multi-stage matching approach:

1. **Exact Match**: Full name + date of birth
2. **Partial Match**: Last name + first name + date of birth
3. **New Record**: If no match found, insert as new

Confidence scoring ranges from 60% to 100% based on match quality.

### Template System
Create reusable templates to define:
- Core field mappings (first_name, last_name, birthday, etc.)
- Custom fields with type validation (string, integer, decimal, date, boolean)
- Required vs optional fields
- Field-specific validation rules

### Main System Records
Manage individual records with:
- Auto-generated unique identifiers (UID)
- Complete personal information (name, DOB, gender, civil status)
- Address information
- Registration tracking
- Audit trail of all changes

### Batch Management
Track all file uploads with:
- Batch history and status
- Match result summaries
- Origin tracking for each record
- Duplicate detection reports

## Database Schema

### Main Tables

**main_system**
- Core record storage with personal information
- Tracks origin batch and match result references
- Supports audit trail logging

**upload_batches**
- File upload history and metadata
- Batch status and processing results
- File deduplication tracking

**match_results**
- Individual match records from each batch
- Confidence scores and match status
- Uploaded record details for audit trail

**template_field_values**
- Custom field values for records
- Supports dynamic schema per template
- Tracks field changes and conflicts

## API Endpoints

### Records
- `GET /api/main-system` - List records
- `POST /api/main-system` - Create record
- `GET /api/main-system/{id}` - Get record
- `PUT /api/main-system/{id}` - Update record
- `DELETE /api/main-system/{id}` - Delete record

### Templates
- `GET /api/templates` - List templates
- `POST /api/templates` - Create template
- `GET /api/templates/{id}` - Get template
- `PUT /api/templates/{id}` - Update template
- `DELETE /api/templates/{id}` - Delete template

### Batch Operations
- `POST /api/bulk-action` - Perform bulk actions on records
- `GET /api/batch-analytics/{batchId}` - Get batch statistics
- `GET /api/field-breakdown/{resultId}` - Get field-level match details

## Artisan Commands

### Data Management

**Backfill Origin Batch Data**
```bash
php artisan data:backfill-origin-batch
```
Retroactively links existing records to their source batch files.

**Remove Duplicate Records**
```bash
php artisan duplicates:remove [--dry-run]
```
Identifies and removes duplicate records based on name and DOB.

**Purge Database Data**
```bash
php artisan data:purge [--keep-users]
```
Clears all data while preserving table structure.

## Usage Guide

### Uploading Records

1. Navigate to the Upload page
2. Select a file (Excel or CSV)
3. Optionally choose a template for custom field mapping
4. Click Upload
5. System processes the file and displays match results

### Creating Templates

1. Go to Templates section
2. Click "Create New Template"
3. Map core fields to your file columns
4. Add custom fields with appropriate types
5. Save template for reuse

### Managing Records

- **View**: Browse all records with search and filtering
- **Create**: Add new records manually
- **Edit**: Update existing record information
- **Delete**: Remove records with confirmation
- **Export**: Download records as CSV

### Viewing Match Results

- Access batch history to see all uploads
- View detailed match results with confidence scores
- See field-level breakdowns for each match
- Track origin of each record

## Configuration

### File Upload Settings
Edit `.env` to configure:
```
UPLOAD_MAX_FILE_SIZE=10240  # in KB
UPLOAD_ALLOWED_EXTENSIONS=xlsx,xls,csv
```

### Matching Thresholds
Configure confidence score thresholds in `config/app.php`:
```php
'matching' => [
    'exact_match_threshold' => 100,
    'partial_match_threshold' => 90,
    'possible_duplicate_threshold' => 60,
]
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/UploadControllerTest.php
```

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Http/
│   ├── Controllers/           # Request handlers
│   └── Requests/              # Form validation
├── Models/                    # Database models
├── Services/                  # Business logic
│   ├── DataMappingService.php
│   ├── DataMatchService.php
│   └── MatchAnalyticsService.php
└── Imports/                   # Excel import handlers

resources/
├── views/
│   ├── pages/                 # Page templates
│   └── layouts/               # Layout templates
└── js/
    └── components/            # Vue/JS components

database/
├── migrations/                # Schema changes
└── factories/                 # Test data factories
```

## Contributing

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Make your changes following the coding standards
3. Write tests for new functionality
4. Commit with clear messages: `git commit -m "feat: add new feature"`
5. Push to branch: `git push origin feature/your-feature`
6. Open a Pull Request with detailed description

## Coding Standards

- Follow PSR-12 for PHP code style
- Use type hints for all function parameters and returns
- Write meaningful commit messages
- Maintain minimum 80% test coverage
- Document complex business logic

## License

This project is proprietary and confidential.

---

## 📁 FILE UPLOAD REQUIREMENTS

### Supported File Formats
- Excel files: `.xlsx`, `.xls`
- CSV files: `.csv`
- Maximum file size: 10MB

### Column Requirements

#### Without Template (Core Fields Only)
When uploading without a template, your file must contain these required columns:
- **first_name** (or variations: FirstName, firstname, fname)
- **last_name** (or variations: Surname, lastname, LastName)

Optional core columns:
- middle_name, suffix, birthday, gender, civil_status, address, barangay, uid

#### With Template (Core + Custom Fields)
When using a template, your file must contain:
- All core fields mapped in the template
- All custom fields defined in the template
- No additional columns not defined in the template

### Preparing Your File

**Before uploading, ensure:**
1. Column names match your template exactly (case-insensitive)
2. All required columns are present
3. No extra columns exist
4. Data types match field definitions:
   - Integers: No decimals or text (e.g., 12345)
   - Decimals: Numeric values (e.g., 50000.50)
   - Dates: Valid date format (YYYY-MM-DD recommended)
   - Booleans: yes/no, true/false, or 1/0
5. No empty rows or columns

**Common Issues to Avoid:**
- Misspelled column names
- Extra blank columns in Excel
- Mixed data types in a column
- Missing required columns
- Currency symbols or commas in number fields

---

## 🛠️ ARTISAN COMMANDS

### Data Management Commands

#### Backfill Origin Batch Data
Backfills `origin_batch_id` and `origin_match_result_id` for existing main_system records. This command is useful when you need to retroactively link existing records to their source batch files.

```bash
php artisan data:backfill-origin-batch
```

**What it does:**
- Finds all main_system records without origin batch information
- Locates the first match result where each record was created (status = NEW RECORD)
- Updates records with their origin batch ID and match result ID
- Displays progress and summary of updated records

**When to use:**
- After adding origin tracking to existing data
- When migrating from a system without batch tracking
- To restore batch lineage after data imports

#### Remove Duplicate Records
Identifies and removes duplicate records from the main_system table based on name and date of birth, keeping only the oldest record in each duplicate group.

```bash
# Preview duplicates without deleting (dry run)
php artisan duplicates:remove --dry-run

# Actually remove duplicates
php artisan duplicates:remove
```

**What it does:**
- Finds duplicate groups by matching: last_name, first_name, middle_name, and birthday
- Keeps the oldest record (lowest ID) in each group
- Deletes all newer duplicates
- Shows detailed output of what will be/was deleted

**When to use:**
- After discovering duplicate records in the system
- When cleaning up data quality issues
- Before running reports that require unique records

#### Purge Database Data
Clears all data from the database while preserving the table structure. Useful for testing and development.

```bash
# Purge all data including user accounts
php artisan data:purge

# Purge data but keep user accounts
php artisan data:purge --keep-users
```

**What it does:**
- Truncates match_results, upload_batches, and main_system tables
- Optionally preserves user accounts and authentication data
- Safely handles foreign key constraints
- Provides confirmation prompt before deletion

**When to use:**
- Resetting test environment
- Clearing development data
- Starting fresh without re-running migrations

---

## 📊 FEATURES IMPLEMENTED

### Match Results Tracking
- Each match result stores uploaded record details (name fields)
- Displays full name and origin information for matched records
- Shows batch source and row ID for complete data lineage
- Format: `Name (Row ID: X from Batch #Y: filename.xlsx)`

### Date of Birth Field Variations
The matching algorithm supports multiple DOB field name variations:
- `birthday`, `dob`, `DOB`
- `date_of_birth`, `dateOfBirth`, `DateOfBirth`, `dateofbirth`
- `birthdate`, `BirthDate`, `birth_date`, `Birthday`, `Birthdate`

All variations are automatically normalized to `Y-m-d` format before matching.

### Origin Batch Tracking
- Main system records track which batch file originally created them
- Links back to the specific match result row
- Enables full audit trail of data sources
- Supports data lineage and compliance requirements

### Strict File Validation with Template Support
The system enforces strict column validation to ensure data quality and consistency:

#### Core Validation Rules
- **Exact Column Matching**: Uploaded files must contain exactly the expected columns
- **No Missing Columns**: All required columns must be present
- **No Extra Columns**: Files cannot contain undefined columns
- **Case-Insensitive Matching**: Column names are matched regardless of case

#### Template-Based Custom Fields
Templates allow you to define additional fields beyond the core system columns:

**Creating Templates with Custom Fields:**
1. Navigate to Templates → Create New Template
2. Map core fields (first_name, last_name, birthday, etc.)
3. Add custom fields with:
   - **Field Name**: Column name in your file
   - **Field Type**: string, integer, decimal, date, or boolean
   - **Required**: Whether the field must have a value

**Supported Field Types:**
- **String**: Any text value (names, codes, descriptions)
- **Integer**: Whole numbers only (employee IDs, counts)
- **Decimal**: Numbers with decimals (salaries, scores)
- **Date**: Date values (hire dates, renewal dates)
- **Boolean**: True/false values (yes/no, 1/0, true/false)

**Example Template Configuration:**
```
Core Fields:
  first_name → FirstName
  last_name → Surname
  birthday → DOB

Custom Fields:
  employee_id (Integer, Required)
  department (String, Required)
  hire_date (Date, Optional)
  is_active (Boolean, Required)
```

#### Upload Validation Process
1. **Select Template** (optional): Choose a template or upload with core fields only
2. **File Validation**: System checks column structure before processing
3. **Error Reporting**: Clear messages identify missing, extra, or misnamed columns
4. **Type Validation**: Custom field values are validated against their defined types

**Validation Error Examples:**
```
Missing required column: employee_id
Unexpected column: extra_field
Field 'hire_date' must be a valid date
Field 'employee_id' must be an integer
```

#### Benefits
- **Data Quality**: Prevents incorrect or incomplete data from being imported
- **Clear Feedback**: Detailed error messages help fix file issues quickly
- **Flexibility**: Templates support organization-specific data requirements
- **Consistency**: Ensures all uploads follow the same structure

For detailed guidance on using templates and custom fields, see the [Template Fields User Guide](.kiro/specs/template-dynamic-fields/USER_GUIDE.md).