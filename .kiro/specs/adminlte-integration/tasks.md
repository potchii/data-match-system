# Implementation Plan: AdminLTE Integration

## Overview

This implementation plan outlines the step-by-step approach to refactor the Data Match System from React/Inertia to AdminLTE with Blade templates. The implementation will proceed in phases: dependency cleanup, layout creation, page implementation, controller development, and testing.

## Tasks

- [x] 1. Remove React/Inertia dependencies and clean up configuration
  - Remove React, React-DOM, @inertiajs/react from package.json
  - Remove inertiajs/inertia-laravel from composer.json
  - Remove Inertia middleware from app/Http/Kernel.php or bootstrap/app.php
  - Update or remove Vite configuration for React
  - Remove resources/js/app.tsx and React component files
  - Remove config/inertia.php
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

- [-] 2. Install and configure AdminLTE assets
  - [x] 2.1 Create base AdminLTE layout template
    - Create resources/views/layouts/admin.blade.php with AdminLTE structure
    - Include AdminLTE CSS from CDN (https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css)
    - Include Font Awesome CSS from CDN
    - Include jQuery, Bootstrap, and AdminLTE JavaScript from CDN in correct order
    - Add @yield('content') section for page content
    - Add @stack('styles') and @stack('scripts') for page-specific assets
    - _Requirements: 1.1, 1.2, 1.3, 12.1, 12.2, 12.3, 12.4, 12.5_
  
  - [ ]* 2.2 Write property test for asset loading order
    - **Property 18: Asset loading order**
    - **Validates: Requirements 12.5**

- [x] 3. Create layout partials
  - [x] 3.1 Create sidebar navigation partial
    - Create resources/views/layouts/partials/sidebar.blade.php
    - Add brand link with application name
    - Add navigation menu with links to Dashboard, Upload, Results, and Batches
    - Use request()->routeIs() to highlight active menu items
    - Add Font Awesome icons for each menu item
    - _Requirements: 2.1, 2.3, 2.5_
  
  - [ ]* 3.2 Write property test for active menu highlighting
    - **Property 1: Active menu highlighting**
    - **Validates: Requirements 2.3**
  
  - [ ]* 3.3 Write property test for navigation link correctness
    - **Property 2: Navigation link correctness**
    - **Validates: Requirements 2.4**
  
  - [x] 3.4 Create top navigation bar partial
    - Create resources/views/layouts/partials/navbar.blade.php
    - Add sidebar toggle button
    - Add user dropdown with authenticated user's name
    - Add logout form in dropdown
    - _Requirements: 2.2_
  
  - [x] 3.5 Create alerts partial for flash messages
    - Create resources/views/layouts/partials/alerts.blade.php
    - Display success messages from session('success')
    - Display error messages from session('error')
    - Display validation errors from $errors
    - Use AdminLTE alert styling with dismissible buttons
    - _Requirements: 7.3, 7.4_
  
  - [x] 3.6 Create footer partial
    - Create resources/views/layouts/partials/footer.blade.php
    - Add basic footer content
    - _Requirements: 1.2_

