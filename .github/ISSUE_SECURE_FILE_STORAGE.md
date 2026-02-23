# Feature: Secure Private File Storage for Data Matching

## Status
**Proposed Enhancement** - Improves security, scalability, and maintainability

## Overview
Implement secure private file storage using Laravel's `local` disk to protect sensitive data matching files from public web access. This approach ensures uploaded CSV/Excel files containing PII are stored in `storage/app` (not web-accessible) and provides a foundation for future cloud migration.

## Current Implementation Issues

### Security Concerns
- Files are currently processed in-memory without persistent storage
- No file path tracking in database
- Limited audit trail for uploaded files

### Scalability Limitations
- No organized file structure for long-term storage
- Missing cleanup mechanism for old files
- No support for reprocessing failed batches

## Proposed Solution

### 1. Configure Private Disk
Laravel's `local` disk already points to `storage/app` (non-web-accessible):

```php
// config/filesystems.php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],
],
```

### 2. Update Database Schema
Add file tracking columns to `upload_batches` table:

```php
Schema::table('upload_batches', function (Blueprint $table) {
    $table->string('file_path')->nullable()->after('file_name');
    $table->string('original_name')->nullable()->after('file_path');
    $table->unsignedBigInteger('file_size')->nullable()->after('original_name');
});
```

### 3. Implement Secure Upload Logic

```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

public function store(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv|max:51200', // 50MB limit
    ]);

    $file = $request->file('file');
    
    // Generate unique filename using ULID (collision-resistant)
    $fileName = Str::ulid() . '.' . $file->getClientOriginalExtension();
    
    // Store in organized structure: uploads/2026/02/01jk4...csv
    $path = $file->storeAs(
        'uploads/' . date('Y/m'), 
        $fileName, 
        'local'
    );

    $batch = UploadBatch::create([
        'file_path' => $path,
        'original_name' => $file->getClientOriginalName(),
        'file_name' => $fileName,
        'file_size' => $file->getSize(),
        'uploaded_by' => auth()->user()->name,
        'uploaded_at' => now(),
        'status' => 'PENDING',
    ]);

    // Process file (existing logic)
    Excel::import(new RecordImport($batch->id), storage_path('app/' . $path));

    return redirect()->route('results.index', ['batch_id' => $batch->id]);
}
```

### 4. Optimize File Processing for Large Files

Use Laravel's `LazyCollection` to stream large files without memory exhaustion:

```php
use Illuminate\Support\LazyCollection;

public function processLargeFile(UploadBatch $batch)
{
    $fullPath = storage_path('app/' . $batch->file_path);
    
    LazyCollection::make(function () use ($fullPath) {
        $handle = fopen($fullPath, 'r');
        $headers = fgetcsv($handle);
        
        while (($line = fgetcsv($handle)) !== false) {
            yield array_combine($headers, $line);
        }
        
        fclose($handle);
    })
    ->chunk(100) // Process 100 rows at a time
    ->each(function ($chunk) use ($batch) {
        foreach ($chunk as $row) {
            // Run matching logic
            $this->dataMatchService->findMatch($row, $batch->id);
        }
    });
}
```

### 5. Implement Automated Cleanup

Add scheduled task to prevent disk space exhaustion:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use App\Models\UploadBatch;

Schedule::call(function () {
    $cutoffDate = now()->subDays(30);
    
    // Find old batches
    $oldBatches = UploadBatch::where('uploaded_at', '<', $cutoffDate)
        ->whereNotNull('file_path')
        ->get();
    
    foreach ($oldBatches as $batch) {
        // Delete physical file
        Storage::disk('local')->delete($batch->file_path);
        
        // Update database record
        $batch->update(['file_path' => null]);
    }
    
    Log::info('Cleaned up ' . $oldBatches->count() . ' old upload files');
})->daily()->at('02:00');
```

## Benefits

### Security
- Files stored in `storage/app` are **not web-accessible**
- Must go through authenticated Laravel routes to access
- Prevents direct URL access to sensitive data

### Organization
- Year/Month folder structure prevents single-folder bottlenecks
- ULID filenames prevent collisions and timing attacks
- Easy to locate files for debugging

### Scalability
- **Cloud-ready**: Change one `.env` line to migrate to S3/Azure
- Lazy loading prevents memory issues with large files
- Automated cleanup prevents disk space issues

### Maintainability
- Full audit trail with `file_path`, `original_name`, `file_size`
- Ability to reprocess failed batches
- Clear separation of concerns

## Implementation Checklist

- [ ] Create migration for `file_path`, `original_name`, `file_size` columns
- [ ] Update `UploadBatch` model fillable fields
- [ ] Modify `UploadController::store()` to save files to disk
- [ ] Update `RecordImport` to read from stored file path
- [ ] Add `LazyCollection` support for large file processing
- [ ] Implement scheduled cleanup task
- [ ] Add file download route for admins (optional)
- [ ] Update tests to mock file storage
- [ ] Document storage configuration in README

## Future Enhancements

- Add file encryption at rest
- Implement file versioning for reprocessing
- Add progress tracking for large file imports
- Support for cloud storage (S3, Azure Blob)
- Implement file retention policies per compliance requirements

## References

- [Laravel File Storage Documentation](https://laravel.com/docs/11.x/filesystem)
- [Laravel Lazy Collections](https://laravel.com/docs/11.x/collections#lazy-collections)
- [Task Scheduling](https://laravel.com/docs/11.x/scheduling)

---

**Priority**: High  
**Effort**: Medium (2-3 hours)  
**Impact**: High (Security + Scalability)
