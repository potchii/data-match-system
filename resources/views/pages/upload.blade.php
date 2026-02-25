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
        <!-- Validation Error Alert -->
        @if(session('validation_errors'))
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> File Validation Failed</h5>
                    <p>The uploaded file does not match the expected column structure.</p>
                    
                    <div class="mt-3">
                        <strong>Validation Errors:</strong>
                        <ul class="mb-2">
                            @foreach(session('validation_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>

                    @if(session('validation_info'))
                        @php
                            $info = session('validation_info');
                        @endphp
                        
                        @if(!empty($info['missing_columns']))
                        <div class="mt-2">
                            <strong><i class="fas fa-exclamation-circle"></i> Missing Columns:</strong>
                            <code>{{ implode(', ', $info['missing_columns']) }}</code>
                        </div>
                        @endif

                        @if(!empty($info['extra_columns']))
                        <div class="mt-2">
                            <strong><i class="fas fa-exclamation-triangle"></i> Extra Columns:</strong>
                            <code>{{ implode(', ', $info['extra_columns']) }}</code>
                        </div>
                        @endif

                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-light" data-toggle="collapse" data-target="#columnDetails">
                                <i class="fas fa-info-circle"></i> Show Column Details
                            </button>
                        </div>

                        <div id="columnDetails" class="collapse mt-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Expected Columns:</strong>
                                    <div class="bg-white text-dark p-2 rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                                        <small>{{ implode(', ', $info['expected_columns']) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Found Columns:</strong>
                                    <div class="bg-white text-dark p-2 rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                                        <small>{{ implode(', ', $info['found_columns']) }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

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
                                <label for="template_id">Column Mapping Template (Optional)</label>
                                <select class="form-control" id="template_id" name="template_id">
                                    <option value="">-- No Template (Auto-detect) --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Select a saved template to apply column mappings automatically.
                                    @if($templates->isEmpty())
                                        <a href="{{ route('templates.create') }}">Create your first template</a>
                                    @else
                                        <a href="{{ route('templates.index') }}">Manage templates</a>
                                    @endif
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="file">Select File</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('file') is-invalid @enderror" 
                                           id="file" name="file" accept=".xlsx,.xls,.csv" required>
                                    <label class="custom-file-label" for="file">Choose file</label>
                                </div>
                                @error('file')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">
                                    Accepted formats: .xlsx, .xls, .csv (Max size: 10MB)
                                </small>
                            </div>

                            <!-- Loading Indicator -->
                            <div id="loadingIndicator" class="alert alert-info" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Validating file and processing upload...
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

// Make upload button trigger file browser if no file selected
$('#submitBtn').on('click', function(e) {
    const fileInput = $('#file')[0];
    
    // If no file selected, trigger file browser instead of submitting
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        $('#file').click();
        return false;
    }
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
    $('#loadingIndicator').show();
});

// Show expected columns when template is selected
$('#template_id').on('change', function() {
    const templateId = $(this).val();
    
    if (templateId) {
        // Fetch template details and show expected columns
        $.get('/api/templates/' + templateId, function(response) {
            if (response.success && response.data) {
                showExpectedColumns(response.data);
            }
        });
    } else {
        $('#expectedColumnsDisplay').remove();
    }
});

function showExpectedColumns(template) {
    // Remove existing display
    $('#expectedColumnsDisplay').remove();
    
    // Fetch template fields
    $.get('/api/templates/' + template.id + '/fields', function(response) {
        if (response.success) {
            const fields = response.data;
            const coreColumns = Object.keys(template.mappings);
            
            let html = '<div id="expectedColumnsDisplay" class="alert alert-info mt-3">';
            html += '<h6><i class="fas fa-info-circle"></i> Expected Columns for Template: ' + template.name + '</h6>';
            html += '<div class="row">';
            
            // Core fields
            html += '<div class="col-md-6">';
            html += '<strong>Core Fields:</strong>';
            html += '<ul class="mb-0">';
            coreColumns.forEach(function(col) {
                html += '<li><code>' + col + '</code></li>';
            });
            html += '</ul>';
            html += '</div>';
            
            // Custom fields
            if (fields.length > 0) {
                html += '<div class="col-md-6">';
                html += '<strong>Custom Fields:</strong>';
                html += '<ul class="mb-0">';
                fields.forEach(function(field) {
                    const icon = field.is_required ? '<i class="fas fa-asterisk text-danger" style="font-size: 0.6em;"></i>' : '<i class="fas fa-circle text-muted" style="font-size: 0.6em;"></i>';
                    html += '<li>' + icon + ' <code>' + field.field_name + '</code> <small class="text-muted">(' + field.field_type + ')</small></li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
            
            $('.card-body').append(html);
        }
    });
}
</script>
@endpush
