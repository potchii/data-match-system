# Design Document: File Storage and Deduplication

## Overview

This design implements persistent file storage for uploaded Excel/CSV files with SHA-256 hash-based duplicate detection. The solution stores files in a year/month directory structure, tracks file metadata in the database, and provides a user-friendly duplicate warning system.

### Design Principles

- **Persistent Storage**: All uploaded files are permanently stored for audit and re-processing
- **Duplicate Prevention**: Hash-based detection prevents redundant processing
- **User Control**: Users can override duplicate warnings if needed
- **Storage Efficiency**: Duplicate files reference the same stored file
- **Backward Compatible**: Existing batches without stored files continue to work

## Architecture

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     User Uploads File                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Calculate SHA-256 Hash                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check for Duplicate Hash                        │
└────────────┬───────────────────────────┬────────────────────┘
             │                           │
        Duplicate                   Not Duplicate
             │                           │
             ▼                           ▼
┌──────────────────────────┐  ┌──────────────────────────────┐
│  Show Duplicate Warning  │  │  Store File to Disk          │
│  - Original upload info  │  │  - Year/month directory      │
│  - Process Anyway button │  │  - Unique filename           │
│  - Cancel button         │  │  - Calculate file size       │
└──────────┬───────────────┘  └────────────┬─────────────────┘
           │                                │
           │ Process Anyway                 │
           │                                │
           └────────────┬───────────────────┘
                        │
                        ▼
           ┌──────────────────────────────┐
           │  Create UploadBatch Record   │
           │  - file_hash                 │
           │  - stored_file_path          │
           │  - file_size                 │
           └────────────┬─────────────────┘
                        │
                        ▼
           ┌──────────────────────────────┐
           │  Process Import (Existing)   │
           └──────────────────────────────┘
```

## Components and Interfaces

### 1. Database Migration

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_file_storage_to_upload_batches.php`


```php
public function up(): void
{
    Schema::table('upload_batches', function (Blueprint $table) {
        $table->string('file_hash', 64)->nullable()->after('file_name');
        $table->string('stored_file_path', 500)->nullable()->after('file_hash');
        $table->bigInteger('file_size')->nullable()->after('stored_file_path');
        
        // Index for fast duplicate lookups
        $table->index('file_hash');
    });
}

public function down(): void
{
    Schema::table('upload_batches', function (Blueprint $table) {
        $table->dropIndex(['file_hash']);
        $table->dropColumn(['file_hash', 'stored_file_path', 'file_size']);
    });
}
```

### 2. FileStorageService

**File**: `app/Services/FileStorageService.php`

**Purpose**: Handle all file storage operations including hash calculation, duplicate detection, and file management.

```php
class FileStorageService
{
    /**
     * Store uploaded file and return metadata
     * 
     * @param UploadedFile $file
     * @return array ['hash' => string, 'path' => string, 'size' => int]
     */
    public function storeUploadedFile(UploadedFile $file): array
    {
        // Calculate hash
        $hash = $this->calculateFileHash($file);
        
        // Generate storage path: uploads/2026/02/20260224_abc123_filename.xlsx
        $storagePath = $this->generateStoragePath($file, $hash);
        
        // Store file
        $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            'local'
        );
        
        return [
            'hash' => $hash,
            'path' => $storagePath,
            'size' => $file->getSize(),
        ];
    }
    
    /**
     * Calculate SHA-256 hash of file contents
     */
    public function calculateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }
    
    /**
     * Check if file hash already exists
     * 
     * @return UploadBatch|null
     */
    public function findDuplicateByHash(string $hash): ?UploadBatch
    {
        return UploadBatch::where('file_hash', $hash)->first();
    }
    
    /**
     * Generate storage path with year/month structure
     */
    protected function generateStoragePath(UploadedFile $file, string $hash): string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();
        $shortHash = substr($hash, 0, 8);
        $originalName = $file->getClientOriginalName();
        
        return "uploads/{$year}/{$month}/{$timestamp}_{$shortHash}_{$originalName}";
    }
    
    /**
     * Get full filesystem path for stored file
     */
    public function getFullPath(string $storagePath): string
    {
        return storage_path('app/' . $storagePath);
    }
    
    /**
     * Check if stored file exists
     */
    public function fileExists(string $storagePath): bool
    {
        return Storage::disk('local')->exists($storagePath);
    }
    
    /**
     * Delete stored file
     */
    public function deleteFile(string $storagePath): bool
    {
        return Storage::disk('local')->delete($storagePath);
    }
}
```

