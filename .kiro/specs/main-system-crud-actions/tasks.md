# Implementation Plan: Main System CRUD Actions and Multi-Select

## Overview

This implementation plan breaks down the Main System CRUD Actions feature into discrete, sequential coding tasks. The feature enables administrators to create, read, update, and delete records through a modal interface, perform bulk operations on multiple selected records, and maintain a complete audit trail of all changes.

The implementation follows a backend-first approach: database models and services are implemented first, followed by API endpoints, then frontend components. This ensures the backend is stable before frontend integration begins.

## Tasks

- [x] 1. Set up database models and audit trail infrastructure
  - [x] 1.1 Create AuditTrail migration and model
    - Create migration for audit_trail table with all required columns
    - Create AuditTrail Eloquent model with relationships
    - Add indexes for efficient querying
    - _Requirements: 13.1-13.6_
  
  - [x] 1.2 Update MainSystem model with audit relationships
    - Add hasMany relationship to AuditTrail
    - Add methods for audit trail retrieval
    - _Requirements: 13.1-13.6_
  
  - [x]* 1.3 Write property test for audit trail immutability
    - **Property 10: Audit Trail is Immutable**
    - **Validates: Requirements 13.5**

- [x] 2. Implement validation service and rules
  - [x] 2.1 Create MainSystemValidationService
    - Implement validation for all core fields (UID, names, dates, enums)
    - Implement unique constraint validation for UID
    - Implement custom field validation
    - Return structured error responses
    - _Requirements: 8.1-8.8, 12.4-12.7_
  
  - [x]* 2.2 Write property test for validation rules
    - **Property 2: Validation Rejects Invalid Data**
    - **Validates: Requirements 8.1-8.8**
  
  - [x]* 2.3 Write property test for validation error messages
    - **Property 3: Validation Errors Display Field-Specific Messages**
    - **Validates: Requirements 8.7, 12.4, 21.2**
  
  - [x]* 2.4 Write property test for form data preservation on error
    - **Property 4: Form Data Persists on Validation Error**
    - **Validates: Requirements 21.1, 21.3**

- [x] 3. Implement audit trail service
  - [x] 3.1 Create AuditTrailService
    - Implement method to log create operations
    - Implement method to log update operations with changed fields
    - Implement method to log delete operations
    - Capture user ID, IP address, user agent
    - _Requirements: 13.1-13.6_
  
  - [x]* 3.2 Write property test for audit logging
    - **Property 9: Audit Trail Logs All CRUD Operations**
    - **Validates: Requirements 13.1-13.6**

- [x] 4. Implement bulk action service
  - [x] 4.1 Create BulkActionService
    - Implement bulk delete with transaction handling
    - Implement bulk status update with validation
    - Implement bulk category update with validation
    - Handle partial failures and error reporting
    - _Requirements: 5.4-5.7, 6.4-6.7, 7.4-7.7_
  
  - [x]* 4.2 Write property test for bulk operations
    - **Property 7: Bulk Operations Process All Selected Records**
    - **Validates: Requirements 5.4, 6.4, 7.4, 16.3**
  
  - [x]* 4.3 Write property test for bulk operation atomicity
    - **Property 8: Bulk Operations Fail Atomically or Succeed Completely**
    - **Validates: Requirements 5.4, 6.4, 7.4**

- [x] 5. Implement MainSystem CRUD service
  - [x] 5.1 Create MainSystemCrudService
    - Implement createRecord method with validation and audit logging
    - Implement updateRecord method with change tracking and audit logging
    - Implement deleteRecord method with audit logging
    - Handle template field persistence
    - _Requirements: 1.3-1.5, 2.3-2.5, 3.3, 17.2-17.4_
  
  - [x]* 5.2 Write property test for record creation
    - **Property 1: Record Creation Persists Valid Data**
    - **Validates: Requirements 1.3, 1.5, 2.5**
  
  - [x]* 5.3 Write property test for record deletion
    - **Property 6: Record Deletion Removes from Database**
    - **Validates: Requirements 3.3, 3.4**
  
  - [x]* 5.4 Write property test for template field persistence
    - **Property 21: Template Field Values Persist with Record**
    - **Validates: Requirements 17.2-17.3**

