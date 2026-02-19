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
