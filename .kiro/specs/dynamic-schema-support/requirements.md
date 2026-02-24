# Requirements Document

## Introduction

This feature implements dynamic schema support for the MainSystem model through a hybrid-JSON architecture. The system will preserve core matching fields as indexed database columns while capturing additional user-provided data in a JSON blob. This enables flexible data ingestion from Excel/CSV uploads without losing information, while maintaining high-performance fuzzy matching capabilities.

## Glossary

- **MainSystem**: The primary Laravel Eloquent model representing the master data registry with person records
- **Core_Fields**: Fixed database columns used by the fuzzy matching engine (last_name, first_name, birthday, normalized variants, etc.)
- **Dynamic_Attributes**: User-provided columns from uploads that don't map to Core_Fields, stored in JSON format
- **RecordImport**: Laravel Excel import class that processes uploaded Excel/CSV files
- **DataMatchService**: Service class responsible for fuzzy matching logic using Core_Fields
- **DataMappingService**: Service class that maps uploaded column names to system field names
- **Fuzzy_Matching_Engine**: The matching algorithm that uses indexed Core_Fields for performance
- **Upload_Batch**: A collection of records uploaded together in a single Excel/CSV file
- **JSON_Column**: A database column type that stores structured data as JSON (supported by MySQL 5.7+, PostgreSQL 9.2+, SQLite 3.9+)

## Requirements

### Requirement 1: Database Schema Extension

**User Story:** As a system administrator, I want the main_system table to support dynamic attributes, so that user-provided data columns are preserved during uploads.

#### Acceptance Criteria

1. THE System SHALL add an `additional_attributes` JSON_Column to the main_system table
2. THE additional_attributes column SHALL be nullable to maintain backward compatibility with existing records
3. THE System SHALL preserve all existing Core_Fields and their indexes for Fuzzy_Matching_Engine performance
4. THE System SHALL apply the migration without data loss to existing main_system records
5. WHERE the database is MySQL, THE System SHALL use the JSON column type
6. WHERE the database is PostgreSQL, THE System SHALL use the JSONB column type for better query performance
7. WHERE the database is SQLite, THE System SHALL use the TEXT column type with JSON validation

### Requirement 2: Model Enhancement

**User Story:** As a developer, I want the MainSystem model to handle dynamic attributes transparently, so that I can access both core and dynamic fields using consistent syntax.

#### Acceptance Criteria

1. THE MainSystem model SHALL cast the additional_attributes column to array type
2. WHEN accessing a dynamic attribute, THE System SHALL return the value from the additional_attributes JSON_Column
3. WHEN setting a dynamic attribute, THE System SHALL store it in the additional_attributes JSON_Column
4. THE MainSystem model SHALL maintain the existing fillable array for Core_Fields
5. THE System SHALL provide a method to retrieve all dynamic attribute keys
6. THE System SHALL provide a method to check if a specific dynamic attribute exists

### Requirement 3: Import Processing Enhancement

**User Story:** As a data manager, I want uploaded Excel/CSV files to preserve all columns, so that no user data is lost during import.

#### Acceptance Criteria

1. WHEN RecordImport processes a row, THE System SHALL identify columns that map to Core_Fields
2. WHEN RecordImport processes a row, THE System SHALL identify columns that do not map to Core_Fields as Dynamic_Attributes
3. WHEN creating a new MainSystem record, THE System SHALL populate Core_Fields in their respective columns
4. WHEN creating a new MainSystem record, THE System SHALL populate Dynamic_Attributes in the additional_attributes JSON_Column
5. THE System SHALL normalize Dynamic_Attributes keys to snake_case format for consistency
6. WHEN a Dynamic_Attributes key conflicts with a Core_Fields name, THE System SHALL prioritize the Core_Fields mapping
7. THE System SHALL validate that the additional_attributes JSON size does not exceed 65,535 bytes (MySQL TEXT limit)

### Requirement 4: Data Mapping Service Enhancement

**User Story:** As a developer, I want the DataMappingService to distinguish between core and dynamic fields, so that the import process handles them appropriately.

#### Acceptance Criteria