- [x] 6. Create MainSystem API controller
  - [x] 6.1 Implement POST /api/main-system endpoint
    - Accept MainSystemCreateRequest
    - Call validation service
    - Call CRUD service to create record
    - Return 201 with created record or 422 with validation errors
    - _Requirements: 1.3-1.5, 8.1-8.8_
  
  - [x] 6.2 Implement GET /api/main-system/{id} endpoint
    - Retrieve record with template fields
    - Return 200 with record or 404 if not found
    - _Requirements: 2.1_
  
  - [x] 6.3 Implement PUT /api/main-system/{id} endpoint
    - Accept MainSystemUpdateRequest
    - Call validation service
    - Call CRUD service to update record
    - Return 200 with updated record or 422 with validation errors
    - _Requirements: 2.3-2.5, 8.1-8.8_
  
  - [x] 6.4 Implement DELETE /api/main-system/{id} endpoint
    - Call CRUD service to delete record
    - Return 200 on success or 404 if not found
    - _Requirements: 3.3_

- [x] 7. Create bulk action API controller
  - [x] 7.1 Implement POST /api/main-system/bulk/delete endpoint
    - Accept BulkDeleteRequest with recordIds array
    - Call bulk action service
    - Return 200 with deleted count and failed count
    - _Requirements: 5.4-5.7_
  
  - [x] 7.2 Implement POST /api/main-system/bulk/update-status endpoint
    - Accept BulkStatusUpdateRequest
    - Validate status value
    - Call bulk action service
    - Return 200 with updated count and failed count
    - _Requirements: 6.4-6.7_
  
  - [x] 7.3 Implement POST /api/main-system/bulk/update-category endpoint
    - Accept BulkCategoryUpdateRequest
    - Validate category value
    - Call bulk action service
    - Return 200 with updated count and failed count
    - _Requirements: 7.4-7.7_

- [x] 8. Create audit trail API controller
  - [x] 8.1 Implement GET /api/audit-trail endpoint
    - Accept recordId, action, limit query parameters
    - Retrieve audit trail entries
    - Return 200 with audit entries or 404 if record not found
    - _Requirements: 13.1-13.6_

- [x] 9. Checkpoint - Ensure all backend tests pass
  - Ensure all unit and property tests pass for services and controllers
  - Verify validation logic works correctly
  - Verify audit trail logging works correctly
  - Ask the user if questions arise.


- [x] 10. Create CRUD modal component
  - [x] 10.1 Implement CrudModal.vue component structure
    - Create component with create/edit mode support
    - Implement form fields for all core attributes
    - Implement template field sections
    - Add form state management
    - _Requirements: 1.1-1.2, 2.1-2.2, 10.1-10.8, 15.1-15.7_
  
  - [x] 10.2 Implement form validation and error display
    - Integrate with validation service
    - Display field-level error messages
    - Preserve form data on validation error
    - _Requirements: 1.4, 2.4, 8.7, 12.4, 21.1-21.3_
  
  - [x] 10.3 Implement modal open/close logic
    - Implement openCreate() method
    - Implement openEdit(recordId) method
    - Implement closeModal() with unsaved changes confirmation
    - Implement resetForm() method
    - _Requirements: 1.1, 1.7, 2.1, 2.7, 23.1-23.5_
  
  - [x] 10.4 Implement form submission and API integration
    - Implement saveRecord() method
    - Call API endpoints for create/update
    - Handle success and error responses
    - Emit events for parent component
    - _Requirements: 1.5-1.6, 2.5-2.6_
  
  - [x]* 10.5 Write unit tests for CRUD modal
    - Test modal open/close behavior
    - Test form validation display
    - Test form data preservation
    - Test API integration
    - _Requirements: 1.1-1.7, 2.1-2.7_

