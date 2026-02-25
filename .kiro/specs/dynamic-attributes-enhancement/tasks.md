# Implementation Plan: Dynamic Attributes Enhancement & Column Mapping Templates

## Overview

This implementation plan breaks down the enhancement into discrete, testable steps following TDD principles. Each task builds on previous work, with property-based tests integrated throughout to validate correctness. The plan maintains backward compatibility while adding three major capabilities: dynamic attribute merging for matched records, column mapping templates, and unified confidence scoring.

## Tasks

- [x] 1. Create database migrations and models
  - [x] 1.1 Create column_mapping_templates migration
    - Add table with id, user_id, name, mappings (JSON), timestamps
    - Add unique constraint on (user_id, name)
    - Add index on user_id for query performance
    - _Requirements: 2.1, 2.5_
  
  - [x] 1.2 Add field_breakdown column to match_results
    - Add nullable JSON column for detailed field comparison
    - _Requirements: 5.2_
  
  - [x] 1.3 Create ColumnMappingTemplate model
    - Define fillable fields and casts
    - Add user relationship
    - Implement validation rules
    - _Requirements: 2.2, 2.4_
  
  - [ ]* 1.4 Write unit tests for ColumnMappingTemplate model
    - Test model creation and relationships
    - Test JSON casting
    - Test validation rules
    - _Requirements: 2.2, 2.3, 2.4_

- [x] 2. Implement DynamicAttributeMergeService
  - [x] 2.1 Create DynamicAttributeMergeService class
    - Implement merge() method with preservation and overwrite logic
    - Implement validateSize() method for 65KB limit
    - Add comprehensive PHPDoc comments
    - _Requirements: 1.1, 1.2, 1.3_
  
  - [ ]* 2.2 Write property test for merge preservation and overwriting
    - **Property 1: Dynamic Attribute Merge Preserves and Overwrites Correctly**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**
    - Generate random existing and new attribute sets
    - Verify preservation of non-conflicting attributes
    - Verify overwriting of conflicting attributes
    - Verify merged result persists correctly
  
  - [ ]* 2.3 Write unit tests for merge edge cases
    - Test empty existing attributes
    - Test empty new attributes
    - Test size limit validation
    - Test invalid attribute values
    - _Requirements: 1.5_

- [x] 3. Implement ConfidenceScoreService
  - [x] 3.1 Create ConfidenceScoreService class
    - Implement calculateUnifiedScore() method
    - Implement generateBreakdown() method
    - Implement valuesMatch() with null/empty equivalence
    - Add field categorization (core vs dynamic)
    - _Requirements: 4.1, 4.2, 4.3, 4.6_
  
  - [ ]* 3.2 Write property test for unified confidence calculation
    - **Property 9: Unified Confidence Score Calculation**
    - **Validates: Requirements 4.1, 4.2, 4.3**
    - Generate random records with varied field combinations
    - Verify score equals (matches / total) × 100
    - Verify all fields are counted correctly
  
  - [ ]* 3.3 Write property test for field inclusion logic
    - **Property 10: Field Inclusion Logic**
    - **Validates: Requirements 4.4, 4.5**
    - Generate records with asymmetric fields
    - Verify upload-only fields counted in total but not matches
    - Verify database-only fields excluded from calculation
  
  - [ ]* 3.4 Write property test for null/empty equivalence
    - **Property 11: Null and Empty String Equivalence**
    - **Validates: Requirements 4.6**
    - Generate field pairs with null and empty string
    - Verify they are treated as matching
  
  - [ ]* 3.5 Write property test for breakdown completeness
    - **Property 12: Match Breakdown Completeness**
    - **Validates: Requirements 5.2, 5.4, 5.5, 5.6**
    - Generate random match scenarios
    - Verify breakdown contains all uploaded fields
    - Verify each field has correct status and values
  
  - [ ]* 3.6 Write unit tests for confidence score edge cases
    - Test records with only core fields
    - Test records with only dynamic fields
    - Test records with no matching fields
    - Test records with all matching fields
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 4. Checkpoint - Ensure core services tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Enhance DataMatchService for unified scoring
  - [x] 5.1 Inject ConfidenceScoreService into DataMatchService
    - Add constructor parameter
    - Update service instantiation
    - _Requirements: 4.1_
  
  - [x] 5.2 Modify findMatch() to use unified scoring
    - Replace rule-based confidence with unified calculation
    - Generate field breakdown for all matches
    - Maintain backward compatibility for NEW RECORD status
    - _Requirements: 4.1, 4.2, 4.3_
  
  - [x] 5.3 Add updateMatchedRecord() method
    - Accept MainSystem record and dynamic fields
    - Use DynamicAttributeMergeService to merge attributes
    - Persist merged attributes to database
    - _Requirements: 1.1, 1.4_
  
  - [ ]* 5.4 Write integration test for match with unified scoring
    - Test finding match with dynamic attributes
    - Verify confidence score includes dynamic fields
    - Verify field breakdown is generated
    - _Requirements: 4.1, 4.2, 4.3, 5.2_
  
  - [ ]* 5.5 Write property test for backward compatibility
    - **Property 13: Backward Compatibility for Core-Only Records**
    - **Validates: Requirements 6.1, 6.3**
    - Generate records with only core fields
    - Verify results match previous system behavior
    - Verify confidence scores are accurate

