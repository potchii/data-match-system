# Data Match System - Frontend Design

**Feature Name:** Data Match System Frontend  
**Owner:** Ernest (Frontend & UI Developer)  
**Last Updated:** February 17, 2026

---

## 🏗️ Architecture Overview

### Component Hierarchy

```
App (Inertia Layout)
├── AuthLayout
│   ├── Login
│   ├── Register
│   └── Password Reset
├── AppLayout
│   ├── Header
│   │   ├── Logo
│   │   ├── Navigation
│   │   └── UserMenu
│   ├── Sidebar
│   │   ├── NavMain
│   │   ├── NavFooter
│   │   └── NavUser
│   └── MainContent
│       ├── Dashboard
│       ├── Upload
│       ├── MatchResults
│       ├── BatchHistory
│       └── Settings
└── SettingsLayout
    ├── SettingsSidebar
    └── SettingsContent
```

---

## 📄 Page Designs

### 1. Dashboard Page

**Route:** `/dashboard`  
**Layout:** AppLayout

**Components:**
```
Dashboard
├── Heading (title: "Dashboard")
├── BatchSummary (stats widget)
├── RecentBatches (table of last 5 batches)
└── QuickActions (buttons to upload, view results)
```

**Data Flow:**
```
Dashboard Component
  ├── useEffect: fetch recent batches
  ├── useState: batches, loading, error
  └── render: display summary and recent batches
```

**UI Elements:**
- Page title with icon
- 4 stat cards (Total Uploads, Total Matched, Total New, Total Duplicates)
- Recent batches table (5 rows)
- Quick action buttons

---

### 2. Upload Page

**Route:** `/upload`  
**Layout:** AppLayout

**Components:**
```
Upload
├── Heading (title: "Upload Records")
├── Instructions (text explaining process)
├── FileUploadForm
│   ├── FileInput
│   ├── FilePreview
│   ├── SubmitButton
│   └── ValidationMessages
└── UploadProgress (conditional)
```

**Data Flow:**
```
Upload Component
  ├── useState: file, uploading, error, success
  ├── useFileValidation: validate file
  ├── handleFileChange: update file state
  ├── handleSubmit: POST to /api/upload
  └── render: form or success message
```

**UI Elements:**
- Drag-and-drop file input
- File type and size validation messages
- Submit button (disabled until file selected)
- Progress bar during upload
- Success/error messages

**File Validation Rules:**
- Accepted types: `.xlsx`, `.xls`, `.csv`
- Max size: 10MB
- Required: file must be selected

---

### 3. Match Results Page

**Route:** `/batches/:id/results`  
**Layout:** AppLayout

**Components:**
```
MatchResults
├── Heading (title: "Match Results")
├── BatchSummary (stats widget)
├── ResultsToolbar
│   ├── StatusFilter (dropdown)
│   ├── SearchInput
│   ├── ExportButton
│   └── BulkActionsMenu
├── ResultsTable
│   ├── TableHeader (with sort)
│   ├── TableBody (with rows)
│   └── TablePagination
└── MatchDetailsModal (conditional)
```

**Data Flow:**
```
MatchResults Component
  ├── useParams: get batchId
  ├── useState: results, filter, sort, page, selectedRows
  ├── useEffect: fetch results
  ├── handleFilterChange: update filter
  ├── handleSort: update sort
  ├── handleRowClick: open modal
  └── render: table with filters
```

**Table Columns:**
1. Checkbox (for bulk actions)
2. Uploaded Name (first_name + last_name)
3. Matched Name (first_name + last_name)
4. Confidence % (0-100)
5. Status (badge)
6. Actions (view details, approve, reject)

**Status Badges:**
- MATCHED: Green background, checkmark icon
- POSSIBLE DUPLICATE: Yellow background, warning icon
- NEW RECORD: Red background, plus icon

**Sorting:**
- By Confidence % (ascending/descending)
- By Status
- By Upload Date

**Filtering:**
- By Status (dropdown)
- By Search (name search)

---

### 4. Batch History Page

**Route:** `/batches`  
**Layout:** AppLayout