- [x] 11. Create multi-select interface component
  - [x] 11.1 Implement MultiSelectInterface.vue component
    - Create checkbox column in table
    - Implement individual record selection
    - Implement "Select All" checkbox with indeterminate state
    - Manage Record_Selection state across pages
    - _Requirements: 4.1-4.7, 16.1-16.5_
  
  - [x] 11.2 Implement selection state persistence
    - Store selected record IDs across pagination
    - Preserve selection when navigating pages
    - Clear selection on search/filter
    - _Requirements: 4.7, 16.1-16.5_
  
  - [x]* 11.3 Write property test for selection state
    - **Property 13: Selection Persists Across Pagination**
    - **Validates: Requirements 4.7, 16.1-16.2**
  
  - [x]* 11.4 Write property test for select all behavior
    - **Property 15: Select All Toggles All Visible Records**
    - **Validates: Requirements 4.4, 9.7**
  
  - [x]* 11.5 Write property test for individual selection
    - **Property 16: Individual Record Selection Updates State**
    - **Validates: Requirements 4.3, 4.5**

- [x] 12. Create bulk action toolbar component
  - [x] 12.1 Implement BulkActionToolbar.vue component
    - Display toolbar only when records are selected
    - Show selection count
    - Implement "Clear Selection" button
    - Implement "Delete Selected" button
    - Implement "Update Status" dropdown
    - Implement "Update Category" dropdown
    - _Requirements: 4.6, 5.1-5.2, 6.1-6.3, 7.1-7.3, 9.1-9.6_
  
  - [x] 12.2 Implement toolbar visibility logic
    - Show/hide toolbar based on selection count
    - Update toolbar state when selection changes
    - _Requirements: 4.6, 9.1-9.2_
  
  - [x]* 12.3 Write property test for toolbar visibility
    - **Property 14: Bulk Action Toolbar Visibility Depends on Selection**
    - **Validates: Requirements 4.6, 5.1, 6.1, 7.1, 9.1-9.2**

- [x] 13. Create confirmation dialog component
  - [x] 13.1 Implement ConfirmationDialog.vue component
    - Display confirmation for delete operations
    - Display confirmation for bulk status updates
    - Display confirmation for bulk category updates
    - Show record preview (up to 10 records)
    - Show total count of affected records
    - _Requirements: 3.1-3.2, 5.2-5.3, 6.3, 7.3, 11.1-11.7, 18.1-18.4_
  
  - [x] 13.2 Implement confirmation dialog logic
    - Implement confirm() and cancel() methods
    - Emit events for parent component
    - _Requirements: 11.6-11.7_

- [x] 14. Integrate CRUD modal with main view
  - [x] 14.1 Add "Create Record" button to main view
    - Trigger CrudModal in create mode
    - _Requirements: 1.1_
  
  - [x] 14.2 Add "Edit" action to record rows
    - Trigger CrudModal in edit mode with record ID
    - _Requirements: 2.1_
  
  - [x] 14.3 Add "Delete" action to record rows
    - Trigger confirmation dialog
    - Call delete API endpoint
    - Refresh view after deletion
    - _Requirements: 3.1-3.6_
  
  - [x] 14.4 Implement view refresh after CRUD operations
    - Refresh view after create/update/delete
    - Preserve search filter and pagination
    - Clear selection after successful operations
    - _Requirements: 1.6, 2.6, 3.4, 14.1-14.6_

- [x] 15. Integrate multi-select interface with main view
  - [x] 15.1 Add checkbox column to main table
    - Display checkbox for each record
    - Display "Select All" checkbox in header
    - _Requirements: 4.1-4.2_
  
  - [x] 15.2 Implement selection state management
    - Connect checkboxes to Record_Selection state
    - Update toolbar visibility based on selection
    - _Requirements: 4.3-4.6_

- [x] 16. Integrate bulk action toolbar with main view
  - [x] 16.1 Display toolbar above table when records selected
    - Show/hide toolbar based on selection count
    - Display selection count
    - _Requirements: 4.6, 9.1-9.3_
  
  - [x] 16.2 Implement bulk delete action
    - Show confirmation dialog with record preview
    - Call bulk delete API endpoint
    - Refresh view and clear selection after completion
    - _Requirements: 5.1-5.7_
  
  - [x] 16.3 Implement bulk status update action
    - Show status dropdown
    - Show confirmation dialog
    - Call bulk status update API endpoint
    - Refresh view and clear selection after completion
    - _Requirements: 6.1-6.7_
  
  - [x] 16.4 Implement bulk category update action
    - Show category dropdown
    - Show confirmation dialog
    - Call bulk category update API endpoint
    - Refresh view and clear selection after completion
    - _Requirements: 7.1-7.7_
  
  - [x] 16.5 Implement "Clear Selection" button
    - Deselect all records
    - Hide toolbar
    - _Requirements: 9.4-9.5_

