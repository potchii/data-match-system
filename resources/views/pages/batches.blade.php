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
                                        <a href="{{ route('results.index', ['batch_id' => $batch->id]) }}" class="btn btn-sm btn-info" onclick="event.stopPropagation();">
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