- [x] 6. Implement template management in DataMappingService
  - [x] 6.1 Add applyTemplate() method to DataMappingService
    - Accept row and optional template
    - Remap columns according to template mappings
    - Handle missing columns gracefully
    - _Requirements: 3.4, 3.7_
  
  - [x] 6.2 Add generateTemplateFromMapping() method
    - Analyze sample row and current mappings
    - Generate template-ready mapping structure
    - _Requirements: 3.1_
  
  - [ ]* 6.3 Write property test for template application
    - **Property 7: Template Application Remaps Correctly**
    - **Validates: Requirements 3.4, 3.7**
    - Generate random templates and upload data
    - Verify correct remapping of present columns
    - Verify graceful handling of missing columns
  
  - [ ]* 6.4 Write unit tests for template generation
    - Test generation from various column configurations
    - Test handling of compound names
    - Test handling of dynamic fields
    - _Requirements: 3.1_

- [x] 7. Create TemplateController and routes
  - [x] 7.1 Create TemplateController with CRUD methods
    - Implement index() - list user's templates
    - Implement store() - create new template
    - Implement show() - get template details
    - Implement update() - modify template
    - Implement destroy() - delete template
    - Add authorization checks (user owns template)
    - _Requirements: 3.3, 3.5, 3.6_
  
  - [x] 7.2 Add template routes to web.php
    - Add authenticated route group
    - Define RESTful routes for templates
    - _Requirements: 3.3, 3.5, 3.6_
  
  - [ ]* 7.3 Write property test for user template isolation
    - **Property 6: User Template Isolation**
    - **Validates: Requirements 3.3**
    - Generate templates for multiple users
    - Verify each user sees only their templates
  
  - [ ]* 7.4 Write property test for template CRUD round trip
    - **Property 8: Template CRUD Round Trip**
    - **Validates: Requirements 3.5, 3.6**
    - Create, retrieve, update, retrieve, delete template
    - Verify all operations work correctly
  
  - [ ]* 7.5 Write property test for unique template names
    - **Property 5: Unique Template Names Per User**
    - **Validates: Requirements 2.5**
    - Attempt to create duplicate template names
    - Verify second attempt fails
  
  - [ ]* 7.6 Write property test for template user association
    - **Property 4: Template User Association**
    - **Validates: Requirements 2.4**
    - Create templates as different users
    - Verify user_id matches authenticated user
  
  - [ ]* 7.7 Write unit tests for TemplateController
    - Test authorization (user can't access others' templates)
    - Test validation errors
    - Test successful CRUD operations
    - _Requirements: 2.3, 2.4, 2.5, 3.3, 3.5, 3.6_

