# Implementation Tasks: Template-Based Strict Validation

## Phase 1: Database Changes

### Task 1: Create template_fields Migration
- [x] 1.1 Create migration file `create_template_fields_table`
- [x] 1.2 Add id, template_id, field_name, field_type columns
- [x] 1.3 Add is_required column (boolean, default false)
- [x] 1.4 Add timestamps
- [x] 1.5 Add foreign key constraint to column_mapping_templates with CASCADE delete
- [x] 1.6 Add unique constraint on (template_id, field_name)
- [x] 1.7 Add index on template_id
- [x] 1.8 Implement down() method for rollback
- [x] 1.9 Run migration and verify schema

### Task 2: Remove additional_attributes Column
- [x] 2.1 Create migration file `remove_additional_attributes_from_main_system`
- [x] 2.2 Drop additional_attributes column from main_system table
- [x] 2.3 Implement down() method to restore column (for rollback)
- [x] 2.4 Run migration and verify schema changes

## Phase 2: Models

### Task 3: Create TemplateField Model
- [x] 3.1 Create `app/Models/TemplateField.php`
- [x] 3.2 Define fillable attributes
- [x] 3.3 Add casts for is_required (boolean)
- [x] 3.4 Implement `template()` relationship (belongsTo)
- [x] 3.5 Implement `validateValue($value)` method
- [x] 3.6 Implement type-specific validation methods
- [x] 3.7 Implement `isValidFieldName()` static method
- [x] 3.8 Write unit tests for TemplateField model

### Task 4: Update ColumnMappingTemplate Model
- [x] 4.1 Add `fields()` relationship (hasMany TemplateField)
- [x] 4.2 Implement `getExpectedColumns()` method
- [x] 4.3 Implement `validateFileColumns($fileColumns)` method
- [x] 4.4 Write unit tests for new methods

### Task 5: Update MainSystem Model
- [x] 5.1 Remove `additional_attributes` from fillable array
- [x] 5.2 Remove `additional_attributes` cast
- [x] 5.3 Remove dynamic attribute methods (getDynamicAttributeKeys, etc.)
- [x] 5.4 Update tests to remove additional_attributes references

## Phase 3: Validation Logic

### Task 6: Enhance FileValidationService
- [x] 6.1 Add `validateColumns($file, $template)` method
- [x] 6.2 Implement file header reading logic
- [x] 6.3 Implement `validateCoreFieldsOnly($headers)` method
- [x] 6.4 Implement `getCoreFieldVariations($field)` helper method
- [x] 6.5 Return structured validation result
- [x] 6.6 Write unit tests for validateColumns

### Task 7: Update UploadController for Strict Validation
- [x] 7.1 Load template with fields relationship
- [x] 7.2 Call `validateColumns()` before processing
- [x] 7.3 Return validation errors if validation fails
- [x] 7.4 Pass validation info to view
- [x] 7.5 Log validation failures
- [x] 7.6 Write integration tests for upload validation

### Task 8: Update RecordImport
- [x] 8.1 Remove dynamic attribute extraction logic
- [x] 8.2 Update to work with template fields only
- [x] 8.3 Remove references to additional_attributes
- [x] 8.4 Write integration tests for RecordImport

## Phase 4: Template Field Management

### Task 9: Create TemplateFieldController
- [x] 9.1 Create `app/Http/Controllers/TemplateFieldController.php`
- [x] 9.2 Implement `index()` method (list template fields)
- [x] 9.3 Implement `store()` method (create template field)
- [x] 9.4 Implement `update()` method (update template field)
- [x] 9.5 Implement `destroy()` method (delete template field)
- [x] 9.6 Add authentication and authorization checks
- [x] 9.7 Add validation for field_name uniqueness
- [x] 9.8 Write feature tests for TemplateFieldController

### Task 10: Add API Routes for Template Fields
- [x] 10.1 Add route: `GET /api/templates/{id}/fields`
- [x] 10.2 Add route: `POST /api/templates/{id}/fields`
- [x] 10.3 Add route: `PUT /api/templates/{id}/fields/{fieldId}`
- [x] 10.4 Add route: `DELETE /api/templates/{id}/fields/{fieldId}`
- [x] 10.5 Apply auth middleware to all routes
- [x] 10.6 Test routes with API client

### Task 11: Update TemplateController
- [x] 11.1 Update `storeWeb()` to handle custom fields
- [x] 11.2 Update `updateWeb()` to handle custom fields
- [x] 11.3 Parse field_names[], field_types[], field_required[] arrays
- [x] 11.4 Create/update TemplateField records
- [x] 11.5 Delete removed fields on update
- [x] 11.6 Add validation for field name format
- [x] 11.7 Handle errors gracefully

