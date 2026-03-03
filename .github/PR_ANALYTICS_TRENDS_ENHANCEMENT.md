# Results Page Analytics Trends Enhancement

## Overview
Enhanced the results page analytics section with trend indicators showing performance changes between batches and added visibility into core and custom fields used in the template.

## Changes

### Backend Enhancements

#### MatchAnalyticsService (`app/Services/MatchAnalyticsService.php`)

**Enhanced `calculateBatchTrends()` method:**
- Now compares current batch statistics with the previous batch to determine trend direction
- Returns trend indicators ('up', 'down', 'neutral') for each metric:
  - Quality Score
  - Average Confidence
  - Average Matched Fields
  - Average Mismatched Fields
- Trend direction for mismatched fields is inverted (down is good, indicating fewer mismatches)

**New `calculateTrendDirection()` helper method:**
- Compares previous and current values to determine trend
- Calculates percentage change with 1% threshold for neutral classification
- Supports inverted trends for metrics where lower is better
- Returns: 'up', 'down', or 'neutral'

**New `getTemplateFieldsInfo()` method:**
- Retrieves core fields and custom template fields used in the batch
- Returns structured array with:
  - `core_fields`: Array of core field names (e.g., regs_no, last_name, birthday)
  - `custom_fields`: Array of custom template field names

#### ResultsController (`app/Http/Controllers/ResultsController.php`)

**Updated `getBatchTrends()` endpoint:**
- Now includes template fields information in API response
- Returns:
  - `batch_id`: The batch identifier
  - `trends`: Trend metrics with direction indicators
  - `match_status_chart`: Pie chart data for match status distribution
  - `template_fields`: Core and custom fields used in the batch

### Frontend Enhancements

#### JavaScript (`resources/views/pages/results.blade.php`)

**Updated `MatchStatusAnalytics` class:**

- **`initialize()` method:** Now passes template fields to rendering function

- **`renderTrends()` method:** Enhanced to display:
  - Trend indicators next to each metric using Font Awesome icons:
    - ⬆️ Green up arrow: Improvement from previous batch
    - ⬇️ Red down arrow: Decline from previous batch
    - ➖ Gray dash: No significant change (neutral)
  - Two-column section below trends showing:
    - **Core Fields Used** (left): Displays core fields with primary badges
    - **Custom Fields Used** (right): Displays template fields with info badges
  - Horizontal divider separating trends from field information

- **`getTrendIcon()` helper function:** Returns appropriate Font Awesome icon based on trend direction

### UI/UX Improvements

**Enhanced Batch Analytics Card:**
- Batch Statistics boxes remain unchanged (Total Rows, Matched, Possible Duplicates, New Records)
- Match Status Distribution pie chart displays below statistics
- Batch Trends section now includes:
  - 4 metrics with directional indicators
  - Visual feedback on performance changes
  - Clear labeling of core vs. custom fields
  - Responsive two-column layout for field information

**Visual Layout:**
```
Batch Statistics (4 boxes)
├── Total Rows Processed
├── Matched
├── Possible Duplicates
└── New Records

Match Status Distribution (Pie Chart) | Batch Trends (4 metrics with arrows)
                                      ├── Quality Score: 93.9% ⬆️
                                      ├── Avg Confidence: 93.9% ➖
                                      ├── Avg Matched Fields: 3.8 ⬆️
                                      └── Avg Mismatched Fields: 4.3 ⬇️
                                      
                                      Core Fields Used | Custom Fields Used
                                      [badges]         | [badges]
```

## API Changes

### New Endpoint
- **GET** `/api/batch-trends/{batchId}`
- Returns trends with direction indicators and template field information
- Response includes trend direction for each metric to show performance changes

### Response Format
```json
{
  "batch_id": 1,
  "trends": {
    "quality_score": 93.9,
    "quality_trend": "up",
    "avg_confidence": 93.9,
    "confidence_trend": "neutral",
    "avg_matched_fields": 3.8,
    "matched_fields_trend": "up",
    "avg_mismatched_fields": 4.3,
    "mismatched_fields_trend": "down"
  },
  "match_status_chart": {
    "labels": ["Matched", "Possible Duplicates", "New Records"],
    "data": [20, 15, 25],
    "colors": ["#28a745", "#ffc107", "#dc3545"]
  },
  "template_fields": {
    "core_fields": ["regs_no", "last_name", "first_name", "birthday"],
    "custom_fields": ["phone_number", "email", "address_line_2"]
  }
}
```

## Benefits

1. **Performance Visibility**: Users can quickly see if batch quality is improving or declining
2. **Contextual Insights**: Trend indicators provide immediate feedback on batch performance changes
3. **Field Transparency**: Clear display of which fields are being used in the matching process
4. **Better Decision Making**: Helps identify patterns and trends across batch uploads
5. **Responsive Design**: Two-column layout adapts to different screen sizes

## Technical Details

### Trend Calculation Logic
- Compares current batch with immediately previous batch
- Uses 1% threshold to avoid noise in trend detection
- For mismatched fields, trend direction is inverted (down = improvement)
- Returns 'neutral' if no previous batch exists or change is minimal

### Field Information
- Core fields sourced from batch column mapping
- Custom fields sourced from dynamic fields captured during upload
- Displayed with appropriate badge colors for visual distinction

## Files Modified

- `app/Services/MatchAnalyticsService.php` - Added trend calculation and field retrieval methods
- `app/Http/Controllers/ResultsController.php` - Enhanced trends endpoint
- `routes/web.php` - Added trends route (already included in previous PR)
- `resources/views/pages/results.blade.php` - Updated analytics rendering with trends and field display

## Testing Recommendations

1. Test trend calculation with multiple batches to verify direction accuracy
2. Verify trend indicators display correctly for up/down/neutral scenarios
3. Test with batches containing various core and custom field combinations
4. Verify responsive layout on mobile/tablet devices
5. Test edge cases (first batch, no previous batch, identical metrics)
6. Validate API response structure and data accuracy

## Backward Compatibility

- Existing analytics functionality remains unchanged
- New trend indicators are additive and don't break existing features
- Field information display is optional and gracefully handles missing data
- All changes are backward compatible with existing code

## Performance Considerations

- Trend calculation queries previous batch only (minimal database impact)
- Field information retrieved from cached column mapping data
- No additional database queries required for field display
- Efficient trend direction calculation with early exit conditions
