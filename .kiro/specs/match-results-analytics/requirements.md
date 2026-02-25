# Requirements Document

## Introduction

This feature enhances the match results view in the Laravel/Blade data matching system by providing detailed field-level analytics, improved column mapping metrics, and visual data quality indicators. The enhancement focuses on expanding the "View Breakdown" modal to show comprehensive field comparisons including normalized values, template/custom fields, and field-level confidence scores, while also enriching the column mapping summary with percentage metrics, field-level statistics, and visual charts for better data visualization.

## Glossary

- **Match_Results_View**: The results.blade.php page that displays matching outcomes for uploaded records
- **Field_Breakdown_Modal**: The modal dialog that shows detailed field-by-field comparison between uploaded and existing records
- **Column_Mapping_Summary**: The collapsible card section that displays which columns were mapped and which were skipped during upload
- **Core_Fields**: Standard database fields defined in MainSystem model (uid, last_name, first_name, middle_name, suffix, birthday, gender, civil_status, street_no, street, city, province, barangay)
- **Template_Fields**: Custom fields defined in ColumnMappingTemplate via TemplateField model
- **Field_Breakdown_Data**: JSON structure stored in match_results.field_breakdown containing matched_fields count, total_fields count, and core_fields array
- **Normalized_Values**: Processed field values stored in *_normalized columns (last_name_normalized, first_name_normalized, middle_name_normalized)
- **Match_Quality_Indicator**: Visual element (icon, color, badge) showing the quality level of a field match
- **Field_Confidence_Score**: Numeric score indicating confidence level for individual field matches
- **Data_Quality_Metrics**: Statistical measures of field population rates, match rates, and data completeness
- **Confidence_Score_Service**: Service class that calculates unified confidence scores and generates field breakdown data

## Requirements

### Requirement 1: Enhanced Field Breakdown Modal

**User Story:** As a data analyst, I want to see comprehensive field-by-field comparison details in the breakdown modal, so that I can understand exactly how uploaded records match against existing records.

#### Acceptance Criteria

1. WHEN the Field_Breakdown_Modal is opened, THE Match_Results_View SHALL display all Core_Fields with their comparison status
2. WHEN the Field_Breakdown_Modal is opened, THE Match_Results_View SHALL display all Template_Fields associated with the upload batch
3. WHEN a field has Normalized_Values available, THE Field_Breakdown_Modal SHALL display both original and normalized values side by side
4. FOR ALL fields displayed in Field_Breakdown_Modal, THE Match_Results_View SHALL show Match_Quality_Indicator based on comparison result
5. WHERE Field_Confidence_Score data is available, THE Field_Breakdown_Modal SHALL display confidence percentage for each field
6. THE Field_Breakdown_Modal SHALL group fields by category (Core_Fields vs Template_Fields) with clear visual separation
7. WHEN displaying field values, THE Field_Breakdown_Modal SHALL highlight differences between uploaded and existing values using color coding
8. THE Field_Breakdown_Modal SHALL display field comparison in a responsive table layout that works on mobile devices

### Requirement 2: Column Mapping Metrics Enhancement

**User Story:** As a data administrator, I want to see detailed metrics about column mapping success rates, so that I can assess the quality of my data uploads and identify mapping issues.

#### Acceptance Criteria

1. THE Column_Mapping_Summary SHALL calculate and display the percentage of successfully mapped columns
2. THE Column_Mapping_Summary SHALL calculate and display the percentage of skipped columns
3. FOR ALL Core_Fields, THE Column_Mapping_Summary SHALL display field population rate across all uploaded records
4. THE Column_Mapping_Summary SHALL display aggregate statistics showing most commonly matched fields
5. THE Column_Mapping_Summary SHALL display aggregate statistics showing fields with highest mismatch rates
6. WHERE Template_Fields exist for the upload batch, THE Column_Mapping_Summary SHALL display template fields separately from Core_Fields
7. THE Column_Mapping_Summary SHALL display total column count with breakdown by category (core, template, skipped)

### Requirement 3: Visual Data Quality Charts

**User Story:** As a data administrator, I want to see visual charts and graphs of mapping statistics, so that I can quickly understand data quality patterns without reading detailed numbers.

#### Acceptance Criteria

1. THE Column_Mapping_Summary SHALL display a pie chart showing the distribution of mapped vs skipped columns
2. THE Column_Mapping_Summary SHALL display a bar chart showing field population rates for Core_Fields
3. WHERE Template_Fields exist, THE Column_Mapping_Summary SHALL display a separate bar chart for template field population rates
4. THE Match_Results_View SHALL render charts using a JavaScript charting library (Chart.js or similar)
5. WHEN hovering over chart elements, THE Match_Results_View SHALL display detailed tooltips with exact values
6. THE charts SHALL use color coding consistent with the application theme (success=green, warning=yellow, danger=red, info=blue)
7. THE charts SHALL be responsive and adjust to different screen sizes

### Requirement 4: Field-Level Statistics

**User Story:** As a data quality analyst, I want to see statistics about how many records have each field populated, so that I can identify data completeness issues.

#### Acceptance Criteria

1. FOR ALL Core_Fields in Column_Mapping_Summary, THE Match_Results_View SHALL display count of records with non-empty values
2. FOR ALL Core_Fields in Column_Mapping_Summary, THE Match_Results_View SHALL display percentage of records with populated values
3. WHERE Template_Fields exist, THE Match_Results_View SHALL display population statistics for each template field
4. THE Column_Mapping_Summary SHALL display aggregate statistics showing fields with lowest population rates
5. THE Column_Mapping_Summary SHALL display aggregate statistics showing fields with highest population rates
6. WHEN displaying field statistics, THE Match_Results_View SHALL use Data_Quality_Metrics indicators (icons or badges) to highlight fields below 50% population rate