## Phase 5: User Interface

### Task 12: Update Template Form
- [x] 12.1 Update `resources/views/pages/template-form.blade.php`
- [x] 12.2 Add "Custom Fields" section
- [x] 12.3 Add field row template with name, type, required inputs
- [x] 12.4 Add "Add Field" button with JavaScript handler
- [x] 12.5 Add "Remove" button for each field row
- [x] 12.6 Add field type dropdown with options
- [x] 12.7 Add tooltips explaining each field type
- [x] 12.8 Add client-side validation for field names
- [x] 12.9 Load existing fields when editing template

### Task 13: Update Upload Form
- [x] 13.1 Update `resources/views/pages/upload.blade.php`
- [x] 13.2 Display validation error alert
- [x] 13.3 Show expected vs found columns
- [x] 13.4 List missing columns
- [x] 13.5 List extra columns
- [x] 13.6 Keep form populated after validation error
- [x] 13.7 Add loading indicator during validation

### Task 14: Create Expected Columns Display
- [x] 14.1 Create component to show expected columns when template selected
- [x] 14.2 Display table of expected fields
- [x] 14.3 Show field name, type, required/optional
- [x] 14.4 Add icons for required vs optional fields
- [x] 14.5 Style component consistently

## Phase 6: Cleanup and Migration

### Task 15: Remove DynamicAttributeMergeService
- [x] 15.1 Delete `app/Services/DynamicAttributeMergeService.php`
- [x] 15.2 Remove all references to DynamicAttributeMergeService
- [x] 15.3 Delete tests for DynamicAttributeMergeService
- [x] 15.4 Update DataMatchService to remove dynamic attribute merging

### Task 16: Update DataMappingService
- [x] 16.1 Remove dynamic attribute extraction logic
- [x] 16.2 Remove `normalizeDynamicKey()` method
- [x] 16.3 Remove `sanitizeDynamicValue()` method
- [x] 16.4 Remove `validateJsonSize()` method
- [x] 16.5 Update tests to remove dynamic attribute references

### Task 17: Update DataMatchService
- [x] 17.1 Remove dynamic attribute handling in `findMatch()`
- [x] 17.2 Remove `updateMatchedRecord()` dynamic attribute logic
- [x] 17.3 Update tests to remove dynamic attribute references

## Phase 7: Testing

### Task 18: Unit Tests for Models
- [x] 18.1 Test TemplateField::validateValue() for all types
- [x] 18.2 Test TemplateField::isValidFieldName()
- [x] 18.3 Test ColumnMappingTemplate::getExpectedColumns()
- [x] 18.4 Test ColumnMappingTemplate::validateFileColumns()

### Task 19: Integration Tests for Upload Flow
- [x] 19.1 Test upload without template (valid core fields)
- [x] 19.2 Test upload without template (missing required field)
- [x] 19.3 Test upload without template (extra column)
- [x] 19.4 Test upload with template (valid data)
- [x] 19.5 Test upload with template (missing template field)
- [x] 19.6 Test upload with template (extra column)
- [x] 19.7 Test upload with template (type mismatch)

### Task 20: Feature Tests for Template Field Management
- [x] 20.1 Test creating template with custom fields
- [x] 20.2 Test updating template with custom fields
- [x] 20.3 Test deleting template cascades to fields
- [x] 20.4 Test field name uniqueness validation
- [x] 20.5 Test field type validation
- [x] 20.6 Test API endpoints for template fields

### Task 21: End-to-End Testing
- [x] 21.1 Test complete flow: create template → add fields → upload file
- [x] 21.2 Test error handling: invalid file → validation errors
- [x] 21.3 Test backward compatibility: existing templates
- [x] 21.4 Test performance: large file validation
- [x] 21.5 Run full test suite and verify all tests pass

## Phase 8: Documentation

### Task 22: Update Documentation
- [x] 22.1 Update README with strict validation feature
- [x] 22.2 Document field types and validation rules
- [x] 22.3 Add examples of template field definitions
- [x] 22.4 Document API endpoints for template fields
- [x] 22.5 Add troubleshooting section for validation errors
- [x] 22.6 Create user guide for template fields

### Task 23: Code Review and Cleanup
- [x] 23.1 Review all new code for consistency
- [x] 23.2 Ensure error messages are clear and helpful
- [x] 23.3 Verify all validation logic is correct
- [x] 23.4 Check for code duplication and refactor
- [x] 23.5 Ensure proper logging throughout
- [x] 23.6 Verify security (authorization, input validation)
- [x] 23.7 Remove all dead code related to additional_attributes

