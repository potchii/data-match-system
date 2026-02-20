@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Dashboard</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalBatches }}</h3>
                        <p>Total Batches</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $matchedRecords }}</h3>
                        <p>Matched Records</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $newRecords }}</h3>
                        <p>New Records</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $possibleDuplicates }}</h3>
                        <p>Possible Duplicates</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Upload Activity</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>File Name</th>
                                    <th>Uploaded By</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentBatches as $batch)
                                <tr>
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
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No recent batches found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
