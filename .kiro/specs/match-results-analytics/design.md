# Design Document: Match Results Analytics

## Overview

This feature enhances the match results view (`resources/views/pages/results.blade.php`) by providing comprehensive field-level analytics, visual data quality indicators, and interactive filtering capabilities. The enhancement transforms the existing basic field breakdown modal into a rich analytics dashboard that displays core fields, template fields, normalized values, confidence scores, and visual charts.

The design focuses on three main areas:
1. **Enhanced Field Breakdown Modal** - Detailed field-by-field comparison with filtering, export, and quality indicators
2. **Column Mapping Analytics** - Comprehensive metrics, charts, and batch-level statistics
3. **Performance & Accessibility** - Optimized data loading and WCAG-compliant UI components

### Key Design Decisions

- **Progressive Enhancement**: Analytics features load on-demand to maintain fast initial page load
- **Client-Side Rendering**: Charts and interactive features use JavaScript for responsive UX
- **Backward Compatibility**: Enhanced field breakdown structure maintains compatibility with existing data
- **Separation of Concerns**: Analytics logic separated into dedicated service classes

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Results View (Blade)                     │
│  ┌────────────────┐  ┌──────────────────────────────────┐  │
│  │ Column Mapping │  │   Match Results Table            │  │
│  │ Summary Card   │  │   - Batch filtering              │  │
│  │ - Metrics      │  │   - Status filtering             │  │
│  │ - Charts       │  │   - Pagination                   │  │
│  │ - Statistics   │  │   - View Breakdown buttons       │  │
│  └────────────────┘  └──────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              Field Breakdown Modal (JavaScript)              │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │ Filter Tabs  │  │ Field Table  │  │ Export Button   │  │
│  │ - All        │  │ - Core       │  │ - CSV Download  │  │
│  │ - Matched    │  │ - Template   │  │                 │  │
│  │ - Mismatched │  │ - Normalized │  │                 │  │
│  │ - New        │  │ - Confidence │  │                 │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Backend Services                          │
│  ┌──────────────────────┐  ┌──────────────────────────┐    │
│  │ ConfidenceScore      │  │ MatchAnalytics           │    │
│  │ Service              │  │ Service                  │    │
│  │ - Field breakdown    │  │ - Batch statistics       │    │
│  │ - Score calculation  │  │ - Field population rates │    │
│  │ - Template fields    │  │ - Chart data generation  │    │
│  └──────────────────────┘  └──────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │ MatchResult  │  │ MainSystem   │  │ TemplateField    │  │
│  │ - breakdown  │  │ - core fields│  │ - custom fields  │  │
│  │ - confidence │  │ - normalized │  │ - field types    │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Page Load**: Controller fetches match results with basic data
2. **Card Expansion**: AJAX request loads analytics data when Column Mapping Summary is expanded
3. **Modal Open**: JavaScript fetches detailed field breakdown for specific match result
4. **Chart Rendering**: Chart.js renders visualizations from JSON data
5. **Export**: Client-side CSV generation from field breakdown data

## Components and Interfaces

### Backend Components

#### MatchAnalyticsService

New service class for calculating batch-level analytics and generating chart data.

```php
namespace App\Services;

class MatchAnalyticsService
{
    /**
     * Calculate batch-level statistics
     * 
     * @param int $batchId
     * @return array Statistics including match rates, field population, quality score
     */
    public function calculateBatchStatistics(int $batchId): array;

    /**
     * Generate field population rates for core fields
     * 
     * @param int $batchId
     * @return array Field names with population counts and percentages
     */
    public function calculateFieldPopulationRates(int $batchId): array;

    /**
     * Generate chart data for column mapping visualization
     * 
     * @param int $batchId
     * @param array $columnMapping Session data
     * @return array Chart datasets for pie and bar charts
     */
    public function generateChartData(int $batchId, array $columnMapping): array;

    /**
     * Calculate quality score for batch
     * 
     * @param array $statistics Batch statistics
     * @return array Quality level (excellent/good/fair/poor) and score
     */
    public function calculateQualityScore(array $statistics): array;
}
```