### Requirement 5: Enhanced Field Breakdown Data Structure

**User Story:** As a developer, I want the field breakdown data structure to include template fields and normalized values, so that the frontend can display comprehensive field comparison information.

#### Acceptance Criteria

1. THE Confidence_Score_Service SHALL include Template_Fields in the Field_Breakdown_Data structure
2. THE Confidence_Score_Service SHALL include Normalized_Values in the Field_Breakdown_Data structure when available
3. THE Field_Breakdown_Data structure SHALL include a "category" property for each field indicating whether it is a Core_Field or Template_Field
4. THE Field_Breakdown_Data structure SHALL include original and normalized values as separate properties
5. WHERE Field_Confidence_Score calculation is implemented, THE Field_Breakdown_Data SHALL include confidence score for each field
6. THE Confidence_Score_Service SHALL maintain backward compatibility with existing Field_Breakdown_Data consumers
7. THE Field_Breakdown_Data structure SHALL be documented with clear property definitions and examples

### Requirement 6: Batch-Level Analytics Summary

**User Story:** As a data administrator, I want to see batch-level analytics that summarize data quality across all records in a batch, so that I can quickly assess overall upload quality.

#### Acceptance Criteria

1. THE Column_Mapping_Summary SHALL display average confidence score across all records in the batch
2. THE Column_Mapping_Summary SHALL display distribution of match statuses (MATCHED, POSSIBLE DUPLICATE, NEW RECORD) as percentages
3. THE Column_Mapping_Summary SHALL display average number of matched fields per record
4. THE Column_Mapping_Summary SHALL display average number of mismatched fields per record
5. WHERE multiple batches are displayed, THE Match_Results_View SHALL allow comparison of batch-level statistics
6. THE Column_Mapping_Summary SHALL display a quality score indicator (excellent/good/fair/poor) based on overall match rates
7. WHEN batch statistics are calculated, THE Match_Results_View SHALL exclude NEW RECORD entries from match quality calculations

### Requirement 7: Interactive Field Filtering

**User Story:** As a data analyst, I want to filter the field breakdown view to show only matched, mismatched, or new fields, so that I can focus on specific types of field comparisons.

#### Acceptance Criteria

1. THE Field_Breakdown_Modal SHALL provide filter buttons for "All Fields", "Matched Only", "Mismatched Only", and "New Only"
2. WHEN a filter is selected, THE Field_Breakdown_Modal SHALL display only fields matching the selected status
3. THE Field_Breakdown_Modal SHALL update the field count display to reflect filtered results
4. THE Field_Breakdown_Modal SHALL maintain filter selection when switching between field categories (Core vs Template)
5. WHEN no fields match the selected filter, THE Field_Breakdown_Modal SHALL display a message indicating no results
6. THE filter buttons SHALL use visual indicators (badges) showing count of fields in each category

### Requirement 8: Export Field Breakdown Data

**User Story:** As a data analyst, I want to export field breakdown data to CSV format, so that I can perform offline analysis and share results with stakeholders.

#### Acceptance Criteria

1. THE Field_Breakdown_Modal SHALL provide an "Export to CSV" button
2. WHEN the export button is clicked, THE Match_Results_View SHALL generate a CSV file containing all field comparison data
3. THE exported CSV file SHALL include columns for field name, category, status, uploaded value, existing value, normalized uploaded value, normalized existing value
4. WHERE Field_Confidence_Score is available, THE exported CSV SHALL include confidence score column
5. THE exported CSV filename SHALL include the match result ID and timestamp
6. THE Match_Results_View SHALL trigger browser download of the CSV file without page reload
7. THE exported CSV SHALL use proper escaping for special characters and commas in field values

### Requirement 9: Performance Optimization for Large Datasets

**User Story:** As a system administrator, I want the enhanced analytics features to load quickly even with large batches, so that users have a responsive experience.

#### Acceptance Criteria

1. WHEN calculating batch-level statistics, THE Match_Results_View SHALL use database aggregation queries rather than loading all records into memory
2. THE Column_Mapping_Summary SHALL lazy-load chart data only when the card is expanded
3. WHERE batch contains more than 1000 records, THE Match_Results_View SHALL display a loading indicator while calculating statistics
4. THE Field_Breakdown_Modal SHALL load field data on-demand when modal is opened, not on initial page load
5. THE Match_Results_View SHALL cache calculated statistics in session for the duration of the user session
6. WHEN rendering charts, THE Match_Results_View SHALL limit data points to prevent browser performance degradation
7. THE Match_Results_View SHALL complete initial page load within 2 seconds for batches up to 10,000 records

### Requirement 10: Accessibility Compliance

**User Story:** As a user with visual impairments, I want the enhanced analytics features to be accessible with screen readers and keyboard navigation, so that I can effectively use the match results view.

#### Acceptance Criteria

1. THE Field_Breakdown_Modal SHALL be navigable using keyboard only (Tab, Enter, Escape keys)
2. THE charts in Column_Mapping_Summary SHALL include ARIA labels describing the data visualization
3. THE Match_Quality_Indicator elements SHALL include text alternatives for screen readers
4. THE filter buttons in Field_Breakdown_Modal SHALL announce their state (selected/unselected) to screen readers
5. THE color coding used for field comparison SHALL be supplemented with icons or text labels for color-blind users
6. THE Column_Mapping_Summary SHALL use semantic HTML elements (table, th, td) for tabular data
7. WHEN focus moves to the Field_Breakdown_Modal, THE Match_Results_View SHALL announce the modal title and purpose to screen readers
