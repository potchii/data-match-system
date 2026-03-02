# Implementation Plan: Template Field Persistence

## Overview

This implementation plan breaks down the template field persistence feature into discrete coding tasks. The feature implements an EAV-style persistent storage system for template field values with intelligent merge strategies based on match confidence levels (NEW RECORD 0%, MATCHED 80-100%, POSSIBLE DUPLICATE 70%).

The implementation follows a bottom-up approach: database schema → models → services → integration → UI → API → testing.

## Tasks

- [x] 1. Create database migration and schema
  - Create migration file for template_field_values table with all columns (id, main_system_id, template_field_id, value, previous_value, batch_id, needs_review, conflict_with, timestamps)
  - Add foreign key constraints with CASCADE on main_system_id and template_field_id
  - Add foreign key constraint with SET NULL on batch_id
  - Add self-referencing foreign key on conflict_with
  - Add unique constraint on (main_system_id, template_field_id)
  - Add indexes on main_system_id, template_field_id, batch_id, and needs_review
  - Implement reversible down() method
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10_

- [x] 1.1 Write property test for migration schema
  - **Property 3: Migration Reversibility**
  - **Validates: Requirements 1.10**

- [x] 2. Create TemplateFieldValue model
  - [x] 2.1 Create TemplateFieldValue Eloquent model with fillable fields
    - Define fillable array (main_system_id, template_field_id, value, previous_value, batch_id, needs_review, conflict_with)
    - Add casts for needs_review (boolean), created_at and updated_at (datetime)
    - Set table name to 'template_field_values'
    - _Requirements: 2.1, 2.6, 2.7_

  - [x] 2.2 Add relationships to TemplateFieldValue model
    - Define belongsTo relationship to MainSystem
    - Define belongsTo relationship to TemplateField
    - Define belongsTo relationship to UploadBatch
    - Define self-referencing belongsTo relationship for conflict_with
    - _Requirements: 2.2, 2.3, 2.4, 2.5_

  - [x] 2.3 Add helper methods to TemplateFieldValue model
    - Implement hasConflict() method returning boolean
    - Implement getHistory() method returning array of value changes
    - Implement resolveConflict() method for conflict resolution
    - _Requirements: 2.8, 2.9_

  - [x] 2.4 Write property tests for TemplateFieldValue model
    - **Property 4: Model Relationship Integrity**
    - **Property 5: Type Casting Correctness**
    - **Property 6: Conflict Detection**
    - **Property 7: History Retrieval**
    - **Validates: Requirements 2.2-2.9**

- [x] 3. Enhance MainSystem model with template field access
  - [x] 3.1 Add templateFieldValues relationship to MainSystem
    - Define hasMany relationship to TemplateFieldValue
    - _Requirements: 3.1_

  - [x] 3.2 Add template field accessor methods to MainSystem
    - Implement getTemplateFieldValue($fieldName) method
    - Implement getAllTemplateFields() method returning associative array
    - Implement hasTemplateField($fieldName) method
    - Implement getTemplateFieldsNeedingReview() method
    - _Requirements: 3.2, 3.3, 3.4, 3.5_

  - [x] 3.3 Write property tests for MainSystem template field methods
    - **Property 8: MainSystem Template Field Access**
    - **Property 9: MainSystem All Template Fields Retrieval**
    - **Property 10: MainSystem Conflict Filtering**
    - **Validates: Requirements 3.2-3.5**