- [x] 17. Implement error handling and user feedback
  - [x] 17.1 Implement error handling for API calls
    - Catch validation errors and display field-level messages
    - Catch database errors and display user-friendly messages
    - Catch unique constraint violations with specific message
    - _Requirements: 12.1-12.7_
  
  - [x] 17.2 Implement toast notifications
    - Show success notifications after CRUD operations
    - Show error notifications with retry option
    - _Requirements: 12.1-12.7_
  
  - [x] 17.3 Implement progress indicator for bulk operations
    - Show progress during bulk operations
    - Display processed/total count
    - Update progress in real-time
    - _Requirements: 20.1-20.6_
  
  - [ ]* 17.4 Write property test for error handling
    - **Property 25: Errors Prevent Data Modification**
    - **Validates: Requirements 12.6, 12.7**

- [x] 18. Implement responsive design
  - [x] 18.1 Implement responsive CRUD modal
    - Desktop layout (1024px+): 600px width, centered
    - Tablet layout (768px-1023px): 90% width, max 500px
    - Mobile layout (<768px): full-screen with padding
    - _Requirements: 19.1-19.7_
  
  - [x] 18.2 Implement responsive form fields
    - Desktop: side-by-side fields where appropriate
    - Tablet/Mobile: stack vertically
    - _Requirements: 19.4-19.5_
  
  - [x] 18.3 Implement responsive bulk action toolbar
    - Desktop: horizontal button layout
    - Tablet/Mobile: vertical stacked buttons, full-width
    - _Requirements: 19.4-19.5_

- [x] 19. Implement accessibility features
  - [x] 19.1 Implement keyboard navigation
    - Tab/Shift+Tab navigation through form fields
    - Enter key submits form
    - Escape key closes modal
    - Arrow keys navigate dropdowns
    - _Requirements: 15.4-15.5_
  
  - [x] 19.2 Implement screen reader support
    - Add ARIA labels to all form fields
    - Add ARIA descriptions for error messages
    - Add ARIA live regions for notifications
    - Add ARIA selected for checkboxes
    - _Requirements: 15.2-15.3_
  
  - [x] 19.3 Implement focus management
    - Move focus to first form field when modal opens
    - Return focus to trigger button when modal closes
    - Trap focus within confirmation dialogs
    - _Requirements: 15.6-15.7_

- [x] 20. Implement template field support
  - [x] 20.1 Display template fields in CRUD modal
    - Fetch template fields for record
    - Display template field sections
    - Pre-populate template field values on edit
    - _Requirements: 17.1-17.3_
  
  - [x] 20.2 Implement template field persistence
    - Save template field values with record
    - Retrieve template field values on edit
    - _Requirements: 17.2-17.3_
  
  - [ ]* 20.3 Write property test for template field changes
    - **Property 22: Template Field Changes are Audited**
    - **Validates: Requirements 17.4**

- [x] 21. Implement search and filter preservation
  - [x] 21.1 Preserve search criteria after CRUD operations
    - Store current search/filter state
    - Re-apply search after view refresh
    - _Requirements: 14.5, 24.1-24.2_
  
  - [x] 21.2 Preserve pagination state after CRUD operations
    - Store current page number
    - Return to same page after refresh if possible
    - _Requirements: 14.5, 24.4_
  
  - [ ]* 21.3 Write property test for search preservation
    - **Property 17: Search and Filter Criteria Preserved After CRUD**
    - **Validates: Requirements 24.1-24.2, 24.4**

