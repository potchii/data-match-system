# Requirements Document

## Introduction

This feature implements persistent storage for template field values using an EAV-style approach that leverages existing template infrastructure. Currently, template field values are only stored temporarily in match_results.field_breakdown JSON and are lost after viewing results. This feature enables progressive data enrichment where each upload adds or updates template data on MainSystem records, with intelligent merge strategies based on match confidence levels and conflict resolution for duplicate cases.

## Glossary

- **Template_Field**: Custom field definition stored in template_fields table (linked to column_mapping_templates)
- **Template_Field_Value**: Persistent storage of actual values for template fields (new: template_field_values table)
- **MainSystem**: Core record entity representing a person in the main_system table
- **Match_Confidence**: Percentage score indicating likelihood of record match (100%, 90%, 80%, 70%, 0%)
- **Enrich_And_Preserve**: Merge strategy that adds new data while preserving existing values with history
- **Progressive_Enrichment**: Process of accumulating data across multiple uploads to build complete records
- **Conflict_Resolution**: Process of handling value disagreements when POSSIBLE DUPLICATE (70% confidence) occurs
- **Batch_Audit_Trail**: Record of which upload batch added or modified which template field values
- **RecordImport**: Laravel import class that processes uploaded CSV files
- **DataMatchService**: Service that performs record matching and confidence scoring
- **ConfidenceScoreService**: Service that calculates match confidence and generates field breakdown data

## Requirements

### Requirement 1: Template Field Values Table

**User Story:** As a system architect, I want persistent storage for template field values, so that custom field data is retained across uploads and linked to MainSystem records.

#### Acceptance Criteria

1. THE System SHALL create `template_field_values` table with columns:
   - id (primary key)
   - main_system_id (foreign key to main_system.id)
   - template_field_id (foreign key to template_fields.id)
   - value (TEXT - the actual field value)
   - previous_value (TEXT - for history tracking)
   - batch_id (foreign key to upload_batches.id)
   - needs_review (BOOLEAN, default false)
   - conflict_with (foreign key to template_field_values.id, nullable)
   - created_at, updated_at
2. THE System SHALL add foreign key constraint on main_system_id with CASCADE delete
3. THE System SHALL add foreign key constraint on template_field_id with CASCADE delete
4. THE System SHALL add foreign key constraint on batch_id with SET NULL on delete
5. THE System SHALL add unique constraint on (main_system_id, template_field_id)
6. THE System SHALL add index on main_system_id
7. THE System SHALL add index on template_field_id
8. THE System SHALL add index on batch_id
9. THE System SHALL add index on needs_review for filtering conflict cases
10. THE migration SHALL be reversible

### Requirement 2: TemplateFieldValue Model

**User Story:** As a developer, I want an Eloquent model for template field values, so that I can manage persistent template data programmatically.

#### Acceptance Criteria

1. THE System SHALL create `App\Models\TemplateFieldValue` model
2. THE model SHALL define relationship: `belongsTo(MainSystem, 'main_system_id')`
3. THE model SHALL define relationship: `belongsTo(TemplateField, 'template_field_id')`
4. THE model SHALL define relationship: `belongsTo(UploadBatch, 'batch_id')`
5. THE model SHALL define relationship: `belongsTo(TemplateFieldValue, 'conflict_with')` for self-referencing conflicts
6. THE model SHALL cast needs_review to boolean
7. THE model SHALL cast created_at and updated_at to datetime
8. THE model SHALL provide method `hasConflict()` returning boolean
9. THE model SHALL provide method `getHistory()` returning array of value changes

### Requirement 3: Enhanced MainSystem Model

**User Story:** As a developer, I want MainSystem records to access their template field values, so that I can retrieve enriched data easily.

#### Acceptance Criteria

