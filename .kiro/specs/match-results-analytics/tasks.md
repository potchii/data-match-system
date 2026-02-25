# Implementation Plan: Match Results Analytics

## Overview

This implementation enhances the match results view with comprehensive field-level analytics, visual data quality indicators, and interactive filtering. The feature adds a new backend service for analytics calculation, enhances the existing ConfidenceScoreService, creates AJAX endpoints, and implements frontend JavaScript modules for charts and interactive modals using AdminLTE and Chart.js.

## Tasks

- [ ] 1. Set up analytics infrastructure and dependencies
  - Install Chart.js library via npm for data visualization
  - Create MatchAnalyticsService class in app/Services/
  - Add routes for analytics AJAX endpoints in routes/web.php
  - _Requirements: 3.4, 9.2_

- [ ] 2. Implement MatchAnalyticsService for batch-level analytics
  - [ ] 2.1 Create calculateBatchStatistics method
    - Calculate total records, match status distribution, average confidence
    - Exclude NEW RECORD entries from confidence calculations
    - Use database aggregation queries for performance
    - _Requirements: 6.1, 6.2, 6.7, 9.1_
  
  - [ ]* 2.2 Write property test for batch statistics
    - **Property 8: Batch Average Confidence Calculation**
    - **Property 9: Match Status Distribution**
    - **Validates: Requirements 6.1, 6.2, 6.7**
  
  - [ ] 2.3 Create calculateFieldPopulationRates method
    - Calculate population count and percentage for each core field
    - Handle template fields when template_id is provided
    - Implement chunking for batches over 10,000 records
    - _Requirements: 2.3, 4.1, 4.2, 9.1, 9.6_
  
  - [ ]* 2.4 Write property test for field population rates
    - **Property 3: Field Population Rate Calculation**
    - **Validates: Requirements 2.3, 4.1, 4.2**
  
  - [ ] 2.5 Create generateChartData method
    - Generate pie chart data for mapped vs skipped columns
    - Generate bar chart data for field population rates
    - Apply color coding (green for >80%, yellow for 50-80%, red for <50%)
    - _Requirements: 3.1, 3.2, 3.3, 3.6_
  
  - [ ] 2.6 Create calculateQualityScore method
    - Map confidence scores to quality levels (excellent ≥90%, good ≥75%, fair ≥60%, poor <60%)
    - Return quality level, score, and color code
    - _Requirements: 6.6_
  
  - [ ]* 2.7 Write property test for quality score mapping
    - **Property 11: Quality Score Mapping**
    - **Validates: Requirements 6.6**
  
  - [ ] 2.8 Create identifyTopBottomFields method
    - Rank fields by match rate and population rate
    - Return top 5 and bottom 5 fields
    - _Requirements: 2.4, 2.5, 4.4, 4.5_
  
  - [ ]* 2.9 Write property test for field ranking
    - **Property 4: Most/Least Common Field Identification**
    - **Validates: Requirements 2.4, 2.5, 4.4, 4.5**

- [ ] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 4. Enhance ConfidenceScoreService for template fields and normalized values
  - [ ] 4.1 Update generateBreakdown method to include template fields
    - Accept optional templateId parameter
    - Fetch template fields from TemplateField model
    - Process template_fields from uploadedData array
    - Add category property ('core' or 'template') to each field
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [ ] 4.2 Add normalized values to field breakdown structure
    - Include uploaded_normalized and existing_normalized properties
    - Fetch normalized values from *_normalized columns (last_name_normalized, first_name_normalized, middle_name_normalized)
    - Only include normalized values when available
    - _Requirements: 1.3, 5.2, 5.4_
  
  - [ ] 4.3 Implement calculateFieldConfidence method
    - Calculate confidence score for individual fields (0-100)
    - Use exact match = 100%, fuzzy match = 50-99%, no match = 0%
    - Handle different field types (string, date, integer, decimal)
    - _Requirements: 1.5, 5.5_
  
  - [ ]* 4.4 Write unit tests for enhanced breakdown structure
    - Test template field inclusion
    - Test normalized value inclusion
    - Test category property assignment
    - Test backward compatibility
    - _Requirements: 5.1, 5.2, 5.3, 5.6_

