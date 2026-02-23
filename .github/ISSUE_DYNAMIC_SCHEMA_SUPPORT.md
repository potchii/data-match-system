# 🚀 Feature: Dynamic Schema Support via JSON Metadata

## 📝 Description

Our current `MainSystem` model is limited by a rigid database schema. When users upload datasets with columns not defined in our `fillable` array (e.g., `department`, `employment_status`, `emergency_contact`), that data is currently lost or ignored.

We need to transition to a **Hybrid-JSON architecture**. This allows us to keep "Core" matching fields as standard, indexed columns for performance, while capturing all "Extra" upload data in a flexible JSON blob.

## 🎯 Goals

- [ ] Implement an `additional_attributes` JSON column in the `main_system` table
- [ ] Preserve existing `MainSystem` fixed columns for the fuzzy matching engine
- [ ] Implement logic to automatically detect "extra" columns in an upload and map them to the JSON field
- [ ] Maintain backward compatibility with existing data and matching logic
- [ ] Enable querying and filtering by dynamic attributes

## 🛠 Technical Tasks

### 1. Database Migration

Add a JSON column to the existing table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            // Stores all columns not present in the core schema
            $table->json('additional_attributes')->nullable()->after('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn('additional_attributes');
        });
    }
};
```

### 2. Model Configuration

Update `App\Models\MainSystem` to handle the JSON-to-Array casting.

```php
protected $fillable = [
    // ... existing fields ...
    'additional_attributes',
];

protected $casts = [
    'birthday' => 'date',
    'additional_attributes' => 'array', // Automatically handles json_encode/decode
];
```

### 3. Service Logic Refactor

Update `DataMatchService` to use `array_diff_key` to separate core fields from dynamic ones.

```php
// Example logic for the Service
$allUploadedData = $request->row;
$coreColumns = (new MainSystem)->getFillable();

// Separate core attributes from extra attributes
$attributes = array_intersect_key($allUploadedData, array_flip($coreColumns));
$extra = array_diff_key($allUploadedData, array_flip($coreColumns));

// Store extra attributes in JSON field
if (!empty($extra)) {
    $attributes['additional_attributes'] = $extra;
}

MainSystem::create($attributes);
```

### 4. Update Import Logic

Modify `RecordImport` to handle dynamic columns during Excel/CSV imports.

```php
// In RecordImport::model() method
public function model(array $row)
{
    $mappedData = $this->mappingService->mapRow($row, $this->columnMapping);
    
    $coreFields = array_intersect_key($mappedData, array_flip((new MainSystem)->getFillable()));
    $extraFields = array_diff_key($mappedData, array_flip((new MainSystem)->getFillable()));
    
    if (!empty($extraFields)) {
        $coreFields['additional_attributes'] = $extraFields;
    }
    
    return new MainSystem($coreFields);
}
```

### 5. UI Enhancement

Add display logic in views to show dynamic attributes.

```blade
@if($record->additional_attributes)
    <div class="card mt-3">
        <div class="card-header">
            <h5>Additional Information</h5>
        </div>
        <div class="card-body">
            @foreach($record->additional_attributes as $key => $value)
                <div class="row mb-2">
                    <div class="col-md-4"><strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong></div>
                    <div class="col-md-8">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif
```

## 🧪 Expected Behavior

### Data Retention
If a user uploads a column named `salary_grade`, it should be stored and retrievable via:
```php
$record->additional_attributes['salary_grade']
```

### Queryability
We should be able to filter by dynamic attributes using Laravel's arrow syntax:
```php
MainSystem::where('additional_attributes->department', 'Sales')->get();
MainSystem::whereJsonContains('additional_attributes->skills', 'PHP')->get();
```

### Performance
The core fuzzy matching logic remains high-speed because it still targets the indexed `last_name_normalized` and `first_name_normalized` columns. The JSON field is only used for storage and retrieval of supplementary data.

## 📊 Benefits

1. **Flexibility**: Accept any column structure from user uploads without schema changes
2. **Performance**: Core matching fields remain indexed and optimized
3. **Data Preservation**: No data loss when users upload additional columns
4. **Scalability**: Easy to extend without database migrations
5. **User Experience**: Users can upload their existing datasets without modification

## ⚠️ Considerations

- JSON columns are supported in MySQL 5.7+, PostgreSQL 9.2+, SQLite 3.9+
- Indexing JSON fields requires special syntax (JSON indexes or generated columns)
- Consider adding validation for JSON field size limits
- Document which fields are "core" vs "dynamic" for users

## 🔄 Migration Strategy

1. Run migration to add `additional_attributes` column
2. Existing records will have `null` in this field (backward compatible)
3. New uploads will automatically populate the JSON field
4. Optional: Backfill script if needed to migrate old data

## 📝 Testing Checklist

- [ ] Test upload with only core columns (should work as before)
- [ ] Test upload with extra columns (should store in JSON)
- [ ] Test querying by dynamic attributes
- [ ] Test matching logic still works correctly
- [ ] Test UI displays dynamic attributes properly
- [ ] Test performance with large datasets
- [ ] Test JSON field size limits

## 🏷️ Labels

`enhancement` `database` `matching-engine` `flexibility`

## 👤 Assignees

@me

---

## 💡 Why This Works

By putting this in a GitHub Issue, we're documenting exactly **why** we chose the Hybrid-JSON path. It shows we're prioritizing:

- **Performance** for the matching engine (via fixed, indexed columns)
- **Flexibility** for the user's data (via JSON storage)
- **Maintainability** by avoiding constant schema changes
- **User Experience** by accepting diverse data formats

This approach gives us the best of both worlds: structured data where it matters for performance, and flexibility where it matters for user needs.
