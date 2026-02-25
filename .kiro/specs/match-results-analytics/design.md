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

