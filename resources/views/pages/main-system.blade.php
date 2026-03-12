@extends('layouts.admin')

@section('title', 'Main System Records')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/bulk-action-toolbar.css') }}">
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Main System Records</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Records ({{ $records->total() }})</h3>
                        <div class="card-tools d-flex align-items-center">
                            <a href="{{ route('main-system.create') }}" class="btn btn-sm btn-primary mr-2" title="Create new record">
                                <i class="fas fa-plus"></i> Create Record
                            </a>
                            <form method="GET" action="{{ route('main-system.index') }}" class="form-inline mr-2">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by name or UID" 
                                           value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <a href="{{ route('main-system.export', request()->query()) }}" 
                               class="btn btn-sm btn-success"
                               title="Export all records">
                                <i class="fas fa-file-download"></i> Export
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap" id="recordsTable">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" id="selectAll" title="Select all records">
                                    </th>
                                    <th>Regs No</th>
                                    <th>Name</th>
                                    <th>Birthday</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Category</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="record-checkbox" value="{{ $record->id }}" data-record-id="{{ $record->id }}">
                                    </td>
                                    <td>
                                        @if($record->regs_no)
                                            <span class="badge badge-primary">{{ $record->regs_no }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $record->last_name }}, {{ $record->first_name }}</strong>
                                        @if($record->middle_name)
                                            {{ $record->middle_name }}
                                        @endif
                                        @if($record->suffix)
                                            {{ $record->suffix }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->birthday)
                                            {{ $record->birthday->format('Y-m-d') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $record->gender ?? 'N/A' }}</td>
                                    <td>
                                        @if($record->status)
                                            <span class="badge badge-{{ $record->status === 'active' ? 'success' : ($record->status === 'inactive' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($record->status) }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $record->category ?? 'N/A' }}</td>
                                    <td>
                                        @if($record->registration_date)
                                            {{ $record->registration_date->format('Y-m-d') }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('main-system.edit', $record->id) }}" class="btn btn-xs btn-info" title="Edit record">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('main-system.destroy', $record->id) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete record" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">
                                        @if(request('search'))
                                            No records found matching "{{ request('search') }}"
                                        @else
                                            No records found
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($records->hasPages())
                    <div class="card-footer clearfix">
                        <div class="float-right">
                            {{ $records->appends(request()->query())->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectAllCheckbox = document.getElementById('selectAll');
  const recordCheckboxes = document.querySelectorAll('.record-checkbox');

  selectAllCheckbox.addEventListener('change', function() {
    recordCheckboxes.forEach(checkbox => {
      checkbox.checked = this.checked;
    });
  });

  recordCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const allChecked = Array.from(recordCheckboxes).every(cb => cb.checked);
      const someChecked = Array.from(recordCheckboxes).some(cb => cb.checked);
      selectAllCheckbox.checked = allChecked;
      selectAllCheckbox.indeterminate = someChecked && !allChecked;
    });
  });
});
</script>
@endpush
