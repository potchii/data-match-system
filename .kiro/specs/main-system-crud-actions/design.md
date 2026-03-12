# Design Document: Main System CRUD Actions and Multi-Select

## Overview

This design document outlines the technical implementation of comprehensive CRUD (Create, Read, Update, Delete) operations and multi-select bulk action capabilities for the Main System module. The feature enables administrators to efficiently manage records through a modal-based interface, perform bulk operations on multiple selected records, and maintain a complete audit trail of all changes.

The system is built on a Laravel backend with a Vue.js frontend, providing a responsive, accessible interface that works across desktop, tablet, and mobile devices. All operations are validated, logged, and include confirmation dialogs to prevent accidental data loss.

## Architecture

### System Components

The CRUD actions feature consists of five primary layers:

1. **Frontend Layer**: Vue.js components for modal dialogs, multi-select interface, and bulk action controls
2. **API Layer**: RESTful endpoints for CRUD operations and bulk actions
3. **Service Layer**: Business logic for validation, bulk processing, and audit logging
4. **Data Layer**: Eloquent models and database operations
5. **Audit Layer**: Immutable audit trail logging for compliance and accountability

### Component Interactions

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Vue.js)                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ CRUD Modal   │  │ Multi-Select │  │ Bulk Action  │      │
│  │ Component    │  │ Interface    │  │ Toolbar      │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼──────────────────┼──────────────────┼──────────────┘
          │                  │                  │
          └──────────────────┼──────────────────┘
                             │
┌────────────────────────────▼──────────────────────────────────┐
│                    API Layer (Laravel)                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ MainSystem   │  │ Bulk Action  │  │ Audit Trail  │       │
│  │ Controller   │  │ Controller   │  │ Controller   │       │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘       │
└─────────┼──────────────────┼──────────────────┼───────────────┘
          │                  │                  │
          └──────────────────┼──────────────────┘
                             │
┌────────────────────────────▼──────────────────────────────────┐
│                   Service Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ Validation   │  │ Bulk Action  │  │ Audit        │       │
│  │ Service      │  │ Service      │  │ Service      │       │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘       │
└─────────┼──────────────────┼──────────────────┼───────────────┘
          │                  │                  │
          └──────────────────┼──────────────────┘
                             │
┌────────────────────────────▼──────────────────────────────────┐
│                    Data Layer                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ MainSystem   │  │ Template     │  │ Audit Trail  │       │
│  │ Model        │  │ FieldValue   │  │ Model        │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└───────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### Frontend Components

#### 1. CRUD Modal Component

**Purpose**: Provides a unified interface for creating and editing Main System records.

**Props**:
- `isOpen: boolean` - Controls modal visibility
- `mode: 'create' | 'edit'` - Determines modal behavior
- `recordId?: number` - ID of record being edited (null for create)
- `templateFields?: TemplateField[]` - Available template fields for the record

**State**:
- `formData: MainSystemFormData` - Current form field values
- `errors: Record<string, string>` - Field-level validation errors
- `isLoading: boolean` - Indicates API request in progress
- `hasChanges: boolean` - Tracks if form data has been modified
- `serverError?: string` - General server error message

**Methods**:
- `openCreate()` - Initialize modal for creating new record
- `openEdit(recordId)` - Load record data and initialize modal for editing
- `saveRecord()` - Validate and submit form data
- `closeModal()` - Close modal with unsaved changes confirmation
- `resetForm()` - Clear all form fields and errors

**Events**:
- `@record-created` - Emitted when record is successfully created
- `@record-updated` - Emitted when record is successfully updated
- `@modal-closed` - Emitted when modal closes

#### 2. Multi-Select Interface Component

**Purpose**: Provides checkbox-based record selection with "Select All" functionality.

**Props**:
- `records: MainSystem[]` - Records displayed on current page
- `selectedRecords: Set<number>` - IDs of selected records across all pages
- `isAllSelected: boolean` - Indicates if all visible records are selected

**State**:
- `pageSelection: Set<number>` - Records selected on current page
- `isSelectAllIndeterminate: boolean` - Indicates partial selection state

**Methods**:
- `toggleRecord(recordId)` - Toggle selection of individual record
- `toggleSelectAll()` - Select or deselect all visible records
- `clearSelection()` - Deselect all records across all pages

**Events**:
- `@selection-changed` - Emitted when selection state changes with updated set

#### 3. Bulk Action Toolbar Component

**Purpose**: Displays bulk action controls when records are selected.

**Props**:
- `selectedCount: number` - Number of selected records
- `isVisible: boolean` - Controls toolbar visibility
- `categories: string[]` - Available categories for bulk update
- `statuses: string[]` - Available statuses for bulk update

**State**:
- `isProcessing: boolean` - Indicates bulk action in progress
- `progress: { processed: number, total: number }` - Bulk operation progress

**Methods**:
- `onDeleteSelected()` - Initiate bulk delete with confirmation
- `onUpdateStatus(status)` - Initiate bulk status update with confirmation
- `onUpdateCategory(category)` - Initiate bulk category update with confirmation
- `clearSelection()` - Deselect all records

**Events**:
- `@bulk-action-initiated` - Emitted when bulk action starts
- `@bulk-action-completed` - Emitted when bulk action completes
- `@selection-cleared` - Emitted when selection is cleared

#### 4. Confirmation Dialog Component

**Purpose**: Provides confirmation for destructive operations with record preview.

**Props**:
- `isOpen: boolean` - Controls dialog visibility
- `title: string` - Dialog title
- `message: string` - Main confirmation message
- `recordPreview: RecordPreview[]` - Records affected by operation (max 10)
- `totalCount: number` - Total number of affected records
- `actionType: 'delete' | 'status-update' | 'category-update'` - Type of operation

**Methods**:
- `confirm()` - Confirm the operation
- `cancel()` - Cancel the operation

**Events**:
- `@confirmed` - Emitted when user confirms action
- `@cancelled` - Emitted when user cancels action

### Backend API Endpoints

#### MainSystem CRUD Endpoints

**POST /api/main-system**
- Creates a new Main System record
- Request body: `MainSystemCreateRequest`
- Response: `{ success: boolean, data: MainSystem, errors?: Record<string, string[]> }`
- Status codes: 201 (created), 422 (validation error), 500 (server error)

**GET /api/main-system/{id}**
- Retrieves a single Main System record with template fields
- Response: `{ success: boolean, data: MainSystem & { templateFields: TemplateFieldValue[] } }`
- Status codes: 200 (success), 404 (not found)

**PUT /api/main-system/{id}**
- Updates an existing Main System record
- Request body: `MainSystemUpdateRequest`
- Response: `{ success: boolean, data: MainSystem, errors?: Record<string, string[]> }`
- Status codes: 200 (success), 422 (validation error), 404 (not found), 500 (server error)

**DELETE /api/main-system/{id}**
- Deletes a single Main System record
- Response: `{ success: boolean, message: string }`
- Status codes: 200 (success), 404 (not found), 500 (server error)

#### Bulk Action Endpoints

**POST /api/main-system/bulk/delete**
- Deletes multiple Main System records
- Request body: `{ recordIds: number[] }`
- Response: `{ success: boolean, deleted: number, failed: number, errors?: Record<number, string> }`
- Status codes: 200 (success), 422 (validation error), 500 (server error)

**POST /api/main-system/bulk/update-status**
- Updates status for multiple records
- Request body: `{ recordIds: number[], status: string }`
- Response: `{ success: boolean, updated: number, failed: number, errors?: Record<number, string> }`
- Status codes: 200 (success), 422 (validation error), 500 (server error)

**POST /api/main-system/bulk/update-category**
- Updates category for multiple records
- Request body: `{ recordIds: number[], category: string }`
- Response: `{ success: boolean, updated: number, failed: number, errors?: Record<number, string> }`
- Status codes: 200 (success), 422 (validation error), 500 (server error)

#### Audit Trail Endpoints

