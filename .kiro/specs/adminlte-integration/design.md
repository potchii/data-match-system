# Design Document: AdminLTE Integration

## Overview

This design document outlines the technical approach for refactoring the Data Match System from a React/Inertia frontend to an AdminLTE-based Blade template frontend. The refactoring will replace the client-side rendering approach with server-side Blade templates while preserving all existing backend functionality, business logic, and data structures.

The migration strategy focuses on:
- Removing React/Inertia dependencies and replacing with Blade templates
- Integrating AdminLTE 3.x for consistent admin UI components
- Maintaining existing controllers, services, and models without modification
- Preserving authentication via Laravel Fortify
- Ensuring all existing functionality remains intact

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         AdminLTE UI (Blade Templates)                 │  │
│  │  - Dashboard  - Upload  - Results  - Batch History   │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼ HTTP Requests
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                       │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                   Routes (web.php)                    │  │
│  │         Auth Middleware (Laravel Fortify)             │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                    Controllers                        │  │
│  │  - UploadController (existing)                        │  │
│  │  - DashboardController (new)                          │  │
│  │  - ResultsController (new)                            │  │
│  │  - BatchController (new)                              │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                     Services                          │  │
│  │  - DataMappingService (existing)                      │  │
│  │  - DataMatchService (existing)                        │  │
│  └──────────────────────────────────────────────────────┘  │
│                            │                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                      Models                           │  │
│  │  - User  - MainSystem  - UploadBatch  - MatchResult  │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                       Database                               │
│  - users  - main_system  - upload_batches  - match_results  │
└─────────────────────────────────────────────────────────────┘
```

### Key Architectural Changes

1. **Rendering Strategy**: Shift from client-side rendering (React) to server-side rendering (Blade)
2. **State Management**: Remove client-side state management; use traditional request-response cycle
3. **Asset Pipeline**: Replace React build process with AdminLTE static assets
4. **Navigation**: Replace Inertia's SPA navigation with traditional page loads

### Preserved Components

- All Eloquent models remain unchanged
- All service classes remain unchanged
- Authentication system (Laravel Fortify) remains unchanged
- Database schema remains unchanged
- File upload and processing logic remains unchanged

## Components and Interfaces

### Blade Layout Structure

#### Base Layout (`layouts/admin.blade.php`)

The base AdminLTE layout that all pages will extend:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Data Match System')</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        @include('layouts.partials.navbar')
        @include('layouts.partials.sidebar')
        
        <div class="content-wrapper">
            @include('layouts.partials.alerts')
            @yield('content')
        </div>
        
        @include('layouts.partials.footer')
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    @stack('scripts')
</body>
</html>
```

#### Sidebar Partial (`layouts/partials/sidebar.blade.php`)

```php
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">Data Match System</span>
    </a>
    
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('upload.index') }}" class="nav-link {{ request()->routeIs('upload.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-upload"></i>
                        <p>Upload</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('results.index') }}" class="nav-link {{ request()->routeIs('results.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Match Results</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('batches.index') }}" class="nav-link {{ request()->routeIs('batches.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Batch History</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
```

#### Navbar Partial (`layouts/partials/navbar.blade.php`)

```php
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
    </ul>
    
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i> {{ Auth::user()->name }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>
```

#### Alerts Partial (`layouts/partials/alerts.blade.php`)

