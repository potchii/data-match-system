# Design Document: Dynamic Schema Support

## Overview

This design implements a hybrid-JSON architecture for the MainSystem model that preserves high-performance fuzzy matching while enabling flexible data ingestion. The solution maintains all existing indexed columns for the matching engine while capturing additional user-provided data in a JSON column.

The key insight is that fuzzy matching requires only a small set of normalized fields (names, birthday, address components), while users may upload datasets with dozens of additional columns (employee IDs, department codes, salary grades, etc.). By separating these concerns, we achieve both performance and flexibility.

### Design Principles

- **Separation of Concerns**: Core matching fields remain as indexed columns; supplementary data goes to JSON
- **Backward Compatibility**: Existing code continues to work without modification
- **Zero Performance Impact**: Matching queries use the same indexes and logic
- **Transparent Access**: Dynamic attributes accessible via familiar Laravel syntax
- **Data Preservation**: No user data is lost during import

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Excel/CSV Upload                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              RecordImport (Enhanced)                         │
│  - Reads all columns from uploaded file                      │
│  - Delegates to DataMappingService                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│         DataMappingService (Enhanced)                        │
│  - Maps known columns → core_fields                          │
│  - Maps unknown columns → dynamic_fields                     │
│  - Returns: { core_fields: {...}, dynamic_fields: {...} }   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│            DataMatchService (Unchanged)                      │
│  - Receives core_fields for matching                         │
│  - Performs fuzzy matching using indexed columns             │
│  - Passes dynamic_fields through to model creation           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              MainSystem Model (Enhanced)                     │
│  ┌──────────────────────┐  ┌──────────────────────────┐    │
│  │   Core Columns       │  │  additional_attributes   │    │
│  │  (Indexed)           │  │  (JSON Column)           │    │
│  │                      │  │                          │    │
│  │  - last_name         │  │  {                       │    │
│  │  - first_name        │  │    "employee_id": "123", │    │
│  │  - birthday          │  │    "department": "IT",   │    │
│  │  - *_normalized      │  │    "salary_grade": "5"   │    │
│  │  - address fields    │  │  }                       │    │
│  └──────────────────────┘  └──────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Upload Phase**: User uploads Excel/CSV with mixed columns (core + extra)
2. **Mapping Phase**: DataMappingService separates columns into core_fields and dynamic_fields
3. **Matching Phase**: DataMatchService uses only core_fields for fuzzy matching
4. **Storage Phase**: MainSystem stores core_fields in columns, dynamic_fields in JSON
5. **Retrieval Phase**: Application accesses both types transparently

## Components and Interfaces

### 1. Database Migration

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_additional_attributes_to_main_system.php`

```php
public function up(): void
{
    Schema::table('main_system', function (Blueprint $table) {
        // Add JSON column after the last core field
        $table->json('additional_attributes')->nullable()->after('barangay');
    });
}

public function down(): void
{
    Schema::table('main_system', function (Blueprint $table) {
        $table->dropColumn('additional_attributes');
    });
}
```

**Design Notes**:
- Uses `json()` type which Laravel translates to appropriate DB type (JSON for MySQL, JSONB for PostgreSQL, TEXT for SQLite)
- Nullable to support existing records and records with no dynamic data
- Positioned after core fields for logical organization
- No indexes added (JSON queries are rare; core field queries dominate)

### 2. MainSystem Model Enhancement

**File**: `app/Models/MainSystem.php`

**Changes**:

```php
class MainSystem extends Model
{
    protected $table = 'main_system';

    protected $fillable = [
        'uid',
        'origin_batch_id',
        'origin_match_result_id',
        'last_name',
        'first_name',
        'middle_name',
        'last_name_normalized',
        'first_name_normalized',
        'middle_name_normalized',
        'suffix',
        'birthday',
        'gender',
        'civil_status',
        'street_no',
        'street',
        'city',
        'province',
        'barangay',
        'additional_attributes',  // NEW
    ];

    protected $casts = [
        'birthday' => 'date',
        'additional_attributes' => 'array',  // NEW: Auto JSON encode/decode
    ];

    /**
     * Get all dynamic attribute keys
     */
    public function getDynamicAttributeKeys(): array
    {
        return array_keys($this->additional_attributes ?? []);
    }

    /**
     * Check if a dynamic attribute exists
     */
    public function hasDynamicAttribute(string $key): bool
    {
        return isset($this->additional_attributes[$key]);
    }