- [x] 4. Create TemplateFieldPersistenceService
  - [x] 4.1 Create service class with constructor and dependencies
    - Create TemplateFieldPersistenceService class in app/Services
    - Inject dependencies (TemplateFieldValue model, Logger)
    - Add constructor with dependency injection
    - _Requirements: 7.1_

  - [x] 4.2 Implement field validation logic
    - Create validateFieldValue() private method
    - Validate against TemplateField data types (string, integer, decimal, date, boolean)
    - Return validation result with error messages
    - _Requirements: 7.7, 4.6, 4.7, 12_

  - [x] 4.3 Write property test for field validation
    - **Property 12: Template Field Type Validation**
    - **Validates: Requirements 4.6, 4.7, 14.2**

  - [x] 4.3 Implement NEW RECORD merge strategy
    - Create handleNewRecord() private method
    - Create TemplateFieldValue records for all non-empty template fields
    - Set batch_id, needs_review=false, previous_value=null
    - Use bulk insert for multiple fields
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 7.4_

  - [x] 4.4 Write property test for NEW RECORD strategy
    - **Property 11: NEW RECORD Creates MainSystem and Template Values**
    - **Validates: Requirements 4.1-4.5**

  - [x] 4.4 Implement MATCHED RECORD merge strategy
    - Create handleMatchedRecord() private method
    - Query existing TemplateFieldValue records
    - Implement Enrich_And_Preserve logic (create new, update empty, preserve history)
    - Skip identical values
    - Update batch_id and timestamps
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 7.5_

  - [x] 4.5 Write property tests for MATCHED RECORD strategy
    - **Property 13: MATCHED Record Does Not Create New MainSystem**
    - **Property 14: MATCHED Record Creates New Template Field Values**
    - **Property 15: MATCHED Record Updates Empty Values**
    - **Property 16: MATCHED Record Preserves History**
    - **Property 17: MATCHED Record Updates Timestamp**
    - **Property 18: MATCHED Record Idempotence**
    - **Validates: Requirements 5.1-5.8**

  - [x] 4.5 Implement POSSIBLE DUPLICATE merge strategy
    - Create handlePossibleDuplicate() private method
    - Create new TemplateFieldValue records with needs_review=true
    - Set conflict_with to existing record ID if exists
    - Do not modify existing records
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.6_

  - [x] 4.6 Write property tests for POSSIBLE DUPLICATE strategy
    - **Property 19: POSSIBLE DUPLICATE Does Not Create New MainSystem**
    - **Property 20: POSSIBLE DUPLICATE Flags for Review**
    - **Property 21: POSSIBLE DUPLICATE Links Conflicts**
    - **Validates: Requirements 6.1-6.6**

  - [x] 4.6 Implement main persistTemplateFields() method
    - Create public persistTemplateFields() method
    - Route to appropriate merge strategy based on match confidence
    - Wrap operations in database transaction
    - Add audit logging for all operations
    - Return summary of created, updated, and conflicted fields
    - _Requirements: 7.2, 7.3, 7.8, 7.9, 7.10_

  - [x] 4.7 Write property tests for persistence service
    - **Property 23: Persistence Service Transaction Atomicity**
    - **Property 24: Persistence Service Audit Logging**
    - **Property 25: Persistence Service Return Summary**
    - **Validates: Requirements 7.8-7.10**

  - [x] 4.7 Implement resolveConflict() method
    - Create public resolveConflict() method
    - Handle "keep_existing", "use_new", and "edit_manually" options
    - Update needs_review flag
    - Validate custom values against field types
    - _Requirements: 6.7_

  - [x] 4.8 Write property test for conflict resolution
    - **Property 22: Conflict Resolution Updates State**
    - **Validates: Requirements 6.7**

  - [x] 4.8 Implement bulkPersist() method for performance
    - Create public bulkPersist() method for batch operations
    - Use bulk insert operations
    - Cache TemplateField definitions
    - Optimize for large batches (1000+ records)
    - _Requirements: 13.1, 13.2, 13.6_

  - [x] 4.9 Write property tests for performance optimization
    - **Property 35: Bulk Insert Performance**
    - **Property 36: Large Batch Performance**
    - **Property 37: Template Field Definition Caching**
    - **Validates: Requirements 13.1, 13.2, 13.4, 13.6**

- [x] 5. Checkpoint - Ensure all tests pass
  - Run all unit and property tests for models and service
  - Verify database migration works correctly
  - Ask the user if questions arise

- [x] 6. Integrate with DataMappingService
  - [x] 6.1 Modify DataMappingService to separate core and template fields
    - Update mapUploadedData() method to return structure with 'core_fields' and 'template_fields'
    - Extract template fields from mapped data based on TemplateField definitions
    - Preserve field data types from uploaded file
    - _Requirements: 9.1, 9.5_

  - [x] 6.2 Write property test for DataMappingService template field handling
    - **Property 32: DataMatchService Preserves Template Field Values**
    - **Validates: Requirements 9.4, 9.5**

- [x] 7. Integrate with DataMatchService
  - [x] 7.1 Modify DataMatchService to include template fields in match results
    - Update match result structure to include template field data
    - Maintain field_breakdown structure with template fields
    - Do not modify template field values during matching
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x] 7.2 Write property test for DataMatchService integration
    - **Property 31: DataMatchService Includes Template Fields**
    - **Validates: Requirements 9.1-9.3**

