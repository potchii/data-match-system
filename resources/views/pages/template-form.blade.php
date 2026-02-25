@extends('layouts.admin')

@section('title', isset($template) ? 'Edit Template' : 'Create Template')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ isset($template) ? 'Edit Template' : 'Create Template' }}</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <a href="{{ route('templates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Templates
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Template Details</h3>
                    </div>
                    <form action="{{ isset($template) ? route('templates.update', $template->id) : route('templates.store') }}" method="POST" id="templateForm">
                        @csrf
                        @if(isset($template))
                            @method('PUT')
                        @endif
                        
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       value="{{ old('name', $template->name ?? '') }}" 
                                       required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Give your template a descriptive name</small>
                            </div>

                            <div class="form-group">
                                <label>Column Mappings <span class="text-danger">*</span></label>
                                <div id="mappings-container">
                                    @php
                                        $coreFields = [
                                            'uid',
                                            'last_name',
                                            'first_name',
                                            'middle_name',
                                            'suffix',
                                            'birthday',
                                            'gender',
                                            'civil_status',
                                            'address',
                                            'barangay',
                                        ];
                                        
                                        if(isset($template) && $template->mappings) {
                                            $mappings = $template->mappings;
                                        } else {
                                            // Pre-populate with core fields for new templates
                                            $mappings = [];
                                            foreach($coreFields as $field) {
                                                $mappings[$field] = $field;
                                            }
                                        }
                                    @endphp
                                    
                                    @foreach($mappings as $excelColumn => $systemField)
                                        @php
                                            $isCoreField = in_array($systemField, $coreFields);
                                        @endphp
                                        <div class="mapping-row mb-2" data-core-field="{{ $isCoreField ? 'true' : 'false' }}">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" 
                                                           name="excel_columns[]" 
                                                           value="{{ $excelColumn }}" 
                                                           placeholder="Excel Column Name" required>
                                                </div>
                                                <div class="col-md-1 text-center pt-2">
                                                    <i class="fas fa-arrow-right"></i>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" 
                                                           name="system_fields[]" 
                                                           value="{{ $systemField }}" 
                                                           placeholder="System Field Name" 
                                                           readonly
                                                           style="background-color: #f4f6f9;"
                                                           required>
                                                    @if($isCoreField)
                                                        <small class="text-muted"><i class="fas fa-lock"></i> Core Field</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-1">
                                                    @if($isCoreField)
                                                        <button type="button" class="btn btn-secondary btn-sm" disabled title="Core fields cannot be removed">
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-danger btn-sm remove-mapping">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">
                                    Core fields are pre-populated. Just enter your Excel column names on the left.
                                </small>
                            </div>

                            <!-- Custom Fields Section -->
                            <div class="form-group mt-4">
                                <label>Custom Fields</label>
                                <small class="form-text text-muted mb-2">
                                    Define additional fields beyond the core columns. These will be validated during upload with specific data types.
                                </small>
                                <div id="custom-fields-container">
                                    @if(isset($template) && $template->fields && $template->fields->count() > 0)
                                        @foreach($template->fields as $field)
                                            <div class="custom-field-row mb-2">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" 
                                                               name="field_names[]" 
                                                               value="{{ $field->field_name }}"
                                                               placeholder="Field Name (e.g., department)">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select name="field_types[]" class="form-control">
                                                            <option value="string" {{ $field->field_type === 'string' ? 'selected' : '' }}>String</option>
                                                            <option value="integer" {{ $field->field_type === 'integer' ? 'selected' : '' }}>Integer</option>
                                                            <option value="decimal" {{ $field->field_type === 'decimal' ? 'selected' : '' }}>Decimal</option>
                                                            <option value="date" {{ $field->field_type === 'date' ? 'selected' : '' }}>Date</option>
                                                            <option value="boolean" {{ $field->field_type === 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-check mt-2">
                                                            <input type="checkbox" name="field_required[]" 
                                                                   value="{{ $loop->index }}"
                                                                   class="form-check-input"
                                                                   {{ $field->is_required ? 'checked' : '' }}>
                                                            <label class="form-check-label">Required</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger btn-sm remove-custom-field">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="add-custom-field">
                                    <i class="fas fa-plus"></i> Add Custom Field
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($template) ? 'Update Template' : 'Create Template' }}
                            </button>
                            <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">How to Use Templates</h3>
                    </div>
                    <div class="card-body">
                        <h6 class="text-bold">Core Fields (Pre-populated)</h6>
                        <p class="text-sm">All core database fields are already listed. Just enter your Excel column names on the left side.</p>
                        
                        <h6 class="text-bold mt-3">Example:</h6>
                        <p class="text-sm mb-1">If your Excel has:</p>
                        <ul class="text-sm">
                            <li><code>Surname</code> → map to <code>last_name</code></li>
                            <li><code>FirstName</code> → map to <code>first_name</code></li>
                            <li><code>DOB</code> → map to <code>birthday</code></li>
                            <li><code>Address</code> → map to <code>address</code></li>
                        </ul>
                        
                        <h6 class="text-bold mt-3">Custom Template Fields</h6>
                        <p class="text-sm">Define additional fields with specific types and validation:</p>
                        <ul class="text-sm">
                            <li><strong>String:</strong> Any text value</li>
                            <li><strong>Integer:</strong> Whole numbers only</li>
                            <li><strong>Decimal:</strong> Numbers with decimals</li>
                            <li><strong>Date:</strong> Valid date format</li>
                            <li><strong>Boolean:</strong> true/false, yes/no, 1/0</li>
                        </ul>
                        <p class="text-sm text-info">
                            <i class="fas fa-info-circle"></i> Custom fields are validated during upload and stored in the template_fields table.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let customFieldIndex = {{ isset($template) && $template->fields ? $template->fields->count() : 0 }};

    // Add new mapping row for dynamic fields
    $('#add-mapping').on('click', function() {
        const newRow = `
            <div class="mapping-row mb-2">
                <div class="row">
                    <div class="col-md-5">
                        <input type="text" class="form-control" 
                               name="excel_columns[]" 
                               placeholder="Excel Column Name (e.g., Department)" required>
                    </div>
                    <div class="col-md-1 text-center pt-2">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" 
                               name="system_fields[]" 
                               placeholder="Dynamic Field Name (e.g., department)" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-mapping">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#mappings-container').append(newRow);
    });

    // Remove mapping row
    $(document).on('click', '.remove-mapping', function() {
        if ($('.mapping-row').length > 1) {
            $(this).closest('.mapping-row').remove();
        } else {
            alert('At least one mapping is required');
        }
    });

    // Add custom field
    $('#add-custom-field').on('click', function() {
        const newField = `
            <div class="custom-field-row mb-2">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" class="form-control field-name-input" 
                               name="field_names[]" 
                               placeholder="Field Name (e.g., department)"
                               pattern="[a-zA-Z0-9_]+"
                               title="Only letters, numbers, and underscores allowed">
                    </div>
                    <div class="col-md-3">
                        <select name="field_types[]" class="form-control">
                            <option value="string">String</option>
                            <option value="integer">Integer</option>
                            <option value="decimal">Decimal</option>
                            <option value="date">Date</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-2">
                            <input type="checkbox" name="field_required[]" 
                                   value="${customFieldIndex}" 
                                   class="form-check-input">
                            <label class="form-check-label">Required</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm remove-custom-field">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#custom-fields-container').append(newField);
        customFieldIndex++;
    });

    // Remove custom field
    $(document).on('click', '.remove-custom-field', function() {
        $(this).closest('.custom-field-row').remove();
    });

    // Client-side validation for field names
    $(document).on('input', '.field-name-input', function() {
        const value = $(this).val();
        const isValid = /^[a-zA-Z0-9_]*$/.test(value);
        
        if (!isValid) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Only letters, numbers, and underscores allowed</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Form validation
    $('#templateForm').on('submit', function(e) {
        const excelColumns = $('input[name="excel_columns[]"]').map(function() {
            return $(this).val().trim();
        }).get();
        
        const systemFields = $('input[name="system_fields[]"]').map(function() {
            return $(this).val().trim();
        }).get();

        // Check for empty values
        if (excelColumns.some(col => !col) || systemFields.some(field => !field)) {
            e.preventDefault();
            alert('All mapping fields must be filled');
            return false;
        }

        // Check for duplicate Excel columns
        const duplicates = excelColumns.filter((item, index) => excelColumns.indexOf(item) !== index);
        if (duplicates.length > 0) {
            e.preventDefault();
            alert('Duplicate Excel column names found: ' + duplicates.join(', '));
            return false;
        }

        // Validate custom field names
        const fieldNames = $('input[name="field_names[]"]').map(function() {
            return $(this).val().trim();
        }).get().filter(name => name !== '');

        // Check for duplicate field names
        const duplicateFields = fieldNames.filter((item, index) => fieldNames.indexOf(item) !== index);
        if (duplicateFields.length > 0) {
            e.preventDefault();
            alert('Duplicate custom field names found: ' + duplicateFields.join(', '));
            return false;
        }

        // Check field name format
        const invalidFields = fieldNames.filter(name => !/^[a-zA-Z0-9_]+$/.test(name));
        if (invalidFields.length > 0) {
            e.preventDefault();
            alert('Invalid field names (only letters, numbers, and underscores allowed): ' + invalidFields.join(', '));
            return false;
        }
    });
});
</script>
@endpush
