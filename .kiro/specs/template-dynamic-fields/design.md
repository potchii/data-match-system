# Design Document: Template-Based Strict Validation

## Overview

This design implements strict column validation for file uploads. Files must match expected columns exactly - no additional_attributes JSON, no auto-capturing unknown columns. Templates allow defining custom fields that are validated alongside core fields.

### Design Principles

- **Strict Validation**: Files must match expected schema exactly
- **No JSON Storage**: All fields are proper database columns
- **Clear Errors**: Validation failures provide actionable feedback
- **Template Optional**: Users can upload without templates (core fields only)
- **Type Safety**: Template fields have defined types and validation

## Architecture

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  User Uploads File                           │
│              (Optional: Select Template)                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Read File Headers                               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
         ┌───────────────────────┐
         │  Template Selected?   │
         └───────┬───────────────┘
                 │
        ┌────────┴────────┐
        │                 │
       Yes               No
        │                 │
        ▼                 ▼
┌──────────────────────┐  ┌──────────────────────┐
│  Expected Columns:   │  │  Expected Columns:   │
│  - Core fields       │  │  - Core fields only  │
│  - Template fields   │  │                      │
└────────┬─────────────┘  └────────┬─────────────┘
         │                          │
         └──────────┬───────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│              Validate Columns                                │
│  - Check all expected columns present                        │
│  - Check no extra columns                                    │
│  - Check no misnamed columns                                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                ┌────┴────┐
               │  Valid?  │
               └────┬─────┘
                    │
           ┌────────┴────────┐
          No                Yes
           │                 │
           ▼                 ▼
┌──────────────────────┐  ┌──────────────────────┐
│  Return Errors       │  │  Process Import      │
│  - Missing columns   │  │  - Map core fields   │
│  - Extra columns     │  │  - Map template      │
│  - Misnamed columns  │  │    fields            │
└──────────────────────┘  └──────────────────────┘
```

## Database Schema

### Changes to main_system Table

```sql
-- Remove additional_attributes column
ALTER TABLE main_system DROP COLUMN additional_attributes;
```

### New Table: template_fields

```sql
CREATE TABLE template_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_type ENUM('string', 'integer', 'date', 'boolean', 'decimal') NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (template_id) REFERENCES column_mapping_templates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_template_field (template_id, field_name),
    INDEX idx_template_id (template_id)
);
```

### Core Fields (main_system columns)

All columns in main_system table:
- uid, origin_batch_id, origin_match_result_id
- last_name, first_name, middle_name
- last_name_normalized, first_name_normalized, middle_name_normalized
- suffix, birthday, gender, civil_status
- address, barangay

## Component Design

### 1. TemplateField Model

**File**: `app/Models/TemplateField.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateField extends Model
{
    protected $fillable = [
        'template_id',
        'field_name',
        'field_type',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ColumnMappingTemplate::class, 'template_id');
    }

    /**
     * Validate value against field type
     */
    public function validateValue($value): array
    {
        if ($value === null || $value === '') {
            if ($this->is_required) {
                return [
                    'valid' => false,
                    'error' => "Field '{$this->field_name}' is required",
                ];
            }
            return ['valid' => true, 'error' => null];
        }

        return match($this->field_type) {
            'string' => ['valid' => true, 'error' => null],
            'integer' => $this->validateInteger($value),
            'decimal' => $this->validateDecimal($value),
            'date' => $this->validateDate($value),
            'boolean' => $this->validateBoolean($value),
        };
    }

    protected function validateInteger($value): array
    {
        if (!is_numeric($value) || strpos((string) $value, '.') !== false) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be an integer",
            ];
        }
        return ['valid' => true, 'error' => null];
    }

    protected function validateDecimal($value): array
    {
        if (!is_numeric($value)) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be a number",
            ];
        }
        return ['valid' => true, 'error' => null];
    }

    protected function validateDate($value): array
    {
        try {
            $date = date('Y-m-d', strtotime($value));
            if ($date === '1970-01-01' && $value !== '1970-01-01') {
                throw new \Exception('Invalid date');
            }
            return ['valid' => true, 'error' => null];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be a valid date",
            ];
        }
    }

    protected function validateBoolean($value): array
    {
        $normalized = strtolower(trim((string) $value));
        
        if (in_array($normalized, ['true', '1', 'yes', 'y', 'false', '0', 'no', 'n'])) {
            return ['valid' => true, 'error' => null];
        }

        return [
            'valid' => false,
            'error' => "Field '{$this->field_name}' must be true/false, yes/no, or 1/0",
        ];
    }

    public static function isValidFieldName(string $name): bool
    {
        return preg_match('/^[a-z0-9_]+$/i', $name) === 1;
    }
}
```

### 2. Enhanced ColumnMappingTemplate Model

**File**: `app/Models/ColumnMappingTemplate.php` (additions)

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function fields(): HasMany
{
    return $this->hasMany(TemplateField::class, 'template_id');
}

/**
 * Get all expected column names (core + template fields)
 */
public function getExpectedColumns(): array
{
    // Core field mappings (Excel column names)
    $coreColumns = array_keys($this->mappings);
    
    // Template field names
    $templateColumns = $this->fields->pluck('field_name')->toArray();
    
    return array_merge($coreColumns, $templateColumns);
}

/**
 * Validate file columns against template
 */
public function validateFileColumns(array $fileColumns): array
{
    $expected = $this->getExpectedColumns();
    $expectedLower = array_map('strtolower', $expected);
    $fileLower = array_map('strtolower', $fileColumns);
    
    $missing = array_diff($expectedLower, $fileLower);
    $extra = array_diff($fileLower, $expectedLower);
    
    $errors = [];
    
    foreach ($missing as $col) {
        $errors[] = "Missing required column: {$col}";
    }
    
    foreach ($extra as $col) {
        $errors[] = "Unexpected column: {$col}";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'expected' => $expected,
        'missing' => array_values($missing),
        'extra' => array_values($extra),
    ];
}
```

