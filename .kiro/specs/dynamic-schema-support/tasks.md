# Implementation Plan: Dynamic Schema Support

## Overview

This implementation adds dynamic schema support to the MainSystem model through a hybrid-JSON architecture. The approach maintains high-performance fuzzy matching on indexed core fields while capturing additional user data in a JSON column. Implementation follows a test-driven development approach with incremental validation.

## Tasks

- [x] 1. Create database migration for additional_attributes column
  - Create migration file: `add_additional_attributes_to_main_system_table.php`
  - Add JSON column `additional_attributes` (nullable) after `barangay` column
  - Use Laravel's `json()` method for cross-database compatibility
  - Test migration up/down on clean database
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 2. Enhance MainSystem model with dynamic attribute support
  - [x] 2.1 Add additional_attributes to fillable array and casts
    - Add `'additional_attributes'` to `$fillable` array
    - Add `'additional_attributes' => 'array'` to `$casts` array
    - _Requirements: 2.1, 2.4_
  
  - [x] 2.2 Implement dynamic attribute helper methods
    - Implement `getDynamicAttributeKeys(): array` method
    - Implement `hasDynamicAttribute(string $key): bool` method
    - Implement `getDynamicAttribute(string $key, $default = null)` method
    - Implement `setDynamicAttribute(string $key, $value): void` method
    - _Requirements: 2.2, 2.3, 2.5, 2.6_
  
  - [x] 2.3 Write property tests for MainSystem dynamic attributes
    - **Property 1: Dynamic attribute round-trip consistency**
    - **Property 2: Dynamic attribute key enumeration**
    - **Property 3: Dynamic attribute existence checking**
    - **Property 20: Null additional_attributes support**
    - **Validates: Requirements 2.2, 2.3, 2.5, 2.6, 9.1, 9.2**
  
  - [x] 2.4 Write unit tests for MainSystem model
    - Test storing and retrieving dynamic attributes
    - Test null additional_attributes returns empty array
    - Test array access syntax works
    - Test object property access syntax works
    - _Requirements: 2.1, 6.2, 6.3, 9.1, 9.2_

- [x] 3. Checkpoint - Verify model changes
  - Run migrations on test database
  - Ensure all model tests pass
  - Verify backward compatibility with existing records
  - Ask user if questions arise

- [x] 4. Enhance DataMappingService to separate core and dynamic fields
  - [x] 4.1 Extract core field mappings to constant
    - Create `CORE_FIELD_MAPPINGS` constant with all known column variations
    - Include all existing mappings from current implementation
    - _Requirements: 3.1, 4.2_
  
  - [x] 4.2 Refactor mapUploadedData to return structured array
    - Change return type to `['core_fields' => [...], 'dynamic_fields' => [...]]`
    - Process compound first/middle names first (mark as processed)
    - Loop through remaining fields and classify as core or dynamic
    - Skip empty/null values for dynamic fields
    - _Requirements: 3.1, 3.2, 4.1, 4.4_
  
  - [x] 4.3 Implement dynamic key normalization
    - Create `normalizeDynamicKey(string $key): string` method
    - Convert to snake_case using regex
    - Remove special characters, keep only alphanumeric and underscores
    - _Requirements: 3.5, 7.3_
  
  - [x] 4.4 Implement dynamic value sanitization
    - Create `sanitizeDynamicValue($value)` method
    - Convert objects to strings
    - Recursively sanitize arrays
    - Ensure JSON-serializable types
    - _Requirements: 7.4, 7.5_
  
  - [x] 4.5 Implement JSON size validation
    - Create `validateJsonSize(array $data): void` method
    - JSON-encode and check byte size
    - Throw InvalidArgumentException if exceeds 65,535 bytes
    - _Requirements: 3.7, 7.2_
  
  - [x] 4.6 Update field normalization helper
    - Create `normalizeFieldValue(string $field, $value)` method
    - Use match expression to route to appropriate normalizer
    - Maintain existing normalization logic for each field type
    - _Requirements: 4.2_
  
  - [x] 4.7 Write property tests for DataMappingService
    - **Property 4: Core field identification**
    - **Property 5: Dynamic field identification**
    - **Property 8: Dynamic key normalization**
    - **Property 9: Core field priority**
    - **Property 10: JSON size validation**
    - **Property 11: Structured mapping output**
    - **Property 12: Empty value exclusion**
    - **Property 18: Key sanitization**
    - **Validates: Requirements 3.1, 3.2, 3.5, 3.6, 3.7, 4.1, 4.3, 4.4, 7.2, 7.3**
  
  - [x] 4.8 Write unit tests for DataMappingService
    - Test known column variations map to core fields
    - Test unknown columns become dynamic fields
    - Test empty values excluded from dynamic fields
    - Test core field priority over dynamic fields
    - Test JSON size validation throws error
    - _Requirements: 3.1, 3.2, 3.6, 3.7, 4.2, 4.3, 4.4_

- [x] 5. Checkpoint - Verify mapping service changes
  - Ensure all DataMappingService tests pass
  - Verify structured output format
  - Verify key normalization works correctly
  - Ask user if questions arise

