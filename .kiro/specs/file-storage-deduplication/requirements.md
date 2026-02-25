# Requirements Document: File Storage and Deduplication

## Introduction

This feature implements persistent file storage for uploaded Excel/CSV files and prevents duplicate file uploads. Currently, uploaded files are processed but not stored locally, making it impossible to re-process files or audit uploads. This enhancement will store all uploaded files in a structured directory and detect duplicates based on file hash to prevent redundant processing.

## Glossary

- **Upload_File**: The Excel/CSV file uploaded by the user through the web interface
- **File_Hash**: A cryptographic hash (SHA-256) of the file contents used for duplicate detection
- **Storage_Path**: The local filesystem path where uploaded files are permanently stored
- **UploadBatch**: Laravel model representing a batch upload with metadata
- **Duplicate_File**: A file with identical content (same hash) as a previously uploaded file
- **File_Metadata**: Information about the stored file (original name, size, hash, storage path)

## Requirements

### Requirement 1: File Storage Implementation

**User Story:** As a system administrator, I want all uploaded files to be stored locally, so that I can audit uploads and re-process files if needed.

#### Acceptance Criteria

1. WHEN a user uploads an Excel/CSV file, THE System SHALL store the file in the local filesystem
2. THE System SHALL store files in the directory `storage/app/uploads/{year}/{month}/`
3. THE System SHALL generate a unique filename using the format: `{timestamp}_{hash}_{original_name}`
4. THE System SHALL preserve the original file extension (.xlsx, .xls, .csv)
5. THE System SHALL create the storage directory structure if it doesn't exist
6. THE System SHALL store the file path in the `upload_batches` table
7. WHEN file storage fails, THE System SHALL return an error and not process the upload
8. THE System SHALL set appropriate file permissions (0644) for stored files

### Requirement 2: File Hash Calculation

**User Story:** As a developer, I want to calculate file hashes, so that I can detect duplicate uploads.

#### Acceptance Criteria

1. THE System SHALL calculate a SHA-256 hash of the uploaded file contents
2. THE System SHALL calculate the hash before storing the file
3. THE System SHALL store the file hash in the `upload_batches` table
4. THE System SHALL use the hash as part of the stored filename
5. THE hash calculation SHALL be performed on the raw file bytes (not the filename)
6. THE System SHALL handle hash calculation errors gracefully

### Requirement 3: Duplicate File Detection

**User Story:** As a data manager, I want the system to detect duplicate file uploads, so that I don't waste time processing the same data twice.

#### Acceptance Criteria

1. WHEN a user uploads a file, THE System SHALL check if a file with the same hash already exists
2. WHEN a duplicate file is detected, THE System SHALL display a warning message to the user
3. THE warning message SHALL include: original upload date, original uploader, and batch ID
4. THE System SHALL allow the user to choose: "Process Anyway" or "Cancel Upload"
5. WHEN the user chooses "Process Anyway", THE System SHALL create a new batch but reference the existing stored file
6. WHEN the user chooses "Cancel Upload", THE System SHALL not create a new batch
7. THE duplicate check SHALL be performed before file storage
8. THE System SHALL consider files with identical hashes as duplicates regardless of filename

### Requirement 4: Database Schema Enhancement

**User Story:** As a developer, I want the database to track file storage metadata, so that I can manage stored files.

#### Acceptance Criteria

1. THE System SHALL add a `file_hash` column (VARCHAR 64) to the `upload_batches` table
2. THE System SHALL add a `stored_file_path` column (VARCHAR 500) to the `upload_batches` table
3. THE System SHALL add a `file_size` column (BIGINT) to the `upload_batches` table
4. THE System SHALL add an index on the `file_hash` column for fast duplicate lookups
5. THE migration SHALL be reversible (support rollback)
6. THE System SHALL maintain backward compatibility with existing batch records (nullable columns)

### Requirement 5: Storage Management

**User Story:** As a system administrator, I want to manage stored files, so that I can clean up old uploads and free disk space.

#### Acceptance Criteria

1. THE System SHALL provide an Artisan command to list all stored files with metadata
2. THE System SHALL provide an Artisan command to delete files older than a specified date
3. WHEN deleting old files, THE System SHALL update the `upload_batches` table to mark files as deleted
4. THE System SHALL provide an Artisan command to verify file integrity (check if stored files exist)
5. THE System SHALL log all file storage operations (store, delete, verify)
6. THE System SHALL calculate and display total storage usage

### Requirement 6: User Interface Enhancement

**User Story:** As a user, I want to see information about stored files, so that I can understand what has been uploaded.

#### Acceptance Criteria

1. WHEN viewing batch results, THE System SHALL display the stored file path
2. WHEN viewing batch results, THE System SHALL display the file size in human-readable format (KB, MB)
3. WHEN viewing batch results, THE System SHALL provide a download link for the stored file
4. WHEN a duplicate is detected, THE System SHALL display a modal dialog with duplicate information
5. THE duplicate dialog SHALL show: original filename, upload date, uploader, batch ID
6. THE duplicate dialog SHALL have "Process Anyway" and "Cancel" buttons
7. THE System SHALL display a warning icon next to batches that are duplicates

### Requirement 7: File Download Capability

**User Story:** As a user, I want to download previously uploaded files, so that I can review the original data.

#### Acceptance Criteria

1. THE System SHALL provide a route to download stored files by batch ID
2. THE download route SHALL verify user authentication
3. THE download route SHALL verify the user has permission to access the batch
4. THE System SHALL serve the file with the original filename
5. THE System SHALL set appropriate Content-Type headers based on file extension
6. THE System SHALL set Content-Disposition header to trigger browser download
7. WHEN the stored file doesn't exist, THE System SHALL return a 404 error with a descriptive message

### Requirement 8: Error Handling and Validation

**User Story:** As a developer, I want robust error handling for file operations, so that the system remains stable.

#### Acceptance Criteria

1. WHEN the storage directory is not writable, THE System SHALL return an error message
2. WHEN disk space is insufficient, THE System SHALL return an error before processing
3. WHEN file hash calculation fails, THE System SHALL return an error
4. WHEN file storage fails, THE System SHALL clean up any partial files
5. THE System SHALL log all file operation errors with context
6. THE System SHALL validate file paths to prevent directory traversal attacks
7. THE System SHALL validate that stored files are within the allowed storage directory

### Requirement 9: Performance Considerations

**User Story:** As a system architect, I want file operations to be efficient, so that upload performance is not degraded.

#### Acceptance Criteria

1. THE hash calculation SHALL use streaming to handle large files efficiently
2. THE duplicate check SHALL use an indexed database query (< 100ms)
3. THE file storage operation SHALL not block the import process
4. THE System SHALL handle files up to 10MB without performance degradation
5. THE System SHALL provide progress feedback for large file uploads

### Requirement 10: Backward Compatibility

**User Story:** As a system maintainer, I want existing functionality to continue working, so that the migration is seamless.

#### Acceptance Criteria

1. THE System SHALL support existing `upload_batches` records without file storage metadata
2. WHEN displaying batches without stored files, THE System SHALL hide the download link
3. THE System SHALL not break existing upload functionality
4. THE System SHALL maintain all existing API contracts
5. THE System SHALL not require re-uploading existing batches
