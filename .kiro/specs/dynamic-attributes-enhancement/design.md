# Design Document: Dynamic Attributes Enhancement & Column Mapping Templates

## Overview

This design enhances the existing dynamic schema support system to provide comprehensive attribute management across all record types (new and matched), introduce reusable column mapping templates, and calculate unified confidence scores that incorporate both core fields and dynamic attributes. The system currently saves dynamic attributes only for new records and calculates confidence based solely on core fields, limiting its ability to accurately assess data quality and match confidence.

The enhancement maintains backward compatibility with the existing Laravel 11 application while adding three major capabilities:
1. Dynamic attribute persistence for matched records with intelligent merge strategies
2. Column mapping template system for saving and reusing custom field mappings
3. Unified confidence score calculation incorporating all available data fields

## Architecture

### System Components

The enhancement integrates with the existing three-tier architecture:

**Data Layer (Models)**
- `MainSystem`: Extended to support dynamic attribute merging
- `ColumnMappingTemplate`: New model for storing user-defined mappings
- `MatchResult`: Enhanced to store detailed field-level match information

**Service Layer**
- `DataMappingService`: Enhanced to apply saved templates
- `DataMatchService`: Extended with unified confidence calculation
- `DynamicAttributeMergeService`: New service for attribute merging logic
- `ConfidenceScoreService`: New service for unified score calculation

**Controller Layer**
- `UploadController`: Enhanced to support template management
- `TemplateController`: New controller for template CRUD operations

### Data Flow

```
Excel Upload
    ↓
DataMappingService (apply template if selected)
    ↓
RecordImport (process rows)
    ↓
DataMatchService (find matches with unified scoring)
    ↓
DynamicAttributeMergeService (merge attributes for matched records)
    ↓
MainSystem (persist merged data)
    ↓
MatchResult (store detailed match breakdown)
```

## Components and Interfaces

### 1. DynamicAttributeMergeService

**Purpose**: Handle merging of dynamic attributes when updating matched records

**Interface**:
```php
class DynamicAttributeMergeService
{
    /**
     * Merge new dynamic attributes with existing ones
     * Strategy: Preserve old attributes not in new data, overwrite conflicts with new values
     * 
     * @param array $existingAttributes Current additional_attributes from database
     * @param array $newAttributes Dynamic fields from upload
     * @return array Merged attributes
     */
    public function merge(array $existingAttributes, array $newAttributes): array;
    
    /**
     * Validate merged attributes don't exceed size limits
     * 
     * @param array $mergedAttributes
     * @throws \InvalidArgumentException if size exceeds 65KB
     */
    protected function validateSize(array $mergedAttributes): void;
}
```

**Merge Algorithm**:
1. Start with existing attributes as base
2. Iterate through new attributes
3. For each new attribute:
   - If key exists in old: overwrite with new value
   - If key doesn't exist: add to merged result
4. Validate JSON size doesn't exceed MySQL TEXT limit (65KB)
5. Return merged array

**Example**:
```php
// Existing: {"province": "Cavite", "dept": "HR", "employee_id": "12345"}
// New: {"dept": "IT", "position": "Developer"}
// Result: {"province": "Cavite", "dept": "IT", "employee_id": "12345", "position": "Developer"}
```

### 2. ConfidenceScoreService

**Purpose**: Calculate unified confidence scores incorporating core fields and dynamic attributes

**Interface**:
```php
class ConfidenceScoreService
{
    /**
     * Calculate unified confidence score
     * 
     * @param array $uploadedData All fields from upload (core + dynamic)
     * @param MainSystem $existingRecord Database record to compare against
     * @return array ['score' => float, 'breakdown' => array]
     */
    public function calculateUnifiedScore(array $uploadedData, MainSystem $existingRecord): array;
    
    /**
     * Generate field-by-field comparison breakdown
     * 
     * @param array $uploadedData
     * @param MainSystem $existingRecord
     * @return array Field comparison details
     */
    public function generateBreakdown(array $uploadedData, MainSystem $existingRecord): array;
    
    /**
     * Compare two field values for equality
     * Treats null and empty string as equivalent
     * 
     * @param mixed $value1
     * @param mixed $value2
     * @return bool
     */
    protected function valuesMatch($value1, $value2): bool;
}
```