**GET /api/audit-trail?recordId={id}&action={action}&limit={limit}**
- Retrieves audit trail entries for a record
- Query parameters: `recordId` (required), `action` (optional), `limit` (default: 50)
- Response: `{ success: boolean, data: AuditTrailEntry[] }`
- Status codes: 200 (success), 404 (not found)

## Data Models

### MainSystem Model Updates

The existing `MainSystem` model requires no structural changes. The following attributes are used for CRUD operations:

**Core Attributes**:
- `id: integer` - Primary key
- `uid: string` - Unique identifier (unique constraint)
- `regs_no: string` - Registration number
- `registration_date: date` - Date of registration
- `first_name: string` - First name (required)
- `middle_name: string` - Middle name (nullable)
- `last_name: string` - Last name (required)
- `suffix: string` - Name suffix (nullable)
- `birthday: date` - Date of birth (nullable)
- `gender: enum` - Gender (Male, Female, Other, null)
- `civil_status: string` - Civil status (nullable)
- `address: text` - Street address (nullable)
- `barangay: string` - Barangay/district (nullable)
- `status: enum` - Record status (active, inactive, archived, null)
- `category: string` - Record category (nullable)
- `origin_batch_id: integer` - Foreign key to UploadBatch
- `created_at: timestamp` - Record creation timestamp
- `updated_at: timestamp` - Record last update timestamp

**Relationships**:
- `templateFieldValues()` - HasMany relationship to TemplateFieldValue
- `originBatch()` - BelongsTo relationship to UploadBatch

### Audit Trail Model

A new `AuditTrail` model is created to log all CRUD operations:

**Attributes**:
- `id: integer` - Primary key
- `user_id: integer` - ID of user performing action (foreign key)
- `action_type: enum` - Type of action (create, update, delete)
- `model_type: string` - Model class name (e.g., 'MainSystem')
- `model_id: integer` - ID of affected record
- `old_values: json` - Previous field values (null for create)
- `new_values: json` - New field values (null for delete)
- `changed_fields: json` - Array of field names that changed
- `reason: string` - Optional reason for change (nullable)
- `ip_address: string` - IP address of request
- `user_agent: string` - User agent string
- `created_at: timestamp` - Timestamp of action

**Indexes**:
- `(model_type, model_id, created_at)` - For querying record history
- `(user_id, created_at)` - For querying user actions
- `(action_type, created_at)` - For querying action types

### TemplateFieldValue Model (Existing)

The existing `TemplateFieldValue` model is used to store custom field values for records. No changes required.

## Request/Response Data Structures

### MainSystemCreateRequest

```typescript
interface MainSystemCreateRequest {
  uid: string                    // Required, must be unique
  regs_no?: string              // Optional
  registration_date?: string    // Optional, YYYY-MM-DD format
  first_name: string            // Required
  middle_name?: string          // Optional
  last_name: string             // Required
  suffix?: string               // Optional
  birthday?: string             // Optional, YYYY-MM-DD format
  gender?: 'Male' | 'Female' | 'Other' | null
  civil_status?: string         // Optional
  address?: string              // Optional
  barangay?: string             // Optional
  status?: 'active' | 'inactive' | 'archived' | null
  category?: string             // Optional
  templateFields?: Record<string, string>  // Optional custom fields
}
```

### MainSystemUpdateRequest

```typescript
interface MainSystemUpdateRequest {
  uid?: string                  // Optional, cannot change existing UID
  regs_no?: string             // Optional
  registration_date?: string   // Optional, YYYY-MM-DD format
  first_name?: string          // Optional
  middle_name?: string         // Optional
  last_name?: string           // Optional
  suffix?: string              // Optional
  birthday?: string            // Optional, YYYY-MM-DD format
  gender?: 'Male' | 'Female' | 'Other' | null
  civil_status?: string        // Optional
  address?: string             // Optional
  barangay?: string            // Optional
  status?: 'active' | 'inactive' | 'archived' | null
  category?: string            // Optional
  templateFields?: Record<string, string>  // Optional custom fields
}
```

### BulkDeleteRequest

```typescript
interface BulkDeleteRequest {
  recordIds: number[]  // Array of record IDs to delete
}
```

### BulkStatusUpdateRequest

```typescript
interface BulkStatusUpdateRequest {
  recordIds: number[]
  status: 'active' | 'inactive' | 'archived'
}
```

### BulkCategoryUpdateRequest

```typescript
interface BulkCategoryUpdateRequest {
  recordIds: number[]
  category: string
}
```

### AuditTrailEntry

```typescript
interface AuditTrailEntry {
  id: number
  user_id: number
  action_type: 'create' | 'update' | 'delete'
  model_type: string
  model_id: number
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  changed_fields: string[]
  reason?: string
  ip_address: string
  user_agent: string
  created_at: string  // ISO 8601 timestamp
}
```

## Error Handling

### Validation Errors

The system validates all input data before persistence:

**Field-Level Validation**:
- `uid`: Required, must be unique, max 255 characters
- `first_name`: Required, non-empty string, max 255 characters
- `last_name`: Required, non-empty string, max 255 characters
- `birthday`: Optional, must be valid date in YYYY-MM-DD format, cannot be in future
- `gender`: Optional, must be one of: Male, Female, Other
- `status`: Optional, must be one of: active, inactive, archived
- `category`: Optional, must exist in system configuration
- `registration_date`: Optional, must be valid date in YYYY-MM-DD format
- `address`: Optional, max 1000 characters
- `barangay`: Optional, max 255 characters
- `civil_status`: Optional, max 255 characters
- `suffix`: Optional, max 50 characters
- `middle_name`: Optional, max 255 characters

**Validation Error Response**:
```json
{
  "success": false,
  "errors": {
    "uid": ["The uid field is required.", "The uid must be unique."],
    "first_name": ["The first name field is required."],
    "birthday": ["The birthday must be a valid date."]
  }
}
```

### Database Errors

**Unique Constraint Violation**:
- When UID already exists, return 422 with specific error message
- Frontend displays: "UID already exists. Please use a different UID."

**Foreign Key Constraint Violation**:
- When referenced batch or category doesn't exist, return 422
- Frontend displays: "Invalid category or batch reference."

**Concurrency Errors**:
- If record is deleted by another user before update, return 404
- Frontend displays: "Record was deleted by another user. Please refresh."

### Server Errors

**Database Connection Errors**:
- Return 500 with generic error message
- Log detailed error for debugging
- Frontend displays: "Database error. Please try again later."

**Timeout Errors**:
- For bulk operations exceeding 30 seconds, return 408
- Frontend displays: "Operation timed out. Some records may not have been processed."

### Error Recovery Strategies

**Form Data Preservation**:
- On validation error, preserve all entered form data
- User can correct invalid fields and resubmit
- Only invalid fields display error messages

**Bulk Operation Partial Failure**:
- If some records fail in bulk operation, continue processing others
- Return response with `updated` and `failed` counts
- Include error details for failed records
- User can retry failed records

**Automatic Retry**:
- For transient errors (network timeouts), implement exponential backoff
- Maximum 3 retry attempts with 1s, 2s, 4s delays
- Log retry attempts for debugging

## Testing Strategy

### Unit Testing

Unit tests verify individual components and services in isolation:

**Validation Service Tests**:
- Test each validation rule independently
- Test edge cases (empty strings, null values, boundary dates)
- Test unique constraint validation
- Test custom field validation

**Audit Service Tests**:
- Test audit trail entry creation
- Test JSON serialization of old/new values
- Test audit trail retrieval and filtering

**Bulk Action Service Tests**:
- Test bulk delete with various record counts
- Test bulk status update with invalid statuses
- Test partial failure scenarios
- Test transaction rollback on error

**Model Tests**:
- Test MainSystem model relationships
- Test TemplateFieldValue associations
- Test AuditTrail model queries

### Property-Based Testing