- [x] 22. Implement bulk operation state management
  - [x] 22.1 Implement concurrent bulk operation prevention
    - Disable bulk action buttons during operation
    - Show progress indicator
    - _Requirements: 20.6_
  
  - [x] 22.2 Implement selection clearing after successful bulk operations
    - Clear selection after successful bulk delete
    - Clear selection after successful bulk status update
    - Clear selection after successful bulk category update
    - _Requirements: 22.1, 22.3-22.4_
  
  - [x] 22.3 Implement selection preservation after failed bulk operations
    - Keep selection if bulk operation fails
    - Allow user to retry
    - _Requirements: 22.2_
  
  - [ ]* 22.4 Write property test for bulk operation state
    - **Property 12: Selection Clears After Successful Bulk Operation**
    - **Validates: Requirements 22.1, 22.3-22.4**
  
  - [ ]* 22.5 Write property test for bulk operation failure
    - **Property 26: Bulk Operation Failure Preserves Selection**
    - **Validates: Requirements 22.2**

- [x] 23. Implement modal cancel confirmation
  - [x] 23.1 Detect unsaved changes in CRUD modal
    - Compare current form data with initial data
    - Track if user has made changes
    - _Requirements: 23.1_
  
  - [x] 23.2 Show confirmation dialog on cancel with changes
    - Display "Discard changes?" confirmation
    - Allow user to confirm or cancel
    - _Requirements: 23.2-23.4_
  
  - [ ]* 23.3 Write property test for modal cancel behavior
    - **Property 20: Modal Closes Without Saving on Cancel**
    - **Validates: Requirements 1.7, 2.7, 23.1, 23.5**

- [x] 24. Implement modal initial state logic
  - [x] 24.1 Implement create mode initialization
    - Open modal with empty form fields
    - _Requirements: 1.1_
  
  - [x] 24.2 Implement edit mode initialization
    - Load record data from API
    - Pre-populate all form fields
    - Pre-populate template field values
    - _Requirements: 2.1, 17.3_
  
  - [ ]* 24.3 Write property test for modal initialization
    - **Property 19: Modal Opens with Correct Initial State**
    - **Validates: Requirements 1.1, 2.1, 17.3**

- [x] 25. Implement view refresh logic
  - [x] 25.1 Implement view refresh after create
    - Refresh record list
    - Preserve search and pagination
    - Clear selection
    - _Requirements: 1.6, 14.1-14.6_
  
  - [x] 25.2 Implement view refresh after update
    - Refresh record list
    - Preserve search and pagination
    - Clear selection
    - _Requirements: 2.6, 14.1-14.6_
  
  - [x] 25.3 Implement view refresh after delete
    - Refresh record list
    - Preserve search and pagination
    - Clear selection
    - _Requirements: 3.4, 14.1-14.6_
  
  - [x] 25.4 Implement view refresh after bulk operations
    - Refresh record list
    - Preserve search and pagination
    - Clear selection
    - _Requirements: 14.4-14.6_
  
  - [ ]* 25.5 Write property test for view refresh
    - **Property 11: View Refreshes After CRUD Operations**
    - **Validates: Requirements 14.1-14.5**

- [x] 26. Implement search clearing logic
  - [x] 26.1 Clear selection when search is performed
    - Detect search/filter changes
    - Clear Record_Selection state
    - _Requirements: 16.5_
  
  - [ ]* 26.2 Write property test for search clearing
    - **Property 27: Selection Clears on Search or Filter**
    - **Validates: Requirements 16.5**

- [x] 27. Implement progress tracking for bulk operations
  - [x] 27.1 Implement real-time progress updates
    - Track processed/total count
    - Update progress indicator
    - _Requirements: 20.2-20.3_
  
  - [ ]* 27.2 Write property test for progress updates
    - **Property 24: Progress Updates During Bulk Operations**
    - **Validates: Requirements 20.2-20.3**

- [x] 28. Checkpoint - Ensure all frontend tests pass
  - Ensure all unit and integration tests pass for components
  - Verify modal functionality works correctly
  - Verify multi-select interface works correctly
  - Verify bulk action toolbar works correctly
  - Ask the user if questions arise.