**Calculation Algorithm**:
1. Extract all core fields from uploaded data
2. Extract all dynamic fields from uploaded data
3. Extract all core fields from existing record
4. Extract all dynamic attributes from existing record
5. For each field in uploaded data:
   - If field exists in existing record: compare values
   - If values match: increment match counter
   - Track field status (match/mismatch/new)
6. Calculate: (matched fields / total uploaded fields) × 100
7. Return score and detailed breakdown

**Breakdown Structure**:
```php
[
    'score' => 75.0,
    'total_fields' => 4,
    'matched_fields' => 3,
    'fields' => [
        'last_name' => ['status' => 'match', 'uploaded' => 'Cruz', 'existing' => 'Cruz'],
        'first_name' => ['status' => 'match', 'uploaded' => 'Juan', 'existing' => 'Juan'],
        'province' => ['status' => 'match', 'uploaded' => 'Cavite', 'existing' => 'Cavite'],
        'dept' => ['status' => 'mismatch', 'uploaded' => 'IT', 'existing' => 'HR'],
    ]
]
```

### 3. ColumnMappingTemplate Model

**Purpose**: Store and manage user-defined column mappings

**Schema**:
```php
Schema::create('column_mapping_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->json('mappings'); // {"excel_column": "system_field", ...}
    $table->timestamps();
    
    $table->unique(['user_id', 'name']); // Unique template names per user
});
```

**Model Interface**:
```php
class ColumnMappingTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'mappings'];
    protected $casts = ['mappings' => 'array'];
    
    /**
     * Get templates for authenticated user
     */
    public static function forUser(int $userId): Collection;
    
    /**
     * Apply template mappings to uploaded data
     * 
     * @param array $row Raw Excel row
     * @return array Remapped row
     */
    public function applyTo(array $row): array;
    
    /**
     * Validate template mappings
     */
    public function validateMappings(): bool;
}
```

### 4. Enhanced DataMappingService

**Additions**:
```php
class DataMappingService
{
    /**
     * Apply a saved template to remap columns before processing
     * 
     * @param array $row Raw Excel row
     * @param ColumnMappingTemplate|null $template
     * @return array Remapped row
     */
    public function applyTemplate(array $row, ?ColumnMappingTemplate $template): array;
    
    /**
     * Generate template from current mapping
     * 
     * @param array $sampleRow First row of upload
     * @return array Template-ready mappings
     */
    public function generateTemplateFromMapping(array $sampleRow): array;
}
```

**Template Application Logic**:
1. If no template provided, return row unchanged
2. Create new row array
3. For each mapping in template:
   - If Excel column exists in row: map to system field
   - If Excel column doesn't exist: skip mapping
4. Return remapped row
5. Continue with normal mapUploadedData() processing

### 5. Enhanced DataMatchService

**Modifications**:
```php
class DataMatchService
{
    protected ConfidenceScoreService $scoreService;
    
    /**
     * Find match with unified confidence scoring
     * Now returns detailed breakdown
     */
    public function findMatch(array $uploadedData): array;
    
    /**
     * Update matched record with merged dynamic attributes
     * 
     * @param MainSystem $record
     * @param array $dynamicFields
     */
    public function updateMatchedRecord(MainSystem $record, array $dynamicFields): void;
}
```

### 6. TemplateController

**Purpose**: Handle template CRUD operations

**Routes**:
```php
Route::middleware('auth')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);
    Route::put('/templates/{id}', [TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);
});
```

**Controller Methods**:
```php
class TemplateController extends Controller
{
    public function index(): JsonResponse; // List user's templates
    public function store(Request $request): JsonResponse; // Create template
    public function show(int $id): JsonResponse; // Get template details
    public function update(Request $request, int $id): JsonResponse; // Update template
    public function destroy(int $id): JsonResponse; // Delete template
}
```

## Data Models

### ColumnMappingTemplate