**Components:**
```
BatchHistory
├── Heading (title: "Batch History")
├── HistoryToolbar
│   ├── SearchInput
│   ├── DateRangeFilter
│   └── StatusFilter
├── BatchList
│   ├── BatchCard (for each batch)
│   │   ├── FileName
│   │   ├── UploadDate
│   │   ├── Status
│   │   ├── Stats
│   │   └── Actions
│   └── Pagination
└── DeleteConfirmationDialog (conditional)
```

**Data Flow:**
```
BatchHistory Component
  ├── useState: batches, filter, search, page
  ├── useEffect: fetch batches
  ├── handleSearch: filter batches
  ├── handleDelete: delete batch with confirmation
  └── render: batch list
```

**Batch Card Layout:**
- File name (bold)
- Upload date (formatted)
- Status badge
- Stats: Total, Matched, New, Duplicates
- Action buttons: View, Export, Delete

**Pagination:**
- 10 batches per page
- Previous/Next buttons
- Page indicator

---

### 5. Settings Page

**Route:** `/settings`  
**Layout:** SettingsLayout

**Components:**
```
Settings
├── SettingsSidebar
│   ├── ProfileLink
│   ├── PreferencesLink
│   ├── NotificationsLink
│   └── SecurityLink
└── SettingsContent
    ├── ProfileSettings
    ├── PreferenceSettings
    ├── NotificationSettings
    └── SecuritySettings
```

**Settings Sections:**
1. **Profile:** Name, Email, Avatar
2. **Preferences:** Theme, Page Size, Language
3. **Notifications:** Email alerts, Upload notifications
4. **Security:** Password, Two-factor auth

---

## 🎨 Design System

### Color Palette

```
Primary: #3B82F6 (Blue)
Success: #10B981 (Green)
Warning: #F59E0B (Amber)
Danger: #EF4444 (Red)
Gray: #6B7280 (Gray-500)

Status Colors:
- MATCHED: #10B981 (Green)
- POSSIBLE DUPLICATE: #F59E0B (Amber)
- NEW RECORD: #EF4444 (Red)
- PROCESSING: #3B82F6 (Blue)
```

### Typography

```
Headings: Inter, Bold
Body: Inter, Regular
Code: Mono, Regular

Sizes:
- H1: 32px (2rem)
- H2: 24px (1.5rem)
- H3: 20px (1.25rem)
- Body: 16px (1rem)
- Small: 14px (0.875rem)
```

### Spacing

```
xs: 4px (0.25rem)
sm: 8px (0.5rem)
md: 16px (1rem)
lg: 24px (1.5rem)
xl: 32px (2rem)
2xl: 48px (3rem)
```

### Components

**Buttons:**
- Primary: Blue background, white text
- Secondary: Gray background, gray text
- Danger: Red background, white text
- Disabled: Gray background, gray text, opacity 50%

**Inputs:**
- Border: 1px solid gray-300
- Focus: Blue border, blue shadow
- Error: Red border, red text

**Cards:**
- Background: White
- Border: 1px solid gray-200
- Shadow: 0 1px 3px rgba(0,0,0,0.1)
- Padding: 16px

**Tables:**
- Header: Gray background (gray-100)
- Rows: White background, alternating gray-50
- Borders: 1px solid gray-200
- Padding: 12px

---

## 🔄 Data Models

### Batch Model
```typescript
interface Batch {
  id: string
  fileName: string
  uploadedBy: string
  uploadedAt: Date
  status: 'processing' | 'completed' | 'failed'
  totalRecords: number
  matchedCount: number
  newCount: number
  duplicateCount: number
  errorMessage?: string
}
```

### MatchResult Model
```typescript
interface MatchResult {
  id: string
  batchId: string
  uploadedRecord: {
    firstName: string
    lastName: string
    middleName?: string
    birthday?: Date
    gender?: string
    address?: string
  }
  matchedRecord?: {
    id: string
    firstName: string
    lastName: string
    middleName?: string
    birthday?: Date
    gender?: string
    address?: string
  }
  status: 'matched' | 'possible_duplicate' | 'new'
  confidenceScore: number
  matchDetails: {
    firstNameMatch: boolean
    lastNameMatch: boolean
    birthdayMatch: boolean
    addressMatch: boolean
  }
}
```

### User Model
```typescript
interface User {
  id: string
  email: string
  name: string
  avatar?: string
  preferences: {
    theme: 'light' | 'dark'
    pageSize: number
    language: string
  }
}
```

---

## 🔌 API Integration

### Endpoints Used

