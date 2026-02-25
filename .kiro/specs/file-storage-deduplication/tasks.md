# Implementation Tasks: File Storage and Deduplication

## Phase 1: Database and Core Infrastructure

### Task 1: Create Database Migration
- [x] 1.1 Create migration file `add_file_storage_to_upload_batches`
- [x] 1.2 Add `file_hash` column (VARCHAR 64, nullable)
- [x] 1.3 Add `stored_file_path` column (VARCHAR 500, nullable)
- [x] 1.4 Add `file_size` column (BIGINT, nullable)
- [x] 1.5 Add index on `file_hash` column
- [x] 1.6 Implement down() method for rollback
- [x] 1.7 Run migration and verify schema changes

### Task 2: Create FileStorageService
- [ ] 2.1 Create `app/Services/FileStorageService.php`
- [ ] 2.2 Implement `storeUploadedFile()` method
- [ ] 2.3 Implement `calculateFileHash()` method using SHA-256
- [ ] 2.4 Implement `findDuplicateByHash()` method
- [ ] 2.5 Implement `generateStoragePath()` with year/month structure
- [ ] 2.6 Implement `getFullPath()` helper method
- [ ] 2.7 Implement `fileExists()` method
- [ ] 2.8 Implement `deleteFile()` method
- [ ] 2.9 Add error handling for all file operations

### Task 3: Enhance UploadBatch Model
- [ ] 3.1 Add new columns to `$fillable` array
- [ ] 3.2 Implement `getFileSizeHumanAttribute()` accessor
- [ ] 3.3 Implement `hasStoredFile()` method
- [ ] 3.4 Implement `storedFileExists()` method
- [ ] 3.5 Write unit tests for model methods

## Phase 2: Upload Controller Enhancement

### Task 4: Update UploadController for File Storage
- [ ] 4.1 Inject FileStorageService into constructor
- [ ] 4.2 Calculate file hash before processing
- [ ] 4.3 Check for duplicate hash in database
- [ ] 4.4 Handle duplicate detection (return warning)
- [ ] 4.5 Store file using FileStorageService
- [ ] 4.6 Save file metadata to batch record
- [ ] 4.7 Handle `force_process` parameter for duplicate override
- [ ] 4.8 Reuse existing stored file for forced duplicates
- [ ] 4.9 Clean up stored file on validation failure
- [ ] 4.10 Update Excel import to use stored file path

### Task 5: Implement File Download Route
- [ ] 5.1 Add `download()` method to UploadController
- [ ] 5.2 Verify batch exists and user has access
- [ ] 5.3 Check if batch has stored file
- [ ] 5.4 Verify file exists on disk
- [ ] 5.5 Return file download response with original filename
- [ ] 5.6 Handle 404 errors gracefully
- [ ] 5.7 Add route to `routes/web.php`

## Phase 3: User Interface

### Task 6: Create Duplicate Warning Modal
- [ ] 6.1 Add modal HTML to `resources/views/pages/upload.blade.php`
- [ ] 6.2 Display duplicate batch information (ID, filename, uploader, date)
- [ ] 6.3 Add "Cancel" button to return to upload page
- [ ] 6.4 Add "Process Anyway" button with hidden form
- [ ] 6.5 Include `force_process` hidden input
- [ ] 6.6 Style modal with warning theme
- [ ] 6.7 Add JavaScript to handle modal display

### Task 7: Add Download Link to Results Page
- [ ] 7.1 Update `resources/views/pages/results.blade.php`
- [ ] 7.2 Check if batch has stored file
- [ ] 7.3 Display download button with icon
- [ ] 7.4 Show file size in human-readable format
- [ ] 7.5 Hide download section for batches without stored files
- [ ] 7.6 Add styling for download button

### Task 8: Update Batch List View
- [ ] 8.1 Add file size column to batch list table
- [ ] 8.2 Add download link column
- [ ] 8.3 Add duplicate indicator icon
- [ ] 8.4 Format file size display
- [ ] 8.5 Handle batches without stored files

## Phase 4: Management Commands