    /**
     * Get a dynamic attribute value with default
     */
    public function getDynamicAttribute(string $key, $default = null)
    {
        return $this->additional_attributes[$key] ?? $default;
    }

    /**
     * Set a dynamic attribute value
     */
    public function setDynamicAttribute(string $key, $value): void
    {
        $attributes = $this->additional_attributes ?? [];
        $attributes[$key] = $value;
        $this->additional_attributes = $attributes;
    }

    // Existing relationships remain unchanged
    public function originBatch()
    {
        return $this->belongsTo(UploadBatch::class, 'origin_batch_id');
    }
}
```

**Design Notes**:
- `additional_attributes` cast to `array` enables automatic JSON encoding/decoding
- Helper methods provide clean API for dynamic attribute access
- Array access syntax works automatically: `$record->additional_attributes['key']`
- Null coalescing prevents errors when additional_attributes is null

### 3. DataMappingService Enhancement

**File**: `app/Services/DataMappingService.php`

**Changes**:

```php
class DataMappingService
{
    /**
     * Core field mappings (column name variations → system field)
     */
    protected const CORE_FIELD_MAPPINGS = [
        'regsno' => 'uid',
        'RegsNo' => 'uid',
        'regsnumber' => 'uid',
        'registration_no' => 'uid',
        'surname' => 'last_name',
        'Surname' => 'last_name',
        'lastname' => 'last_name',
        'LastName' => 'last_name',
        'last_name' => 'last_name',
        'firstname' => 'first_name',
        'FirstName' => 'first_name',
        'first_name' => 'first_name',
        'fname' => 'first_name',
        'secondname' => 'second_name',
        'SecondName' => 'second_name',
        'second_name' => 'second_name',
        'middlename' => 'middle_name',
        'MiddleName' => 'middle_name',
        'middle_name' => 'middle_name',
        'mname' => 'middle_name',
        'extension' => 'suffix',
        'Extension' => 'suffix',
        'suffix' => 'suffix',
        'Suffix' => 'suffix',
        'ext' => 'suffix',
        'dob' => 'birthday',
        'DOB' => 'birthday',
        'birthday' => 'birthday',
        'Birthday' => 'birthday',
        'birthdate' => 'birthday',
        'BirthDate' => 'birthday',
        'birth_date' => 'birthday',
        'date_of_birth' => 'birthday',
        'DateOfBirth' => 'birthday',
        'dateofbirth' => 'birthday',
        'sex' => 'gender',
        'Sex' => 'gender',
        'gender' => 'gender',
        'Gender' => 'gender',
        'status' => 'civil_status',
        'Status' => 'civil_status',
        'civilstatus' => 'civil_status',
        'CivilStatus' => 'civil_status',
        'civil_status' => 'civil_status',
        'address' => 'street',
        'Address' => 'street',
        'street' => 'street',
        'Street' => 'street',
        'brgydescription' => 'barangay',
        'BrgyDescription' => 'barangay',
        'city' => 'city',
        'City' => 'city',
        'barangay' => 'barangay',
        'Barangay' => 'barangay',
    ];

    /**
     * Map uploaded Excel columns to system format
     * Returns: ['core_fields' => [...], 'dynamic_fields' => [...]]
     */
    public function mapUploadedData(array $row): array
    {
        $coreFields = [];
        $dynamicFields = [];
        $processedKeys = [];

        // Process compound first name (Philippine naming convention)
        $firstName = $this->buildCompoundFirstName($row);
        $middleName = $this->extractMiddleName($row);

        if ($firstName !== null) {
            $coreFields['first_name'] = $firstName;
        }
        if ($middleName !== null) {
            $coreFields['middle_name'] = $middleName;
        }

        // Mark compound name fields as processed
        $processedKeys = array_merge($processedKeys, [
            'firstname', 'FirstName', 'first_name', 'fname',
            'secondname', 'SecondName', 'second_name',
            'middlename', 'MiddleName', 'middle_name', 'mname'
        ]);

        // Map all other fields
        foreach ($row as $key => $value) {
            // Skip already processed compound name fields
            if (in_array($key, $processedKeys)) {
                continue;
            }

            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Check if this is a known core field
            if (isset(self::CORE_FIELD_MAPPINGS[$key])) {
                $systemField = self::CORE_FIELD_MAPPINGS[$key];
                
                // Apply normalization based on field type
                $coreFields[$systemField] = $this->normalizeFieldValue($systemField, $value);
            } else {
                // Unknown field → dynamic attribute
                $normalizedKey = $this->normalizeDynamicKey($key);
                $dynamicFields[$normalizedKey] = $this->sanitizeDynamicValue($value);
            }
        }

        // Validate JSON size
        if (!empty($dynamicFields)) {
            $this->validateJsonSize($dynamicFields);
        }

        return [
            'core_fields' => $coreFields,
            'dynamic_fields' => $dynamicFields,
        ];
    }