### 3. UploadBatch Model Enhancement

**File**: `app/Models/UploadBatch.php`

```php
class UploadBatch extends Model
{
    protected $fillable = [
        'file_name',
        'file_hash',           // NEW
        'stored_file_path',    // NEW
        'file_size',           // NEW
        'uploaded_by',
        'uploaded_at',
        'status',
    ];
    
    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Check if this batch has a stored file
     */
    public function hasStoredFile(): bool
    {
        return !empty($this->stored_file_path);
    }
    
    /**
     * Check if stored file exists on disk
     */
    public function storedFileExists(): bool
    {
        if (!$this->hasStoredFile()) {
            return false;
        }
        
        return Storage::disk('local')->exists($this->stored_file_path);
    }
}
```

### 4. UploadController Enhancement

**File**: `app/Http/Controllers/UploadController.php`


```php
class UploadController extends Controller
{
    protected FileStorageService $storageService;
    
    public function __construct(FileStorageService $storageService)
    {
        $this->storageService = $storageService;
    }
    
    /**
     * Process the uploaded file
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'template_id' => 'nullable|integer|exists:column_mapping_templates,id',
            'force_process' => 'nullable|boolean', // For duplicate override
        ]);

        try {
            $file = $request->file('file');
            
            // Calculate file hash
            $hash = $this->storageService->calculateFileHash($file);
            
            // Check for duplicate
            $duplicate = $this->storageService->findDuplicateByHash($hash);
            
            if ($duplicate && !$request->boolean('force_process')) {
                // Return duplicate warning
                if ($request->expectsJson()) {
                    return response()->json([
                        'duplicate' => true,
                        'original_batch' => [
                            'id' => $duplicate->id,
                            'file_name' => $duplicate->file_name,
                            'uploaded_by' => $duplicate->uploaded_by,
                            'uploaded_at' => $duplicate->uploaded_at->format('Y-m-d H:i:s'),
                        ],
                    ], 409);
                }
                
                return redirect()->route('upload.index')
                    ->with('duplicate', $duplicate)
                    ->with('uploaded_file_hash', $hash)
                    ->withInput();
            }
            
            // Store file (or reuse existing if duplicate with force_process)
            if ($duplicate && $request->boolean('force_process')) {
                // Reuse existing stored file
                $fileMetadata = [
                    'hash' => $duplicate->file_hash,
                    'path' => $duplicate->stored_file_path,
                    'size' => $duplicate->file_size,
                ];
            } else {
                // Store new file
                $fileMetadata = $this->storageService->storeUploadedFile($file);
            }
            
            // Validate file structure
            $validator = new FileValidationService();
            $validation = $validator->validateUploadedFile($file);
            
            if (!$validation['valid']) {
                // Clean up stored file if validation fails
                if (!$duplicate) {
                    $this->storageService->deleteFile($fileMetadata['path']);
                }
                
                return redirect()->route('upload.index')
                    ->with('error', implode("\n", $validation['errors']));
            }
            
            // Load template if provided
            $template = null;
            if ($request->has('template_id')) {
                $template = ColumnMappingTemplate::where('id', $request->template_id)
                    ->where('user_id', auth()->id())
                    ->first();
            }

            // Create batch record
            $batch = UploadBatch::create([
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileMetadata['hash'],
                'stored_file_path' => $fileMetadata['path'],
                'file_size' => $fileMetadata['size'],
                'uploaded_by' => auth()->user()->name,
                'uploaded_at' => now(),
                'status' => 'PROCESSING',
            ]);

            // Import and process
            $import = new RecordImport($batch->id, $template);
            Excel::import($import, $this->storageService->getFullPath($fileMetadata['path']));

            $batch->update(['status' => 'COMPLETED']);

            return redirect()->route('results.index', ['batch_id' => $batch->id])
                ->with('success', "Batch #{$batch->id} processed successfully.");

        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update(['status' => 'FAILED']);
            }
            
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            return redirect()->route('upload.index')
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
    
    /**
     * Download stored file
     */
    public function download(int $batchId)
    {
        $batch = UploadBatch::findOrFail($batchId);
        
        // Verify user has access (optional: add authorization logic)
        
        if (!$batch->hasStoredFile()) {
            abort(404, 'File not found. This batch does not have a stored file.');
        }
        
        if (!$batch->storedFileExists()) {
            abort(404, 'File not found on disk. It may have been deleted.');
        }
        
        return Storage::disk('local')->download(
            $batch->stored_file_path,
            $batch->file_name
        );
    }
}
```