### Task 9: Create ListStoredFiles Command
- [ ] 9.1 Create `app/Console/Commands/ListStoredFiles.php`
- [ ] 9.2 Implement command signature with `--older-than` option
- [ ] 9.3 Query batches with stored files
- [ ] 9.4 Filter by date if option provided
- [ ] 9.5 Display table with ID, filename, size, hash, date, exists status
- [ ] 9.6 Calculate and display total file count
- [ ] 9.7 Calculate and display total storage size
- [ ] 9.8 Implement `formatBytes()` helper method

### Task 10: Create DeleteOldFiles Command
- [ ] 10.1 Create `app/Console/Commands/DeleteOldFiles.php`
- [ ] 10.2 Implement command signature with `--older-than` and `--dry-run` options
- [ ] 10.3 Validate `--older-than` option is provided
- [ ] 10.4 Query batches older than specified date
- [ ] 10.5 Display files to be deleted
- [ ] 10.6 Implement dry-run mode (show without deleting)
- [ ] 10.7 Add confirmation prompt
- [ ] 10.8 Delete files and update database
- [ ] 10.9 Display deletion summary

### Task 11: Create VerifyFileIntegrity Command
- [ ] 11.1 Create `app/Console/Commands/VerifyFileIntegrity.php`
- [ ] 11.2 Query all batches with stored files
- [ ] 11.3 Check if each file exists on disk
- [ ] 11.4 Verify file hash matches stored hash
- [ ] 11.5 Report missing files
- [ ] 11.6 Report hash mismatches
- [ ] 11.7 Provide option to fix database records

## Phase 5: Testing

### Task 12: Unit Tests for FileStorageService
- [ ] 12.1 Test `calculateFileHash()` with sample files
- [ ] 12.2 Test `generateStoragePath()` format
- [ ] 12.3 Test `storeUploadedFile()` creates correct directory structure
- [ ] 12.4 Test `findDuplicateByHash()` returns correct batch
- [ ] 12.5 Test `fileExists()` for existing and missing files
- [ ] 12.6 Test `deleteFile()` removes file from disk
- [ ] 12.7 Test error handling for storage failures

### Task 13: Unit Tests for UploadBatch Model
- [ ] 13.1 Test `getFileSizeHumanAttribute()` formatting
- [ ] 13.2 Test `hasStoredFile()` returns correct boolean
- [ ] 13.3 Test `storedFileExists()` checks disk
- [ ] 13.4 Test backward compatibility with null values

### Task 14: Integration Tests for Upload Flow
- [ ] 14.1 Test successful file upload and storage
- [ ] 14.2 Test duplicate detection returns warning
- [ ] 14.3 Test force_process creates new batch with same file
- [ ] 14.4 Test file metadata saved to database
- [ ] 14.5 Test validation failure cleans up stored file
- [ ] 14.6 Test file download returns correct file
- [ ] 14.7 Test download with missing file returns 404

### Task 15: Feature Tests for Management Commands
- [ ] 15.1 Test `files:list` displays correct information
- [ ] 15.2 Test `files:list --older-than` filters correctly
- [ ] 15.3 Test `files:delete-old --dry-run` doesn't delete
- [ ] 15.4 Test `files:delete-old` removes files and updates database
- [ ] 15.5 Test `files:verify` detects missing files

## Phase 6: Documentation and Cleanup

### Task 16: Update Documentation
- [ ] 16.1 Update README with file storage information
- [ ] 16.2 Document management commands usage
- [ ] 16.3 Add storage requirements to deployment guide
- [ ] 16.4 Document duplicate detection behavior
- [ ] 16.5 Add troubleshooting section for file storage issues

### Task 17: Security and Performance Review
- [ ] 17.1 Verify path traversal prevention
- [ ] 17.2 Verify access control on download route
- [ ] 17.3 Test hash calculation performance with large files
- [ ] 17.4 Verify duplicate query uses index
- [ ] 17.5 Test storage directory permissions
- [ ] 17.6 Review error logging for sensitive data

### Task 18: Final Integration Testing
- [ ] 18.1 Test complete upload flow with new file
- [ ] 18.2 Test complete upload flow with duplicate file
- [ ] 18.3 Test download functionality
- [ ] 18.4 Test all management commands
- [ ] 18.5 Verify backward compatibility with existing batches
- [ ] 18.6 Test migration rollback
- [ ] 18.7 Run full test suite and verify all tests pass
