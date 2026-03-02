# Bugfix Requirements Document

## Introduction

The matching rules in the data matching system are incorrectly requiring the `middle_name` field to match in both `ExactMatchRule` and `FullNameMatchRule`. This causes records that should be classified as "MATCHED" or "POSSIBLE DUPLICATE" to be incorrectly classified as "NEW RECORD", leading to duplicate records in the database. The specification defines "full name" as first + last name, not requiring middle name to be present or match.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a record has matching first_name, last_name, and birthday but different or missing middle_name THEN the system fails to match via ExactMatchRule (100% confidence) and incorrectly classifies it as "NEW RECORD"

1.2 WHEN a record has matching first_name and last_name but different or missing middle_name THEN the system fails to match via FullNameMatchRule (80% confidence) and incorrectly classifies it as "NEW RECORD"

1.3 WHEN ExactMatchRule checks for matches THEN the system requires `middle_name_normalized === middle_name_normalized` causing false negatives for records without middle names

1.4 WHEN FullNameMatchRule checks for matches THEN the system requires `middle_name_normalized === middle_name_normalized` causing false negatives for records without middle names

### Expected Behavior (Correct)

2.1 WHEN a record has matching first_name, last_name, and birthday THEN the system SHALL match via ExactMatchRule (100% confidence, "MATCHED") regardless of middle_name presence or value

2.2 WHEN a record has matching first_name and last_name THEN the system SHALL match via FullNameMatchRule (80% confidence, "POSSIBLE DUPLICATE") regardless of middle_name presence or value

2.3 WHEN ExactMatchRule checks for matches THEN the system SHALL only require last_name_normalized, first_name_normalized, and birthday to match

2.4 WHEN FullNameMatchRule checks for matches THEN the system SHALL only require last_name_normalized and first_name_normalized to match

2.5 WHEN PartialMatchWithDobRule is evaluated after the fixed ExactMatchRule THEN the system SHALL recognize that PartialMatchWithDobRule is now redundant (both check first + last + DOB) and MAY be removed from the rule chain

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a record matches via PartialMatchWithDobRule (first + last + DOB) THEN the system SHALL CONTINUE TO return 90% confidence with "MATCHED" status

3.2 WHEN a record matches via FuzzyNameMatchRule (similar names) THEN the system SHALL CONTINUE TO return 60-75% confidence with "POSSIBLE DUPLICATE" status

3.3 WHEN no matching rules are satisfied THEN the system SHALL CONTINUE TO classify the record as "NEW RECORD" with 0% confidence

3.4 WHEN the rule chain is evaluated THEN the system SHALL CONTINUE TO process rules in priority order: ExactMatchRule (100%), PartialMatchWithDobRule (90%), FullNameMatchRule (80%), FuzzyNameMatchRule (70%)

3.5 WHEN a match is found by any rule THEN the system SHALL CONTINUE TO stop processing subsequent rules and return the first match result

3.6 WHEN birthday comparison is performed THEN the system SHALL CONTINUE TO handle Carbon instances by converting them to 'Y-m-d' format strings

3.7 WHEN confidence scores are calculated THEN the system SHALL CONTINUE TO use the ConfidenceScoreService for unified score calculation with field breakdown