    /**
     * Normalize field value based on field type
     */
    protected function normalizeFieldValue(string $field, $value)
    {
        return match($field) {
            'birthday' => $this->normalizeDate($value),
            'gender' => $this->normalizeGender($value),
            'uid', 'last_name', 'first_name', 'middle_name', 'suffix',
            'civil_status', 'street', 'city', 'barangay' => $this->normalizeString($value),
            default => $value,
        };
    }

    /**
     * Normalize dynamic attribute key to snake_case
     */
    protected function normalizeDynamicKey(string $key): string
    {
        // Convert to snake_case and sanitize
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
        $normalized = preg_replace('/_+/', '_', $normalized);
        return trim($normalized, '_');
    }

    /**
     * Sanitize dynamic attribute value
     */
    protected function sanitizeDynamicValue($value)
    {
        // Ensure value is JSON-serializable
        if (is_object($value)) {
            return (string) $value;
        }
        
        if (is_array($value)) {
            return array_map([$this, 'sanitizeDynamicValue'], $value);
        }

        return $value;
    }

    /**
     * Validate JSON size doesn't exceed database limits
     */
    protected function validateJsonSize(array $data): void
    {
        $json = json_encode($data);
        $size = strlen($json);
        
        // MySQL TEXT type limit: 65,535 bytes
        if ($size > 65535) {
            throw new \InvalidArgumentException(
                "Dynamic attributes exceed maximum size (65KB). Current size: {$size} bytes"
            );
        }
    }

    // Existing helper methods remain unchanged
    protected function buildCompoundFirstName(array $row): ?string
    {
        $firstName = $this->normalizeString($row['firstname'] ?? $row['FirstName'] ?? $row['first_name'] ?? $row['fname'] ?? null);
        $secondName = $this->normalizeString($row['secondname'] ?? $row['SecondName'] ?? $row['second_name'] ?? null);
        
        if ($firstName && $secondName) {
            return trim($firstName . ' ' . $secondName);
        }
        
        return $firstName;
    }
    
    protected function extractMiddleName(array $row): ?string
    {
        return $this->normalizeString($row['middlename'] ?? $row['MiddleName'] ?? $row['middle_name'] ?? $row['mname'] ?? null);
    }

    protected function normalizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        return ucwords(strtolower(trim($value)));
    }

    protected function normalizeDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }
        $gender = strtoupper(trim($gender));
        
        if (in_array($gender, ['M', 'MALE'])) {
            return 'Male';
        }
        if (in_array($gender, ['F', 'FEMALE'])) {
            return 'Female';
        }
        return $gender;
    }
}
```

**Design Notes**:
- Extracts core field mappings to a constant for maintainability
- Returns structured array with separate core_fields and dynamic_fields
- Normalizes dynamic keys to snake_case for consistency
- Validates JSON size to prevent database errors
- Sanitizes values to ensure JSON serializability
- Maintains backward compatibility by keeping all existing helper methods

### 4. DataMatchService Enhancement

**File**: `app/Services/DataMatchService.php`

**Changes**:

```php
class DataMatchService
{
    // ... existing properties and constructor remain unchanged ...

    /**
     * Find match for single uploaded record
     * Now accepts structured data with core_fields and dynamic_fields
     */
    public function findMatch(array $uploadedData): array
    {
        // Support both old format (flat array) and new format (structured)
        if (isset($uploadedData['core_fields'])) {
            $coreData = $uploadedData['core_fields'];
        } else {
            // Backward compatibility: treat entire array as core data
            $coreData = $uploadedData;
        }

        $normalized = $this->normalizeRecord($coreData);
        $this->refreshCandidates(collect([$normalized]));
        
        return $this->findMatchFromCache($normalized);
    }

