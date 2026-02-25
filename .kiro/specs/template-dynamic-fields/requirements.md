# Requirements Document: Template-Based Strict Validation

## Introduction

This feature implements strict column validation for file uploads with optional template support. Files must match expected columns exactly - no missing columns, no extra columns, no misnamed columns. Templates allow users to define additional fields beyond the core main_system columns.

## Glossary

- **Core_Fields**: All columns in the main_system table (uid, first_name, last_name, birthday, etc.)
- **Template**: A saved configuration defining expected columns (existing: column_mapping_templates)
- **Template_Field**: Additional field defined in a template (new: template_fields table)
- **Strict_Validation**: Uploaded file columns must match expected columns exactly
- **Column_Mismatch**: Any missing, extra, or misnamed column in uploaded file
- **Validation_Exception**: Error returned when file columns don't match expected schema

## Requirements

### Requirement 1: Remove Dynamic Attributes

**User Story:** As a system architect, I want to remove the additional_attributes JSON column, so that all data is stored in proper database columns.

#### Acceptance Criteria

1. THE System SHALL create migration to drop `additional_attributes` column from main_system table
2. THE System SHALL remove all references to additional_attributes in code
3. THE System SHALL remove DynamicAttributeMergeService
4. THE System SHALL update MainSystem model to remove dynamic attribute methods
5. THE migration SHALL be reversible (support rollback)

### Requirement 2: Template Fields Table

**User Story:** As a user, I want to define custom fields in templates, so that I can upload files with additional columns beyond core fields.

#### Acceptance Criteria

1. THE System SHALL create `template_fields` table with columns:
   - id (primary key)
   - template_id (foreign key to column_mapping_templates.id)
   - field_name (VARCHAR 255)
   - field_type (ENUM: string, integer, date, boolean, decimal)
   - is_required (BOOLEAN, default false)
   - created_at, updated_at
2. THE System SHALL add foreign key constraint with CASCADE delete
3. THE System SHALL add index on template_id
4. THE System SHALL add unique constraint on (template_id, field_name)
5. THE migration SHALL be reversible

### Requirement 3: Strict Validation Without Template

**User Story:** As a user, I want to upload files that match main_system columns exactly, so that data integrity is maintained.

#### Acceptance Criteria

1. WHEN user uploads file without template, THE System SHALL validate against main_system columns
2. THE System SHALL identify expected columns from DataMappingService mappings
3. WHEN file has missing required column, THE System SHALL return error: "Missing required column: {column_name}"
4. WHEN file has extra column, THE System SHALL return error: "Unexpected column: {column_name}"
5. WHEN file has misnamed column, THE System SHALL return error: "Unknown column: {column_name}"
6. THE System SHALL NOT process file if validation fails
7. THE System SHALL return all validation errors at once (not one at a time)
8. THE required columns SHALL be: first_name, last_name (minimum)

### Requirement 4: Strict Validation With Template

**User Story:** As a user, I want to upload files that match template-defined columns exactly, so that custom fields are validated.

#### Acceptance Criteria

1. WHEN user uploads file with template, THE System SHALL validate against main_system + template_fields
2. THE System SHALL check all core field mappings from template
3. THE System SHALL check all template_fields definitions
4. WHEN file has missing template field, THE System SHALL return error: "Missing template field: {field_name}"
5. WHEN file has extra column not in template, THE System SHALL return error: "Unexpected column: {column_name}"
6. THE System SHALL validate field types for template fields
7. THE System SHALL NOT process file if validation fails
8. THE System SHALL return all validation errors at once

### Requirement 5: Template Field Management UI

**User Story:** As a user, I want to define custom fields when creating templates, so that I can specify additional columns.

#### Acceptance Criteria

1. THE template creation form SHALL include "Custom Fields" section
2. THE form SHALL allow adding multiple field definitions
3. EACH field SHALL have inputs for:
   - Field Name (text, required)
   - Field Type (dropdown: string, integer, date, boolean, decimal)
   - Is Required (checkbox)
4. THE form SHALL provide "Add Field" button
5. THE form SHALL provide "Remove" button for each field
6. THE form SHALL validate field names are unique within template
7. THE form SHALL validate field names use only alphanumeric and underscores
8. THE template edit form SHALL load existing field definitions

### Requirement 6: Enhanced FileValidationService

**User Story:** As a developer, I want centralized validation logic, so that validation is consistent.

#### Acceptance Criteria