1. THE DataMappingService SHALL return a structured array with 'core_fields' and 'dynamic_fields' keys
2. THE DataMappingService SHALL map known column names to Core_Fields using existing logic
3. WHEN a column name does not match any Core_Fields mapping, THE System SHALL include it in dynamic_fields
4. THE System SHALL exclude empty or null values from dynamic_fields to minimize JSON storage
5. THE System SHALL preserve the original column name as the key in dynamic_fields
6. THE DataMappingService SHALL maintain backward compatibility with existing mapUploadedData return format

### Requirement 5: Matching Service Compatibility

**User Story:** As a system architect, I want the fuzzy matching logic to remain unchanged, so that matching performance is not degraded.

#### Acceptance Criteria

1. THE DataMatchService SHALL continue using only Core_Fields for matching operations
2. THE System SHALL NOT include Dynamic_Attributes in candidate queries
3. THE System SHALL NOT include Dynamic_Attributes in matching rule evaluations
4. WHEN inserting a new record, THE DataMatchService SHALL accept Dynamic_Attributes and pass them to MainSystem creation
5. THE Fuzzy_Matching_Engine SHALL maintain its current performance characteristics

### Requirement 6: Query and Retrieval Capabilities

**User Story:** As a developer, I want to query records by dynamic attributes, so that I can filter and search based on user-provided data.

#### Acceptance Criteria

1. THE System SHALL support Laravel JSON query syntax for Dynamic_Attributes (e.g., `where('additional_attributes->key', 'value')`)
2. THE System SHALL support retrieving dynamic attribute values using array access syntax (e.g., `$record->additional_attributes['key']`)
3. THE System SHALL support retrieving dynamic attribute values using object property syntax (e.g., `$record->additional_attributes->key`)
4. WHEN a dynamic attribute does not exist, THE System SHALL return null without throwing an error
5. THE System SHALL support checking for dynamic attribute existence using `isset()` or `array_key_exists()`

### Requirement 7: Data Validation and Constraints

**User Story:** As a system administrator, I want dynamic attributes to be validated, so that data integrity is maintained.

#### Acceptance Criteria

1. WHEN storing Dynamic_Attributes, THE System SHALL validate that the JSON is well-formed
2. WHEN the additional_attributes JSON exceeds size limits, THE System SHALL reject the record with a descriptive error message
3. THE System SHALL sanitize Dynamic_Attributes keys to prevent JSON injection attacks
4. THE System SHALL validate that Dynamic_Attributes values are JSON-serializable types
5. IF a Dynamic_Attributes value cannot be JSON-encoded, THEN THE System SHALL convert it to a string representation

### Requirement 8: User Interface Display

**User Story:** As a data viewer, I want to see dynamic attributes in record detail views, so that I can access all uploaded information.

#### Acceptance Criteria

1. WHEN displaying a MainSystem record, THE System SHALL render Core_Fields in their designated sections
2. WHEN displaying a MainSystem record, THE System SHALL render Dynamic_Attributes in a separate "Additional Information" section
3. THE System SHALL display Dynamic_Attributes with human-readable labels (converting snake_case to Title Case)
4. WHEN a record has no Dynamic_Attributes, THE System SHALL hide the "Additional Information" section
5. THE System SHALL display Dynamic_Attributes in alphabetical order by key

### Requirement 9: Backward Compatibility

**User Story:** As a system maintainer, I want existing functionality to continue working, so that the migration is seamless.

#### Acceptance Criteria

1. THE System SHALL support existing MainSystem records where additional_attributes is null
2. WHEN querying records without Dynamic_Attributes, THE System SHALL return an empty array for additional_attributes
3. THE System SHALL maintain all existing API contracts for MainSystem model methods
4. THE System SHALL maintain all existing API contracts for DataMatchService methods
5. THE System SHALL maintain all existing API contracts for RecordImport processing
6. THE System SHALL not break existing unit tests or integration tests

### Requirement 10: Performance Considerations

**User Story:** As a system architect, I want dynamic schema support to have minimal performance impact, so that the system remains responsive.

#### Acceptance Criteria

1. THE System SHALL NOT add indexes to the additional_attributes JSON_Column by default
2. WHEN querying by Core_Fields, THE System SHALL use existing indexes and maintain current query performance
3. THE System SHALL lazy-load Dynamic_Attributes only when accessed
4. THE System SHALL cache parsed JSON to avoid repeated deserialization within a single request
5. WHEN batch importing records, THE System SHALL process Dynamic_Attributes without significant overhead (< 10% increase in import time)