- [ ] 5. Create controller endpoints for analytics data
  - [ ] 5.1 Add getBatchAnalytics method to MatchResultsController
    - Accept batchId parameter
    - Call MatchAnalyticsService methods
    - Return JSON with statistics, quality, field_population, chart_data
    - Handle errors with 404/500 responses
    - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 6.1, 6.2_
  
  - [ ] 5.2 Add getFieldBreakdown method to MatchResultsController
    - Accept resultId parameter
    - Fetch MatchResult with field_breakdown JSON
    - Return enhanced breakdown data
    - Handle missing or invalid result IDs
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 9.4_
  
  - [ ] 5.3 Add exportFieldBreakdown method to MatchResultsController
    - Generate CSV from field breakdown data
    - Include all columns: field name, category, status, values, normalized values, confidence
    - Use proper CSV escaping for special characters
    - Set filename as "field-breakdown-{resultId}-{timestamp}.csv"
    - Return CSV download response
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.7_
  
  - [ ]* 5.4 Write unit tests for controller endpoints
    - Test getBatchAnalytics returns correct JSON structure
    - Test getBatchAnalytics returns 404 for invalid batch
    - Test getFieldBreakdown returns correct data
    - Test exportFieldBreakdown generates valid CSV
    - _Requirements: 2.1, 8.2, 8.3_
  
  - [ ]* 5.5 Write property test for CSV escaping
    - **Property 15: CSV Special Character Escaping**
    - **Validates: Requirements 8.7**

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Enhance Column Mapping Summary card in results.blade.php
  - [ ] 7.1 Add percentage calculations to card display
    - Calculate and display mapped percentage
    - Calculate and display skipped percentage
    - Display total column count breakdown
    - _Requirements: 2.1, 2.2, 2.7_
  
  - [ ] 7.2 Add batch statistics section
    - Display average confidence score
    - Display match status distribution percentages
    - Display average matched/mismatched fields per record
    - Display quality score indicator with color badge
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6_
  
  - [ ] 7.3 Add chart containers with loading indicators
    - Add canvas element for mapping pie chart
    - Add canvas element for field population bar chart
    - Add loading spinner that displays while fetching data
    - _Requirements: 3.1, 3.2, 3.3, 9.3_
  
  - [ ] 7.4 Add field statistics table
    - Display core fields with population counts and percentages
    - Display template fields separately when available
    - Add warning icons for fields below 50% population
    - Display top/bottom fields by match rate
    - _Requirements: 2.3, 2.4, 2.5, 4.1, 4.2, 4.3, 4.6_

- [ ] 8. Create ColumnMappingAnalytics JavaScript module
  - [ ] 8.1 Implement initialize method
    - Trigger on card expansion event
    - Fetch analytics data via AJAX from getBatchAnalytics endpoint
    - Cache results in sessionStorage
    - Display loading indicator during fetch
    - _Requirements: 9.2, 9.3, 9.5_
  
  - [ ] 8.2 Implement renderMappingPieChart method
    - Use Chart.js to render pie chart
    - Apply color coding from chart_data
    - Add tooltips with exact values
    - Make chart responsive
    - _Requirements: 3.1, 3.4, 3.5, 3.7_
  
  - [ ] 8.3 Implement renderPopulationBarChart method
    - Use Chart.js to render bar chart for core fields
    - Render separate bar chart for template fields if present
    - Apply color coding based on population rate
    - Add tooltips with exact values
    - Make chart responsive
    - _Requirements: 3.2, 3.3, 3.4, 3.5, 3.7_
  
  - [ ] 8.4 Implement updateStatistics method
    - Update DOM elements with batch statistics
    - Update quality score badge with appropriate color
    - Update field statistics table
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6_
  
  - [ ] 8.5 Implement error handling
    - Display user-friendly error messages
    - Provide retry button on failure
    - Show fallback table view if charts fail to render
    - Log errors to console
    - _Requirements: 9.3_
  
  - [ ]* 8.6 Write unit tests for ColumnMappingAnalytics
    - Test analytics initialization on card expansion
    - Test chart rendering with valid data
    - Test error handling for failed AJAX requests
    - Test cache utilization

- [ ] 9. Enhance Field Breakdown Modal in results.blade.php
  - [ ] 9.1 Update modal structure
    - Add filter button group (All, Matched, Mismatched, New)
    - Add export to CSV button
    - Add field count display
    - Group fields by category (Core vs Template) with visual separation
    - _Requirements: 1.6, 7.1, 8.1_
  
  - [ ] 9.2 Update field table layout
    - Add columns for field name, status, uploaded value, existing value
    - Add columns for normalized values (when available)
    - Add column for confidence score
    - Add match quality indicator icons
    - Use color coding for value differences
    - Make table responsive for mobile
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7, 1.8_