**Upload:**
```
POST /api/upload
Content-Type: multipart/form-data
Body: { file: File }
Response: { batchId: string, message: string }
```

**Get Batch Results:**
```
GET /api/batches/:id/results?page=1&filter=all&sort=confidence
Response: {
  results: MatchResult[],
  pagination: { page, total, perPage },
  summary: { total, matched, new, duplicates }
}
```

**Get Batches:**
```
GET /api/batches?page=1&search=&status=
Response: {
  batches: Batch[],
  pagination: { page, total, perPage }
}
```

**Export Results:**
```
GET /api/batches/:id/export
Response: File (Excel)
```

**Delete Batch:**
```
DELETE /api/batches/:id
Response: { message: string }
```

---

## 🧩 Reusable Components

### UI Components (Radix UI based)

```typescript
// Already available in project
- Button
- Dialog
- Dropdown Menu
- Select
- Input
- Label
- Checkbox
- Separator
- Avatar
- Badge
- Tooltip

// To create
- StatusBadge (extends Badge)
- FileInput (custom)
- ProgressBar (custom)
- Toast (custom)
- Table (custom wrapper)
- Pagination (custom)
```

### Custom Components

```typescript
// Layout Components
- AppShell
- Sidebar
- Header
- MainContent

// Feature Components
- BatchSummary
- ResultsTable
- MatchDetailsModal
- FileUploadForm
- BatchList
- StatusBadge

// Utility Components
- LoadingSpinner
- ErrorBoundary
- EmptyState
- ConfirmationDialog
```

---

## 🎯 State Management

### Global State (Inertia Props)

```typescript
interface AppProps {
  auth: {
    user: User
  }
  batches?: Batch[]
  results?: MatchResult[]
  batch?: Batch
}
```

### Local State (Component State)

```typescript
// Upload Component
- file: File | null
- uploading: boolean
- error: string | null
- success: boolean

// MatchResults Component
- results: MatchResult[]
- filter: string
- sort: string
- page: number
- selectedRows: string[]
- loading: boolean
- error: string | null

// BatchHistory Component
- batches: Batch[]
- search: string
- page: number
- loading: boolean
- error: string | null
```

---

## 🔐 Security Considerations

1. **File Upload:**
   - Validate file type on client (extension check)
   - Validate file size on client (max 10MB)
   - Backend will perform additional validation

2. **Data Display:**
   - Sanitize all user input before display
   - Use React's built-in XSS protection
   - Never use dangerouslySetInnerHTML

3. **API Calls:**
   - Use CSRF tokens (Inertia handles this)
   - Validate all responses
   - Handle 401/403 errors appropriately

4. **Authentication:**
   - Check user is authenticated before rendering
   - Redirect to login if not authenticated
   - Use Inertia's auth middleware

---

## 📱 Responsive Design

### Breakpoints (Tailwind)

```
sm: 640px
md: 768px
lg: 1024px
xl: 1280px
2xl: 1536px
```

### Mobile Optimizations

1. **Tables:** Stack columns on mobile, show key info only
2. **Forms:** Full width inputs on mobile
3. **Navigation:** Hamburger menu on mobile
4. **Modals:** Full screen on mobile
5. **Buttons:** Larger touch targets (44x44px minimum)

---

## ⚡ Performance Considerations

1. **Code Splitting:**
   - Lazy load pages with React.lazy()
   - Lazy load modals and dialogs

2. **Image Optimization:**
   - Use next-gen formats (WebP)
   - Lazy load images
   - Optimize file sizes

3. **Component Optimization:**
   - Use React.memo for expensive components
   - Memoize callbacks with useCallback
   - Memoize values with useMemo

4. **Bundle Size:**
   - Tree shake unused code
   - Minimize dependencies
   - Use dynamic imports

---

## ♿ Accessibility

1. **Keyboard Navigation:**
   - All interactive elements accessible via Tab
   - Escape key closes modals
   - Enter key submits forms

2. **Screen Readers:**
   - Proper heading hierarchy (h1, h2, h3)
   - ARIA labels on buttons and icons
   - ARIA live regions for dynamic content
   - Form labels associated with inputs

3. **Color Contrast:**
   - All text meets WCAG AA standards (4.5:1)
   - Don't rely on color alone for meaning
   - Use icons + color for status