```php
{
    "id": 1,
    "user_id": 42,
    "name": "HR Department Import",
    "mappings": {
        "Employee No": "uid",
        "Surname": "last_name",
        "Given Name": "first_name",
        "Department": "dept",
        "Office": "office_location"
    },
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

### Enhanced MatchResult

Add field-level breakdown storage:

```php
Schema::table('match_results', function (Blueprint $table) {
    $table->json('field_breakdown')->nullable(); // Detailed field comparison
});
```

**Field Breakdown Structure**:
```json
{
    "total_fields": 6,
    "matched_fields": 5,
    "core_fields": {
        "last_name": {"status": "match", "uploaded": "Cruz", "existing": "Cruz"},
        "first_name": {"status": "match", "uploaded": "Juan", "existing": "Juan"},
        "birthday": {"status": "mismatch", "uploaded": "1990-05-15", "existing": "1990-05-14"}
    },
    "dynamic_fields": {
        "province": {"status": "match", "uploaded": "Cavite", "existing": "Cavite"},
        "dept": {"status": "mismatch", "uploaded": "IT", "existing": "HR"},
        "position": {"status": "new", "uploaded": "Developer", "existing": null}
    }
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Now I need to perform prework analysis on the acceptance criteria:



### Property 1: Dynamic Attribute Merge Preserves and Overwrites Correctly

*For any* existing record with dynamic attributes and any new upload with dynamic attributes, merging them should preserve all existing attributes not in the upload, overwrite conflicting attributes with new values, and persist the complete merged result to the database such that retrieving the record returns the merged attributes.

**Validates: Requirements 1.1, 1.2, 1.3, 1.4**

### Property 2: Template Mappings Round Trip

*For any* valid column mapping template, serializing the mappings to JSON and deserializing them back should produce an equivalent mapping structure.

**Validates: Requirements 2.2**

### Property 3: Invalid JSON Rejected

*For any* string that is not valid JSON, attempting to store it as template mappings should be rejected with an appropriate error.

**Validates: Requirements 2.3**

### Property 4: Template User Association

*For any* template creation by an authenticated user, the stored template should have a user_id matching the authenticated user's ID.

**Validates: Requirements 2.4**

### Property 5: Unique Template Names Per User

*For any* user, attempting to create two templates with the same name should succeed for the first and fail for the second with a uniqueness constraint error.

**Validates: Requirements 2.5**

### Property 6: User Template Isolation

*For any* user querying their templates, the returned list should contain only templates where user_id matches their ID and should not contain templates belonging to other users.

**Validates: Requirements 3.3**

### Property 7: Template Application Remaps Correctly

*For any* template and any upload data, applying the template should remap all columns that exist in both the template and the upload, ignore template mappings for columns not in the upload, and leave unmapped columns unchanged.

**Validates: Requirements 3.4, 3.7**

### Property 8: Template CRUD Round Trip

*For any* template, creating it, retrieving it, updating its name and mappings, and retrieving it again should return the updated values; deleting it should make it no longer retrievable.

**Validates: Requirements 3.5, 3.6**

### Property 9: Unified Confidence Score Calculation

*For any* uploaded record and any existing database record, the confidence score should equal (number of matching fields / total number of uploaded fields) × 100, where matching fields are those with identical values in both records, and the total includes all core and dynamic fields from the upload.

**Validates: Requirements 4.1, 4.2, 4.3**

### Property 10: Field Inclusion Logic

*For any* confidence score calculation, fields that exist only in the upload should be counted in the total but not in matches, and fields that exist only in the database should be excluded from both total and match counts.

**Validates: Requirements 4.4, 4.5**

### Property 11: Null and Empty String Equivalence

*For any* field comparison during confidence scoring, if one value is null and the other is an empty string, they should be treated as matching.

**Validates: Requirements 4.6**

### Property 12: Match Breakdown Completeness

*For any* match result, the field breakdown should contain entries for all uploaded fields (both core and dynamic), each entry should have a status (match/mismatch/new), and mismatched or new fields should include both uploaded and existing values.

**Validates: Requirements 5.2, 5.4, 5.5, 5.6**

### Property 13: Backward Compatibility for Core-Only Records

*For any* record containing only core fields (no dynamic attributes), processing it through the enhanced system should produce the same match results and confidence scores as the previous version.

**Validates: Requirements 6.1, 6.3**

### Property 14: API Contract Stability

*For any* existing API endpoint, the response structure should remain unchanged when processing records without dynamic attributes, ensuring all existing integrations continue to function.

**Validates: Requirements 6.4**

## Error Handling

### Merge Operation Errors

**Size Limit Exceeded**:
- **Condition**: Merged attributes exceed 65KB JSON size
- **Response**: Throw `InvalidArgumentException` with size details
- **Recovery**: User must reduce dynamic attribute count or size

**Invalid Attribute Data**:
- **Condition**: Attribute values cannot be JSON-serialized
- **Response**: Log warning, skip problematic attribute
- **Recovery**: Continue with remaining valid attributes

### Template Operation Errors

**Duplicate Template Name**:
- **Condition**: User attempts to create template with existing name
- **Response**: Return validation error with 422 status
- **Recovery**: User must choose different name

**Template Not Found**:
- **Condition**: User attempts to access non-existent or unauthorized template
- **Response**: Return 404 error
- **Recovery**: User must select valid template

**Invalid Template Mappings**:
- **Condition**: Template contains invalid JSON or malformed mappings
- **Response**: Return validation error with details
- **Recovery**: User must correct mapping structure

### Confidence Score Errors

**Missing Required Fields**:
- **Condition**: Uploaded record lacks last_name or first_name
- **Response**: Skip record, log warning
- **Recovery**: Continue processing remaining records

**Data Type Mismatch**:
- **Condition**: Field values have incompatible types for comparison
- **Response**: Treat as non-matching, log warning
- **Recovery**: Continue with remaining field comparisons

## Testing Strategy

### Dual Testing Approach

The system requires both unit tests and property-based tests for comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Specific merge scenarios (empty existing, empty new, conflicts)
- Template CRUD operations with known data
- API endpoint response formats
- Error conditions and boundary cases

**Property-Based Tests**: Verify universal properties across all inputs
- Merge operations with randomized attribute sets
- Confidence calculations with varied field combinations
- Template applications with random mappings and data
- Field comparison logic with diverse value types

### Property-Based Testing Configuration

**Framework**: Use Pest PHP with property-based testing plugin or implement custom generators

**Minimum Iterations**: 100 runs per property test

**Test Tagging**: Each property test must reference its design property
```php
// Feature: dynamic-attributes-enhancement, Property 1: Dynamic Attribute Merge Preserves and Overwrites Correctly
test('merge preserves existing and overwrites conflicts', function () {
    // Property-based test implementation
})->repeat(100);
```

### Test Coverage Requirements

**Critical Paths** (100% coverage required):
- `DynamicAttributeMergeService::merge()`
- `ConfidenceScoreService::calculateUnifiedScore()`
- `ColumnMappingTemplate::applyTo()`
- Template CRUD operations

**Integration Tests**:
- End-to-end upload with template application
- Match finding with unified scoring
- Dynamic attribute persistence for matched records
- Backward compatibility with existing workflows

### Backward Compatibility Testing

**Regression Suite**: All 1,691 existing tests must pass

**Compatibility Scenarios**:
- Records with no dynamic attributes
- Uploads without templates
- Existing API consumers
- Legacy confidence score calculations (for core-only records)

## Implementation Notes

### Performance Considerations

**Database Queries**:
- Template loading: Single query per user session (cache in session)
- Attribute merging: In-memory operation, no additional queries
- Confidence calculation: In-memory comparison, no additional queries

**JSON Operations**:
- Use native PHP `json_encode`/`json_decode` for performance
- Validate size before database write to prevent errors
- Consider indexing on `user_id` for template queries

### Migration Strategy

**Phase 1**: Add column_mapping_templates table
```php
Schema::create('column_mapping_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->json('mappings');
    $table->timestamps();
    $table->unique(['user_id', 'name']);
    $table->index('user_id');
});
```

**Phase 2**: Add field_breakdown to match_results
```php
Schema::table('match_results', function (Blueprint $table) {
    $table->json('field_breakdown')->nullable();
});
```

**Phase 3**: Deploy service layer changes (backward compatible)

**Phase 4**: Deploy UI enhancements

### Security Considerations

**Template Access Control**:
- Enforce user_id matching on all template operations
- Use Laravel policy for authorization
- Prevent template sharing between users (future feature)

**JSON Injection Prevention**:
- Validate JSON structure before storage
- Sanitize template names (prevent XSS)
- Limit template count per user (prevent abuse)

**Data Privacy**:
- Dynamic attributes may contain sensitive data
- Ensure proper access controls on MainSystem records
- Log template operations for audit trail

### UI/UX Enhancements

**Upload Page**:
- Add template selector dropdown
- "Save as template" button after column mapping
- Template management link

**Results Page**:
- Enhanced match breakdown display
- Color-coded field comparison table
- Expandable sections for core vs dynamic fields
- Unified confidence score prominently displayed

**Template Management Page**:
- List of user's templates
- Edit/Delete actions
- Template preview showing mappings
- Create new template form
