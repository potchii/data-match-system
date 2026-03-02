# Requirements Document

## Introduction

This feature enhances the fuzzy matching algorithm to incorporate DOB (Date of Birth), gender, address/barangay, and template fields as discriminators in the matching logic. Currently, the FuzzyNameMatchRule only matches on normalized first/last names with an 85% similarity threshold, which can produce false positives when multiple people have similar names. By integrating additional demographic fields and template data, the system will reduce false positives, improve match accuracy, and provide more granular confidence scoring that reflects the quality of the match.

## Glossary

- **Fuzzy_Matching**: Approximate string matching algorithm that finds similar names even with typos or variations
- **Discriminator**: Field used to distinguish between similar records and reduce false positives
- **Confidence_Score**: Percentage (0-100%) indicating the likelihood that two records represent the same person
- **Match_Status**: Classification of match result: MATCHED (high confidence), POSSIBLE DUPLICATE (medium confidence), NEW RECORD (no match)
- **DOB**: Date of Birth field used as a discriminator
- **Gender**: Gender field used as a discriminator
- **Address_Barangay**: Combined address and barangay fields used as a discriminator
- **Template_Fields**: Custom fields defined in column_mapping_templates that can be used in matching
- **Similarity_Threshold**: Minimum percentage (85%) for fuzzy name matching to be considered a candidate
- **Normalized_Name**: Name converted to standard format (lowercase, trimmed, special characters removed)
- **FuzzyNameMatchRule**: Matching rule that performs approximate string matching on names
- **MatchRule**: Abstract base class for all matching rules
- **DataMatchService**: Service that orchestrates matching rules and returns best match
- **ConfidenceScoreService**: Service that calculates confidence scores for matches
- **MainSystem**: Core record entity representing a person
- **UploadBatch**: Batch of records uploaded in a single file
- **TemplateFieldValue**: Persistent storage of custom field values linked to MainSystem records

## Requirements

### Requirement 1: Enhanced FuzzyNameMatchRule with DOB Discriminator

**User Story:** As a data quality manager, I want fuzzy matching to use DOB as a discriminator, so that records with similar names but different birth dates are not incorrectly matched.

#### Acceptance Criteria

1. WHEN FuzzyNameMatchRule finds candidates with fuzzy name similarity >= 85%, THE Rule SHALL check DOB match
2. IF both records have DOB values, THE Rule SHALL require exact DOB match to proceed with fuzzy match
3. IF uploaded record has DOB but candidate has no DOB, THE Rule SHALL apply 5% confidence penalty
4. IF uploaded record has no DOB but candidate has DOB, THE Rule SHALL apply 5% confidence penalty
5. IF neither record has DOB, THE Rule SHALL proceed with fuzzy name matching without penalty
6. WHEN DOB matches exactly, THE Rule SHALL increase confidence score by 10% (from base 70% to 80%)
7. WHEN DOB is missing from both records, THE Rule SHALL use base confidence of 70%
8. THE Rule SHALL normalize DOB values to YYYY-MM-DD format before comparison
9. THE Rule SHALL handle partial DOB matches (e.g., same month/year but different day) with 3% confidence penalty
10. THE Rule SHALL log DOB comparison results for audit trail

### Requirement 2: Enhanced FuzzyNameMatchRule with Gender Discriminator

**User Story:** As a data quality manager, I want fuzzy matching to use gender as a discriminator, so that records with similar names but different genders are not incorrectly matched.

#### Acceptance Criteria