Property-based tests verify universal properties across randomized inputs:

**Configuration**:
- Minimum 100 iterations per property test
- Use fast-check library for JavaScript/TypeScript
- Use Pest for PHP property tests

**Test Tagging**:
- Format: `Feature: main-system-crud-actions, Property {number}: {property_text}`
- Include in test comments for traceability

### Integration Testing

Integration tests verify component interactions:

**API Integration Tests**:
- Test complete CRUD workflows (create → read → update → delete)
- Test multi-select state persistence across pagination
- Test bulk operations with mixed success/failure scenarios
- Test audit trail logging for all operations

**Frontend Integration Tests**:
- Test modal open/close with form state preservation
- Test multi-select with pagination
- Test bulk action toolbar visibility and state
- Test confirmation dialogs and user interactions

### End-to-End Testing

E2E tests verify complete user workflows:

**User Workflows**:
- Create new record via modal
- Edit existing record and verify changes
- Delete record with confirmation
- Select multiple records and perform bulk delete
- Perform bulk status update across pages
- Verify audit trail entries after operations

### Test Coverage Requirements

- Minimum 80% code coverage for all services
- 100% coverage for validation logic
- 100% coverage for audit trail logging
- All error paths must be tested

## UI/UX Considerations

### Responsive Design

**Desktop (1024px+)**:
- CRUD modal displays at 600px width, centered on screen
- Multi-select checkboxes in first column
- Bulk action toolbar displays above table
- Full form layout with side-by-side fields where appropriate

**Tablet (768px-1023px)**:
- CRUD modal displays at 90% width with max 500px
- Multi-select checkboxes remain visible
- Bulk action toolbar stacks buttons vertically
- Form fields stack vertically

**Mobile (<768px)**:
- CRUD modal displays full-screen with padding
- Multi-select checkboxes remain visible
- Bulk action toolbar buttons full-width and stacked
- Form fields stack vertically
- Scrollable content area for long forms

### Accessibility Implementation

**Keyboard Navigation**:
- Tab key navigates through form fields
- Shift+Tab navigates backwards
- Enter key submits form
- Escape key closes modal
- Arrow keys navigate dropdown options

**Screen Reader Support**:
- All form fields have associated labels
- Modal has descriptive title
- Buttons have descriptive labels
- Error messages associated with fields via aria-describedby
- Selection state announced via aria-selected
- Bulk action count announced via aria-live

**Color Contrast**:
- All text meets WCAG AA standards (4.5:1 for normal text)
- Selected rows use color + additional visual indicator (not color alone)
- Error messages use color + icon

**Focus Management**:
- Modal opens with focus on first form field
- Modal closes with focus returned to trigger button
- Confirmation dialogs trap focus within dialog
- Focus visible indicator on all interactive elements

### User Interaction Flows

**Create Record Flow**:
1. User clicks "Create Record" button
2. Modal opens with empty form
3. User enters data
4. User clicks "Save"
5. Form validates
6. If valid: record created, modal closes, view refreshes
7. If invalid: error messages display, form data preserved

**Edit Record Flow**:
1. User clicks "Edit" on record row
2. Modal opens with record data pre-populated
3. User modifies fields
4. User clicks "Save"
5. Form validates
6. If valid: record updated, modal closes, view refreshes
7. If invalid: error messages display, form data preserved

**Delete Record Flow**:
1. User clicks "Delete" on record row
2. Confirmation dialog displays with record details
3. User clicks "Confirm"
4. Record deleted, view refreshes
5. Audit trail entry created

**Bulk Delete Flow**:
1. User selects multiple records via checkboxes
2. Bulk action toolbar appears
3. User clicks "Delete Selected"
4. Confirmation dialog displays with record count and preview
5. User clicks "Confirm"
6. Records deleted, view refreshes, selection cleared
7. Audit trail entries created for each record

**Bulk Status Update Flow**:
1. User selects multiple records
2. User clicks "Update Status" dropdown
3. User selects new status
4. Confirmation dialog displays with count and new status
5. User clicks "Confirm"
6. Status updated for all records, view refreshes
7. Audit trail entries created for each record

### Visual Feedback

**Loading States**:
- Spinner displayed during API requests
- Buttons disabled during processing
- Progress indicator for bulk operations

**Success States**:
- Toast notification after successful operation
- View automatically refreshes to show changes
- Selection cleared after bulk operations

**Error States**:
- Field-level error messages in red
- Error icon next to invalid fields
- Toast notification for server errors
- Retry button for failed operations

**Selection States**:
- Selected rows highlighted with background color
- Checkbox shows checked state
- "Select All" checkbox shows indeterminate state when partially selected
- Selection count displayed in toolbar



## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Acceptance Criteria Testing Prework

**Requirement 1: Create New Records via Modal**

1.1 WHEN the user clicks a "Create Record" button, THE CRUD_Modal SHALL display with empty form fields
  - Thoughts: This is a UI state test. We can verify that clicking the button triggers the modal to open with empty fields.
  - Testable: yes - example

1.2 THE CRUD_Modal SHALL include input fields for all core Main System attributes
  - Thoughts: This is testing that specific fields are present in the modal. We can verify all required fields exist.
  - Testable: yes - example

1.3 WHEN the user enters data and clicks "Save", THE Validation_Engine SHALL validate all required fields are populated
  - Thoughts: This is a universal rule about validation. For any set of required fields, if any are empty, validation should fail.
  - Testable: yes - property

1.4 IF validation fails, THEN THE CRUD_Modal SHALL display error messages next to invalid fields
  - Thoughts: For any invalid field, an error message should be displayed. This is a universal property.
  - Testable: yes - property

1.5 WHEN validation succeeds, THE Main_System record SHALL be persisted to the database
  - Thoughts: For any valid record data, after successful validation, the record should exist in the database.
  - Testable: yes - property

1.6 WHEN a record is successfully created, THE CRUD_Modal SHALL close and THE Main System view SHALL refresh
  - Thoughts: This is a UI state transition. After successful creation, the modal should close and view should refresh.
  - Testable: yes - example

1.7 WHEN the user clicks "Cancel", THE CRUD_Modal SHALL close without saving changes
  - Thoughts: Clicking cancel should close the modal without persisting any data.
  - Testable: yes - example

**Requirement 2: Update Existing Records via Modal**

2.1 WHEN the user clicks an "Edit" action on a record row, THE CRUD_Modal SHALL display with all current record data pre-populated
  - Thoughts: For any existing record, when edit is clicked, the modal should open with that record's data.
  - Testable: yes - property

2.2 THE CRUD_Modal SHALL display the record's ID and indicate it is in edit mode
  - Thoughts: This is a UI state test for edit mode indication.
  - Testable: yes - example

2.3 WHEN the user modifies field values and clicks "Save", THE Validation_Engine SHALL validate all required fields
  - Thoughts: Same as 1.3 - validation should apply to all updates.
  - Testable: yes - property

2.4 IF validation fails, THEN THE CRUD_Modal SHALL display error messages next to invalid fields
  - Thoughts: Same as 1.4 - error display is universal.
  - Testable: yes - property

2.5 WHEN validation succeeds, THE Main_System record SHALL be updated in the database with the new values
  - Thoughts: For any valid update, the database should reflect the new values.
  - Testable: yes - property

2.6 WHEN a record is successfully updated, THE CRUD_Modal SHALL close and THE Main System view SHALL refresh
  - Thoughts: UI state transition after successful update.
  - Testable: yes - example

2.7 WHEN the user clicks "Cancel", THE CRUD_Modal SHALL close without saving changes
  - Thoughts: Cancel should not persist changes.
  - Testable: yes - example

2.8 WHEN a record is updated, THE Audit_Trail SHALL log the update with timestamp and changed fields
  - Thoughts: For any record update, an audit entry should be created with the changes.
  - Testable: yes - property

**Requirement 3: Delete Individual Records**