- [x] 4. Implement Dashboard page
  - [x] 4.1 Create DashboardController
    - Create app/Http/Controllers/DashboardController.php
    - Implement index() method to calculate statistics
    - Query total batches count from UploadBatch model
    - Query matched records count from MatchResult where status = 'MATCHED'
    - Query new records count from MatchResult where status = 'NEW RECORD'
    - Query possible duplicates count from MatchResult where status = 'POSSIBLE DUPLICATE'
    - Query recent 10 batches ordered by uploaded_at desc
    - Return view with all statistics data
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_
  
  - [x] 4.2 Create dashboard view template
    - Create resources/views/pages/dashboard.blade.php
    - Extend layouts.admin layout
    - Display statistics in AdminLTE small-box components (info, success, primary, warning)
    - Display recent batches in a table with columns: Batch ID, File Name, Uploaded By, Upload Date, Status
    - Use color-coded badges for batch status
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_
  
  - [ ]* 4.3 Write property test for dashboard statistics accuracy
    - **Property 3: Dashboard statistics accuracy**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
  
  - [ ]* 4.4 Write property test for recent activity display
    - **Property 4: Recent activity display**
    - **Validates: Requirements 3.5**
  
  - [ ]* 4.5 Write unit tests for DashboardController
    - Test index method returns correct view
    - Test statistics calculations with various database states
    - Test with empty database (no batches or results)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 5. Implement Upload page
  - [x] 5.1 Update UploadController
    - Add index() method to display upload form
    - Update store() method to use auth()->user()->name for uploaded_by
    - Update redirect to use route('upload.index') instead of back()
    - Ensure validation rules are present: required|mimes:xlsx,xls,csv|max:10240
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 8.4_
  
  - [x] 5.2 Create upload view template
    - Create resources/views/pages/upload.blade.php
    - Extend layouts.admin layout
    - Create form with file input using AdminLTE custom-file styling
    - Add accept attribute for .xlsx, .xls, .csv files
    - Add submit button with upload icon
    - Add help text about accepted formats and max size
    - _Requirements: 4.1, 4.8_
  
  - [x] 5.3 Add client-side validation JavaScript
    - Add JavaScript to update file label with selected filename
    - Add form submit handler to validate file type and size
    - Show loading state on submit button during processing
    - _Requirements: 4.2, 4.3, 4.7, 7.5_
  
  - [ ]* 5.4 Write property test for file type validation
    - **Property 5: File type validation**
    - **Validates: Requirements 4.2**
  
  - [ ]* 5.5 Write property test for valid file processing
    - **Property 6: Valid file processing**
    - **Validates: Requirements 4.4**
  
  - [ ]* 5.6 Write property test for operation feedback messages
    - **Property 7: Operation feedback messages**
    - **Validates: Requirements 4.5, 4.6, 7.3, 7.4**
  
  - [ ]* 5.7 Write unit tests for UploadController
    - Test index method returns upload view
    - Test store method with valid file creates batch
    - Test store method with invalid file type returns validation error
    - Test store method with file too large returns validation error
    - Test store method with empty file throws exception
    - Test store method with missing columns throws exception
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [ ] 6. Checkpoint - Ensure upload functionality works
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Implement Match Results page
  - [ ] 7.1 Create ResultsController
    - Create app/Http/Controllers/ResultsController.php
    - Implement index() method with filtering and pagination
    - Accept batch_id and status query parameters
    - Query MatchResult with eager loading of batch relationship
    - Apply filters if parameters are present
    - Order by created_at desc
    - Paginate results (20 per page)
    - Query all batches for filter dropdown
    - Return view with results and batches
    - _Requirements: 5.1, 5.4, 5.5, 5.6_
  
  - [ ] 7.2 Create results view template
    - Create resources/views/pages/results.blade.php
    - Extend layouts.admin layout
    - Add filter form with batch_id and status dropdowns
    - Display results in table with columns: Batch ID, Uploaded Record ID, Match Status, Confidence Score, Matched System ID
    - Use color-coded badges for match status (success for MATCHED, warning for POSSIBLE DUPLICATE, info for NEW RECORD)
    - Add pagination links
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_
  
  - [ ]* 7.3 Write property test for results pagination and display
    - **Property 8: Results pagination and display**
    - **Validates: Requirements 5.1**
  
  - [ ]* 7.4 Write property test for status badge differentiation
    - **Property 9: Status badge differentiation**
    - **Validates: Requirements 5.3, 6.3**
  
  - [ ]* 7.5 Write property test for results filtering functionality
    - **Property 10: Results filtering functionality**
    - **Validates: Requirements 5.4, 5.5**
  
  - [ ]* 7.6 Write property test for results sorting order
    - **Property 11: Results sorting order**
    - **Validates: Requirements 5.6**
  
  - [ ]* 7.7 Write unit tests for ResultsController
    - Test index method returns results view
    - Test filtering by batch_id works correctly
    - Test filtering by status works correctly
    - Test pagination works correctly
    - Test with no results displays empty state
    - _Requirements: 5.1, 5.4, 5.5, 5.6_