1. WHEN FuzzyNameMatchRule evaluates a fuzzy name match, THE Rule SHALL check gender match
2. IF both records have gender values, THE Rule SHALL require gender match to proceed with fuzzy match
3. IF genders do not match, THE Rule SHALL reject the match and return null
4. IF uploaded record has gender but candidate has no gender, THE Rule SHALL apply 3% confidence penalty
5. IF uploaded record has no gender but candidate has gender, THE Rule SHALL apply 3% confidence penalty
6. IF neither record has gender, THE Rule SHALL proceed without penalty
7. WHEN gender matches, THE Rule SHALL increase confidence score by 5% (from base 70% to 75%)
8. THE Rule SHALL normalize gender values to standard format (e.g., 'M', 'F', 'Other')
9. THE Rule SHALL handle gender variations (e.g., 'Male' vs 'M') with case-insensitive comparison
10. THE Rule SHALL log gender comparison results for audit trail

### Requirement 3: Enhanced FuzzyNameMatchRule with Address/Barangay Discriminator

**User Story:** As a data quality manager, I want fuzzy matching to use address and barangay as discriminators, so that records with similar names but different locations are not incorrectly matched.

#### Acceptance Criteria

1. WHEN FuzzyNameMatchRule evaluates a fuzzy name match, THE Rule SHALL check address and barangay match
2. IF both records have barangay values, THE Rule SHALL require exact barangay match to proceed with fuzzy match
3. IF barangays do not match, THE Rule SHALL apply 10% confidence penalty instead of rejecting
4. IF both records have address values, THE Rule SHALL perform fuzzy matching on addresses with 80% threshold
5. IF address fuzzy match succeeds, THE Rule SHALL increase confidence score by 5%
6. IF address fuzzy match fails, THE Rule SHALL apply 5% confidence penalty
7. IF uploaded record has address/barangay but candidate has no address/barangay, THE Rule SHALL apply 5% confidence penalty
8. IF uploaded record has no address/barangay but candidate has address/barangay, THE Rule SHALL apply 5% confidence penalty
9. IF neither record has address/barangay, THE Rule SHALL proceed without penalty
10. THE Rule SHALL normalize address values (lowercase, trim, remove extra spaces) before comparison
11. THE Rule SHALL log address/barangay comparison results for audit trail

### Requirement 4: Template Fields Integration in Fuzzy Matching

**User Story:** As a data quality manager, I want fuzzy matching to consider template fields, so that custom data fields can help distinguish between similar records.

#### Acceptance Criteria

1. WHEN FuzzyNameMatchRule evaluates a fuzzy name match, THE Rule SHALL retrieve template fields for both records
2. IF template fields are available for the upload, THE Rule SHALL compare matching template fields
3. FOR EACH template field that exists in both records, THE Rule SHALL perform fuzzy matching with 80% threshold
4. WHEN template field values match exactly, THE Rule SHALL increase confidence score by 2% per field (max 10% total)
5. WHEN template field values fuzzy match, THE Rule SHALL increase confidence score by 1% per field (max 5% total)
6. WHEN template field values do not match, THE Rule SHALL apply 1% confidence penalty per field (max 5% penalty)
7. IF template fields are missing from one or both records, THE Rule SHALL skip template field comparison
8. THE Rule SHALL prioritize template fields marked as "matching_field" in template definition
9. THE Rule SHALL normalize template field values before comparison (lowercase, trim)
10. THE Rule SHALL log template field comparison results for audit trail

### Requirement 5: Confidence Scoring Strategy for Fuzzy Matches

**User Story:** As a data analyst, I want clear confidence scoring that reflects match quality, so that I can understand why records were matched or not matched.

#### Acceptance Criteria