3.1 WHEN the user clicks a "Delete" action on a record row, THE system SHALL display a confirmation dialog
  - Thoughts: Delete action should trigger confirmation dialog.
  - Testable: yes - example

3.2 THE confirmation dialog SHALL display the record's name and registration number
  - Thoughts: Confirmation dialog should contain record details.
  - Testable: yes - example

3.3 WHEN the user confirms deletion, THE Main_System record SHALL be deleted from the database
  - Thoughts: For any record, confirming deletion should remove it from the database.
  - Testable: yes - property

3.4 WHEN a record is successfully deleted, THE Main System view SHALL refresh and the record SHALL no longer appear
  - Thoughts: After deletion, the record should not be queryable.
  - Testable: yes - property

3.5 WHEN the user cancels the confirmation dialog, THE record SHALL remain unchanged
  - Thoughts: Canceling should not delete the record.
  - Testable: yes - property

3.6 WHEN a record is deleted, THE Audit_Trail SHALL log the deletion with timestamp and record details
  - Thoughts: For any deletion, an audit entry should be created.
  - Testable: yes - property

**Requirement 4: Multi-Select Interface with Checkboxes**

4.1 THE Main System table SHALL display a checkbox column as the first column in each row
  - Thoughts: This is a UI structure test.
  - Testable: yes - example

4.2 THE table header SHALL include a "Select All" checkbox
  - Thoughts: This is a UI structure test.
  - Testable: yes - example

4.3 WHEN a user clicks a row checkbox, THE Record_Selection state SHALL update to include or exclude that record
  - Thoughts: For any record, clicking its checkbox should toggle its selection state.
  - Testable: yes - property

4.4 WHEN the user clicks the "Select All" checkbox, ALL visible records on the current page SHALL be selected or deselected
  - Thoughts: Select All should toggle all visible records.
  - Testable: yes - property

4.5 WHEN records are selected, THE selected row background color SHALL change to indicate selection status
  - Thoughts: Selected rows should have visual indication.
  - Testable: yes - property

4.6 WHEN the Record_Selection state changes, THE bulk action controls SHALL become visible or hidden based on selection count
  - Thoughts: Bulk controls visibility depends on selection count.
  - Testable: yes - property

4.7 WHEN the user navigates to a different page, THE Record_Selection state SHALL be preserved for records on the current page only
  - Thoughts: Selection should persist across pagination.
  - Testable: yes - property

**Requirement 5: Bulk Delete Action**

5.1 WHEN one or more records are selected, THE bulk action toolbar SHALL display a "Delete Selected" button
  - Thoughts: Toolbar visibility depends on selection count.
  - Testable: yes - property

5.2 WHEN the user clicks "Delete Selected", THE system SHALL display a confirmation dialog showing the count
  - Thoughts: Bulk delete should show confirmation with count.
  - Testable: yes - example

5.3 THE confirmation dialog SHALL list the names and registration numbers of records to be deleted
  - Thoughts: Confirmation should show record details.
  - Testable: yes - example

5.4 WHEN the user confirms bulk deletion, ALL selected Main_System records SHALL be deleted from the database
  - Thoughts: For any set of selected records, confirming should delete all of them.
  - Testable: yes - property

5.5 WHEN bulk deletion completes, THE Main System view SHALL refresh and deleted records SHALL no longer appear
  - Thoughts: After bulk delete, records should not be queryable.
  - Testable: yes - property

5.6 WHEN the user cancels the confirmation dialog, NO records SHALL be deleted
  - Thoughts: Canceling should not delete any records.
  - Testable: yes - property

5.7 WHEN records are bulk deleted, THE Audit_Trail SHALL log each deletion with timestamp and record details
  - Thoughts: For each deleted record, an audit entry should be created.
  - Testable: yes - property

**Requirement 6: Bulk Status Update Action**

6.1 WHEN one or more records are selected, THE bulk action toolbar SHALL display a "Update Status" dropdown
  - Thoughts: Toolbar visibility depends on selection count.
  - Testable: yes - property

6.2 THE "Update Status" dropdown SHALL display available status options: active, inactive, archived
  - Thoughts: Dropdown should contain specific options.
  - Testable: yes - example

6.3 WHEN the user selects a status option, THE system SHALL display a confirmation dialog
  - Thoughts: Status selection should trigger confirmation.
  - Testable: yes - example

6.4 WHEN the user confirms the status update, ALL selected Main_System records SHALL have their status field updated
  - Thoughts: For any set of selected records and any valid status, all records should be updated.
  - Testable: yes - property

6.5 WHEN the status update completes, THE Main System view SHALL refresh to display updated status values
  - Thoughts: After update, view should reflect new status values.
  - Testable: yes - property

6.6 WHEN the user cancels the confirmation dialog, NO records SHALL be modified
  - Thoughts: Canceling should not modify any records.
  - Testable: yes - property

6.7 WHEN records are bulk updated, THE Audit_Trail SHALL log each update with timestamp, record ID, and new status value
  - Thoughts: For each updated record, an audit entry should be created.
  - Testable: yes - property

**Requirement 7: Bulk Category Update Action**

7.1 WHEN one or more records are selected, THE bulk action toolbar SHALL display an "Update Category" dropdown
  - Thoughts: Toolbar visibility depends on selection count.
  - Testable: yes - property

7.2 THE "Update Category" dropdown SHALL display available category options from the system configuration
  - Thoughts: Dropdown should contain configured categories.
  - Testable: yes - example

7.3 WHEN the user selects a category option, THE system SHALL display a confirmation dialog
  - Thoughts: Category selection should trigger confirmation.
  - Testable: yes - example

7.4 WHEN the user confirms the category update, ALL selected Main_System records SHALL have their category field updated
  - Thoughts: For any set of selected records and any valid category, all records should be updated.
  - Testable: yes - property

7.5 WHEN the category update completes, THE Main System view SHALL refresh to display updated category values
  - Thoughts: After update, view should reflect new category values.
  - Testable: yes - property

7.6 WHEN the user cancels the confirmation dialog, NO records SHALL be modified
  - Thoughts: Canceling should not modify any records.
  - Testable: yes - property

7.7 WHEN records are bulk updated, THE Audit_Trail SHALL log each update with timestamp, record ID, and new category value
  - Thoughts: For each updated record, an audit entry should be created.
  - Testable: yes - property

**Requirement 8: CRUD Modal Form Validation**

8.1 THE Validation_Engine SHALL require UID to be unique across all Main_System records
  - Thoughts: For any UID, if it already exists, validation should fail.
  - Testable: yes - property

8.2 THE Validation_Engine SHALL require First Name and Last Name to be non-empty strings
  - Thoughts: For any record, first and last names must be non-empty.
  - Testable: yes - property

8.3 THE Validation_Engine SHALL require Birthday to be a valid date in YYYY-MM-DD format if provided
  - Thoughts: If birthday is provided, it must be valid date format.
  - Testable: yes - property

8.4 THE Validation_Engine SHALL require Gender to be one of: Male, Female, Other, or empty
  - Thoughts: Gender must be from allowed set or empty.
  - Testable: yes - property

8.5 THE Validation_Engine SHALL require Status to be one of: active, inactive, archived, or empty
  - Thoughts: Status must be from allowed set or empty.
  - Testable: yes - property

8.6 THE Validation_Engine SHALL require Category to be a valid category from system configuration or empty
  - Thoughts: Category must be configured or empty.
  - Testable: yes - property

8.7 WHEN validation fails, THE CRUD_Modal SHALL display specific error messages for each invalid field
  - Thoughts: Each invalid field should have an error message.
  - Testable: yes - property

8.8 WHEN validation succeeds, THE form data SHALL be persisted without modification
  - Thoughts: Valid data should be persisted as-is.
  - Testable: yes - property

**Requirement 9: Bulk Action Toolbar Visibility and State**

9.1 WHEN no records are selected, THE bulk action toolbar SHALL be hidden
  - Thoughts: Toolbar visibility depends on selection count.
  - Testable: yes - property