- [ ] 10. Create FieldBreakdownModal JavaScript module
  - [ ] 10.1 Implement loadBreakdown method
    - Fetch field breakdown via AJAX from getFieldBreakdown endpoint
    - Display loading indicator
    - Handle empty or invalid data
    - _Requirements: 1.1, 1.2, 9.4_
  
  - [ ] 10.2 Implement renderFieldTable method
    - Render field comparison table with all columns
    - Apply match quality indicators (icons and colors)
    - Highlight differences between uploaded and existing values
    - Group fields by category
    - Display normalized values when available
    - Display confidence scores when available
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_
  
  - [ ] 10.3 Implement applyFilter method
    - Filter fields by status (all, matched, mismatched, new)
    - Update field count display
    - Update filter button badges with counts
    - Display "no results" message when filter returns empty
    - Maintain filter when switching categories
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_
  
  - [ ] 10.4 Implement exportToCSV method
    - Generate CSV from field breakdown data
    - Trigger browser download
    - Use proper filename format
    - Handle export failures with fallback copy option
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  
  - [ ] 10.5 Implement error handling
    - Display error messages in modal
    - Handle AJAX failures
    - Handle empty data gracefully
    - Disable export button when no data
    - _Requirements: 9.4_
  
  - [ ]* 10.6 Write unit tests for FieldBreakdownModal
    - Test modal opens with correct data
    - Test field table rendering
    - Test filter functionality
    - Test CSV generation
    - Test error handling

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Implement accessibility features
  - [ ] 12.1 Add keyboard navigation support
    - Make modal navigable with Tab, Enter, Escape keys
    - Add focus management for modal open/close
    - Add keyboard shortcuts for filter buttons
    - _Requirements: 10.1_
  
  - [ ] 12.2 Add ARIA labels and semantic HTML
    - Add ARIA labels to charts describing data
    - Add ARIA labels to match quality indicators
    - Add ARIA live regions for filter state announcements
    - Use semantic HTML (table, th, td) for tabular data
    - Add screen reader announcements for modal title
    - _Requirements: 10.2, 10.3, 10.4, 10.6, 10.7_
  
  - [ ] 12.3 Add color-blind friendly indicators
    - Supplement color coding with icons
    - Add text labels for quality indicators
    - Ensure sufficient contrast ratios
    - _Requirements: 10.5_
  
  - [ ]* 12.4 Write accessibility tests
    - Test keyboard navigation
    - Test ARIA labels
    - Test screen reader announcements
    - Run axe-core automated accessibility audit

- [ ] 13. Performance optimization and caching
  - [ ] 13.1 Implement session caching for analytics data
    - Cache batch statistics in session
    - Cache field breakdown data in sessionStorage
    - Set appropriate cache expiration
    - _Requirements: 9.5_
  
  - [ ] 13.2 Optimize database queries
    - Use query chunking for large batches
    - Add database indexes if needed
    - Use eager loading for relationships
    - _Requirements: 9.1, 9.6_
  
  - [ ] 13.3 Implement lazy loading
    - Load analytics data only when card is expanded
    - Load field breakdown only when modal is opened
    - Limit chart data points for large datasets
    - _Requirements: 9.2, 9.4, 9.6_
  
  - [ ]* 13.4 Write performance tests
    - Test page load time with large batches
    - Test analytics calculation performance
    - Test chart rendering performance

- [ ] 14. Integration and final testing
  - [ ] 14.1 Wire all components together
    - Connect card expansion to analytics loading
    - Connect "View Breakdown" buttons to modal
    - Connect filter buttons to field display
    - Connect export button to CSV download
    - _Requirements: All_
  
  - [ ] 14.2 Test complete user workflows
    - Test upload → view results → expand analytics → view breakdown flow
    - Test filtering and export functionality
    - Test responsive behavior on mobile devices
    - Test with various batch sizes (small, medium, large)
    - _Requirements: All_
  
  - [ ]* 14.3 Write integration tests
    - Test end-to-end workflows with Laravel Dusk
    - Test chart interactions
    - Test CSV download
    - Test error scenarios

- [ ] 15. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- This feature requires both PHP (Laravel backend) and JavaScript (AdminLTE frontend)
- Chart.js should be added to package.json and compiled with Vite
- All AJAX endpoints should include CSRF token protection
- Session caching improves performance but should respect data freshness
- Property tests validate universal correctness across randomized inputs
- Integration tests ensure complete workflows function correctly
