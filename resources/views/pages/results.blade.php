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
        @if(session('column_mapping'))
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-info collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-columns"></i> Column Mapping Summary
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5 class="text-success">
                                    <i class="fas fa-check-circle"></i> Core Fields Mapped
                                </h5>
                                <div class="mb-3">
                                    @if(count(session('column_mapping')['core_fields_mapped']) > 0)
                                        @foreach(session('column_mapping')['core_fields_mapped'] as $field)
                                            <span class="badge badge-success mr-1 mb-1">{{ $field }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-info">
                                    <i class="fas fa-plus-circle"></i> Dynamic Fields Captured
                                </h5>
                                <div class="mb-3">
                                    @if(count(session('column_mapping')['dynamic_fields_captured']) > 0)
                                        @foreach(session('column_mapping')['dynamic_fields_captured'] as $field)
                                            <span class="badge badge-info mr-1 mb-1">{{ $field }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-secondary">
                                    <i class="fas fa-minus-circle"></i> Skipped Columns
                                </h5>
                                <div class="mb-3">
                                    @if(count(session('column_mapping')['skipped_columns']) > 0)
                                        @foreach(session('column_mapping')['skipped_columns'] as $field)
                                            <span class="badge badge-secondary mr-1 mb-1">{{ $field }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="mb-2">
                                    <strong>Total Columns:</strong> 
                                    {{ count(session('column_mapping')['core_fields_mapped']) + count(session('column_mapping')['dynamic_fields_captured']) + count(session('column_mapping')['skipped_columns']) }}
                                    <span class="ml-3">
                                        <strong>Core:</strong> {{ count(session('column_mapping')['core_fields_mapped']) }}
                                    </span>
                                    <span class="ml-3">
                                        <strong>Dynamic:</strong> {{ count(session('column_mapping')['dynamic_fields_captured']) }}
                                    </span>
                                    <span class="ml-3">
                                        <strong>Skipped:</strong> {{ count(session('column_mapping')['skipped_columns']) }}
                                    </span>
                                </p>
                                @if($batchStats)
                                <p class="mb-0">
                                    <strong>Total Rows Processed:</strong> {{ $batchStats['total_rows'] }}
                                    <span class="ml-3">
                                        <strong>New Records:</strong> <span class="text-info">{{ $batchStats['new_records'] }}</span>
                                    </span>
                                    <span class="ml-3">
                                        <strong>Matched:</strong> <span class="text-success">{{ $batchStats['matched'] }}</span>
                                    </span>
                                    <span class="ml-3">
                                        <strong>Possible Duplicates:</strong> <span class="text-warning">{{ $batchStats['possible_duplicates'] }}</span>
                                    </span>
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
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
                                    <th>ID</th>
                                    <th>Batch ID</th>
                                    <th>Uploaded Record</th>
                                    <th>Match Status</th>
                                    <th>Confidence</th>
                                    <th>Matched With</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                <tr>
                                    <td>{{ $result->id }}</td>
                                    <td>{{ $result->batch_id }}</td>
                                    <td>
                                        <strong>{{ $result->uploaded_first_name }} {{ $result->uploaded_middle_name }} {{ $result->uploaded_last_name }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $result->uploaded_record_id }}</small>
                                    </td>
                                    <td>
                                        @if($result->match_status === 'MATCHED')
                                            <span class="badge badge-success">{{ $result->match_status }}</span>
                                        @elseif($result->match_status === 'POSSIBLE DUPLICATE')
                                            <span class="badge badge-warning">{{ $result->match_status }}</span>
                                        @else
                                            <span class="badge badge-info">{{ $result->match_status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($result->confidence_score, 1) }}%</td>
                                    <td>
                                        @if($result->matchedRecord)
                                            <strong>{{ $result->matchedRecord->first_name }} {{ $result->matchedRecord->middle_name }} {{ $result->matchedRecord->last_name }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Row ID: {{ $result->matchedRecord->origin_match_result_id ?? $result->matchedRecord->id }}
                                                @if($result->matchedRecord->originBatch)
                                                    (From Batch #{{ $result->matchedRecord->origin_batch_id }}: {{ $result->matchedRecord->originBatch->file_name }})
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No results found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer clearfix">
                        <div class="float-right">
                            {{ $results->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