9.2 WHEN one or more records are selected, THE bulk action toolbar SHALL display above the table
  - Thoughts: Toolbar visibility depends on selection count.
  - Testable: yes - property

9.3 THE bulk action toolbar SHALL display the count of selected records
  - Thoughts: Toolbar should show selection count.
  - Testable: yes - example

9.4 THE bulk action toolbar SHALL include a "Clear Selection" button
  - Thoughts: Toolbar should have clear button.
  - Testable: yes - example

9.5 WHEN the "Clear Selection" button is clicked, ALL selected records SHALL be deselected
  - Thoughts: Clear button should deselect all records.
  - Testable: yes - property

9.6 THE bulk action toolbar SHALL display action buttons: "Delete Selected", "Update Status", "Update Category"
  - Thoughts: Toolbar should have specific buttons.
  - Testable: yes - example

9.7 WHEN all records on the current page are deselected, THE "Select All" checkbox SHALL be unchecked
  - Thoughts: Select All checkbox state should reflect page selection.
  - Testable: yes - property

**Requirement 10: CRUD Modal Field Behavior**

10.1 THE CRUD_Modal UID field SHALL be a text input that is read-only when editing an existing record
  - Thoughts: UID should be read-only in edit mode.
  - Testable: yes - example

10.2 THE CRUD_Modal Birthday field SHALL be a date picker input
  - Thoughts: Birthday should use date picker.
  - Testable: yes - example

10.3 THE CRUD_Modal Gender field SHALL be a dropdown with options: Male, Female, Other, blank
  - Thoughts: Gender should be dropdown with specific options.
  - Testable: yes - example

10.4 THE CRUD_Modal Status field SHALL be a dropdown with options: active, inactive, archived, blank
  - Thoughts: Status should be dropdown with specific options.
  - Testable: yes - example

10.5 THE CRUD_Modal Category field SHALL be a dropdown with available categories from system configuration
  - Thoughts: Category should be dropdown with configured options.
  - Testable: yes - example

10.6 THE CRUD_Modal Address field SHALL be a textarea input
  - Thoughts: Address should use textarea.
  - Testable: yes - example

10.7 THE CRUD_Modal Registration Date field SHALL be a date picker input
  - Thoughts: Registration date should use date picker.
  - Testable: yes - example

10.8 WHEN the CRUD_Modal is displayed, THE form fields SHALL have appropriate placeholder text or labels
  - Thoughts: Fields should have labels/placeholders.
  - Testable: yes - example

**Requirement 11: Confirmation Dialogs for Destructive Actions**

11.1 WHEN a user initiates a delete action (single or bulk), THE system SHALL display a confirmation dialog
  - Thoughts: Delete actions should trigger confirmation.
  - Testable: yes - example

11.2 THE confirmation dialog SHALL clearly state the action being performed and the number of records affected
  - Thoughts: Confirmation should show action and count.
  - Testable: yes - example

11.3 THE confirmation dialog SHALL display record details (name, registration number) for single deletes
  - Thoughts: Single delete confirmation should show record details.
  - Testable: yes - example

11.4 THE confirmation dialog SHALL display a list of record details for bulk deletes (up to 10 records, with count if more)
  - Thoughts: Bulk delete confirmation should show preview.
  - Testable: yes - example

11.5 THE confirmation dialog SHALL include "Confirm" and "Cancel" buttons
  - Thoughts: Confirmation dialog should have these buttons.
  - Testable: yes - example

11.6 WHEN the user clicks "Confirm", THE delete operation SHALL proceed
  - Thoughts: Confirm button should execute delete.
  - Testable: yes - example

11.7 WHEN the user clicks "Cancel", THE delete operation SHALL be cancelled
  - Thoughts: Cancel button should not delete.
  - Testable: yes - example

**Requirement 12: CRUD Modal Error Handling**

12.1 IF a database error occurs during record creation, THE CRUD_Modal SHALL display a user-friendly error message
  - Thoughts: Database errors should be caught and displayed.
  - Testable: yes - example

12.2 IF a database error occurs during record update, THE CRUD_Modal SHALL display a user-friendly error message
  - Thoughts: Database errors should be caught and displayed.
  - Testable: yes - example

12.3 IF a database error occurs during record deletion, THE system SHALL display an error notification
  - Thoughts: Database errors should be caught and displayed.
  - Testable: yes - example

12.4 IF a validation error occurs, THE CRUD_Modal SHALL display specific field-level error messages
  - Thoughts: Validation errors should be field-specific.
  - Testable: yes - property

12.5 IF a unique constraint violation occurs (duplicate UID), THE CRUD_Modal SHALL display "UID already exists" error
  - Thoughts: Duplicate UID should show specific error.
  - Testable: yes - example

12.6 WHEN an error occurs, THE record data SHALL NOT be modified in the database
  - Thoughts: Errors should not cause partial updates.
  - Testable: yes - property

12.7 WHEN an error is displayed, THE user SHALL be able to correct the issue and retry the operation
  - Thoughts: Form data should be preserved for retry.
  - Testable: yes - property

**Requirement 13: Audit Trail Logging**

13.1 WHEN a record is created, THE Audit_Trail SHALL log: timestamp, user ID, action type (create), record ID, and new values
  - Thoughts: Create operations should be logged with all details.
  - Testable: yes - property

13.2 WHEN a record is updated, THE Audit_Trail SHALL log: timestamp, user ID, action type (update), record ID, and changed fields
  - Thoughts: Update operations should be logged with changed fields.
  - Testable: yes - property

13.3 WHEN a record is deleted, THE Audit_Trail SHALL log: timestamp, user ID, action type (delete), record ID, and deleted record values
  - Thoughts: Delete operations should be logged with record values.
  - Testable: yes - property

13.4 WHEN a bulk action is performed, THE Audit_Trail SHALL log each individual record change
  - Thoughts: Bulk operations should log each record individually.
  - Testable: yes - property

13.5 THE Audit_Trail logs SHALL be immutable and stored in a dedicated audit table
  - Thoughts: Audit logs should not be modifiable.
  - Testable: yes - property

13.6 THE Audit_Trail SHALL include the authenticated user's ID for all operations
  - Thoughts: All audit entries should have user ID.
  - Testable: yes - property

**Requirement 14: Main System View Refresh After Operations**

14.1 WHEN a record is created, THE Main System view SHALL refresh to display the new record
  - Thoughts: View should reflect new records.
  - Testable: yes - property

14.2 WHEN a record is updated, THE Main System view SHALL refresh to display the updated record
  - Thoughts: View should reflect updated records.
  - Testable: yes - property

14.3 WHEN a record is deleted, THE Main System view SHALL refresh and the deleted record SHALL no longer appear
  - Thoughts: View should not show deleted records.
  - Testable: yes - property

14.4 WHEN bulk operations complete, THE Main System view SHALL refresh to reflect all changes
  - Thoughts: View should reflect all bulk changes.
  - Testable: yes - property

14.5 WHEN the view refreshes, THE current search filter and pagination state SHALL be preserved
  - Thoughts: Refresh should preserve filter and pagination.
  - Testable: yes - property

14.6 WHEN the view refreshes, THE Record_Selection state SHALL be cleared
  - Thoughts: Refresh should clear selection.
  - Testable: yes - property

**Requirement 15: CRUD Modal Accessibility**

15.1 THE CRUD_Modal SHALL have a descriptive title that indicates create or edit mode
  - Thoughts: Modal should have descriptive title.
  - Testable: yes - example

15.2 ALL form fields SHALL have associated labels
  - Thoughts: All fields should have labels.
  - Testable: yes - example

15.3 ALL form fields SHALL have appropriate ARIA attributes for screen readers
  - Thoughts: Fields should have ARIA attributes.
  - Testable: yes - example