1. THE System SHALL calculate fuzzy match confidence using formula: base_score + discriminator_bonuses - discriminator_penalties
2. THE base_score for fuzzy name match SHALL be 70% (representing 60-75% range from requirements)
3. WHEN DOB matches exactly, THE System SHALL add 10% bonus (total: 80%)
4. WHEN gender matches, THE System SHALL add 5% bonus (total: 75%)
5. WHEN barangay matches exactly, THE System SHALL add 5% bonus (total: 75%)
6. WHEN address fuzzy matches, THE System SHALL add 5% bonus (total: 75%)
7. WHEN template fields match, THE System SHALL add up to 10% bonus (2% per exact match, 1% per fuzzy match)
8. WHEN DOB is missing from one record, THE System SHALL apply 5% penalty
9. WHEN gender is missing from one record, THE System SHALL apply 3% penalty
10. WHEN address/barangay is missing from one record, THE System SHALL apply 5% penalty
11. WHEN template field values do not match, THE System SHALL apply up to 5% penalty (1% per field)
12. THE final confidence score SHALL be capped at 100% maximum
13. THE final confidence score SHALL be floored at 0% minimum
14. THE System SHALL round confidence score to nearest integer percentage
15. THE System SHALL map confidence scores to statuses: 100% = MATCHED, 90-99% = MATCHED, 80-89% = MATCHED, 70-79% = POSSIBLE DUPLICATE, <70% = NEW RECORD

### Requirement 6: Backward Compatibility with Existing Matching Rules

**User Story:** As a system maintainer, I want enhanced fuzzy matching to work alongside existing rules, so that migration is smooth and existing functionality is preserved.

#### Acceptance Criteria

1. THE System SHALL maintain existing ExactMatchRule with 100% confidence
2. THE System SHALL maintain existing FullNameMatchRule with 80% confidence
3. THE System SHALL maintain existing PartialMatchWithDobRule with 90% confidence
4. THE enhanced FuzzyNameMatchRule SHALL be evaluated after existing rules in matching order
5. THE System SHALL NOT modify existing rule logic or confidence scores
6. THE System SHALL preserve existing match result structure and field_breakdown format
7. WHEN existing rules find a match, THE System SHALL NOT evaluate enhanced FuzzyNameMatchRule
8. THE System SHALL support disabling enhanced fuzzy matching via configuration flag
9. THE System SHALL log which rule matched for audit trail
10. THE System SHALL maintain backward compatibility with existing match results from before enhancement

### Requirement 7: Incomplete Data Handling in Fuzzy Matching

**User Story:** As a data quality manager, I want fuzzy matching to work with incomplete data, so that records can be matched even when some discriminator fields are missing.

#### Acceptance Criteria

1. WHEN DOB is missing from uploaded record, THE System SHALL proceed with fuzzy matching using other discriminators
2. WHEN DOB is missing from candidate record, THE System SHALL proceed with fuzzy matching using other discriminators
3. WHEN gender is missing from both records, THE System SHALL proceed without gender comparison
4. WHEN address/barangay is missing from both records, THE System SHALL proceed without address comparison
5. WHEN template fields are missing from one or both records, THE System SHALL proceed without template field comparison
6. THE System SHALL NOT reject matches solely due to missing discriminator fields
7. THE System SHALL apply appropriate confidence penalties for missing fields as specified in requirements
8. THE System SHALL log which fields were missing during matching for audit trail
9. THE System SHALL ensure that matches with all discriminators present have higher confidence than matches with missing discriminators
10. WHEN all discriminators are missing except name, THE System SHALL use base fuzzy name confidence (70%)

### Requirement 8: Enhanced FuzzyNameMatchRule Implementation

**User Story:** As a developer, I want a refactored FuzzyNameMatchRule that incorporates all discriminators, so that the matching logic is maintainable and testable.

#### Acceptance Criteria

1. THE System SHALL refactor FuzzyNameMatchRule to accept discriminator configuration
2. THE Rule SHALL provide method `calculateDiscriminatorScore()` that returns confidence adjustment
3. THE Rule SHALL provide method `validateDobMatch()` that returns boolean and penalty
4. THE Rule SHALL provide method `validateGenderMatch()` that returns boolean and penalty
5. THE Rule SHALL provide method `validateAddressMatch()` that returns boolean and penalty
6. THE Rule SHALL provide method `validateTemplateFieldMatch()` that returns boolean and penalty
7. THE Rule SHALL provide method `calculateFinalConfidence()` that applies all bonuses and penalties
8. THE Rule SHALL use dependency injection for ConfidenceScoreService
9. THE Rule SHALL use dependency injection for TemplateFieldPersistenceService to access template fields
10. THE Rule SHALL maintain existing `similarity()` method for backward compatibility
11. THE Rule SHALL be fully testable with unit tests for each discriminator validation
12. THE Rule SHALL log detailed matching process for debugging