- [x] 8. Integrate with RecordImport
  - [x] 8.1 Modify RecordImport to extract and persist template fields
    - Extract template field values from mapped data
    - Call TemplateFieldPersistenceService after MainSystem record is saved
    - Pass main_system_id, template fields, batch_id, and match confidence
    - Handle persistence errors gracefully without stopping import
    - Log errors with row number and continue processing
    - Maintain backward compatibility for uploads without templates
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

  - [x] 8.2 Write property tests for RecordImport integration
    - **Property 26: RecordImport Extracts Template Fields**
    - **Property 27: RecordImport Calls Persistence Service**
    - **Property 28: RecordImport Error Handling**
    - **Property 29: RecordImport Backward Compatibility**
    - **Property 30: RecordImport Persistence Ordering**
    - **Validates: Requirements 8.1-8.7, 15.4**

- [x] 9. Checkpoint - Ensure integration tests pass
  - Run full upload workflow tests with templates
  - Test NEW RECORD, MATCHED, and POSSIBLE DUPLICATE scenarios
  - Verify backward compatibility with non-template uploads
  - Ask the user if questions arise

- [x] 10. Enhance Results view with template field display
  - [x] 10.1 Modify Results controller to load template field values
    - Eager load templateFieldValues relationship on MainSystem records
    - Include batch information for each template field value
    - Filter and prepare data for view
    - _Requirements: 10.1, 10.2, 13.3, 13.7_

  - [x] 10.2 Update results.blade.php to display template fields
    - Add template field values section to results table
    - Display current value, previous value, and batch information
    - Add warning indicator for needs_review=true fields
    - Add link to view field history
    - Style conflicted fields with visual highlighting
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.7_

  - [x] 10.3 Create or enhance Field Breakdown Modal
    - Display both current and previous values for template fields
    - Show full history timeline for fields with multiple updates
    - Include batch information for each change
    - _Requirements: 10.6_

  - [x] 10.4 Write unit tests for Results view enhancements
    - Test template field display in results
    - Test conflict highlighting
    - Test history modal rendering
    - _Requirements: 10.1-10.7_

- [x] 11. Create Conflict Resolution UI
  - [x] 11.1 Create ConflictResolutionController
    - Create controller in app/Http/Controllers
    - Implement index() method to list conflicts
    - Implement resolve() method for single conflict resolution
    - Implement bulkResolve() method for multiple conflicts
    - Add filtering by batch, field name, or MainSystem record
    - _Requirements: 11.1, 11.2, 11.9_

  - [x] 11.2 Create conflicts.blade.php view
    - List all TemplateFieldValue records where needs_review=true
    - Display side-by-side comparison of existing vs new value
    - Add "Keep Existing", "Use New", and "Edit Manually" buttons
    - Implement bulk selection and resolution
    - Add filters for batch, field name, and record
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.9_

  - [x] 11.3 Implement conflict resolution actions
    - Handle "Keep Existing" action (delete conflicting record)
    - Handle "Use New" action (update existing with new value)
    - Handle "Edit Manually" action (custom value input with validation)
    - Update match_results status when all conflicts resolved
    - _Requirements: 11.5, 11.6, 11.7, 11.8, 11.10_

  - [x] 11.4 Add routes for conflict resolution
    - Add GET /conflicts route
    - Add POST /conflicts/{id}/resolve route
    - Add POST /conflicts/bulk-resolve route
    - _Requirements: 11.1_

  - [x] 11.5 Write feature tests for conflict resolution UI
    - Test conflict listing and filtering
    - Test each resolution action
    - Test bulk resolution
    - Test validation of custom values
    - _Requirements: 11.1-11.10_

- [x] 12. Implement batch audit trail functionality
  - [x] 12.1 Add query methods for batch audit
    - Add scope to TemplateFieldValue model for filtering by batch
    - Create method to retrieve all values added by specific batch
    - Create method to retrieve all values modified by specific batch
    - Create method to retrieve timeline of changes for a MainSystem record
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.6_

  - [x] 12.2 Update MainSystem record view to show batch provenance
    - Display batch information for each template field value
    - Show audit timeline of template field changes
    - Handle cases where batch_id is NULL (deleted batches)
    - _Requirements: 12.4, 12.5, 12.6_

  - [x] 12.3 Write property tests for batch audit trail
    - **Property 33: Batch Audit Trail**
    - **Property 34: Audit Timeline Retrieval**
    - **Validates: Requirements 12.1-12.6**

