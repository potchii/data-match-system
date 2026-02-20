@extends('layouts.admin')

@section('title', 'Main System Records')

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
                        <div class="card-tools">
                            <form method="GET" action="{{ route('main-system.index') }}" class="form-inline">
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
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>UID</th>
                                    <th>Name</th>
                                    <th>Birthday</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Origin Batch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                <tr>
                                    <td>{{ $record->id }}</td>
                                    <td>
                                        @if($record->uid)
                                            <span class="badge badge-info">{{ $record->uid }}</span>
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
                                        @if($record->street || $record->barangay || $record->city)
                                            <small>
                                                @if($record->street_no) {{ $record->street_no }} @endif
                                                @if($record->street) {{ $record->street }}, @endif
                                                @if($record->barangay) {{ $record->barangay }}, @endif
                                                @if($record->city) {{ $record->city }} @endif
                                                @if($record->province) {{ $record->province }} @endif
                                            </small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->originBatch)
                                            <small>
                                                Batch #{{ $record->origin_batch_id }}<br>
                                                <span class="text-muted">{{ $record->originBatch->file_name }}</span>
                                            </small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">
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