- [x] 29. Integration testing - Complete CRUD workflows
  - [x] 29.1 Test create record workflow
    - Click "Create Record" button
    - Fill form with valid data
    - Click "Save"
    - Verify record appears in list
    - _Requirements: 1.1-1.7_
  
  - [x] 29.2 Test edit record workflow
    - Click "Edit" on record
    - Modify form fields
    - Click "Save"
    - Verify changes appear in list
    - _Requirements: 2.1-2.8_
  
  - [x] 29.3 Test delete record workflow
    - Click "Delete" on record
    - Confirm deletion
    - Verify record removed from list
    - _Requirements: 3.1-3.6_
  
  - [x] 29.4 Test bulk delete workflow
    - Select multiple records
    - Click "Delete Selected"
    - Confirm deletion
    - Verify all records removed
    - _Requirements: 5.1-5.7_
  
  - [x] 29.5 Test bulk status update workflow
    - Select multiple records
    - Click "Update Status"
    - Select new status
    - Confirm update
    - Verify all records updated
    - _Requirements: 6.1-6.7_
  
  - [x] 29.6 Test bulk category update workflow
    - Select multiple records
    - Click "Update Category"
    - Select new category
    - Confirm update
    - Verify all records updated
    - _Requirements: 7.1-7.7_

- [x] 30. Integration testing - Multi-select and pagination
  - [x] 30.1 Test selection persistence across pages
    - Select records on page 1
    - Navigate to page 2
    - Verify page 1 records still selected
    - Navigate back to page 1
    - Verify records still selected
    - _Requirements: 16.1-16.2_
  
  - [x] 30.2 Test bulk operations across pages
    - Select records on multiple pages
    - Perform bulk operation
    - Verify all selected records processed
    - _Requirements: 16.3_
  
  - [x] 30.3 Test selection clearing on search
    - Select records
    - Perform search
    - Verify selection cleared
    - _Requirements: 16.5_

- [x] 31. Integration testing - Error handling and recovery
  - [x] 31.1 Test validation error handling
    - Submit form with invalid data
    - Verify error messages displayed
    - Verify form data preserved
    - Correct errors and resubmit
    - _Requirements: 12.4, 21.1-21.3_
  
  - [x] 31.2 Test unique constraint violation
    - Try to create record with duplicate UID
    - Verify specific error message displayed
    - _Requirements: 12.5_
  
  - [x] 31.3 Test database error handling
    - Simulate database error
    - Verify user-friendly error message displayed
    - Verify retry option available
    - _Requirements: 12.1-12.3_

- [x] 32. Integration testing - Audit trail logging
  - [x] 32.1 Test audit trail for create operations
    - Create record
    - Verify audit entry created with correct data
    - _Requirements: 13.1_
  
  - [x] 32.2 Test audit trail for update operations
    - Update record
    - Verify audit entry created with changed fields
    - _Requirements: 13.2_
  
  - [x] 32.3 Test audit trail for delete operations
    - Delete record
    - Verify audit entry created with record data
    - _Requirements: 13.3_
  
  - [x] 32.4 Test audit trail for bulk operations
    - Perform bulk operation
    - Verify individual audit entries for each record
    - _Requirements: 13.4_

- [x] 33. Integration testing - Accessibility
  - [x] 33.1 Test keyboard navigation
    - Navigate modal with Tab/Shift+Tab
    - Submit form with Enter
    - Close modal with Escape
    - Navigate dropdowns with arrow keys
    - _Requirements: 15.4-15.5_
  
  - [x] 33.2 Test screen reader support
    - Verify all fields have labels
    - Verify error messages associated with fields
    - Verify notifications announced
    - _Requirements: 15.2-15.3_
  
  - [x] 33.3 Test focus management
    - Verify focus moves to first field on modal open
    - Verify focus returns to trigger button on close
    - _Requirements: 15.6-15.7_

- [x] 34. Integration testing - Responsive design
  - [x] 34.1 Test desktop layout (1024px+)
    - Verify modal displays at correct width
    - Verify form layout is appropriate
    - _Requirements: 19.1_
  
  - [x] 34.2 Test tablet layout (768px-1023px)
    - Verify modal displays at correct width
    - Verify form fields stack appropriately
    - _Requirements: 19.2, 19.4_
  
  - [x] 34.3 Test mobile layout (<768px)
    - Verify modal displays full-screen
    - Verify form fields stack vertically
    - Verify buttons stack vertically
    - _Requirements: 19.3-19.5_

- [x] 35. Final checkpoint - Ensure all tests pass
  - Ensure all unit, property, and integration tests pass
  - Verify all 24 requirements are covered by tests
  - Verify all 27 correctness properties are validated
  - Ask the user if questions arise.