```php
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-3">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-3">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show m-3">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

### Page Templates

#### Dashboard Page (`pages/dashboard.blade.php`)

```php
@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Dashboard</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalBatches }}</h3>
                        <p>Total Batches</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $matchedRecords }}</h3>
                        <p>Matched Records</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $newRecords }}</h3>
                        <p>New Records</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $possibleDuplicates }}</h3>
                        <p>Possible Duplicates</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Upload Activity</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>File Name</th>
                                    <th>Uploaded By</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBatches as $batch)
                                <tr>
                                    <td>{{ $batch->id }}</td>
                                    <td>{{ $batch->file_name }}</td>
                                    <td>{{ $batch->uploaded_by }}</td>
                                    <td>{{ $batch->uploaded_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if($batch->status === 'COMPLETED')
                                            <span class="badge badge-success">{{ $batch->status }}</span>
                                        @elseif($batch->status === 'FAILED')
                                            <span class="badge badge-danger">{{ $batch->status }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ $batch->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
```

#### Upload Page (`pages/upload.blade.php`)

```php
@extends('layouts.admin')

@section('title', 'Upload File')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Upload File</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upload Excel or CSV File</h3>
                    </div>
                    <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="file">Select File</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                    <label class="custom-file-label" for="file">Choose file</label>
                                </div>
                                <small class="form-text text-muted">
                                    Accepted formats: .xlsx, .xls, .csv (Max size: 10MB)
                                </small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// Update file label with selected filename
$('#file').on('change', function() {
    const fileName = $(this).val().split('\\').pop();
    $(this).next('.custom-file-label').html(fileName);
});

// Client-side validation
$('#uploadForm').on('submit', function(e) {
    const fileInput = $('#file')[0];
    const file = fileInput.files[0];
    
    if (!file) {
        e.preventDefault();
        alert('Please select a file');
        return false;
    }
    
    // Check file size (10MB = 10485760 bytes)
    if (file.size > 10485760) {
        e.preventDefault();
        alert('File size must not exceed 10MB');
        return false;
    }
    
    // Check file extension
    const allowedExtensions = ['xlsx', 'xls', 'csv'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
        e.preventDefault();
        alert('Please select a valid Excel or CSV file');
        return false;
    }
    
    // Show loading state
    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
});
</script>
@endpush
```

#### Match Results Page (`pages/results.blade.php`)

```php
@extends('layouts.admin')

@section('title', 'Match Results')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Match Results</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Match Results</h3>
                        <div class="card-tools">
                            <form method="GET" action="{{ route('results.index') }}" class="form-inline">
                                <div class="form-group mr-2">
                                    <select name="batch_id" class="form-control form-control-sm">
                                        <option value="">All Batches</option>
                                        @foreach($batches as $batch)
                                            <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                                Batch #{{ $batch->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="">All Statuses</option>
                                        <option value="MATCHED" {{ request('status') == 'MATCHED' ? 'selected' : '' }}>Matched</option>
                                        <option value="POSSIBLE DUPLICATE" {{ request('status') == 'POSSIBLE DUPLICATE' ? 'selected' : '' }}>Possible Duplicate</option>
                                        <option value="NEW RECORD" {{ request('status') == 'NEW RECORD' ? 'selected' : '' }}>New Record</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Uploaded Record ID</th>
                                    <th>Match Status</th>
                                    <th>Confidence Score</th>
                                    <th>Matched System ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                <tr>
                                    <td>{{ $result->batch_id }}</td>
                                    <td>{{ $result->uploaded_record_id }}</td>
                                    <td>
                                        @if($result->match_status === 'MATCHED')
                                            <span class="badge badge-success">{{ $result->match_status }}</span>
                                        @elseif($result->match_status === 'POSSIBLE DUPLICATE')
                                            <span class="badge badge-warning">{{ $result->match_status }}</span>
                                        @else
                                            <span class="badge badge-info">{{ $result->match_status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $result->confidence_score }}%</td>
                                    <td>{{ $result->matched_system_id ?? 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No results found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $results->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
```

#### Batch History Page (`pages/batches.blade.php`)

```php
@extends('layouts.admin')

@section('title', 'Batch History')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Batch History</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Upload Batches</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>File Name</th>
                                    <th>Uploaded By</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batches as $batch)
                                <tr style="cursor: pointer;" onclick="window.location='{{ route('results.index', ['batch_id' => $batch->id]) }}'">
                                    <td>{{ $batch->id }}</td>
                                    <td>{{ $batch->file_name }}</td>
                                    <td>{{ $batch->uploaded_by }}</td>
                                    <td>{{ $batch->uploaded_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if($batch->status === 'COMPLETED')
                                            <span class="badge badge-success">{{ $batch->status }}</span>
                                        @elseif($batch->status === 'FAILED')
                                            <span class="badge badge-danger">{{ $batch->status }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ $batch->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('results.index', ['batch_id' => $batch->id]) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View Results
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No batches found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $batches->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
```

### Controllers

#### DashboardController (New)

```php
<?php

namespace App\Http\Controllers;

use App\Models\MatchResult;
use App\Models\UploadBatch;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBatches = UploadBatch::count();
        $matchedRecords = MatchResult::where('match_status', 'MATCHED')->count();
        $newRecords = MatchResult::where('match_status', 'NEW RECORD')->count();
        $possibleDuplicates = MatchResult::where('match_status', 'POSSIBLE DUPLICATE')->count();
        $recentBatches = UploadBatch::orderBy('uploaded_at', 'desc')->take(10)->get();
        
        return view('pages.dashboard', compact(
            'totalBatches',
            'matchedRecords',
            'newRecords',
            'possibleDuplicates',
            'recentBatches'
        ));
    }
}
```

#### UploadController (Modified)

```php
<?php

namespace App\Http\Controllers;

use App\Imports\RecordImport;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function index()
    {
        return view('pages.upload');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $batch = UploadBatch::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => auth()->user()->name ?? 'System Admin',
                'uploaded_at' => now(),
                'status' => 'PROCESSING',
            ]);

            Excel::import(new RecordImport($batch->id), $request->file('file'));

            $batch->update(['status' => 'COMPLETED']);

            return redirect()->route('upload.index')
                ->with('success', "Batch #{$batch->id} (File: {$batch->file_name}) processed successfully.");

        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update(['status' => 'FAILED']);
            }

            return redirect()->route('upload.index')
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
```

#### ResultsController (New)

```php
<?php

namespace App\Http\Controllers;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        $query = MatchResult::query()->with('batch');
        
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('match_status', $request->status);
        }
        
        $results = $query->orderBy('created_at', 'desc')->paginate(20);
        $batches = UploadBatch::orderBy('id', 'desc')->get();
        
        return view('pages.results', compact('results', 'batches'));
    }
}
```

#### BatchController (New)

```php
<?php