1. THE FileValidationService SHALL add method `validateColumns($file, $template = null)`
2. THE method SHALL read file headers
3. THE method SHALL compare headers against expected columns
4. THE method SHALL return structured result:
   ```php
   [
     'valid' => bool,
     'errors' => array,
     'info' => [
       'expected_columns' => array,
       'found_columns' => array,
       'missing_columns' => array,
       'extra_columns' => array,
     ]
   ]
   ```
5. THE method SHALL handle both template and non-template scenarios

### Requirement 7: Clear Error Messages

**User Story:** As a user, I want clear error messages when validation fails, so that I can fix my file.

#### Acceptance Criteria

1. WHEN validation fails, THE System SHALL display error modal with:
   - Summary: "File validation failed: X errors found"
   - List of missing columns
   - List of extra columns
   - List of misnamed columns
2. THE error modal SHALL have "Close" button
3. THE System SHALL suggest correct column names for common misspellings
4. THE System SHALL log validation errors for debugging
5. THE upload form SHALL remain populated after error

### Requirement 8: Template Field Type Validation

**User Story:** As a system, I want to validate field types, so that data integrity is maintained.

#### Acceptance Criteria

1. FOR field_type = 'string', THE System SHALL accept any text value
2. FOR field_type = 'integer', THE System SHALL validate value is numeric without decimal
3. FOR field_type = 'decimal', THE System SHALL validate value is numeric
4. FOR field_type = 'date', THE System SHALL validate value can be parsed as date
5. FOR field_type = 'boolean', THE System SHALL accept: true/false, 1/0, yes/no, y/n
6. WHEN type validation fails, THE System SHALL return error with row number
7. THE System SHALL sample first 10 rows for type validation

### Requirement 9: TemplateField Model

**User Story:** As a developer, I want a TemplateField model, so that I can manage custom fields programmatically.

#### Acceptance Criteria

1. THE System SHALL create `App\Models\TemplateField` model
2. THE model SHALL define relationship: `belongsTo(ColumnMappingTemplate)`
3. THE model SHALL cast field_type to enum
4. THE model SHALL cast is_required to boolean
5. THE model SHALL provide method `validateValue($value)` for type checking
6. THE model SHALL validate field_name format (alphanumeric + underscores only)

### Requirement 10: Enhanced ColumnMappingTemplate Model

**User Story:** As a developer, I want templates to manage their custom fields, so that field definitions are accessible.

#### Acceptance Criteria

1. THE ColumnMappingTemplate model SHALL define relationship: `hasMany(TemplateField)`
2. THE model SHALL provide method `getExpectedColumns()` returning all expected column names
3. THE model SHALL provide method `validateFileColumns($fileColumns)` returning validation result
4. WHEN template is deleted, THE System SHALL cascade delete all template_fields

### Requirement 11: Backward Compatibility

**User Story:** As a system maintainer, I want existing uploads to continue working, so that migration is smooth.

#### Acceptance Criteria

1. THE System SHALL support existing templates without template_fields
2. THE System SHALL maintain existing column_mapping_templates.mappings structure
3. THE System SHALL NOT break existing upload functionality
4. THE System SHALL handle migration of existing data gracefully

### Requirement 12: Performance

**User Story:** As a system architect, I want validation to be fast, so that user experience is good.

#### Acceptance Criteria

1. THE column validation SHALL complete in < 100ms for typical files
2. THE template field lookup SHALL use indexed query
3. THE type validation SHALL sample maximum 10 rows (not entire file)
4. THE System SHALL handle files up to 10MB without performance issues

### Requirement 13: API Endpoints for Template Fields

**User Story:** As a developer, I want API endpoints for template fields, so that I can build integrations.

#### Acceptance Criteria

1. THE System SHALL provide endpoint: `GET /api/templates/{id}/fields`
2. THE System SHALL provide endpoint: `POST /api/templates/{id}/fields`
3. THE System SHALL provide endpoint: `PUT /api/templates/{id}/fields/{fieldId}`
4. THE System SHALL provide endpoint: `DELETE /api/templates/{id}/fields/{fieldId}`
5. ALL endpoints SHALL require authentication
6. ALL endpoints SHALL verify user owns the template

### Requirement 14: Documentation

**User Story:** As a user, I want documentation on using templates, so that I can configure them correctly.

#### Acceptance Criteria

1. THE template form SHALL include help text explaining custom fields
2. THE upload page SHALL show expected columns when template is selected
3. THE System SHALL provide tooltip on field type dropdown
4. THE System SHALL provide inline validation feedback on template form