### 3. Enhanced FileValidationService

**File**: `app/Services/FileValidationService.php` (additions)

```php
/**
 * Validate file columns against expected schema
 */
public function validateColumns($file, $template = null): array
{
    $errors = [];
    $info = [
        'expected_columns' => [],
        'found_columns' => [],
        'missing_columns' => [],
        'extra_columns' => [],
    ];
    
    try {
        // Read file headers
        $reader = IOFactory::createReader($this->getReaderType($file));
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [];
        $headerRow = $sheet->getRowIterator(1, 1)->current();
        foreach ($headerRow->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if ($value !== null && $value !== '') {
                $headers[] = trim($value);
            }
        }
        
        $info['found_columns'] = $headers;
        
        if ($template) {
            // Validate against template
            $validation = $template->validateFileColumns($headers);
            $errors = $validation['errors'];
            $info['expected_columns'] = $validation['expected'];
            $info['missing_columns'] = $validation['missing'];
            $info['extra_columns'] = $validation['extra'];
        } else {
            // Validate against core fields only
            $validation = $this->validateCoreFieldsOnly($headers);
            $errors = $validation['errors'];
            $info['expected_columns'] = $validation['expected'];
            $info['missing_columns'] = $validation['missing'];
            $info['extra_columns'] = $validation['extra'];
        }
        
    } catch (\Exception $e) {
        $errors[] = "File reading error: " . $e->getMessage();
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'info' => $info,
    ];
}

/**
 * Validate against core fields only (no template)
 */
protected function validateCoreFieldsOnly(array $fileColumns): array
{
    // Required core fields
    $requiredFields = ['first_name', 'last_name'];
    
    // All possible core field variations from DataMappingService
    $allCoreVariations = [
        'regsno', 'RegsNo', 'regsnumber', 'registration_no',
        'surname', 'Surname', 'lastname', 'LastName', 'last_name',
        'firstname', 'FirstName', 'first_name', 'fname',
        'middlename', 'MiddleName', 'middle_name', 'mname',
        'extension', 'Extension', 'suffix', 'Suffix', 'ext',
        'dob', 'DOB', 'birthday', 'Birthday', 'birthdate', 'BirthDate',
        'sex', 'Sex', 'gender', 'Gender',
        'status', 'Status', 'civilstatus', 'CivilStatus', 'civil_status',
        'address', 'Address', 'street', 'Street',
        'brgydescription', 'BrgyDescription', 'barangay', 'Barangay',
    ];
    
    $fileLower = array_map('strtolower', $fileColumns);
    $coreLower = array_map('strtolower', $allCoreVariations);
    
    $errors = [];
    $missing = [];
    $extra = [];
    
    // Check required fields
    foreach ($requiredFields as $required) {
        $found = false;
        foreach ($fileColumns as $col) {
            if (in_array(strtolower($col), $this->getCoreFieldVariations($required))) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "Missing required column: {$required}";
            $missing[] = $required;
        }
    }
    
    // Check for extra columns
    foreach ($fileColumns as $col) {
        if (!in_array(strtolower($col), $coreLower)) {
            $errors[] = "Unexpected column: {$col}";
            $extra[] = $col;
        }
    }
    
    return [
        'errors' => $errors,
        'expected' => $allCoreVariations,
        'missing' => $missing,
        'extra' => $extra,
    ];
}

protected function getCoreFieldVariations(string $field): array
{
    $variations = [
        'first_name' => ['firstname', 'FirstName', 'first_name', 'fname'],
        'last_name' => ['surname', 'Surname', 'lastname', 'LastName', 'last_name'],
    ];
    
    return $variations[$field] ?? [];
}
```