namespace App\Http\Controllers;

use App\Models\UploadBatch;

class BatchController extends Controller
{
    public function index()
    {
        $batches = UploadBatch::orderBy('uploaded_at', 'desc')->paginate(20);
        
        return view('pages.batches', compact('batches'));
    }
}
```

## Data Models

No changes to existing models. The following models remain unchanged:

- **User**: Handles authentication and user information
- **MainSystem**: Stores existing records in the main database
- **UploadBatch**: Tracks file upload batches
- **MatchResult**: Stores matching results for each uploaded record

All relationships and model methods remain intact.


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

### Property 1: Active menu highlighting

*For any* authenticated page request, the navigation menu item corresponding to the current route should have the 'active' CSS class applied.

**Validates: Requirements 2.3**

### Property 2: Navigation link correctness

*For any* sidebar menu item, the href attribute should correctly point to the corresponding route (Dashboard → /dashboard, Upload → /upload, Results → /results, Batches → /batches).

**Validates: Requirements 2.4**

### Property 3: Dashboard statistics accuracy

*For any* database state, the dashboard should display statistics that accurately reflect the database counts: total batches should equal UploadBatch count, matched records should equal MatchResult count where status is 'MATCHED', new records should equal MatchResult count where status is 'NEW RECORD', and possible duplicates should equal MatchResult count where status is 'POSSIBLE DUPLICATE'.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

### Property 4: Recent activity display

*For any* database state with upload batches, the dashboard should display the most recent batches (up to 10) in descending order by upload date.

**Validates: Requirements 3.5**

### Property 5: File type validation

*For any* file upload attempt, files with extensions other than .xlsx, .xls, or .csv should be rejected with a validation error.

**Validates: Requirements 4.2**

### Property 6: Valid file processing

*For any* valid Excel or CSV file upload, the system should create an UploadBatch record, process the file through RecordImport, and update the batch status to 'COMPLETED' or 'FAILED' based on processing outcome.

**Validates: Requirements 4.4**

### Property 7: Operation feedback messages

*For any* file upload operation, successful processing should result in a success flash message containing batch information, and failed processing should result in an error flash message with error details.

**Validates: Requirements 4.5, 4.6, 7.3, 7.4**

### Property 8: Results pagination and display

*For any* database state with match results, the results page should display results in a paginated table with all required columns (Batch ID, Uploaded Record ID, Match Status, Confidence Score, Matched System ID).

**Validates: Requirements 5.1**

### Property 9: Status badge differentiation

*For any* match result or upload batch, different status values should render with different badge CSS classes: 'MATCHED' and 'COMPLETED' should use success/green badges, 'POSSIBLE DUPLICATE' and 'PROCESSING' should use warning/yellow badges, 'NEW RECORD' should use info/blue badges, and 'FAILED' should use danger/red badges.

**Validates: Requirements 5.3, 6.3**

### Property 10: Results filtering functionality

*For any* results page request with filter parameters (batch_id or status), only match results matching the filter criteria should be displayed in the results table.

**Validates: Requirements 5.4, 5.5**

### Property 11: Results sorting order

*For any* results page request, match results should be ordered by created_at timestamp in descending order (most recent first).

**Validates: Requirements 5.6**

### Property 12: Batch pagination and display

*For any* database state with upload batches, the batches page should display batches in a paginated table with all required columns (Batch ID, File Name, Uploaded By, Upload Date, Status).

**Validates: Requirements 6.1**

### Property 13: Batch row navigation

*For any* batch displayed in the batch history table, clicking the row or view button should navigate to the results page with that batch's ID as a filter parameter.

**Validates: Requirements 6.4**

### Property 14: Batch sorting order

*For any* batches page request, upload batches should be ordered by uploaded_at timestamp in descending order (most recent first).

**Validates: Requirements 6.5**

### Property 15: Form validation error display

*For any* form submission with invalid or missing required data, the response should include validation error messages that identify the specific fields with issues.

**Validates: Requirements 7.1, 7.2, 7.6**

### Property 16: Authentication protection

*For any* request to protected routes (/dashboard, /upload, /results, /batches) without authentication, the system should redirect to the login page.

**Validates: Requirements 10.1, 10.2**

### Property 17: Authenticated user display

*For any* authenticated page request, the rendered page should contain the authenticated user's name in the navigation bar.

**Validates: Requirements 10.3**

### Property 18: Asset loading order

*For any* page render, JavaScript assets should be loaded in dependency order: jQuery must be loaded before Bootstrap, and Bootstrap must be loaded before AdminLTE.

**Validates: Requirements 12.5**

## Error Handling

### File Upload Errors

1. **Invalid File Type**: When a user uploads a file with an unsupported extension, return a 422 validation error with message "The file must be a file of type: xlsx, xls, csv."

2. **File Size Exceeded**: When a user uploads a file larger than 10MB, return a 422 validation error with message "The file may not be greater than 10240 kilobytes."

3. **Empty File**: When a user uploads an empty file or a file with no data rows, throw an exception with message "Uploaded file is empty."

4. **Missing Required Columns**: When a user uploads a file missing required columns (surname, firstname, dob), throw an exception with message "Missing required column: {column_name}. Please check your Excel headers."

5. **Processing Failure**: When file processing fails for any reason, update the batch status to 'FAILED' and redirect back with error message including the exception details.

### Authentication Errors

1. **Unauthenticated Access**: When an unauthenticated user attempts to access protected routes, redirect to the login page with a 302 status code.

2. **Session Expiration**: When an authenticated user's session expires, redirect to login page on next request.

### Database Errors

1. **Connection Failure**: When database connection fails during file processing, catch the exception, mark batch as 'FAILED', and display user-friendly error message.

2. **Transaction Rollback**: When any error occurs during the import transaction, rollback all changes and ensure no partial data is saved.

### General Error Handling Strategy

- All controller methods should use try-catch blocks for exception handling
- User-facing error messages should be descriptive but not expose sensitive system information
- All errors should be logged with sufficient context for debugging
- Failed operations should always provide feedback to the user via flash messages
- Database transactions should be used for multi-step operations to ensure data consistency

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests to ensure comprehensive coverage:

- **Unit tests**: Verify specific examples, edge cases, and integration points
- **Property tests**: Verify universal properties across randomized inputs

### Unit Testing Focus Areas

1. **Controller Tests**
   - Test that each controller method returns the correct view
   - Test that controllers pass correct data to views
   - Test authentication middleware is applied
   - Test redirect behavior for unauthenticated users
   - Test file upload validation rules
   - Test success and error flash message handling

2. **View Tests**
   - Test that views render without errors
   - Test that required HTML elements are present (forms, tables, navigation)
   - Test that asset links are included in rendered output
   - Test that authentication-dependent content renders correctly

3. **Integration Tests**
   - Test complete file upload workflow from form submission to batch creation
   - Test navigation flow between pages
   - Test filter and pagination functionality
   - Test authentication flow (login, logout, protected routes)

4. **Edge Cases**
   - Empty database state (no batches, no results)
   - Large file uploads (approaching 10MB limit)
   - Files with missing or malformed data
   - Concurrent file uploads
   - Special characters in file names

### Property-Based Testing Configuration

- **Testing Library**: Use Laravel's built-in testing features with custom property test helpers
- **Iterations**: Minimum 100 iterations per property test
- **Test Tagging**: Each property test must reference its design document property

Example property test structure:

```php
/**
 * Feature: adminlte-integration, Property 3: Dashboard statistics accuracy
 * 
 * @test
 */
