@extends('layouts.admin')

@section('title', $record ? 'Edit Record' : 'Create Record')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $record ? 'Edit Record' : 'Create Record' }}</h1>
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
                        <h3 class="card-title">{{ $record ? 'Edit' : 'Create' }} Main System Record</h3>
                    </div>
                    <form id="recordForm" method="POST" action="{{ $record ? route('main-system.update', $record->id) : route('main-system.store') }}">
                        @csrf
                        @if($record)
                            @method('PUT')
                        @endif
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h4>Validation Errors:</h4>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-section">
                                <h5 class="mb-3"><i class="fas fa-database"></i> Core Information</h5>
                                
                                @if($record)
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="uid">UID</label>
                                        <input type="text" class="form-control" id="uid" value="{{ $record->uid }}" disabled>
                                        <small class="form-text text-muted">Auto-generated identifier (read-only)</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="regs_no">Registration Number</label>
                                        <input type="text" class="form-control @error('regs_no') is-invalid @enderror" id="regs_no" name="regs_no" value="{{ old('regs_no', $record->regs_no ?? '') }}">
                                        @error('regs_no')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                @else
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="regs_no">Registration Number</label>
                                        <input type="text" class="form-control @error('regs_no') is-invalid @enderror" id="regs_no" name="regs_no" value="{{ old('regs_no', '') }}">
                                        @error('regs_no')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                @endif

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $record->first_name ?? '') }}" required>
                                        @error('first_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="middle_name">Middle Name</label>
                                        <input type="text" class="form-control @error('middle_name') is-invalid @enderror" id="middle_name" name="middle_name" value="{{ old('middle_name', $record->middle_name ?? '') }}">
                                        @error('middle_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $record->last_name ?? '') }}" required>
                                        @error('last_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="suffix">Suffix</label>
                                        <input type="text" class="form-control @error('suffix') is-invalid @enderror" id="suffix" name="suffix" value="{{ old('suffix', optional($record)->suffix ?? '') }}">
                                        @error('suffix')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="birthday">Birthday</label>
                                        <input type="date" class="form-control @error('birthday') is-invalid @enderror" id="birthday" name="birthday" value="{{ old('birthday', optional($record)->birthday ? $record->birthday->format('Y-m-d') : '') }}">
                                        @error('birthday')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="gender">Gender</label>
                                        <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                                            <option value="">-- Select Gender --</option>
                                            <option value="Male" {{ old('gender', optional($record)->gender ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender', optional($record)->gender ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender', optional($record)->gender ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="civil_status">Civil Status</label>
                                        <select class="form-control @error('civil_status') is-invalid @enderror" id="civil_status" name="civil_status">
                                            <option value="">-- Select Civil Status --</option>
                                            <option value="Single" {{ old('civil_status', optional($record)->civil_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                                            <option value="Married" {{ old('civil_status', optional($record)->civil_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                                            <option value="Annulled" {{ old('civil_status', optional($record)->civil_status ?? '') === 'Annulled' ? 'selected' : '' }}>Annulled</option>
                                            <option value="Widowed" {{ old('civil_status', optional($record)->civil_status ?? '') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                            <option value="Separated" {{ old('civil_status', optional($record)->civil_status ?? '') === 'Separated' ? 'selected' : '' }}>Separated</option>
                                        </select>
                                        @error('civil_status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Street Address">{{ old('address', optional($record)->address ?? '') }}</textarea>
                                    @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="status">Status</label>
                                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status">
                                            <option value="">-- Select Status --</option>
                                            <option value="active" {{ old('status', optional($record)->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', optional($record)->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="archived" {{ old('status', optional($record)->status ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                                        </select>
                                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="category">Category</label>
                                        <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', optional($record)->category ?? '') }}">
                                        @error('category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="registration_date">Registration Date</label>
                                    <input type="date" class="form-control @error('registration_date') is-invalid @enderror" id="registration_date" name="registration_date" value="{{ old('registration_date', optional($record)->registration_date ? $record->registration_date->format('Y-m-d') : '') }}">
                                    @error('registration_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('main-system.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">{{ $record ? 'Update' : 'Create' }} Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