15.4 THE CRUD_Modal buttons SHALL be keyboard accessible and have descriptive labels
  - Thoughts: Buttons should be keyboard accessible.
  - Testable: yes - example

15.5 THE CRUD_Modal SHALL support keyboard navigation (Tab, Shift+Tab, Enter, Escape)
  - Thoughts: Modal should support keyboard navigation.
  - Testable: yes - example

15.6 WHEN the CRUD_Modal opens, keyboard focus SHALL move to the first form field
  - Thoughts: Focus should move to first field on open.
  - Testable: yes - example

15.7 WHEN the CRUD_Modal closes, keyboard focus SHALL return to the trigger element
  - Thoughts: Focus should return to trigger on close.
  - Testable: yes - example

**Requirement 16: Multi-Select Persistence Across Pagination**

16.1 WHEN a user selects records on page 1 and navigates to page 2, THE selected records from page 1 SHALL remain in the Record_Selection state
  - Thoughts: Selection should persist across pages.
  - Testable: yes - property

16.2 WHEN a user navigates back to page 1, THE previously selected records SHALL still be selected
  - Thoughts: Selection should persist when returning to page.
  - Testable: yes - property

16.3 WHEN the user performs a bulk action, ALL selected records across all pages SHALL be included in the operation
  - Thoughts: Bulk operations should include all selected records.
  - Testable: yes - property

16.4 WHEN the user clears the selection, ALL selected records across all pages SHALL be deselected
  - Thoughts: Clear should deselect all records across pages.
  - Testable: yes - property

16.5 WHEN the user performs a search or filter, THE Record_Selection state SHALL be cleared
  - Thoughts: Search/filter should clear selection.
  - Testable: yes - property

**Requirement 17: CRUD Modal Template Fields Support**

17.1 WHEN the CRUD_Modal is displayed for a record with associated Template_Fields, THE modal SHALL display sections for each template field
  - Thoughts: Modal should display template fields.
  - Testable: yes - example

17.2 WHEN a user enters values for template fields and clicks "Save", THE Template_Field values SHALL be persisted to the database
  - Thoughts: Template field values should be saved.
  - Testable: yes - property

17.3 WHEN editing a record, THE CRUD_Modal SHALL pre-populate template field values from the database
  - Thoughts: Template fields should be pre-populated on edit.
  - Testable: yes - property

17.4 WHEN a template field is updated, THE Audit_Trail SHALL log the change with field name and new value
  - Thoughts: Template field changes should be audited.
  - Testable: yes - property

17.5 IF a template field has validation rules, THE Validation_Engine SHALL apply those rules before saving
  - Thoughts: Template field validation should be applied.
  - Testable: yes - property

**Requirement 18: Bulk Action Confirmation with Record Preview**

18.1 WHEN a user initiates a bulk action, THE confirmation dialog SHALL display a preview of affected records
  - Thoughts: Confirmation should show record preview.
  - Testable: yes - example

18.2 THE preview SHALL show record names and registration numbers for up to 10 records
  - Thoughts: Preview should show record details.
  - Testable: yes - example

18.3 IF more than 10 records are affected, THE preview SHALL show "and X more records"
  - Thoughts: Preview should indicate additional records.
  - Testable: yes - example

18.4 THE confirmation dialog SHALL display the total count of affected records
  - Thoughts: Confirmation should show total count.
  - Testable: yes - example

18.5 WHEN the user confirms the bulk action, ALL affected records SHALL be processed
  - Thoughts: Confirm should process all records.
  - Testable: yes - property

18.6 WHEN the user cancels the bulk action, NO records SHALL be modified
  - Thoughts: Cancel should not modify any records.
  - Testable: yes - property

**Requirement 19: CRUD Modal Responsive Design**

19.1 THE CRUD_Modal SHALL display properly on desktop screens (1024px and wider)
  - Thoughts: Modal should display on desktop.
  - Testable: yes - example

19.2 THE CRUD_Modal SHALL display properly on tablet screens (768px to 1023px)
  - Thoughts: Modal should display on tablet.
  - Testable: yes - example

19.3 THE CRUD_Modal SHALL display properly on mobile screens (less than 768px)
  - Thoughts: Modal should display on mobile.
  - Testable: yes - example

19.4 ON mobile screens, THE CRUD_Modal form fields SHALL stack vertically
  - Thoughts: Fields should stack on mobile.
  - Testable: yes - example

19.5 ON mobile screens, THE CRUD_Modal buttons SHALL be full-width and stacked
  - Thoughts: Buttons should stack on mobile.
  - Testable: yes - example

19.6 THE CRUD_Modal SHALL have appropriate padding and spacing on all screen sizes
  - Thoughts: Spacing should be appropriate on all sizes.
  - Testable: yes - example

19.7 THE CRUD_Modal scrollable content area SHALL be accessible on small screens
  - Thoughts: Content should be scrollable on small screens.
  - Testable: yes - example

**Requirement 20: Bulk Action Progress Indication**

20.1 WHEN a bulk action is initiated, THE system SHALL display a progress indicator
  - Thoughts: Bulk actions should show progress.
  - Testable: yes - example

20.2 THE progress indicator SHALL show the number of records processed and total records to process
  - Thoughts: Progress should show counts.
  - Testable: yes - example

20.3 THE progress indicator SHALL update in real-time as records are processed
  - Thoughts: Progress should update during processing.
  - Testable: yes - property

20.4 WHEN the bulk action completes, THE progress indicator SHALL disappear and THE view SHALL refresh
  - Thoughts: Progress should disappear on completion.
  - Testable: yes - example

20.5 IF the bulk action fails, THE progress indicator SHALL display an error message
  - Thoughts: Progress should show errors.
  - Testable: yes - example

20.6 WHEN a bulk action is in progress, THE user SHALL NOT be able to initiate another bulk action
  - Thoughts: Bulk actions should be exclusive.
  - Testable: yes - property

**Requirement 21: CRUD Modal Data Persistence on Error**

21.1 WHEN validation fails in the CRUD_Modal, ALL entered form data SHALL be preserved in the form fields
  - Thoughts: Form data should persist on validation error.
  - Testable: yes - property

21.2 WHEN validation fails, ONLY the invalid fields SHALL display error messages
  - Thoughts: Only invalid fields should show errors.
  - Testable: yes - property

21.3 WHEN the user corrects the invalid fields and resubmits, THE previously entered data in other fields SHALL remain
  - Thoughts: Non-invalid fields should retain data.
  - Testable: yes - property

21.4 WHEN the user clicks "Cancel", THE form data SHALL be discarded
  - Thoughts: Cancel should clear form data.
  - Testable: yes - property

**Requirement 22: Bulk Action Deselection After Completion**

22.1 WHEN a bulk action completes successfully, ALL selected records SHALL be automatically deselected
  - Thoughts: Successful bulk action should clear selection.
  - Testable: yes - property

22.2 WHEN a bulk action fails, THE selected records SHALL remain selected so the user can retry
  - Thoughts: Failed bulk action should keep selection.
  - Testable: yes - property

22.3 WHEN records are deselected, THE bulk action toolbar SHALL hide
  - Thoughts: Toolbar should hide when no records selected.
  - Testable: yes - property

22.4 WHEN records are deselected, THE "Select All" checkbox SHALL be unchecked
  - Thoughts: Select All should be unchecked when no records selected.
  - Testable: yes - property

**Requirement 23: CRUD Modal Cancel Confirmation**

23.1 WHEN the user has entered data in the CRUD_Modal and clicks "Cancel", THE system SHALL check if data has changed
  - Thoughts: Cancel should check for changes.
  - Testable: yes - property

23.2 IF data has changed, THE system SHALL display a confirmation dialog asking "Discard changes?"
  - Thoughts: Changed data should trigger confirmation.
  - Testable: yes - example

23.3 IF the user confirms, THE CRUD_Modal SHALL close without saving
  - Thoughts: Confirming discard should close modal.
  - Testable: yes - example