- [x] 6. Update DataMatchService to handle structured data
  - [x] 6.1 Update findMatch method for backward compatibility
    - Check if input has 'core_fields' key (new format)
    - If not, treat entire array as core data (old format)
    - Extract core_fields for matching operations
    - _Requirements: 5.1, 9.3, 9.4_
  
  - [x] 6.2 Update insertNewRecord to handle dynamic fields
    - Check if input has 'core_fields' and 'dynamic_fields' keys
    - Extract both or use backward-compatible format
    - Add dynamic_fields to additional_attributes when creating record
    - _Requirements: 3.4, 5.4_
  
  - [x] 6.3 Write property tests for DataMatchService
    - **Property 6: Core field storage**
    - **Property 7: Dynamic field storage**
    - **Property 13: Matching uses only core fields**
    - **Validates: Requirements 3.3, 3.4, 5.1, 5.2, 5.3, 5.4**
  
  - [x] 6.4 Write unit tests for DataMatchService
    - Test backward compatibility with flat array input
    - Test new structured input format
    - Test dynamic fields stored in additional_attributes
    - Test matching ignores dynamic attributes
    - _Requirements: 5.1, 5.4, 9.4_

- [x] 7. Update RecordImport to use structured mapping
  - [x] 7.1 Adapt to new DataMappingService output format
    - Extract core_fields and dynamic_fields from mapping result
    - Use core_fields for validation (last_name, first_name required)
    - Pass structured data to DataMatchService
    - _Requirements: 3.1, 3.2_
  
  - [x] 7.2 Update record creation logic
    - Reconstruct full data structure for insertNewRecord
    - Include both core_fields and dynamic_fields
    - Ensure origin_batch_id added to core_fields
    - _Requirements: 3.3, 3.4_
  
  - [x] 7.3 Write integration tests for RecordImport
    - Test import with only core columns (backward compatibility)
    - Test import with mixed core and dynamic columns
    - Test dynamic attributes preserved after import
    - Test matching ignores dynamic attributes during import
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 5.1, 9.5_

- [x] 8. Checkpoint - Verify end-to-end import flow
  - Create test Excel file with mixed columns
  - Run import and verify data stored correctly
  - Verify core fields in columns, dynamic in JSON
  - Verify matching works correctly
  - Ask user if questions arise

- [x] 9. Add JSON query support and validation
  - [x] 9.1 Write property tests for JSON querying
    - **Property 14: JSON query support**
    - **Property 15: Object property access**
    - **Property 16: Graceful missing key handling**
    - **Validates: Requirements 6.1, 6.3, 6.4, 6.5**
  
  - [x] 9.2 Write unit tests for JSON queries
    - Test querying by dynamic attributes using JSON syntax
    - Test object property access returns correct values
    - Test missing keys return null without errors
    - Test isset() and array_key_exists() work correctly
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 10. Add comprehensive validation tests
  - [x] 10.1 Write property tests for validation
    - **Property 17: JSON well-formedness**
    - **Property 19: Non-serializable value handling**
    - **Validates: Requirements 7.1, 7.4, 7.5**
  
  - [x] 10.2 Write unit tests for edge cases
    - Test objects converted to strings
    - Test resources handled gracefully
    - Test nested arrays preserved
    - Test special characters in keys sanitized
    - _Requirements: 7.1, 7.3, 7.4, 7.5_

- [x] 11. Create database factory for testing
  - Update MainSystemFactory to support additional_attributes
  - Add faker methods for generating dynamic attributes
  - Ensure factory creates valid test data
  - _Requirements: Testing infrastructure_

- [x] 12. Add UI enhancements for column mapping feedback
  - [x] 12.1 Update UploadController to return column mapping summary
    - After import, analyze first row to show mapping preview
    - Return JSON with core_fields_mapped, dynamic_fields_captured, skipped_columns
    - Include this in the upload response
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [x] 12.2 Enhance upload view with mapping feedback section
    - Add collapsible section to show column mapping results after upload
    - Display core fields mapped (with green badges)
    - Display dynamic fields captured (with blue badges)
    - Display any skipped/empty columns (with gray badges)
    - Show total rows processed and records created/matched
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  
  - [x] 12.3 Add column mapping preview before import (optional enhancement)
    - Parse first row of uploaded file
    - Show preview of how columns will be mapped
    - Allow user to confirm before processing full import
    - _Requirements: User experience enhancement_

- [x] 13. Update record detail views to display dynamic attributes
  - [x] 13.1 Update main-system.blade.php to show additional information section
    - Add conditional section for additional_attributes
    - Display dynamic attributes with human-readable labels
    - Sort attributes alphabetically
    - Hide section when no dynamic attributes exist
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [x] 13.2 Update results view to show dynamic attributes in match details
    - Include dynamic attributes in matched record display
    - Show side-by-side comparison if uploaded data had dynamic fields
    - _Requirements: 8.1, 8.2_

- [x] 14. Final checkpoint - Run full test suite
  - Run all unit tests and verify 100% pass
  - Run all property tests (minimum 100 iterations each)
  - Run integration tests for complete import flow
  - Verify backward compatibility with existing code
  - Check test coverage meets 80% minimum
  - Test UI displays correctly with and without dynamic attributes
  - Ask user if questions arise

## Notes

- Tasks marked with `*` are optional property-based and unit tests
- Each property test should run minimum 100 iterations
- Use Pest PHP with pest-plugin-faker for property testing
- Maintain backward compatibility throughout implementation
- Core matching logic remains completely unchanged
- All tests should pass before moving to next checkpoint
- Migration should be tested on clean database before applying to production