### 5. Artisan Commands

**File**: `app/Console/Commands/ListStoredFiles.php`

```php
class ListStoredFiles extends Command
{
    protected $signature = 'files:list {--older-than=}';
    protected $description = 'List all stored upload files';

    public function handle()
    {
        $query = UploadBatch::whereNotNull('stored_file_path');
        
        if ($this->option('older-than')) {
            $date = Carbon::parse($this->option('older-than'));
            $query->where('uploaded_at', '<', $date);
        }
        
        $batches = $query->orderBy('uploaded_at', 'desc')->get();
        
        $this->table(
            ['ID', 'File Name', 'Size', 'Hash', 'Uploaded', 'Exists'],
            $batches->map(fn($b) => [
                $b->id,
                $b->file_name,
                $b->file_size_human,
                substr($b->file_hash, 0, 16) . '...',
                $b->uploaded_at->format('Y-m-d H:i'),
                $b->storedFileExists() ? '✓' : '✗',
            ])
        );
        
        $totalSize = $batches->sum('file_size');
        $this->info("\nTotal files: " . $batches->count());
        $this->info("Total size: " . $this->formatBytes($totalSize));
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
}
```

**File**: `app/Console/Commands/DeleteOldFiles.php`

```php
class DeleteOldFiles extends Command
{
    protected $signature = 'files:delete-old {--older-than=} {--dry-run}';
    protected $description = 'Delete stored files older than specified date';

    public function handle(FileStorageService $storageService)
    {
        if (!$this->option('older-than')) {
            $this->error('Please specify --older-than option (e.g., --older-than="30 days ago")');
            return 1;
        }
        
        $date = Carbon::parse($this->option('older-than'));
        $batches = UploadBatch::whereNotNull('stored_file_path')
            ->where('uploaded_at', '<', $date)
            ->get();
        
        if ($batches->isEmpty()) {
            $this->info('No files found older than ' . $date->format('Y-m-d'));
            return 0;
        }
        
        $this->info("Found {$batches->count()} files to delete");
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No files will be deleted');
            $this->table(
                ['ID', 'File Name', 'Uploaded'],
                $batches->map(fn($b) => [$b->id, $b->file_name, $b->uploaded_at])
            );
            return 0;
        }
        
        if (!$this->confirm('Are you sure you want to delete these files?')) {
            return 0;
        }
        
        $deleted = 0;
        foreach ($batches as $batch) {
            if ($storageService->deleteFile($batch->stored_file_path)) {
                $batch->update(['stored_file_path' => null]);
                $deleted++;
            }
        }
        
        $this->info("Deleted {$deleted} files");
        return 0;
    }
}
```

### 6. View Enhancements

**File**: `resources/views/pages/upload.blade.php`

Add duplicate warning modal:

```blade
@if(session('duplicate'))
<div class="modal fade show" id="duplicateModal" style="display: block;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Duplicate File Detected</h5>
            </div>
            <div class="modal-body">
                <p>This file has already been uploaded:</p>
                <ul>
                    <li><strong>Batch ID:</strong> {{ session('duplicate')->id }}</li>
                    <li><strong>File Name:</strong> {{ session('duplicate')->file_name }}</li>
                    <li><strong>Uploaded By:</strong> {{ session('duplicate')->uploaded_by }}</li>
                    <li><strong>Upload Date:</strong> {{ session('duplicate')->uploaded_at->format('Y-m-d H:i:s') }}</li>
                </ul>
                <p>Do you want to process this file again?</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ route('upload.store') }}">
                    @csrf
                    <input type="hidden" name="force_process" value="1">
                    <input type="hidden" name="file_hash" value="{{ session('uploaded_file_hash') }}">
                    <button type="button" class="btn btn-secondary" onclick="window.location='{{ route('upload.index') }}'">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Anyway</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
```

**File**: `resources/views/pages/results.blade.php`

Add download link:

```blade
@if($batch->hasStoredFile())
<div class="mb-3">
    <a href="{{ route('upload.download', $batch->id) }}" class="btn btn-sm btn-primary">
        <i class="fas fa-download"></i> Download Original File
    </a>
    <span class="text-muted">{{ $batch->file_size_human }}</span>
</div>
@endif
```

### 7. Routes

**File**: `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    // Existing routes...
    
    // File download
    Route::get('/upload/download/{batch}', [UploadController::class, 'download'])
        ->name('upload.download');
});
```

## Data Flow

### Upload Flow with Duplicate Detection

1. User uploads file
2. System calculates SHA-256 hash
3. System queries database for existing hash
4. **If duplicate found:**
   - Display warning modal with original upload info
   - User chooses "Cancel" or "Process Anyway"
   - If "Process Anyway": reuse stored file, create new batch
5. **If not duplicate:**
   - Store file to `storage/app/uploads/{year}/{month}/`
   - Create batch record with file metadata
6. Process import as normal

### Storage Structure

```
storage/app/uploads/
├── 2026/
│   ├── 01/
│   │   ├── 1706097234_a1b2c3d4_employees.xlsx
│   │   └── 1706123456_e5f6g7h8_departments.csv
│   ├── 02/
│   │   ├── 1708012345_i9j0k1l2_payroll.xlsx
│   │   └── 1708098765_m3n4o5p6_benefits.xlsx
```

## Error Handling

### Storage Errors

```php
try {
    $fileMetadata = $this->storageService->storeUploadedFile($file);
} catch (\Exception $e) {
    Log::error('File storage failed', [
        'file' => $file->getClientOriginalName(),
        'error' => $e->getMessage(),
    ]);
    
    return redirect()->back()
        ->with('error', 'Failed to store file: ' . $e->getMessage());
}
```

### Hash Calculation Errors

```php
public function calculateFileHash(UploadedFile $file): string
{
    try {
        $hash = hash_file('sha256', $file->getRealPath());
        
        if ($hash === false) {
            throw new \RuntimeException('Failed to calculate file hash');
        }
        
        return $hash;
    } catch (\Exception $e) {
        Log::error('Hash calculation failed', [
            'file' => $file->getClientOriginalName(),
            'error' => $e->getMessage(),
        ]);
        
        throw new \RuntimeException('Unable to calculate file hash: ' . $e->getMessage());
    }
}
```

## Security Considerations

1. **Path Traversal Prevention**: Validate all file paths
2. **Access Control**: Verify user permissions before file download
3. **File Type Validation**: Maintain existing MIME type checks
4. **Storage Permissions**: Set appropriate file permissions (0644)
5. **Hash Verification**: Use cryptographic hash (SHA-256) for integrity

## Performance Considerations

1. **Hash Calculation**: Uses streaming for large files
2. **Duplicate Lookup**: Indexed query on `file_hash` column
3. **Storage I/O**: Asynchronous file operations where possible
4. **Directory Structure**: Year/month partitioning prevents large directory issues

## Backward Compatibility

- Existing batches without `file_hash`, `stored_file_path`, `file_size` continue to work
- Download link only shown for batches with stored files
- All existing upload functionality preserved
- Migration adds nullable columns with default NULL