### 4. Update UploadController

**File**: `app/Http/Controllers/UploadController.php` (modifications)

```php
public function store(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        'template_id' => 'nullable|integer|exists:column_mapping_templates,id',
    ]);

    try {
        // Load template if provided
        $template = null;
        if ($request->has('template_id')) {
            $template = ColumnMappingTemplate::with('fields')
                ->where('id', $request->template_id)
                ->where('user_id', auth()->id())
                ->first();
            
            if (!$template) {
                return redirect()->route('upload.index')
                    ->with('error', 'Template not found or access denied.');
            }
        }
        
        // Validate columns
        $validator = new FileValidationService();
        $validation = $validator->validateColumns($request->file('file'), $template);
        
        if (!$validation['valid']) {
            Log::warning('File column validation failed', [
                'errors' => $validation['errors'],
                'info' => $validation['info'],
                'user' => auth()->user()->name,
            ]);
            
            return redirect()->route('upload.index')
                ->with('error', 'File validation failed')
                ->with('validation_errors', $validation['errors'])
                ->with('validation_info', $validation['info']);
        }
        
        // Proceed with import...
        $batch = UploadBatch::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => auth()->user()->name,
            'uploaded_at' => now(),
            'status' => 'PROCESSING',
        ]);

        $import = new RecordImport($batch->id, $template);
        Excel::import($import, $request->file('file'));

        $batch->update(['status' => 'COMPLETED']);

        return redirect()->route('results.index', ['batch_id' => $batch->id])
            ->with('success', "Batch #{$batch->id} processed successfully.");

    } catch (\Exception $e) {
        if (isset($batch)) {
            $batch->update(['status' => 'FAILED']);
        }
        
        Log::error('File upload failed', [
            'error' => $e->getMessage(),
            'user' => auth()->user()->name,
        ]);

        return redirect()->route('upload.index')
            ->with('error', 'Error processing file: ' . $e->getMessage());
    }
}
```

## UI Components

### Template Form Enhancement

Add custom fields section to template form:

```blade
<!-- Custom Fields Section -->
<div class="card mt-4">
    <div class="card-header">
        <h5>Custom Fields</h5>
        <small class="text-muted">Define additional fields beyond core columns</small>
    </div>
    <div class="card-body">
        <div id="custom-fields-container">
            <!-- Dynamic field rows will be added here -->
        </div>
        
        <button type="button" id="add-field-btn" class="btn btn-secondary btn-sm mt-2">
            <i class="fas fa-plus"></i> Add Field
        </button>
    </div>
</div>

<script>
document.getElementById('add-field-btn').addEventListener('click', function() {
    const container = document.getElementById('custom-fields-container');
    const row = document.createElement('div');
    row.className = 'field-row row mb-3';
    row.innerHTML = `
        <div class="col-md-4">
            <input type="text" name="field_names[]" class="form-control" 
                   placeholder="Field Name" required>
        </div>
        <div class="col-md-3">
            <select name="field_types[]" class="form-control" required>
                <option value="string">String</option>
                <option value="integer">Integer</option>
                <option value="decimal">Decimal</option>
                <option value="date">Date</option>
                <option value="boolean">Boolean</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-2">
                <input type="checkbox" name="field_required[]" class="form-check-input">
                <label class="form-check-label">Required</label>
            </div>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-sm remove-field">Remove</button>
        </div>
    `;
    container.appendChild(row);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-field')) {
        e.target.closest('.field-row').remove();
    }
});
</script>
```

### Validation Error Display

```blade
@if(session('validation_errors'))
<div class="alert alert-danger">
    <h5>File Validation Failed</h5>
    <p>The uploaded file does not match the expected column structure:</p>
    <ul>
        @foreach(session('validation_errors') as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    
    @if(session('validation_info'))
        <hr>
        <p><strong>Expected columns:</strong> {{ implode(', ', session('validation_info')['expected_columns']) }}</p>
        <p><strong>Found columns:</strong> {{ implode(', ', session('validation_info')['found_columns']) }}</p>
    @endif
</div>
@endif
```

## Migration Strategy

1. **Backup Data**: Export existing additional_attributes data
2. **Create template_fields table**: Run new migration
3. **Drop additional_attributes column**: Run cleanup migration
4. **Update Code**: Remove all references to additional_attributes
5. **Test**: Verify uploads work with strict validation

## Key Differences from Previous Approach

| Aspect | Old (JSON) | New (Strict) |
|--------|-----------|--------------|
| Unknown columns | Auto-captured in JSON | Error returned |
| Extra columns | Ignored or captured | Error returned |
| Missing columns | Allowed (optional) | Error returned |
| Storage | additional_attributes JSON | No dynamic storage |
| Validation | Lenient | Strict |
| Template fields | Stored in JSON | Proper table with types |