1. THE MainSystem model SHALL define relationship: `hasMany(TemplateFieldValue, 'main_system_id')`
2. THE MainSystem model SHALL provide method `getTemplateFieldValue($fieldName)` returning value or null
3. THE MainSystem model SHALL provide method `getAllTemplateFields()` returning associative array of field_name => value
4. THE MainSystem model SHALL provide method `hasTemplateField($fieldName)` returning boolean
5. THE MainSystem model SHALL provide method `getTemplateFieldsNeedingReview()` returning collection of conflicted values

### Requirement 4: Merge Strategy for NEW RECORD (0% Confidence)

**User Story:** As a data administrator, I want new records to store all template field values, so that complete data is captured from the first upload.

#### Acceptance Criteria

1. WHEN match confidence is 0% (NEW RECORD), THE System SHALL create new MainSystem record
2. WHEN new MainSystem record is created, THE System SHALL create TemplateFieldValue records for all non-empty template fields
3. FOR ALL template field values created, THE System SHALL set batch_id to current upload batch
4. FOR ALL template field values created, THE System SHALL set needs_review to false
5. FOR ALL template field values created, THE System SHALL set previous_value to null
6. THE System SHALL validate template field values against TemplateField data types before storing
7. WHEN template field value fails type validation, THE System SHALL log warning and skip that field

### Requirement 5: Merge Strategy for MATCHED Records (100%, 90%, 80% Confidence)

**User Story:** As a data administrator, I want matched records to be enriched with new template data while preserving existing values, so that data accumulates progressively across uploads.

#### Acceptance Criteria

1. WHEN match confidence is 100%, 90%, or 80%, THE System SHALL NOT create new MainSystem record
2. WHEN matched record has no existing TemplateFieldValue for a field, THE System SHALL create new TemplateFieldValue record
3. WHEN matched record has existing TemplateFieldValue with NULL or empty value, THE System SHALL update value and set previous_value to NULL
4. WHEN matched record has existing TemplateFieldValue with non-empty value, THE System SHALL update value and preserve old value in previous_value
5. FOR ALL updated TemplateFieldValue records, THE System SHALL update batch_id to current upload batch
6. FOR ALL updated TemplateFieldValue records, THE System SHALL set needs_review to false
7. THE System SHALL update updated_at timestamp for all modified TemplateFieldValue records
8. THE System SHALL compare new value with existing value and skip update if values are identical

### Requirement 6: Merge Strategy for POSSIBLE DUPLICATE (70% Confidence)

**User Story:** As a data administrator, I want possible duplicate matches to flag conflicts for review, so that I can manually verify before merging data.

#### Acceptance Criteria

1. WHEN match confidence is 70% (POSSIBLE DUPLICATE), THE System SHALL NOT create new MainSystem record
2. WHEN possible duplicate has template field values, THE System SHALL create TemplateFieldValue records with needs_review set to true
3. WHEN possible duplicate conflicts with existing TemplateFieldValue, THE System SHALL set conflict_with to reference existing record
4. FOR ALL conflicted TemplateFieldValue records, THE System SHALL set batch_id to current upload batch
5. THE System SHALL NOT overwrite existing TemplateFieldValue records for possible duplicates
6. THE System SHALL store conflicting values as separate TemplateFieldValue records linked via conflict_with
7. WHEN user resolves conflict, THE System SHALL update needs_review to false and merge chosen value

### Requirement 7: Template Field Value Persistence Service

**User Story:** As a developer, I want a dedicated service for persisting template field values, so that merge logic is centralized and testable.

#### Acceptance Criteria

1. THE System SHALL create `App\Services\TemplateFieldPersistenceService` class
2. THE service SHALL provide method `persistTemplateFields($mainSystemId, $templateFields, $batchId, $matchConfidence)`
3. THE service SHALL implement Enrich_And_Preserve merge strategy based on Match_Confidence
4. THE service SHALL handle NEW RECORD scenario (0% confidence)
5. THE service SHALL handle MATCHED scenario (100%, 90%, 80% confidence)
6. THE service SHALL handle POSSIBLE DUPLICATE scenario (70% confidence)
7. THE service SHALL validate field values against TemplateField definitions
8. THE service SHALL use database transactions to ensure atomicity
9. THE service SHALL log all persistence operations for audit trail
10. THE service SHALL return summary of created, updated, and conflicted fields