#### Enhanced ConfidenceScoreService

Extends existing service to include template fields and normalized values.

```php
namespace App\Services;

class ConfidenceScoreService
{
    /**
     * Generate enhanced field breakdown with template fields
     * 
     * @param array $uploadedData Including core_fields and template_fields
     * @param MainSystem $existingRecord
     * @param int|null $templateId
     * @return array Enhanced breakdown structure
     */
    public function generateBreakdown(
        array $uploadedData, 
        MainSystem $existingRecord,
        ?int $templateId = null
    ): array;

    /**
     * Calculate field-level confidence score
     * 
     * @param string $uploadedValue
     * @param string $existingValue
     * @param string $fieldType
     * @return float Confidence percentage (0-100)
     */
    protected function calculateFieldConfidence(
        string $uploadedValue,
        string $existingValue,
        string $fieldType
    ): float;
}
```

#### MatchResultsController Enhancement

Add methods for AJAX endpoints.

```php
namespace App\Http\Controllers;

class MatchResultsController extends Controller
{
    /**
     * Get analytics data for batch
     * 
     * @param int $batchId
     * @return JsonResponse
     */
    public function getBatchAnalytics(int $batchId): JsonResponse;

    /**
     * Get detailed field breakdown for match result
     * 
     * @param int $resultId
     * @return JsonResponse
     */
    public function getFieldBreakdown(int $resultId): JsonResponse;

    /**
     * Export field breakdown as CSV
     * 
     * @param int $resultId
     * @return Response CSV download
     */
    public function exportFieldBreakdown(int $resultId): Response;
}
```

### Frontend Components

#### ColumnMappingAnalytics (JavaScript Module)

Handles chart rendering and analytics display.

```javascript
class ColumnMappingAnalytics {
    /**
     * Initialize analytics when card is expanded
     * @param {number} batchId
     */
    async initialize(batchId);

    /**
     * Render pie chart for mapped vs skipped columns
     * @param {Object} chartData
     */
    renderMappingPieChart(chartData);

    /**
     * Render bar chart for field population rates
     * @param {Object} chartData
     */
    renderPopulationBarChart(chartData);

    /**
     * Update statistics display
     * @param {Object} statistics
     */
    updateStatistics(statistics);
}
```

#### FieldBreakdownModal (JavaScript Module)

Manages modal interactions, filtering, and export.

```javascript
class FieldBreakdownModal {
    /**
     * Load and display field breakdown
     * @param {number} resultId
     */
    async loadBreakdown(resultId);

    /**
     * Apply filter to field list
     * @param {string} filterType - 'all', 'matched', 'mismatched', 'new'
     */
    applyFilter(filterType);

    /**
     * Export field breakdown to CSV
     */
    exportToCSV();

    /**
     * Render field comparison table
     * @param {Array} fields
     */
    renderFieldTable(fields);
}
```

## Data Models

### Enhanced Field Breakdown Structure

The field breakdown JSON stored in `match_results.field_breakdown` will be enhanced:

```json
{
  "total_fields": 15,
  "matched_fields": 12,
  "core_fields": {
    "last_name": {
      "status": "match",
      "uploaded": "Smith",
      "existing": "Smith",
      "uploaded_normalized": "smith",
      "existing_normalized": "smith",
      "category": "core",
      "confidence": 100.0
    },
    "first_name": {
      "status": "mismatch",
      "uploaded": "John",
      "existing": "Jon",
      "uploaded_normalized": "john",
      "existing_normalized": "jon",
      "category": "core",
      "confidence": 75.0
    },
    "birthday": {
      "status": "match",
      "uploaded": "1990-05-15",
      "existing": "1990-05-15",
      "category": "core",
      "confidence": 100.0
    }
  },
  "template_fields": {
    "employee_id": {
      "status": "match",
      "uploaded": "EMP-12345",
      "existing": "EMP-12345",
      "category": "template",
      "field_type": "string",
      "confidence": 100.0
    },
    "salary": {
      "status": "new",
      "uploaded": "50000.00",
      "existing": null,
      "category": "template",
      "field_type": "decimal",
      "confidence": null
    }
  }
}
```