- [ ] 8. Implement Batch History page
  - [ ] 8.1 Create BatchController
    - Create app/Http/Controllers/BatchController.php
    - Implement index() method
    - Query all UploadBatch records ordered by uploaded_at desc
    - Paginate batches (20 per page)
    - Return view with batches
    - _Requirements: 6.1, 6.5_
  
  - [ ] 8.2 Create batches view template
    - Create resources/views/pages/batches.blade.php
    - Extend layouts.admin layout
    - Display batches in table with columns: Batch ID, File Name, Uploaded By, Upload Date, Status, Actions
    - Use color-coded badges for batch status (success for COMPLETED, danger for FAILED, warning for PROCESSING)
    - Add onclick handler to rows to navigate to filtered results
    - Add "View Results" button in Actions column
    - Add pagination links
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_
  
  - [ ]* 8.3 Write property test for batch pagination and display
    - **Property 12: Batch pagination and display**
    - **Validates: Requirements 6.1**
  
  - [ ]* 8.4 Write property test for batch row navigation
    - **Property 13: Batch row navigation**
    - **Validates: Requirements 6.4**
  
  - [ ]* 8.5 Write property test for batch sorting order
    - **Property 14: Batch sorting order**
    - **Validates: Requirements 6.5**
  
  - [ ]* 8.6 Write unit tests for BatchController
    - Test index method returns batches view
    - Test pagination works correctly
    - Test batches are sorted by uploaded_at desc
    - Test with no batches displays empty state
    - _Requirements: 6.1, 6.5_

- [ ] 9. Update routing configuration
  - [ ] 9.1 Update web.php routes
    - Remove Inertia::render() calls
    - Add route for dashboard: GET /dashboard → DashboardController@index
    - Add route for upload page: GET /upload → UploadController@index
    - Add route for results page: GET /results → ResultsController@index
    - Add route for batches page: GET /batches → BatchController@index
    - Keep existing POST /upload-process route
    - Apply auth middleware to all admin routes
    - Remove or update the root route to redirect to dashboard for authenticated users
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6_
  
  - [ ]* 9.2 Write unit tests for route definitions
    - Test /dashboard route exists and requires authentication
    - Test /upload route exists and requires authentication
    - Test /results route exists and requires authentication
    - Test /batches route exists and requires authentication
    - Test /upload-process route exists and accepts POST
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6_

- [ ] 10. Implement authentication integration
  - [ ]* 10.1 Write property test for authentication protection
    - **Property 16: Authentication protection**
    - **Validates: Requirements 10.1, 10.2**
  
  - [ ]* 10.2 Write property test for authenticated user display
    - **Property 17: Authenticated user display**
    - **Validates: Requirements 10.3**
  
  - [ ]* 10.3 Write unit tests for authentication flow
    - Test unauthenticated access to /dashboard redirects to login
    - Test unauthenticated access to /upload redirects to login
    - Test unauthenticated access to /results redirects to login
    - Test unauthenticated access to /batches redirects to login
    - Test authenticated user can access all protected routes
    - Test logout functionality works correctly
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [ ] 11. Implement form validation
  - [ ]* 11.1 Write property test for form validation error display
    - **Property 15: Form validation error display**
    - **Validates: Requirements 7.1, 7.2, 7.6**
  
  - [ ]* 11.2 Write unit tests for validation
    - Test upload form validates required file field
    - Test upload form validates file type
    - Test upload form validates file size
    - Test validation errors are displayed in alerts partial
    - _Requirements: 7.1, 7.2, 7.6_

- [ ] 12. Create AdminLTE login page (optional styling enhancement)
  - [ ] 12.1 Create custom login view
    - Create resources/views/auth/login.blade.php with AdminLTE styling
    - Use AdminLTE login-box component
    - Style form inputs with AdminLTE classes
    - _Requirements: 10.5_

- [ ] 13. Final integration and cleanup
  - [ ] 13.1 Remove unused React/Inertia files
    - Delete resources/js/pages/ directory
    - Delete resources/js/components/ directory if exists
    - Remove any remaining Inertia references
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_
  
  - [ ] 13.2 Update composer and npm dependencies
    - Run composer update to remove Inertia package
    - Run npm install to update package-lock.json
    - Verify Laravel Fortify and Laravel Excel are still present
    - _Requirements: 9.3, 9.7_
  
  - [ ] 13.3 Test complete user workflow
    - Test login flow
    - Test dashboard displays correctly
    - Test file upload and processing
    - Test viewing match results with filters
    - Test viewing batch history
    - Test logout flow
    - _Requirements: All_

- [ ] 14. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional property-based and unit tests that can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties with minimum 100 iterations
- Unit tests validate specific examples, edge cases, and integration points
- All existing models, services, and business logic remain unchanged
- Authentication continues to use Laravel Fortify
- File processing continues to use Laravel Excel (maatwebsite/excel)