### Requirement 8: Integration with RecordImport

**User Story:** As a developer, I want RecordImport to persist template field values after matching, so that enrichment happens automatically during upload processing.

#### Acceptance Criteria

1. WHEN RecordImport processes a row with template fields, THE System SHALL extract template field values from row data
2. WHEN RecordImport completes matching for a row, THE System SHALL call TemplateFieldPersistenceService
3. THE RecordImport SHALL pass main_system_id, template fields, batch_id, and match confidence to persistence service
4. THE RecordImport SHALL handle persistence errors gracefully without stopping entire import
5. WHEN persistence fails for a row, THE RecordImport SHALL log error with row number and continue processing
6. THE RecordImport SHALL maintain existing functionality for core MainSystem fields
7. THE RecordImport SHALL persist template fields only after MainSystem record is saved

### Requirement 9: Integration with DataMatchService

**User Story:** As a developer, I want DataMatchService to pass template field data to persistence layer, so that matching and enrichment work together seamlessly.

#### Acceptance Criteria

1. WHEN DataMatchService performs matching, THE System SHALL include template field data in match result
2. THE DataMatchService SHALL pass template field values to RecordImport via match result structure
3. THE DataMatchService SHALL maintain field_breakdown structure to include template fields
4. THE DataMatchService SHALL NOT modify template field values during matching process
5. THE DataMatchService SHALL preserve template field data types from uploaded file

### Requirement 10: Enhanced Results View with Template Field Values

**User Story:** As a data analyst, I want to see enriched template field values in the results view, so that I can verify data enrichment worked correctly.

#### Acceptance Criteria

1. WHEN viewing match results, THE Results_View SHALL display template field values from template_field_values table
2. THE Results_View SHALL show which batch added each template field value
3. THE Results_View SHALL display previous_value when available to show data history
4. WHERE needs_review is true, THE Results_View SHALL highlight conflicted fields with warning indicator
5. THE Results_View SHALL provide link to view full template field history for a record
6. THE Field_Breakdown_Modal SHALL display both current and previous values for template fields
7. THE Results_View SHALL show template fields even for records from previous batches

### Requirement 11: Conflict Resolution UI

**User Story:** As a data administrator, I want a UI to review and resolve conflicted template field values, so that I can merge possible duplicates with confidence.

#### Acceptance Criteria

1. THE System SHALL provide conflict resolution page accessible from Results_View
2. THE conflict resolution page SHALL list all TemplateFieldValue records where needs_review is true
3. FOR ALL conflicted values, THE page SHALL display side-by-side comparison of existing vs new value
4. THE page SHALL provide "Keep Existing", "Use New", and "Edit Manually" options for each conflict
5. WHEN user selects "Keep Existing", THE System SHALL delete conflicting TemplateFieldValue record
6. WHEN user selects "Use New", THE System SHALL update existing record with new value and set needs_review to false
7. WHEN user selects "Edit Manually", THE System SHALL provide text input to enter custom value
8. THE page SHALL allow bulk resolution of multiple conflicts at once
9. THE page SHALL filter conflicts by batch, field name, or MainSystem record
10. WHEN all conflicts for a record are resolved, THE System SHALL update match_results status if applicable

### Requirement 12: Batch Audit Trail

**User Story:** As a data administrator, I want to see which batch added or modified each template field value, so that I can track data provenance.

#### Acceptance Criteria

1. FOR ALL TemplateFieldValue records, THE System SHALL store batch_id of the batch that created or last modified the value
2. THE System SHALL provide query method to retrieve all template field values added by a specific batch
3. THE System SHALL provide query method to retrieve all template field values modified by a specific batch
4. WHEN viewing a MainSystem record, THE System SHALL display batch information for each template field value
5. THE System SHALL maintain batch_id even when UploadBatch is deleted (SET NULL constraint)
6. THE System SHALL provide audit log showing timeline of template field value changes per record