4. **Focus Management:**
   - Visible focus indicators
   - Focus trap in modals
   - Focus restoration after modal close

---

## 🧪 Testing Strategy

### Unit Tests
- Component rendering
- Event handlers
- Conditional rendering
- Props validation

### Integration Tests
- Form submission
- API calls
- Navigation
- Error handling

### E2E Tests
- Upload workflow
- View results workflow
- Batch history workflow
- Export workflow

---

## 📚 File Structure

```
resources/js/
├── pages/
│   ├── dashboard.tsx
│   ├── upload.tsx
│   ├── match-results.tsx
│   ├── batch-history.tsx
│   └── settings/
│       ├── profile.tsx
│       ├── preferences.tsx
│       ├── notifications.tsx
│       └── security.tsx
├── components/
│   ├── batch-summary.tsx
│   ├── results-table.tsx
│   ├── status-badge.tsx
│   ├── match-details-modal.tsx
│   ├── file-upload-form.tsx
│   ├── batch-list.tsx
│   ├── batch-status-badge.tsx
│   └── ui/
│       ├── button.tsx
│       ├── dialog.tsx
│       ├── input.tsx
│       └── ... (existing Radix UI components)
├── hooks/
│   ├── use-file-validation.ts
│   ├── use-batch-results.ts
│   └── use-batches.ts
├── types/
│   ├── batch.ts
│   ├── match-result.ts
│   └── user.ts
└── lib/
    ├── api.ts
    └── utils.ts
```

---

## 🚀 Implementation Order

1. **Week 1:** Dashboard, Upload page, basic layout
2. **Week 2:** Match results table, batch summary
3. **Week 3:** Batch history, status indicators
4. **Week 4:** Export, bulk actions, settings
5. **Week 5:** Polish, optimization, accessibility

---

## 📋 Correctness Properties

### Property 1: File Upload Validation
**Property:** All uploaded files must be validated for type and size before submission.

**Implementation:**
```typescript
// File must be one of accepted types
acceptedTypes.includes(file.type)

// File size must not exceed limit
file.size <= MAX_FILE_SIZE

// Both conditions must be true before submit enabled
isValid = typeValid && sizeValid
```

### Property 2: Match Results Display
**Property:** All match results must display with correct status badge and confidence score.

**Implementation:**
```typescript
// Status must be one of valid statuses
validStatuses.includes(result.status)

// Confidence score must be 0-100
result.confidenceScore >= 0 && result.confidenceScore <= 100

// Status badge color must match status
statusColorMap[result.status] exists
```

### Property 3: Pagination Consistency
**Property:** Pagination must always show correct page and total count.

**Implementation:**
```typescript
// Current page must be >= 1
currentPage >= 1

// Current page must be <= total pages
currentPage <= totalPages

// Items per page must be consistent
itemsPerPage === ITEMS_PER_PAGE
```

### Property 4: Form Validation
**Property:** Form submission must be prevented if validation fails.

**Implementation:**
```typescript
// Submit button disabled if form invalid
submitDisabled = !isFormValid

// Error messages shown for invalid fields
showError = fieldInvalid && fieldTouched

// Form cannot be submitted with errors
canSubmit = allFieldsValid
```

### Property 5: Error Handling
**Property:** All errors must be caught and displayed to user.

**Implementation:**
```typescript
// API errors caught and displayed
try {
  await apiCall()
} catch (error) {
  showErrorMessage(error)
}

// Network errors handled
if (!response.ok) {
  showErrorMessage('Network error')
}

// User sees actionable error message
errorMessage.length > 0 && errorMessage.includes('action')
```

---

## 🔍 Validation Rules

### File Upload
- File type: `.xlsx`, `.xls`, `.csv` only
- File size: Max 10MB
- File required: Cannot be empty

### Search/Filter
- Search string: Max 255 characters
- Filter values: Must be from predefined list
- Page number: Must be positive integer

### Form Inputs
- Email: Valid email format
- Name: Max 255 characters
- Date: Valid date format

---

## 📊 Success Metrics

- ✅ All pages render without errors
- ✅ All forms validate correctly
- ✅ All API calls handled properly
- ✅ All error states display correctly
- ✅ Mobile responsive on all screen sizes
- ✅ Keyboard accessible
- ✅ Lighthouse score > 90
- ✅ Zero console errors
- ✅ All tests passing