### Requirement 9: Integration with DataMatchService

**User Story:** As a developer, I want DataMatchService to pass discriminator data to FuzzyNameMatchRule, so that enhanced matching works seamlessly.

#### Acceptance Criteria

1. WHEN DataMatchService normalizes a record, THE Service SHALL extract DOB, gender, address, and barangay
2. WHEN DataMatchService normalizes a record, THE Service SHALL extract template field values if template is provided
3. THE DataMatchService SHALL pass normalized discriminator data to FuzzyNameMatchRule
4. THE DataMatchService SHALL maintain existing normalization logic for names
5. THE DataMatchService SHALL handle missing discriminator fields gracefully
6. THE DataMatchService SHALL pass template_id to FuzzyNameMatchRule for template field lookup
7. THE DataMatchService SHALL log discriminator data passed to matching rules
8. THE DataMatchService SHALL maintain backward compatibility with existing matching flow

### Requirement 10: Integration with ConfidenceScoreService

**User Story:** As a developer, I want ConfidenceScoreService to calculate confidence scores based on discriminators, so that scoring is consistent across the system.

#### Acceptance Criteria

1. WHEN ConfidenceScoreService calculates unified score, THE Service SHALL use discriminator-based scoring
2. THE Service SHALL calculate DOB match score (0-10% bonus)
3. THE Service SHALL calculate gender match score (0-5% bonus)
4. THE Service SHALL calculate address/barangay match score (0-10% bonus)
5. THE Service SHALL calculate template field match score (0-10% bonus)
6. THE Service SHALL apply penalties for missing discriminator fields
7. THE Service SHALL return detailed breakdown of discriminator scores in field_breakdown
8. THE Service SHALL maintain existing field-by-field comparison logic
9. THE Service SHALL ensure discriminator scores are included in unified confidence calculation
10. THE Service SHALL log confidence calculation details for audit trail

### Requirement 11: Configuration and Thresholds

**User Story:** As a system administrator, I want to configure fuzzy matching thresholds, so that I can tune matching behavior for different use cases.

#### Acceptance Criteria

1. THE System SHALL provide configuration for fuzzy name similarity threshold (default: 85%)
2. THE System SHALL provide configuration for address fuzzy similarity threshold (default: 80%)
3. THE System SHALL provide configuration for DOB bonus percentage (default: 10%)
4. THE System SHALL provide configuration for gender bonus percentage (default: 5%)
5. THE System SHALL provide configuration for address/barangay bonus percentage (default: 5%)
6. THE System SHALL provide configuration for template field bonus percentage (default: 2% per field)
7. THE System SHALL provide configuration for penalty percentages (default: 5% for missing fields)
8. THE System SHALL provide configuration to enable/disable each discriminator
9. THE System SHALL provide configuration to enable/disable enhanced fuzzy matching entirely
10. THE System SHALL load configuration from environment variables or config file
11. THE System SHALL validate configuration values are within reasonable ranges (0-100%)
12. THE System SHALL log configuration values on application startup

### Requirement 12: Matching Rule Ordering and Evaluation

**User Story:** As a system architect, I want clear rule evaluation order, so that matching is deterministic and predictable.

#### Acceptance Criteria

1. THE System SHALL evaluate matching rules in this order:
   - ExactMatchRule (100% confidence)
   - FullNameMatchRule (80% confidence)
   - PartialMatchWithDobRule (90% confidence)
   - Enhanced FuzzyNameMatchRule (70-80% confidence with discriminators)