### Batch Analytics Data Structure

Data returned by `getBatchAnalytics` endpoint:

```json
{
  "batch_id": 123,
  "statistics": {
    "total_records": 1000,
    "matched": 850,
    "possible_duplicates": 100,
    "new_records": 50,
    "average_confidence": 87.5,
    "average_matched_fields": 11.2,
    "average_mismatched_fields": 1.8
  },
  "quality": {
    "level": "good",
    "score": 87.5,
    "color": "success"
  },
  "field_population": {
    "core_fields": {
      "last_name": {"count": 998, "percentage": 99.8},
      "first_name": {"count": 995, "percentage": 99.5},
      "middle_name": {"count": 750, "percentage": 75.0},
      "birthday": {"count": 980, "percentage": 98.0}
    },
    "template_fields": {
      "employee_id": {"count": 1000, "percentage": 100.0},
      "department": {"count": 950, "percentage": 95.0}
    }
  },
  "chart_data": {
    "mapping_pie": {
      "labels": ["Mapped", "Skipped"],
      "data": [12, 3],
      "colors": ["#28a745", "#6c757d"]
    },
    "population_bar": {
      "labels": ["last_name", "first_name", "middle_name", "birthday"],
      "data": [99.8, 99.5, 75.0, 98.0],
      "colors": ["#28a745", "#28a745", "#ffc107", "#28a745"]
    }
  }
}
```

### CSV Export Format

Field breakdown CSV structure:

```csv
Field Name,Category,Status,Uploaded Value,Existing Value,Uploaded Normalized,Existing Normalized,Confidence Score
last_name,core,match,Smith,Smith,smith,smith,100.0
first_name,core,mismatch,John,Jon,john,jon,75.0
birthday,core,match,1990-05-15,1990-05-15,,,100.0
employee_id,template,match,EMP-12345,EMP-12345,,,100.0
salary,template,new,50000.00,,,,
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified the following redundancies:
- Properties 2.1 and 2.2 (mapped/skipped percentages) can be combined since they're complementary calculations
- Properties 4.1 and 4.2 (count and percentage) are redundant - percentage calculation implies count
- Properties 4.4 and 4.5 (lowest/highest population) can be combined into one property about ranking
- Properties 6.3 and 6.4 (matched/mismatched averages) are complementary and can be combined
- Properties 7.2 and 7.3 (filter display and count) are related but 7.3 is a consequence of 7.2

### Property 1: Field Quality Indicators

*For any* field displayed in the Field Breakdown Modal, the system should display a Match Quality Indicator (icon, badge, or color) that corresponds to the field's comparison status (match, mismatch, or new).

**Validates: Requirements 1.4**

### Property 2: Column Mapping Percentage Calculation

*For any* set of columns with mapped and skipped subsets, the sum of the mapped percentage and skipped percentage should equal 100%, and each percentage should accurately reflect the count ratio.

**Validates: Requirements 2.1, 2.2**

### Property 3: Field Population Rate Calculation

*For any* core field across a batch of records, the displayed population percentage should equal (count of non-empty values / total records) × 100, rounded to one decimal place.

**Validates: Requirements 2.3, 4.1, 4.2**

### Property 4: Most/Least Common Field Identification

*For any* batch of records, when ranking fields by match rate or population rate, the system should correctly identify and display fields in descending order of their respective rates.

**Validates: Requirements 2.4, 2.5, 4.4, 4.5**

### Property 5: Column Count Breakdown

*For any* upload batch, the total column count should equal the sum of core field count, template field count, and skipped column count.

**Validates: Requirements 2.7**

### Property 6: Low Population Indicator

*For any* field with a population rate below 50%, the system should display a data quality indicator (icon or badge) to highlight the low population.

**Validates: Requirements 4.6**

### Property 7: Field Category Property

*For any* field in the Field Breakdown Data structure, the field object should include a "category" property with value "core" or "template".

**Validates: Requirements 5.3**

### Property 8: Batch Average Confidence Calculation

*For any* batch of match results, the displayed average confidence score should equal the sum of all individual confidence scores divided by the number of records, excluding NEW RECORD entries.

**Validates: Requirements 6.1, 6.7**

### Property 9: Match Status Distribution

*For any* batch of match results, the sum of percentages for MATCHED, POSSIBLE DUPLICATE, and NEW RECORD statuses should equal 100%.

**Validates: Requirements 6.2**

### Property 10: Average Field Counts

*For any* batch of match results, the average matched fields and average mismatched fields per record should sum to the average total fields per record.

**Validates: Requirements 6.3, 6.4**

### Property 11: Quality Score Mapping

*For any* batch with calculated match statistics, the quality level (excellent/good/fair/poor) should be consistently determined by the average confidence score: excellent (≥90%), good (≥75%), fair (≥60%), poor (<60%).

**Validates: Requirements 6.6**

### Property 12: Filter Field Display

*For any* field breakdown with a selected filter (matched/mismatched/new), only fields matching the filter status should be displayed in the modal.

**Validates: Requirements 7.2**

### Property 13: Filter Count Accuracy

*For any* filter applied to the field breakdown, the displayed count badge should equal the number of fields matching that filter status.

**Validates: Requirements 7.3, 7.6**

### Property 14: CSV Filename Format

*For any* exported field breakdown CSV, the filename should match the pattern "field-breakdown-{result_id}-{timestamp}.csv" where result_id is numeric and timestamp is in ISO format.

**Validates: Requirements 8.5**

### Property 15: CSV Special Character Escaping

*For any* field value containing special characters (commas, quotes, newlines), the exported CSV should properly escape these characters according to RFC 4180 standards.

**Validates: Requirements 8.7**


## Error Handling

### Backend Error Scenarios

#### Missing or Invalid Batch Data

**Scenario**: Analytics requested for non-existent or invalid batch ID

**Handling**:
- Return 404 JSON response with error message
- Log warning with batch ID for debugging
- Frontend displays user-friendly message in analytics card

```php
public function getBatchAnalytics(int $batchId): JsonResponse
{
    $batch = UploadBatch::find($batchId);
    
    if (!$batch) {
        Log::warning('Analytics requested for non-existent batch', ['batch_id' => $batchId]);
        return response()->json([
            'error' => 'Batch not found',
            'message' => 'The requested batch does not exist.'
        ], 404);
    }
    
    // Continue with analytics calculation
}
```

#### Database Query Failures

**Scenario**: Database connection issues or query timeouts during statistics calculation

**Handling**:
- Catch database exceptions
- Return 500 JSON response with generic error message
- Log full exception details for debugging
- Frontend displays retry option

```php
try {
    $statistics = $this->matchAnalyticsService->calculateBatchStatistics($batchId);
} catch (\Illuminate\Database\QueryException $e) {
    Log::error('Database error calculating batch statistics', [
        'batch_id' => $batchId,
        'error' => $e->getMessage()
    ]);
    
    return response()->json([
        'error' => 'Database error',
        'message' => 'Unable to calculate statistics. Please try again.'
    ], 500);
}
```

#### Missing Template Field Data

**Scenario**: Template fields referenced but template or fields no longer exist

**Handling**:
- Gracefully skip missing template fields
- Log warning about missing template
- Display only available fields in breakdown
- Show informational message to user

```php
protected function getTemplateFields(?int $templateId): array
{
    if (!$templateId) {
        return [];
    }
    
    $template = ColumnMappingTemplate::with('fields')->find($templateId);
    
    if (!$template) {
        Log::warning('Template not found for field breakdown', ['template_id' => $templateId]);
        return [];
    }
    
    return $template->fields->toArray();
}
```

#### Large Dataset Performance Issues

**Scenario**: Batch with >10,000 records causes timeout or memory issues

**Handling**:
- Implement query chunking for large batches
- Set reasonable timeout limits
- Return partial results with warning if timeout approaching
- Cache results to avoid recalculation

```php
public function calculateFieldPopulationRates(int $batchId): array
{
    $totalRecords = MatchResult::where('batch_id', $batchId)->count();
    
    if ($totalRecords > 10000) {
        // Use chunking for large datasets
        return $this->calculateFieldPopulationRatesChunked($batchId, $totalRecords);
    }
    
    // Standard calculation for smaller datasets
    return $this->calculateFieldPopulationRatesStandard($batchId, $totalRecords);
}
```

### Frontend Error Scenarios

#### AJAX Request Failures

**Scenario**: Network error or server unavailable when loading analytics

**Handling**:
- Display error message in analytics card
- Provide retry button
- Log error to browser console
- Maintain UI in usable state

```javascript
async loadBatchAnalytics(batchId) {
    try {
        const response = await fetch(`/api/batch-analytics/${batchId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        this.renderAnalytics(data);
    } catch (error) {
        console.error('Failed to load batch analytics:', error);
        this.showError('Unable to load analytics. Please try again.', true);
    }
}

showError(message, showRetry = false) {
    const errorHtml = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> ${message}
            ${showRetry ? '<button class="btn btn-sm btn-primary ml-3" onclick="retryLoad()">Retry</button>' : ''}
        </div>
    `;
    document.getElementById('analytics-container').innerHTML = errorHtml;
}
```

#### Chart Rendering Failures

**Scenario**: Chart.js fails to render due to invalid data or library error

**Handling**:
- Catch rendering exceptions
- Display fallback table view of data
- Log error details
- Show message about chart unavailability

```javascript
renderChart(chartData) {
    try {
        const ctx = document.getElementById('mappingChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: chartData,
            options: this.chartOptions
        });
    } catch (error) {
        console.error('Chart rendering failed:', error);
        this.renderFallbackTable(chartData);
        this.showWarning('Chart visualization unavailable. Displaying data in table format.');
    }
}
```

#### CSV Export Failures

**Scenario**: Browser blocks download or CSV generation fails

**Handling**:
- Catch export exceptions
- Display error message
- Offer alternative (copy to clipboard)
- Log error for debugging

```javascript
exportToCSV() {
    try {
        const csv = this.generateCSV();
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = this.getFilename();
        link.click();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('CSV export failed:', error);
        alert('Export failed. Please try copying the data manually.');
        this.showCopyOption();
    }
}
```

#### Empty or Invalid Data

**Scenario**: Field breakdown data is empty or malformed

**Handling**:
- Validate data structure before rendering
- Display appropriate "no data" message
- Provide context about why data might be missing
- Disable export button when no data available

```javascript
renderFieldBreakdown(data) {
    if (!data || !data.fields || Object.keys(data.fields).length === 0) {
        this.showEmptyState('No field comparison data available for this match result.');
        document.getElementById('export-btn').disabled = true;
        return;
    }
    
    // Validate data structure
    if (!this.isValidBreakdownData(data)) {
        console.error('Invalid field breakdown data structure:', data);
        this.showError('Field breakdown data is malformed. Please contact support.');
        return;
    }
    
    // Proceed with rendering
    this.renderFieldTable(data.fields);
}
```

## Testing Strategy

### Dual Testing Approach

This feature requires both unit testing and property-based testing to ensure comprehensive coverage:

- **Unit Tests**: Verify specific examples, edge cases, UI rendering, and integration points
- **Property Tests**: Verify universal properties across randomized inputs (calculations, data transformations)

### Backend Testing

#### Unit Tests (PHPUnit)

**MatchAnalyticsService Tests**:
- Test batch statistics calculation with known dataset
- Test field population rate calculation with various field combinations
- Test chart data generation with different column mappings
- Test quality score calculation for each quality level threshold
- Test handling of batches with no records
- Test handling of batches with only NEW RECORD entries
- Test template field inclusion when template exists
- Test graceful handling when template is deleted

**ConfidenceScoreService Tests**:
- Test enhanced breakdown includes template fields
- Test normalized values included when available
- Test field category property set correctly
- Test backward compatibility with existing consumers
- Test field confidence score calculation
- Test handling of null/empty values
- Test handling of different field types (string, date, integer, decimal)

**MatchResultsController Tests**:
- Test getBatchAnalytics returns correct JSON structure
- Test getBatchAnalytics returns 404 for invalid batch
- Test getFieldBreakdown returns correct data
- Test exportFieldBreakdown generates valid CSV
- Test exportFieldBreakdown includes all required columns
- Test authentication/authorization for endpoints

#### Property-Based Tests (PHPUnit with appropriate library)

**Property Test Configuration**: Minimum 100 iterations per test

**Property 1: Column Mapping Percentages**
```php
/**
 * Feature: match-results-analytics, Property 2: Column Mapping Percentage Calculation
 * For any set of columns, mapped % + skipped % = 100%
 */
public function test_column_mapping_percentages_sum_to_100()
{
    $this->forAll(
        Generator::arrayOf(Generator::string(), Generator::between(1, 50)),
        Generator::arrayOf(Generator::string(), Generator::between(0, 50))
    )->then(function ($mapped, $skipped) {
        $service = new MatchAnalyticsService();
        $result = $service->calculateColumnMappingPercentages($mapped, $skipped);
        
        $this->assertEquals(100.0, $result['mapped_percentage'] + $result['skipped_percentage'], '', 0.1);
    });
}
```

**Property 2: Field Population Rate**
```php
/**
 * Feature: match-results-analytics, Property 3: Field Population Rate Calculation
 * For any field, population % = (non-empty count / total) × 100
 */
public function test_field_population_rate_calculation()
{
    $this->forAll(
        Generator::arrayOf(Generator::oneOf(Generator::string(), Generator::null()), Generator::between(10, 100))
    )->then(function ($fieldValues) {
        $nonEmptyCount = count(array_filter($fieldValues, fn($v) => $v !== null && $v !== ''));
        $total = count($fieldValues);
        $expected = ($nonEmptyCount / $total) * 100;
        
        $service = new MatchAnalyticsService();
        $result = $service->calculateFieldPopulationRate($fieldValues);
        
        $this->assertEquals($expected, $result, '', 0.1);
    });
}
```

**Property 3: Average Confidence Calculation**
```php
/**
 * Feature: match-results-analytics, Property 8: Batch Average Confidence Calculation
 * For any batch, average confidence = sum(scores) / count (excluding NEW RECORD)
 */
public function test_average_confidence_excludes_new_records()
{
    $this->forAll(
        Generator::arrayOf(
            Generator::tuple(
                Generator::float(0, 100),
                Generator::oneOf(Generator::constant('MATCHED'), Generator::constant('POSSIBLE DUPLICATE'), Generator::constant('NEW RECORD'))
            ),
            Generator::between(5, 50)
        )
    )->then(function ($results) {
        $filtered = array_filter($results, fn($r) => $r[1] !== 'NEW RECORD');
        $expected = count($filtered) > 0 ? array_sum(array_column($filtered, 0)) / count($filtered) : 0;
        
        $service = new MatchAnalyticsService();
        $result = $service->calculateAverageConfidence($results);
        
        $this->assertEquals($expected, $result, '', 0.1);
    });
}
```

**Property 4: Quality Score Mapping**
```php
/**
 * Feature: match-results-analytics, Property 11: Quality Score Mapping
 * For any confidence score, quality level should match defined thresholds
 */
public function test_quality_score_mapping_consistency()
{
    $this->forAll(
        Generator::float(0, 100)
    )->then(function ($confidence) {
        $service = new MatchAnalyticsService();
        $result = $service->calculateQualityScore(['average_confidence' => $confidence]);
        
        if ($confidence >= 90) {
            $this->assertEquals('excellent', $result['level']);
        } elseif ($confidence >= 75) {
            $this->assertEquals('good', $result['level']);
        } elseif ($confidence >= 60) {
            $this->assertEquals('fair', $result['level']);
        } else {
            $this->assertEquals('poor', $result['level']);
        }
    });
}
```

**Property 5: CSV Special Character Escaping**
```php
/**
 * Feature: match-results-analytics, Property 15: CSV Special Character Escaping
 * For any field value with special characters, CSV should escape properly
 */
public function test_csv_escapes_special_characters()
{
    $this->forAll(
        Generator::string()->withSpecialChars([',', '"', "\n", "\r"])
    )->then(function ($fieldValue) {
        $service = new MatchAnalyticsService();
        $csv = $service->generateCSVRow(['field' => $fieldValue]);
        
        // Parse CSV back
        $parsed = str_getcsv($csv);
        
        // Original value should be preserved
        $this->assertEquals($fieldValue, $parsed[0]);
    });
}
```

### Frontend Testing

#### Unit Tests (Jest + Testing Library)

**ColumnMappingAnalytics Tests**:
- Test analytics initialization on card expansion
- Test chart rendering with valid data
- Test statistics display updates
- Test error handling for failed AJAX requests
- Test loading indicator display
- Test lazy loading behavior
- Test cache utilization

**FieldBreakdownModal Tests**:
- Test modal opens with correct data
- Test field table rendering
- Test filter button functionality
- Test filter count badges update
- Test category grouping (core vs template)
- Test normalized value display
- Test confidence score display
- Test export button functionality
- Test CSV generation
- Test empty state display
- Test error handling

**Accessibility Tests**:
- Test keyboard navigation (Tab, Enter, Escape)
- Test ARIA labels on charts
- Test ARIA labels on quality indicators
- Test filter button state announcements
- Test semantic HTML structure
- Test color-blind friendly indicators
- Test screen reader announcements

#### Integration Tests (Laravel Dusk)

**End-to-End Workflows**:
- Test complete flow: upload → view results → expand analytics → view breakdown
- Test filter application and field display
- Test CSV export download
- Test chart interactions
- Test responsive behavior at different screen sizes
- Test performance with large batches (1000+ records)

### Test Coverage Goals

- **Backend**: Minimum 80% code coverage, 100% for calculation logic
- **Frontend**: Minimum 75% code coverage for JavaScript modules
- **Property Tests**: 100 iterations minimum per property
- **Integration Tests**: Cover all critical user workflows

### Testing Tools

- **Backend**: PHPUnit for unit tests, consider using `eris/eris` or similar for property-based testing in PHP
- **Frontend**: Jest for unit tests, React Testing Library for component tests, fast-check for property-based testing
- **Integration**: Laravel Dusk for browser automation
- **Accessibility**: axe-core for automated accessibility testing
- **Performance**: Laravel Telescope for backend profiling, Chrome DevTools for frontend profiling

### Continuous Integration

All tests must pass before merge:
1. Run PHPUnit tests (unit + property)
2. Run Jest tests
3. Run Dusk integration tests
4. Run accessibility audit
5. Check code coverage thresholds
6. Verify no console errors in browser tests

