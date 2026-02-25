# Requirements Document

## Introduction

This feature enhances the existing dynamic schema support system to save dynamic attributes for all records (both new and matched), introduce a column mapping template system for reusable mappings, and incorporate dynamic attributes into the unified confidence score calculation. The system currently saves dynamic attributes only for new records and calculates confidence scores based solely on core fields.

## Glossary

- **Dynamic Attribute**: A field in the uploaded data that is not part of the core schema but is stored in the additional_attributes JSON column
- **Core Field**: A standard database column in the main_system table (e.g., last_name, first_name, province)
- **Column Mapping Template**: A saved configuration that maps Excel column names to system fields, allowing users to reuse custom mappings across multiple imports
- **Unified Confidence Score**: A percentage indicating how well an uploaded record matches an existing database record, calculated from both core fields and dynamic attributes
- **Matched Record**: An existing database record that is identified as potentially matching an uploaded record
- **Record Import System**: The Laravel-based system that processes Excel uploads and matches them against existing records
- **Merge Strategy**: The algorithm used to combine existing and new dynamic attributes when updating matched records

## Requirements

### Requirement 1: Save Dynamic Attributes for Matched Records

**User Story:** As a data administrator, I want dynamic attributes to be saved when records match existing data, so that I can maintain complete information for all records regardless of whether they are new or matched.

#### Acceptance Criteria

1. WHEN a record matches an existing database record, THE Record Import System SHALL merge the uploaded dynamic attributes with existing additional_attributes
2. WHEN merging dynamic attributes, THE Record Import System SHALL preserve existing attributes not present in the upload
3. WHEN a dynamic attribute exists in both old and new data, THE Record Import System SHALL overwrite the old value with the new value
4. WHEN the merge operation completes, THE Record Import System SHALL persist the merged additional_attributes to the database
5. WHEN a matched record has no existing additional_attributes, THE Record Import System SHALL save the new dynamic attributes as-is

### Requirement 2: Column Mapping Template Database Schema

**User Story:** As a data administrator, I want to store column mapping templates in the database, so that the system can persist and retrieve custom mappings.

#### Acceptance Criteria

1. THE Record Import System SHALL create a column_mapping_templates table with columns: id, name, user_id, mappings_json, created_at, updated_at
2. THE Record Import System SHALL store mappings in mappings_json as a JSON object with format {"excel_column": "system_field"}
3. WHEN storing a template, THE Record Import System SHALL validate that mappings_json contains valid JSON
4. WHEN storing a template, THE Record Import System SHALL associate the template with the authenticated user via user_id
5. THE Record Import System SHALL enforce unique template names per user

### Requirement 3: Column Mapping Template Management

**User Story:** As a data administrator, I want to create, save, list, apply, edit, and delete column mapping templates, so that I can reuse custom mappings across multiple imports.

#### Acceptance Criteria

1. WHEN a user completes column mapping in the upload preview, THE Record Import System SHALL provide an option to save the mapping as a template
2. WHEN saving a template, THE Record Import System SHALL prompt for a template name and validate uniqueness for that user
3. WHEN a user views the upload page, THE Record Import System SHALL display a list of their saved templates
4. WHEN a user selects a template, THE Record Import System SHALL apply the stored mappings to the current upload preview
5. WHEN a user requests to edit a template, THE Record Import System SHALL allow modification of the template name and mappings
6. WHEN a user requests to delete a template, THE Record Import System SHALL remove the template from the database
7. WHEN applying a template with columns not present in the current upload, THE Record Import System SHALL ignore those mappings

### Requirement 4: Unified Confidence Score Calculation

**User Story:** As a data administrator, I want the confidence score to include dynamic attributes, so that I can accurately assess how well uploaded records match existing data.

#### Acceptance Criteria

1. WHEN calculating confidence score, THE Record Import System SHALL count all fields including core fields and dynamic attributes
2. WHEN calculating confidence score, THE Record Import System SHALL count matching fields where values are identical
3. WHEN calculating confidence score, THE Record Import System SHALL compute the percentage as (matching fields / total fields) × 100
4. WHEN a field exists in the upload but not in the database record, THE Record Import System SHALL count it as a non-matching field
5. WHEN a field exists in the database but not in the upload, THE Record Import System SHALL exclude it from the total field count
6. WHEN comparing field values, THE Record Import System SHALL treat null and empty string as equivalent for matching purposes

### Requirement 5: Enhanced Results Display

**User Story:** As a data administrator, I want to see detailed match information including which dynamic fields matched or didn't match, so that I can make informed decisions about record updates.

#### Acceptance Criteria

1. WHEN displaying match results, THE Record Import System SHALL show the unified confidence score as a percentage
2. WHEN displaying match results, THE Record Import System SHALL list all fields with their match status
3. WHEN a field matches, THE Record Import System SHALL display it with green color coding
4. WHEN a field does not match, THE Record Import System SHALL display it with red color coding and show both old and new values
5. WHEN a field is new (not in database), THE Record Import System SHALL display it with blue color coding
6. WHEN displaying results, THE Record Import System SHALL group core fields and dynamic attributes separately for clarity

### Requirement 6: Backward Compatibility

**User Story:** As a system maintainer, I want the enhanced system to maintain backward compatibility, so that existing functionality continues to work without disruption.

#### Acceptance Criteria

1. WHEN processing records without dynamic attributes, THE Record Import System SHALL function identically to the previous version
2. WHEN existing records have no additional_attributes, THE Record Import System SHALL handle them gracefully without errors
3. WHEN calculating confidence scores for records with only core fields, THE Record Import System SHALL produce accurate results
4. THE Record Import System SHALL maintain all existing API endpoints and their response formats
5. THE Record Import System SHALL ensure all 1,691 existing tests continue to pass

### Requirement 7: Test Coverage

**User Story:** As a developer, I want comprehensive test coverage using TDD approach, so that I can ensure the system works correctly and prevent regressions.

#### Acceptance Criteria

1. THE Record Import System SHALL include unit tests for dynamic attribute merging logic
2. THE Record Import System SHALL include unit tests for column mapping template CRUD operations
3. THE Record Import System SHALL include unit tests for unified confidence score calculation
4. THE Record Import System SHALL include property-based tests for merge strategies
5. THE Record Import System SHALL include integration tests for end-to-end template usage
6. THE Record Import System SHALL include property-based tests for confidence score calculation with various field combinations
7. WHEN all tests run, THE Record Import System SHALL maintain or exceed current test pass rate