    /**
     * Insert new record into main system
     * Now accepts dynamic_fields and stores them in additional_attributes
     */
    public function insertNewRecord(array $data): MainSystem
    {
        // Support both old format and new format
        if (isset($data['core_fields'])) {
            $coreFields = $data['core_fields'];
            $dynamicFields = $data['dynamic_fields'] ?? [];
        } else {
            // Backward compatibility
            $coreFields = $data;
            $dynamicFields = [];
        }

        $coreFields['uid'] = $this->generateUid();
        $coreFields['last_name_normalized'] = $this->normalizeString($coreFields['last_name'] ?? '');
        $coreFields['first_name_normalized'] = $this->normalizeString($coreFields['first_name'] ?? '');
        $coreFields['middle_name_normalized'] = $this->normalizeString($coreFields['middle_name'] ?? '');

        // Add dynamic fields to the data
        if (!empty($dynamicFields)) {
            $coreFields['additional_attributes'] = $dynamicFields;
        }

        $newRecord = MainSystem::create($coreFields);
        
        // Add newly created record to cache
        $this->candidateCache->push($newRecord);
        
        return $newRecord;
    }

    // All other methods remain completely unchanged
    // - batchFindMatches()
    // - findMatchFromCache()
    // - loadCandidates()
    // - refreshCandidates()
    // - normalizeRecord()
    // - normalizeString()
    // - extractAndNormalizeBirthday()
    // - parseDate()
    // - generateUid()
}
```

**Design Notes**:
- Minimal changes to DataMatchService (only 2 methods modified)
- Maintains backward compatibility with flat array format
- Matching logic completely unchanged (uses only core fields)
- Dynamic fields passed through transparently to model creation
- No performance impact on matching operations

### 5. RecordImport Enhancement

**File**: `app/Imports/RecordImport.php`

**Changes**:

```php
class RecordImport implements ToCollection, WithHeadingRow
{
    protected $batchId;
    protected $mappingService;
    protected $matchService;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
        $this->mappingService = new DataMappingService();
        $this->matchService = new DataMatchService();
    }

    /**
     * Process each row from the Excel file
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            
            // Map uploaded data to system format (now returns structured data)
            $mappedData = $this->mappingService->mapUploadedData($rowData);
            
            // Extract core fields for validation
            $coreFields = $mappedData['core_fields'];
            
            // Skip if essential data is missing
            if (empty($coreFields['last_name']) || empty($coreFields['first_name'])) {
                continue;
            }
            
            // Find match in main system (pass structured data)
            $matchResult = $this->matchService->findMatch($mappedData);
            
            // If NEW RECORD, insert into main system
            if ($matchResult['status'] === 'NEW RECORD') {
                $coreFields['origin_batch_id'] = $this->batchId;
                
                // Reconstruct full data for insertion
                $insertData = [
                    'core_fields' => $coreFields,
                    'dynamic_fields' => $mappedData['dynamic_fields'] ?? [],
                ];
                
                $newRecord = $this->matchService->insertNewRecord($insertData);
                $matchResult['matched_id'] = $newRecord->uid;
                
                // Create match result record
                $matchResultRecord = MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $coreFields['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $coreFields['last_name'],
                    'uploaded_first_name' => $coreFields['first_name'],
                    'uploaded_middle_name' => $coreFields['middle_name'] ?? null,
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                ]);
                
                // Update main system record with match result ID
                $newRecord->update(['origin_match_result_id' => $matchResultRecord->id]);
            } else {
                // Create match result record for existing matches
                MatchResult::create([
                    'batch_id' => $this->batchId,
                    'uploaded_record_id' => $coreFields['uid'] ?? 'ROW-' . ($index + 1),
                    'uploaded_last_name' => $coreFields['last_name'],
                    'uploaded_first_name' => $coreFields['first_name'],
                    'uploaded_middle_name' => $coreFields['middle_name'] ?? null,
                    'match_status' => $matchResult['status'],
                    'confidence_score' => $matchResult['confidence'],
                    'matched_system_id' => $matchResult['matched_id'],
                ]);
            }
        }
    }
}
```

**Design Notes**:
- Adapts to new structured return format from DataMappingService
- Passes both core_fields and dynamic_fields to DataMatchService
- Validation logic updated to check core_fields specifically
- Match result creation uses core_fields for required fields
- Clean separation between core and dynamic data throughout

## Data Models

### MainSystem Table Schema

```sql
CREATE TABLE main_system (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(255) UNIQUE NOT NULL,
    origin_batch_id BIGINT UNSIGNED NULL,
    origin_match_result_id BIGINT UNSIGNED NULL,
    
    -- Core matching fields (indexed)
    last_name VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    middle_name VARCHAR(255) NULL,
    last_name_normalized VARCHAR(255) NULL,
    first_name_normalized VARCHAR(255) NULL,
    middle_name_normalized VARCHAR(255) NULL,
    suffix VARCHAR(255) NULL,
    birthday DATE NULL,
    gender VARCHAR(255) NOT NULL,
    civil_status VARCHAR(255) NULL,
    
    -- Address fields
    street_no VARCHAR(255) NULL,
    street VARCHAR(255) NULL,
    city VARCHAR(255) NULL,
    province VARCHAR(255) NULL,
    barangay VARCHAR(255) NULL,
    
    -- Dynamic attributes (NEW)
    additional_attributes JSON NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    -- Indexes for matching performance
    INDEX idx_name_birthday (last_name, first_name, birthday),
    INDEX idx_uid (uid),
    INDEX idx_last_name_normalized (last_name_normalized),
    INDEX idx_first_name_normalized (first_name_normalized),
    INDEX idx_middle_name_normalized (middle_name_normalized),
    
    FOREIGN KEY (origin_batch_id) REFERENCES upload_batches(id) ON DELETE SET NULL
);
```

### Example Data

**Record with dynamic attributes**:
```json
{
    "id": 1,
    "uid": "UID-01HQXYZ123",
    "last_name": "Dela Cruz",
    "first_name": "Juan",
    "middle_name": "Santos",
    "last_name_normalized": "dela cruz",
    "first_name_normalized": "juan",
    "middle_name_normalized": "santos",
    "birthday": "1990-05-15",
    "gender": "Male",
    "additional_attributes": {
        "employee_id": "EMP-2024-001",
        "department": "Information Technology",
        "position": "Senior Developer",
        "salary_grade": "SG-15",
        "hire_date": "2020-01-15",
        "contact_number": "+63-912-345-6789"
    }
}
```

**Record without dynamic attributes (backward compatible)**:
```json
{
    "id": 2,
    "uid": "UID-01HQXYZ456",
    "last_name": "Garcia",
    "first_name": "Maria",
    "middle_name": "Lopez",
    "last_name_normalized": "garcia",
    "first_name_normalized": "maria",
    "middle_name_normalized": "lopez",
    "birthday": "1985-12-20",
    "gender": "Female",
    "additional_attributes": null
}
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified several redundant properties:

- **5.1, 5.2, 5.3** all state that matching should only use core fields (not dynamic attributes). These can be combined into a single comprehensive property.
- **3.7 and 7.2** both validate JSON size limits. One property covers both.
- **2.2 and 6.2** both test retrieving dynamic attribute values. The more comprehensive 6.2 subsumes 2.2.
- **2.3** (setting dynamic attributes) is covered by the round-trip property combining set and get operations.

### Core Properties

**Property 1: Dynamic attribute round-trip consistency**

*For any* MainSystem record and any valid key-value pair, setting a dynamic attribute then retrieving it should return the equivalent value.

**Validates: Requirements 2.2, 2.3, 6.2**

**Property 2: Dynamic attribute key enumeration**

*For any* MainSystem record with dynamic attributes, calling getDynamicAttributeKeys() should return exactly the set of keys present in the additional_attributes JSON.

**Validates: Requirements 2.5**

**Property 3: Dynamic attribute existence checking**

*For any* MainSystem record and any key, hasDynamicAttribute(key) should return true if and only if the key exists in additional_attributes.

**Validates: Requirements 2.6, 6.5**

**Property 4: Core field identification**

*For any* uploaded row containing known column name variations (surname, firstname, DOB, etc.), the DataMappingService should correctly classify them as core_fields.

**Validates: Requirements 3.1, 4.2**

**Property 5: Dynamic field identification**

*For any* uploaded row containing unknown column names, the DataMappingService should classify them as dynamic_fields.

**Validates: Requirements 3.2, 4.3**

**Property 6: Core field storage**

*For any* valid core field data, creating a MainSystem record should store each value in its corresponding database column (not in additional_attributes).

**Validates: Requirements 3.3**

**Property 7: Dynamic field storage**

*For any* dynamic field data, creating a MainSystem record should store all key-value pairs in the additional_attributes JSON column.

**Validates: Requirements 3.4, 5.4**

**Property 8: Dynamic key normalization**

*For any* input column name (camelCase, PascalCase, kebab-case, etc.), the normalized dynamic attribute key should be in snake_case format.

**Validates: Requirements 3.5**

**Property 9: Core field priority**

*For any* uploaded row where a column name matches both a core field mapping and could be a dynamic field, the system should map it to core_fields (not dynamic_fields).

**Validates: Requirements 3.6**

**Property 10: JSON size validation**

*For any* dynamic attributes that when JSON-encoded exceed 65,535 bytes, the system should reject the data with a descriptive error message.

**Validates: Requirements 3.7, 7.2**

**Property 11: Structured mapping output**

*For any* uploaded row, the DataMappingService output should contain both 'core_fields' and 'dynamic_fields' keys.

**Validates: Requirements 4.1**

**Property 12: Empty value exclusion**

*For any* uploaded row with null or empty string values, those values should not appear in the dynamic_fields output.

**Validates: Requirements 4.4**

**Property 13: Matching uses only core fields**

*For any* matching operation (findMatch, batchFindMatches), the candidate queries and rule evaluations should reference only core field columns (last_name_normalized, first_name_normalized, birthday), never additional_attributes.

**Validates: Requirements 5.1, 5.2, 5.3**

**Property 14: JSON query support**

*For any* MainSystem record with a dynamic attribute key-value pair, querying using Laravel's JSON syntax (where('additional_attributes->key', 'value')) should return that record.

**Validates: Requirements 6.1**

**Property 15: Object property access**

*For any* MainSystem record with dynamic attributes, accessing them via object property syntax ($record->additional_attributes->key) should return the stored value.

**Validates: Requirements 6.3**

**Property 16: Graceful missing key handling**

*For any* MainSystem record and any non-existent dynamic attribute key, accessing it should return null without throwing an exception.

**Validates: Requirements 6.4**

**Property 17: JSON well-formedness**

*For any* dynamic attributes being stored, the system should validate that they can be JSON-encoded without errors.

**Validates: Requirements 7.1, 7.4**

**Property 18: Key sanitization**

*For any* dynamic attribute key containing special characters or potential injection patterns, the normalized key should contain only alphanumeric characters and underscores.

**Validates: Requirements 7.3**

**Property 19: Non-serializable value handling**

*For any* dynamic attribute value that is not JSON-serializable (objects, resources), the system should convert it to a string representation.

**Validates: Requirements 7.5**

**Property 20: Null additional_attributes support**

*For any* MainSystem record where additional_attributes is null, all dynamic attribute operations (getDynamicAttributeKeys, hasDynamicAttribute, array access) should work without errors.

**Validates: Requirements 9.1, 9.2**

## Error Handling

### Validation Errors

**JSON Size Exceeded**:
```php
throw new \InvalidArgumentException(
    "Dynamic attributes exceed maximum size (65KB). Current size: {$size} bytes"
);
```
- Thrown by: DataMappingService::validateJsonSize()
- When: JSON-encoded dynamic_fields exceeds 65,535 bytes
- Recovery: User must reduce the number of dynamic columns or data size

**JSON Encoding Failed**:
```php
throw new \RuntimeException(
    "Failed to encode dynamic attributes to JSON: {$error}"
);
```
- Thrown by: MainSystem model when saving
- When: Dynamic attributes contain non-serializable data that couldn't be converted
- Recovery: Check data types and ensure all values are serializable

### Database Errors

**Column Type Mismatch**:
- Scenario: Database doesn't support JSON column type
- Mitigation: Laravel automatically uses appropriate type (JSON/JSONB/TEXT)
- Recovery: Ensure database version meets minimum requirements

**Foreign Key Constraint**:
- Scenario: origin_batch_id references non-existent batch
- Mitigation: Validate batch exists before creating record
- Recovery: Create batch first or set origin_batch_id to null

### Import Errors

**Missing Required Fields**:
```php
// Skip row silently if last_name or first_name is missing
if (empty($coreFields['last_name']) || empty($coreFields['first_name'])) {
    continue;
}
```
- Behavior: Row is skipped, import continues
- Logging: Should log skipped rows for user review
- Recovery: User corrects source data and re-imports

**Malformed Date Values**:
- Scenario: Birthday field contains invalid date
- Behavior: normalizeDate() returns null, record created with null birthday
- Impact: Record won't match on birthday-based rules
- Recovery: User corrects date and re-imports

### Query Errors

**Invalid JSON Path**:
```php
// Laravel handles gracefully - returns empty result set
MainSystem::where('additional_attributes->nonexistent', 'value')->get();
// Returns: Collection (empty)
```
- Behavior: No error thrown, empty result set returned
- Mitigation: None needed - this is expected behavior

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests for comprehensive coverage:

- **Unit tests**: Verify specific examples, edge cases, and integration points
- **Property tests**: Verify universal properties across randomized inputs

### Property-Based Testing

We'll use **Pest PHP** with the **pest-plugin-faker** for property-based testing. Each property test will run a minimum of 100 iterations with randomized data.

**Configuration**:
```php
// tests/Pest.php
uses(Tests\TestCase::class)->in('Feature', 'Unit');

// Property test helper
function propertyTest(string $description, callable $test, int $iterations = 100): void
{
    test($description, function () use ($test, $iterations) {
        for ($i = 0; $i < $iterations; $i++) {
            $test($this->faker);
        }
    });
}
```

**Property Test Examples**:

```php
// tests/Unit/MainSystemDynamicAttributesTest.php

propertyTest('Property 1: Dynamic attribute round-trip consistency', function ($faker) {
    // Feature: dynamic-schema-support, Property 1
    $record = MainSystem::factory()->create();
    $key = $faker->word;
    $value = $faker->sentence;
    
    $record->setDynamicAttribute($key, $value);
    $record->save();
    $record->refresh();
    
    expect($record->getDynamicAttribute($key))->toBe($value);
});

propertyTest('Property 8: Dynamic key normalization', function ($faker) {
    // Feature: dynamic-schema-support, Property 8
    $mappingService = new DataMappingService();
    
    // Generate various case formats
    $camelCase = $faker->word . ucfirst($faker->word);
    $PascalCase = ucfirst($faker->word) . ucfirst($faker->word);
    $kebabCase = $faker->word . '-' . $faker->word;
    
    $row = [
        $camelCase => $faker->word,
        $PascalCase => $faker->word,
        $kebabCase => $faker->word,
    ];
    
    $result = $mappingService->mapUploadedData($row);
    
    foreach (array_keys($result['dynamic_fields']) as $key) {
        // All keys should be snake_case (lowercase with underscores)
        expect($key)->toMatch('/^[a-z0-9_]+$/');
        expect($key)->not->toContain('-');
        expect($key)->not->toMatch('/[A-Z]/');
    }
});

propertyTest('Property 10: JSON size validation', function ($faker) {
    // Feature: dynamic-schema-support, Property 10
    $mappingService = new DataMappingService();
    
    // Generate data that exceeds 65KB
    $largeData = [];
    for ($i = 0; $i < 1000; $i++) {
        $largeData["field_$i"] = $faker->paragraph(50); // ~500 bytes each
    }
    
    expect(fn() => $mappingService->mapUploadedData($largeData))
        ->toThrow(\InvalidArgumentException::class, 'exceed maximum size');
});
```

### Unit Testing

Unit tests focus on specific scenarios, edge cases, and integration points:

```php
// tests/Unit/DataMappingServiceTest.php

test('maps known column variations to core fields', function () {
    $service = new DataMappingService();
    
    $row = [
        'surname' => 'Dela Cruz',
        'firstname' => 'Juan',
        'DOB' => '1990-05-15',
        'Sex' => 'M',
    ];
    
    $result = $service->mapUploadedData($row);
    
    expect($result['core_fields'])->toHaveKeys([
        'last_name', 'first_name', 'birthday', 'gender'
    ]);
    expect($result['core_fields']['last_name'])->toBe('Dela Cruz');
    expect($result['core_fields']['gender'])->toBe('Male');
});

test('unknown columns become dynamic fields', function () {
    $service = new DataMappingService();
    
    $row = [
        'surname' => 'Garcia',
        'employee_id' => 'EMP-001',
        'department' => 'IT',
    ];
    
    $result = $service->mapUploadedData($row);
    
    expect($result['core_fields'])->toHaveKey('last_name');
    expect($result['dynamic_fields'])->toHaveKeys(['employee_id', 'department']);
});

test('empty values excluded from dynamic fields', function () {
    $service = new DataMappingService();
    
    $row = [
        'surname' => 'Lopez',
        'empty_field' => '',
        'null_field' => null,
        'valid_field' => 'value',
    ];
    
    $result = $service->mapUploadedData($row);
    
    expect($result['dynamic_fields'])->toHaveKey('valid_field');
    expect($result['dynamic_fields'])->not->toHaveKey('empty_field');
    expect($result['dynamic_fields'])->not->toHaveKey('null_field');
});

// tests/Unit/MainSystemTest.php

test('can store and retrieve dynamic attributes', function () {
    $record = MainSystem::factory()->create([
        'additional_attributes' => [
            'employee_id' => 'EMP-001',
            'department' => 'IT',
        ],
    ]);
    
    expect($record->additional_attributes['employee_id'])->toBe('EMP-001');
    expect($record->additional_attributes['department'])->toBe('IT');
});

test('null additional_attributes returns empty array', function () {
    $record = MainSystem::factory()->create([
        'additional_attributes' => null,
    ]);
    
    expect($record->additional_attributes)->toBeArray();
    expect($record->additional_attributes)->toBeEmpty();
});

test('can query by dynamic attributes using JSON syntax', function () {
    MainSystem::factory()->create([
        'last_name' => 'Test1',
        'additional_attributes' => ['department' => 'IT'],
    ]);
    
    MainSystem::factory()->create([
        'last_name' => 'Test2',
        'additional_attributes' => ['department' => 'HR'],
    ]);
    
    $results = MainSystem::where('additional_attributes->department', 'IT')->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->last_name)->toBe('Test1');
});

// tests/Feature/RecordImportTest.php

test('import preserves dynamic attributes', function () {
    $batch = UploadBatch::factory()->create();
    
    // Create temporary Excel file with extra columns
    $filePath = storage_path('app/test_import.xlsx');
    Excel::store(new class implements FromArray {
        public function array(): array {
            return [
                ['surname', 'firstname', 'DOB', 'employee_id', 'department'],
                ['Dela Cruz', 'Juan', '1990-05-15', 'EMP-001', 'IT'],
            ];
        }
    }, 'test_import.xlsx');
    
    // Import
    Excel::import(new RecordImport($batch->id), $filePath);
    
    // Verify
    $record = MainSystem::where('last_name', 'Dela Cruz')->first();
    expect($record)->not->toBeNull();
    expect($record->additional_attributes)->toHaveKey('employee_id');
    expect($record->additional_attributes['employee_id'])->toBe('EMP-001');
    expect($record->additional_attributes['department'])->toBe('IT');
    
    unlink($filePath);
});

test('matching ignores dynamic attributes', function () {
    // Create existing record with dynamic attributes
    $existing = MainSystem::factory()->create([
        'last_name' => 'Dela Cruz',
        'first_name' => 'Juan',
        'birthday' => '1990-05-15',
        'additional_attributes' => ['department' => 'IT'],
    ]);
    
    // Upload matching record with different dynamic attributes
    $service = new DataMatchService();
    $uploadData = [
        'core_fields' => [
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'birthday' => '1990-05-15',
        ],
        'dynamic_fields' => [
            'department' => 'HR', // Different!
        ],
    ];
    
    $result = $service->findMatch($uploadData);
    
    // Should match despite different dynamic attributes
    expect($result['status'])->not->toBe('NEW RECORD');
    expect($result['matched_id'])->toBe($existing->uid);
});
```

### Test Coverage Goals

- **Minimum 80% overall code coverage**
- **100% coverage for**:
  - DataMappingService::mapUploadedData()
  - MainSystem dynamic attribute methods
  - JSON size validation logic
  - Key normalization logic

### Integration Testing

Integration tests verify the complete flow from upload to storage:

1. Upload Excel with mixed columns (core + dynamic)
2. Verify correct mapping to core_fields and dynamic_fields
3. Verify matching uses only core fields
4. Verify dynamic attributes stored in JSON column
5. Verify dynamic attributes retrievable via queries

### Performance Testing

While not part of correctness properties, we should verify:

- Import time increase < 10% with dynamic attributes
- Query performance unchanged for core field queries
- JSON column queries acceptable for admin/reporting use cases