public function dashboard_displays_accurate_statistics()
{
    // Run 100 iterations with random database states
    for ($i = 0; $i < 100; $i++) {
        // Generate random batches and results
        $batches = UploadBatch::factory()->count(rand(0, 50))->create();
        $results = MatchResult::factory()->count(rand(0, 200))->create();
        
        // Calculate expected counts
        $expectedMatched = MatchResult::where('match_status', 'MATCHED')->count();
        $expectedNew = MatchResult::where('match_status', 'NEW RECORD')->count();
        $expectedDuplicates = MatchResult::where('match_status', 'POSSIBLE DUPLICATE')->count();
        
        // Request dashboard
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'));
        
        // Assert statistics match database
        $response->assertSee($batches->count());
        $response->assertSee($expectedMatched);
        $response->assertSee($expectedNew);
        $response->assertSee($expectedDuplicates);
        
        // Clean up for next iteration
        MatchResult::truncate();
        UploadBatch::truncate();
    }
}
```

### Testing Coverage Goals

- Minimum 80% code coverage for all new controllers
- 100% coverage for authentication and authorization logic
- All 18 correctness properties must have corresponding property tests
- All edge cases identified in requirements must have unit tests

### Manual Testing Checklist

After automated tests pass, perform manual testing for:

- Visual appearance and AdminLTE styling consistency
- Responsive design on mobile devices
- File upload progress indicators
- Browser compatibility (Chrome, Firefox, Safari, Edge)
- Accessibility compliance (keyboard navigation, screen readers)