- [x] 8. Checkpoint - Ensure template system tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Integrate template support into upload flow
  - [x] 9.1 Modify UploadController to accept template_id parameter
    - Add optional template_id to store() method
    - Load template if provided
    - Pass template to RecordImport
    - _Requirements: 3.4_
  
  - [x] 9.2 Update RecordImport to apply templates
    - Accept template in constructor
    - Apply template before mapping if provided
    - Update column mapping summary generation
    - _Requirements: 3.4, 3.7_
  
  - [ ]* 9.3 Write integration test for upload with template
    - Upload file with template applied
    - Verify columns are remapped correctly
    - Verify records are processed correctly
    - _Requirements: 3.4, 3.7_

- [x] 10. Update RecordImport to save dynamic attributes for matched records
  - [x] 10.1 Modify collection() method to handle matched records
    - When match status is not NEW RECORD, call updateMatchedRecord()
    - Pass dynamic fields to DataMatchService
    - Store field breakdown in MatchResult
    - _Requirements: 1.1, 1.4, 5.2_
  
  - [x] 10.2 Update MatchResult creation to include field_breakdown
    - Store detailed field comparison from ConfidenceScoreService
    - Include both core and dynamic field breakdowns
    - _Requirements: 5.2, 5.6_
  
  - [ ]* 10.3 Write integration test for matched record attribute update
    - Upload record that matches existing
    - Verify dynamic attributes are merged
    - Verify field breakdown is stored
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 11. Create template management UI views
  - [x] 11.1 Create templates index view
    - List user's templates with name and created date
    - Add edit and delete buttons
    - Add "Create New Template" button
    - _Requirements: 3.3_
  
  - [x] 11.2 Create template form view (create/edit)
    - Form for template name
    - JSON editor or key-value pairs for mappings
    - Validation feedback
    - _Requirements: 3.2, 3.5_
  
  - [x] 11.3 Add template selector to upload page
    - Dropdown of user's templates
    - "Save as template" button after preview
    - Template application feedback
    - _Requirements: 3.1, 3.4_

- [x] 12. Enhance results view with detailed breakdown
  - [x] 12.1 Update results.blade.php to show field breakdown
    - Display unified confidence score prominently
    - Show field-by-field comparison table
    - Color-code fields: green (match), red (mismatch), blue (new)
    - Group core and dynamic fields separately
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
  
  - [ ]* 12.2 Write browser test for results display
    - Verify confidence score is displayed
    - Verify field breakdown is rendered
    - Verify color coding is applied
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 13. Add property-based test for template mappings round trip
  - [ ]* 13.1 Write property test for JSON serialization
    - **Property 2: Template Mappings Round Trip**
    - **Validates: Requirements 2.2**
    - Generate random mapping structures
    - Serialize to JSON and deserialize
    - Verify equivalence

- [x] 14. Add property-based test for invalid JSON rejection
  - [ ]* 14.1 Write property test for validation
    - **Property 3: Invalid JSON Rejected**
    - **Validates: Requirements 2.3**
    - Generate invalid JSON strings
    - Attempt to store as template mappings
    - Verify rejection with appropriate error

- [x] 15. Add backward compatibility tests
  - [ ]* 15.1 Write property test for API contract stability
    - **Property 14: API Contract Stability**
    - **Validates: Requirements 6.4**
    - Test existing API endpoints with core-only records
    - Verify response structures unchanged
  
  - [ ]* 15.2 Write integration test for existing workflow
    - Upload file without template
    - Process records without dynamic attributes
    - Verify behavior matches previous version
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 16. Final checkpoint - Run full test suite
  - Ensure all 1,691 existing tests still pass
  - Ensure all new tests pass
  - Verify no regressions introduced
  - Ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based and unit tests that can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical break points
- Property tests validate universal correctness properties with minimum 100 iterations
- Unit tests validate specific examples and edge cases
- Integration tests verify end-to-end workflows
- All tests use Pest PHP framework following existing project conventions
- TDD approach: write tests before implementation for each component