23.4 IF the user cancels, THE CRUD_Modal SHALL remain open with data preserved
  - Thoughts: Canceling discard should keep modal open.
  - Testable: yes - example

23.5 IF no data has changed, THE CRUD_Modal SHALL close immediately without confirmation
  - Thoughts: Unchanged data should close immediately.
  - Testable: yes - property

**Requirement 24: Search and Filter Preservation**

24.1 WHEN a user performs a search and then creates, updates, or deletes a record, THE search criteria SHALL be preserved
  - Thoughts: CRUD operations should preserve search.
  - Testable: yes - property

24.2 WHEN the Main System view refreshes after a CRUD operation, THE search results SHALL be re-applied
  - Thoughts: Refresh should re-apply search.
  - Testable: yes - property

24.3 WHEN the user clears the search, THE full record list SHALL display
  - Thoughts: Clearing search should show all records.
  - Testable: yes - property

24.4 WHEN pagination is active and a CRUD operation occurs, THE current page number SHALL be preserved if possible
  - Thoughts: Pagination should be preserved when possible.
  - Testable: yes - property


### Property Reflection and Consolidation

After analyzing all acceptance criteria, I've identified the following testable properties. Several criteria can be consolidated:

- Validation properties (1.3, 2.3, 8.1-8.8) consolidate into universal validation rules
- Audit logging properties (2.8, 3.6, 5.7, 6.7, 7.7, 13.1-13.6) consolidate into comprehensive audit trail properties
- View refresh properties (14.1-14.4) consolidate into a single refresh property
- Toolbar visibility properties (4.6, 5.1, 6.1, 7.1, 9.1-9.2) consolidate into selection-based visibility
- Selection persistence properties (4.3, 4.4, 16.1-16.2) consolidate into multi-page selection
- Bulk operation properties (5.4, 6.4, 7.4) consolidate into universal bulk update pattern
- Error handling properties (12.4, 12.6, 12.7) consolidate into error recovery pattern
- Form data preservation properties (21.1-21.3) consolidate into single property

### Correctness Properties

**Property 1: Record Creation Persists Valid Data**

*For any* valid Main System record data (with required fields populated), when the record is created through the CRUD modal and validation succeeds, the record SHALL be persisted to the database and queryable by ID.

**Validates: Requirements 1.3, 1.5, 2.5**

**Property 2: Validation Rejects Invalid Data**

*For any* Main System record data with invalid fields (empty required fields, invalid date format, invalid enum values, duplicate UID), the validation engine SHALL reject the data and prevent persistence to the database.

**Validates: Requirements 1.3, 8.1-8.8**

**Property 3: Validation Errors Display Field-Specific Messages**

*For any* invalid field in the CRUD modal, when validation fails, an error message SHALL be displayed next to that field, and only invalid fields SHALL display errors.

**Validates: Requirements 1.4, 2.4, 8.7, 12.4, 21.2**

**Property 4: Form Data Persists on Validation Error**

*For any* form submission that fails validation, all entered form data SHALL be preserved in the form fields, allowing the user to correct errors and resubmit without re-entering unchanged fields.

**Validates: Requirements 21.1, 21.3**

**Property 5: Record Update Modifies Only Changed Fields**

*For any* existing record, when updated with new values and validation succeeds, only the provided fields SHALL be updated in the database, and unchanged fields SHALL retain their original values.

**Validates: Requirements 2.5**

**Property 6: Record Deletion Removes from Database**

*For any* Main System record, when deletion is confirmed, the record SHALL be removed from the database and no longer queryable by ID.

**Validates: Requirements 3.3, 3.4**

**Property 7: Bulk Operations Process All Selected Records**

*For any* set of selected records across multiple pages, when a bulk operation (delete, status update, or category update) is confirmed, ALL selected records SHALL be processed, regardless of which page they appear on.

**Validates: Requirements 5.4, 6.4, 7.4, 16.3**

**Property 8: Bulk Operations Fail Atomically or Succeed Completely**

*For any* bulk operation, either all selected records SHALL be successfully processed, or if any record fails, the operation SHALL be rolled back and no records SHALL be modified.

**Validates: Requirements 5.4, 6.4, 7.4**

**Property 9: Audit Trail Logs All CRUD Operations**

*For any* CRUD operation (create, update, delete), an audit trail entry SHALL be created containing: timestamp, user ID, action type, record ID, and changed field values. For bulk operations, each record SHALL have an individual audit entry.

**Validates: Requirements 2.8, 3.6, 5.7, 6.7, 7.7, 13.1-13.6**

**Property 10: Audit Trail is Immutable**

*For any* audit trail entry, once created, it SHALL NOT be modifiable or deletable, ensuring an immutable record of all system changes.

**Validates: Requirements 13.5**

**Property 11: View Refreshes After CRUD Operations**

*For any* CRUD operation (create, update, delete, or bulk operation), after the operation completes successfully, the Main System view SHALL refresh to reflect the changes, and the current search filter and pagination state SHALL be preserved.

**Validates: Requirements 14.1-14.5**

**Property 12: Selection Clears After Successful Bulk Operation**

*For any* successful bulk operation, all selected records SHALL be automatically deselected, the bulk action toolbar SHALL hide, and the "Select All" checkbox SHALL be unchecked.

**Validates: Requirements 22.1, 22.3, 22.4**

**Property 13: Selection Persists Across Pagination**

*For any* record selected on one page, when the user navigates to another page and returns, that record SHALL remain selected in the Record_Selection state.

**Validates: Requirements 4.7, 16.1-16.2**

**Property 14: Bulk Action Toolbar Visibility Depends on Selection**

*For any* page state, the bulk action toolbar SHALL be visible if and only if one or more records are selected. When selection count changes, toolbar visibility SHALL update accordingly.

**Validates: Requirements 4.6, 5.1, 6.1, 7.1, 9.1-9.2**

**Property 15: Select All Toggles All Visible Records**

*For any* page of records, clicking the "Select All" checkbox SHALL select or deselect all visible records on that page, and the checkbox state SHALL reflect whether all, some, or no records are selected.

**Validates: Requirements 4.4, 9.7**

**Property 16: Individual Record Selection Updates State**

*For any* record on the current page, clicking its checkbox SHALL toggle its selection state, and the Record_Selection state SHALL be updated to include or exclude that record.

**Validates: Requirements 4.3, 4.5**

**Property 17: Search and Filter Criteria Preserved After CRUD**

*For any* active search or filter criteria, when a CRUD operation completes, the search/filter criteria SHALL be preserved and re-applied to the refreshed view.

**Validates: Requirements 24.1-24.2, 24.4**

**Property 18: Search Criteria Cleared on Explicit Clear**

*For any* active search criteria, when the user explicitly clears the search, the full record list SHALL display without filtering.

**Validates: Requirements 24.3**

**Property 19: Modal Opens with Correct Initial State**

*For any* create operation, the CRUD modal SHALL open with empty form fields. For any edit operation, the CRUD modal SHALL open with all current record data pre-populated, including template field values.

**Validates: Requirements 1.1, 2.1, 17.3**

**Property 20: Modal Closes Without Saving on Cancel**

*For any* CRUD modal state, when the user clicks "Cancel" without making changes, the modal SHALL close immediately. If changes have been made, a confirmation dialog SHALL appear, and only if the user confirms SHALL the modal close without saving.

**Validates: Requirements 1.7, 2.7, 23.1, 23.5**

**Property 21: Template Field Values Persist with Record**

*For any* record with associated template fields, when template field values are entered and the record is saved, the template field values SHALL be persisted to the database and retrievable when the record is edited.

**Validates: Requirements 17.2-17.3**

**Property 22: Template Field Changes are Audited**

*For any* template field value change, an audit trail entry SHALL be created with the field name, old value, and new value.

**Validates: Requirements 17.4**

**Property 23: Bulk Operations Prevent Concurrent Execution**