- [x] 13. Create API endpoints for template field values
  - [x] 13.1 Create TemplateFieldValueController for API
    - Create controller in app/Http/Controllers/Api
    - Add authentication middleware
    - Implement pagination for large result sets
    - _Requirements: 16.7, 16.9_

  - [x] 13.2 Implement GET endpoints
    - Implement GET /api/main-system/{id}/template-fields
    - Implement GET /api/template-field-values/{id}
    - Implement GET /api/batches/{id}/template-field-values
    - Implement GET /api/template-field-values/conflicts
    - Include batch information and history in responses
    - _Requirements: 16.1, 16.2, 16.4, 16.5, 16.10_

  - [x] 13.3 Implement POST/PUT endpoints
    - Implement PUT /api/template-field-values/{id} for manual updates
    - Implement POST /api/template-field-values/{id}/resolve for conflict resolution
    - Return appropriate HTTP status codes
    - _Requirements: 16.3, 16.6, 16.8_

  - [x] 13.4 Add API routes
    - Add all API routes to routes/api.php
    - Apply authentication middleware
    - _Requirements: 16.1-16.7_

  - [x] 13.5 Write API endpoint tests
    - Test all GET endpoints with various scenarios
    - Test PUT endpoint for updates
    - Test POST endpoint for conflict resolution
    - Test authentication requirements
    - Test pagination
    - Test error responses
    - _Requirements: 16.1-16.10_

- [x] 14. Implement data integrity and validation
  - [x] 14.1 Add validation for template field belongs to upload template
    - Validate template_field_id belongs to same template used for upload
    - Add validation in TemplateFieldPersistenceService
    - _Requirements: 14.6_

  - [x] 14.2 Write property tests for data integrity
    - **Property 1: Foreign Key Cascade Behavior**
    - **Property 2: Unique Constraint Enforcement**
    - **Property 38: Template Field Belongs to Upload Template**
    - **Property 39: Constraint Violation Error Messages**
    - **Validates: Requirements 1.2-1.5, 14.1-14.7**

  - [x] 14.2 Implement graceful error handling for constraint violations
    - Catch and handle unique constraint violations
    - Catch and handle foreign key violations
    - Return meaningful error messages
    - _Requirements: 14.7_

- [x] 15. Ensure backward compatibility
  - [x] 15.1 Maintain field_breakdown JSON structure
    - Continue populating match_results.field_breakdown with template field data
    - Ensure existing code reading field_breakdown still works
    - _Requirements: 15.1, 15.2_

  - [x] 15.2 Handle legacy batches and empty table
    - Support viewing results from batches before template field persistence
    - Handle queries when template_field_values table is empty
    - Gracefully handle missing template field values
    - _Requirements: 15.3, 15.5_

  - [x] 15.3 Write property tests for backward compatibility
    - **Property 40: Backward Compatibility with field_breakdown**
    - **Property 41: Legacy Batch Support**
    - **Property 42: Empty Table Handling**
    - **Validates: Requirements 15.1-15.5**

- [x] 16. Final checkpoint - Comprehensive testing
  - Run all unit tests, property tests, and feature tests
  - Run performance tests with 1000+ record batches
  - Test full workflow: upload → matching → persistence → results → conflict resolution
  - Verify all 42 correctness properties pass
  - Ensure all tests pass, ask the user if questions arise

- [x] 17. Integration and final wiring
  - [x] 17.1 Verify all components are wired together
    - Test complete upload workflow with templates
    - Test NEW RECORD scenario end-to-end
    - Test MATCHED scenario end-to-end
    - Test POSSIBLE DUPLICATE scenario end-to-end
    - Test conflict resolution workflow
    - Test API endpoints integration
    - _Requirements: All_

  - [x] 17.2 Performance validation
    - Test with 1000+ record batch
    - Verify completion within 10 seconds
    - Check database query efficiency
    - Verify bulk operations are used
    - _Requirements: 13.1-13.7_

  - [x] 17.3 Write integration tests for complete workflows
    - Test full upload with NEW RECORDS
    - Test full upload with MATCHED records
    - Test full upload with POSSIBLE DUPLICATES
    - Test conflict resolution workflow
    - Test batch audit trail
    - _Requirements: All_

## Notes

- Tasks marked with `*` are optional property-based tests and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
- The implementation follows bottom-up approach: schema → models → services → integration → UI → API
- All database operations use transactions for atomicity
- Performance optimization is built-in from the start (bulk operations, caching, indexing)
- Backward compatibility is maintained throughout
