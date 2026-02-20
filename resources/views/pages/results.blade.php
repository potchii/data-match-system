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