2. WHEN a rule finds a match, THE System SHALL return immediately without evaluating remaining rules
3. WHEN no rule finds a match, THE System SHALL return NEW RECORD status
4. THE System SHALL log which rule matched for each record
5. THE System SHALL maintain rule evaluation order in configuration
6. THE System SHALL support custom rule ordering via configuration
7. THE System SHALL ensure rule evaluation is deterministic (same input = same output)
8. THE System SHALL handle rule evaluation errors gracefully without stopping matching process

### Requirement 13: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error handling and logging, so that I can troubleshoot matching issues.

#### Acceptance Criteria

1. WHEN discriminator validation fails, THE System SHALL log error with record details
2. WHEN template field lookup fails, THE System SHALL log warning and continue without template fields
3. WHEN DOB parsing fails, THE System SHALL log warning and treat as missing DOB
4. WHEN gender normalization fails, THE System SHALL log warning and treat as missing gender
5. WHEN address fuzzy matching fails, THE System SHALL log warning and apply penalty
6. THE System SHALL NOT throw exceptions during matching; all errors SHALL be logged and handled gracefully
7. THE System SHALL include record IDs and batch IDs in all error logs
8. THE System SHALL include discriminator values in debug logs (without sensitive data)
9. THE System SHALL provide debug mode that logs detailed matching process
10. THE System SHALL maintain error log for troubleshooting and analysis

### Requirement 14: Performance Optimization

**User Story:** As a system administrator, I want enhanced fuzzy matching to be performant, so that large batches process in reasonable time.

#### Acceptance Criteria

1. WHEN processing batch with 1000+ records, THE System SHALL complete matching within 30 seconds
2. THE System SHALL cache normalized candidate records in memory during batch processing
3. THE System SHALL use indexed queries for candidate lookup by normalized names
4. THE System SHALL lazy-load template fields only when needed
5. THE System SHALL batch template field lookups to minimize database queries
6. THE System SHALL use efficient string similarity algorithm (e.g., Levenshtein distance)
7. THE System SHALL avoid redundant discriminator comparisons
8. THE System SHALL profile matching performance and log timing metrics
9. THE System SHALL support parallel processing of records in batch
10. THE System SHALL provide performance metrics in batch completion report

### Requirement 15: Testing and Validation

**User Story:** As a developer, I want comprehensive test coverage for enhanced fuzzy matching, so that the feature is reliable and maintainable.

#### Acceptance Criteria

1. THE System SHALL have unit tests for each discriminator validation method
2. THE System SHALL have unit tests for confidence score calculation
3. THE System SHALL have integration tests for FuzzyNameMatchRule with all discriminators
4. THE System SHALL have integration tests for DataMatchService with enhanced fuzzy matching
5. THE System SHALL have test cases for incomplete data scenarios
6. THE System SHALL have test cases for edge cases (null values, empty strings, special characters)
7. THE System SHALL have test cases for backward compatibility with existing rules
8. THE System SHALL have performance tests for batch processing
9. THE System SHALL have test cases for configuration variations
10. THE System SHALL achieve minimum 80% code coverage for matching logic
11. THE System SHALL have property-based tests for fuzzy matching invariants
12. THE System SHALL have regression tests for known false positive cases

### Requirement 16: Documentation and Examples

**User Story:** As a developer, I want comprehensive documentation for enhanced fuzzy matching, so that I can understand and extend the feature.

#### Acceptance Criteria

1. THE System SHALL provide documentation of fuzzy matching algorithm with discriminators
2. THE System SHALL provide examples of confidence score calculation for various scenarios
3. THE System SHALL provide examples of incomplete data handling
4. THE System SHALL provide configuration guide with all available options
5. THE System SHALL provide troubleshooting guide for common matching issues
6. THE System SHALL provide API documentation for matching endpoints
7. THE System SHALL provide migration guide for existing installations
8. THE System SHALL provide performance tuning guide
9. THE System SHALL provide examples of custom discriminator implementation
10. THE System SHALL document known limitations and edge cases