*For any* bulk operation in progress, the user SHALL NOT be able to initiate another bulk operation until the current operation completes or fails.

**Validates: Requirements 20.6**

**Property 24: Progress Updates During Bulk Operations**

*For any* bulk operation, a progress indicator SHALL display showing the number of records processed and total records to process, and this progress SHALL update in real-time as records are processed.

**Validates: Requirements 20.2-20.3**

**Property 25: Errors Prevent Data Modification**

*For any* error condition (validation error, database error, constraint violation), the record data SHALL NOT be modified in the database, and the error SHALL be displayed to the user with sufficient information to correct the issue.

**Validates: Requirements 12.6, 12.7**

**Property 26: Bulk Operation Failure Preserves Selection**

*For any* bulk operation that fails, the selected records SHALL remain selected, allowing the user to retry the operation without re-selecting records.

**Validates: Requirements 22.2**

**Property 27: Selection Clears on Search or Filter**

*For any* search or filter operation, the Record_Selection state SHALL be cleared, deselecting all previously selected records.

**Validates: Requirements 16.5**



## Error Handling and Recovery

### Validation Error Handling

**Field-Level Validation**:
- Validate each field independently
- Display error message immediately next to field
- Preserve all other form data
- Allow user to correct and resubmit

**Validation Error Response Format**:
```json
{
  "success": false,
  "errors": {
    "uid": ["The uid field is required.", "The uid must be unique."],
    "first_name": ["The first name field is required."],
    "birthday": ["The birthday must be a valid date."]
  }
}
```

### Database Error Handling

**Connection Errors**:
- Catch database connection exceptions
- Display generic error: "Database error. Please try again later."
- Log detailed error for debugging
- Implement exponential backoff retry (1s, 2s, 4s)

**Constraint Violations**:
- Catch unique constraint violations
- Display specific error: "UID already exists. Please use a different UID."
- Preserve form data for correction

**Concurrency Errors**:
- Detect if record was deleted by another user
- Return 404 with message: "Record was deleted by another user. Please refresh."
- Refresh view to show current state

### Bulk Operation Error Handling

**Partial Failure Strategy**:
- Continue processing remaining records if one fails
- Return response with `updated` and `failed` counts
- Include error details for failed records
- Allow user to retry failed records

**Timeout Handling**:
- Set 30-second timeout for bulk operations
- Return 408 if timeout exceeded
- Display: "Operation timed out. Some records may not have been processed."
- Provide option to check status or retry

**Rollback Strategy**:
- For critical operations, use database transactions
- If any record fails, rollback entire operation
- Display error with count of affected records
- Preserve selection for retry

### User Feedback Mechanisms

**Toast Notifications**:
- Success: "Record created successfully" (2s duration)
- Success: "Record updated successfully" (2s duration)
- Success: "Record deleted successfully" (2s duration)
- Error: Display error message (5s duration)
- Error: Include "Retry" button for transient errors

**Modal Error Display**:
- Field-level errors displayed inline
- Server errors displayed in error banner at top of modal
- Error banner includes close button
- Form data preserved for correction

**Confirmation Dialogs**:
- Display action being performed
- Show count of affected records
- Display record preview (up to 10 records)
- Include "Confirm" and "Cancel" buttons

## Testing Strategy

### Unit Testing Approach

**Validation Service Tests**:
- Test each validation rule independently
- Test edge cases: empty strings, null values, boundary dates
- Test unique constraint validation with existing records
- Test custom field validation rules
- Test error message generation

**Audit Service Tests**:
- Test audit trail entry creation for each operation type
- Test JSON serialization of old/new values
- Test audit trail retrieval and filtering
- Test immutability of audit entries

**Bulk Action Service Tests**:
- Test bulk delete with various record counts (1, 10, 100, 1000)
- Test bulk status update with valid and invalid statuses
- Test bulk category update with valid and invalid categories
- Test partial failure scenarios
- Test transaction rollback on error
- Test progress tracking

**Model Tests**:
- Test MainSystem model relationships
- Test TemplateFieldValue associations
- Test AuditTrail model queries and scopes
- Test model validation rules

**Controller Tests**:
- Test CRUD endpoint request/response formats
- Test authentication and authorization
- Test error response formats
- Test pagination and filtering

### Property-Based Testing Approach

**Configuration**:
- Minimum 100 iterations per property test
- Use fast-check for JavaScript/TypeScript
- Use Pest for PHP property tests
- Tag each test with feature and property reference

**Test Tagging Format**:
```
Feature: main-system-crud-actions, Property {number}: {property_text}
```

**Property Test Examples**:

```typescript
// Property 1: Record Creation Persists Valid Data
describe('Property 1: Record Creation Persists Valid Data', () => {
  it('should persist valid records to database', () => {
    fc.assert(
      fc.property(mainSystemArbitrary(), async (record) => {
        const response = await createRecord(record)
        expect(response.success).toBe(true)
        
        const persisted = await getRecord(response.data.id)
        expect(persisted).toEqual(expect.objectContaining(record))
      }),
      { numRuns: 100 }
    )
  })
})

// Property 2: Validation Rejects Invalid Data
describe('Property 2: Validation Rejects Invalid Data', () => {
  it('should reject records with empty required fields', () => {
    fc.assert(
      fc.property(
        fc.record({
          uid: fc.string(),
          first_name: fc.constant(''),  // Empty required field
          last_name: fc.string(),
        }),
        async (record) => {
          const response = await createRecord(record)
          expect(response.success).toBe(false)
          expect(response.errors).toBeDefined()
        }
      ),
      { numRuns: 100 }
    )
  })
})

// Property 7: Bulk Operations Process All Selected Records
describe('Property 7: Bulk Operations Process All Selected Records', () => {
  it('should delete all selected records', () => {
    fc.assert(
      fc.property(
        fc.array(mainSystemArbitrary(), { minLength: 1, maxLength: 100 }),
        async (records) => {
          const created = await Promise.all(records.map(createRecord))
          const ids = created.map(r => r.data.id)
          
          const response = await bulkDelete({ recordIds: ids })
          expect(response.deleted).toBe(ids.length)
          
          for (const id of ids) {
            const record = await getRecord(id)
            expect(record).toBeNull()
          }
        }
      ),
      { numRuns: 100 }
    )
  })
})
```

### Integration Testing Approach

**API Integration Tests**:
- Test complete CRUD workflows (create → read → update → delete)
- Test multi-select state persistence across pagination
- Test bulk operations with mixed success/failure scenarios
- Test audit trail logging for all operations
- Test view refresh after operations

**Frontend Integration Tests**:
- Test modal open/close with form state preservation
- Test multi-select with pagination
- Test bulk action toolbar visibility and state
- Test confirmation dialogs and user interactions
- Test error handling and recovery

**Database Integration Tests**:
- Test transaction handling for bulk operations
- Test audit trail immutability
- Test constraint enforcement
- Test data consistency after operations

### End-to-End Testing Approach

**User Workflows**:
1. Create new record via modal
2. Edit existing record and verify changes
3. Delete record with confirmation
4. Select multiple records and perform bulk delete
5. Perform bulk status update across pages
6. Verify audit trail entries after operations
7. Test error scenarios and recovery

**Cross-Browser Testing**:
- Test on Chrome, Firefox, Safari, Edge
- Test on desktop, tablet, mobile viewports
- Test keyboard navigation and accessibility

### Test Coverage Requirements

- Minimum 80% code coverage for all services
- 100% coverage for validation logic
- 100% coverage for audit trail logging
- 100% coverage for error paths
- All acceptance criteria must have corresponding tests

### Performance Testing

**Bulk Operation Performance**:
- Test bulk delete with 1000 records
- Test bulk status update with 1000 records
- Verify operations complete within 30 seconds
- Monitor memory usage during operations

**Database Query Performance**:
- Test view refresh with 10,000 records
- Test search/filter performance
- Verify queries use appropriate indexes
- Monitor query execution time

