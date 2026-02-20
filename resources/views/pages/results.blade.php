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
                    <div class="card-footer">
                        {{ $results->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