### Requirement 13: Performance Optimization

**User Story:** As a system administrator, I want template field persistence to be performant, so that large uploads complete in reasonable time.

#### Acceptance Criteria

1. THE TemplateFieldPersistenceService SHALL use bulk insert operations when creating multiple TemplateFieldValue records
2. THE System SHALL use indexed queries on main_system_id + template_field_id for lookups
3. THE System SHALL lazy-load template field values only when needed in Results_View
4. WHEN processing batch with 1000+ records, THE System SHALL complete template field persistence within 10 seconds
5. THE System SHALL use database transactions efficiently to minimize lock time
6. THE System SHALL cache TemplateField definitions during batch processing to avoid repeated queries
7. THE System SHALL use eager loading when displaying multiple records with template fields

### Requirement 14: Data Integrity and Validation

**User Story:** As a system architect, I want strong data integrity for template field values, so that data quality is maintained.

#### Acceptance Criteria

1. THE System SHALL enforce foreign key constraints on all relationships
2. THE System SHALL validate template field values against TemplateField data types before persistence
3. THE System SHALL prevent duplicate TemplateFieldValue records for same main_system_id + template_field_id combination
4. WHEN MainSystem record is deleted, THE System SHALL cascade delete all associated TemplateFieldValue records
5. WHEN TemplateField definition is deleted, THE System SHALL cascade delete all associated TemplateFieldValue records
6. THE System SHALL validate that template_field_id belongs to the same template used for the upload
7. THE System SHALL handle database constraint violations gracefully with meaningful error messages

### Requirement 15: Backward Compatibility

**User Story:** As a system maintainer, I want existing functionality to continue working, so that migration to persistent template fields is smooth.

#### Acceptance Criteria

1. THE System SHALL maintain existing match_results.field_breakdown JSON structure
2. THE System SHALL continue to populate field_breakdown with template field data for backward compatibility
3. THE System SHALL support viewing results from batches uploaded before template field persistence was implemented
4. THE System SHALL NOT break existing RecordImport functionality for uploads without templates
5. THE System SHALL handle cases where template_field_values table is empty gracefully
6. THE System SHALL provide migration path for existing field_breakdown data to template_field_values table

### Requirement 16: API Endpoints for Template Field Values

**User Story:** As a developer, I want API endpoints to access template field values, so that I can build integrations and reports.

#### Acceptance Criteria

1. THE System SHALL provide endpoint: `GET /api/main-system/{id}/template-fields`
2. THE System SHALL provide endpoint: `GET /api/template-field-values/{id}`
3. THE System SHALL provide endpoint: `PUT /api/template-field-values/{id}` for manual updates
4. THE System SHALL provide endpoint: `GET /api/batches/{id}/template-field-values` for batch audit
5. THE System SHALL provide endpoint: `GET /api/template-field-values/conflicts` for conflict list
6. THE System SHALL provide endpoint: `POST /api/template-field-values/{id}/resolve` for conflict resolution
7. ALL endpoints SHALL require authentication
8. ALL endpoints SHALL return JSON responses with appropriate HTTP status codes
9. THE endpoints SHALL support pagination for large result sets
10. THE endpoints SHALL include batch information and history in responses

### Requirement 17: Documentation and Examples

**User Story:** As a developer, I want comprehensive documentation for template field persistence, so that I can understand and extend the feature.

#### Acceptance Criteria

1. THE System SHALL provide database schema documentation for template_field_values table
2. THE System SHALL provide code documentation for TemplateFieldPersistenceService
3. THE System SHALL provide examples of merge strategies for each confidence level
4. THE System SHALL document conflict resolution workflow with screenshots
5. THE System SHALL provide API documentation with request/response examples
6. THE System SHALL document performance considerations and optimization tips
7. THE System SHALL provide migration guide for existing installations
