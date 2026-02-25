@extends('layouts.admin')

@section('title', 'Column Mapping Templates')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Column Mapping Templates</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <a href="{{ route('templates.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Template
                    </a>
                </div>
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
                        <h3 class="card-title">Your Saved Templates</h3>
                    </div>
                    <div class="card-body">
                        @if($templates->isEmpty())
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No templates found. Create your first template to save column mappings for reuse.</p>
                                <a href="{{ route('templates.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Template
                                </a>
                            </div>
                        @else
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Mappings</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <strong>{{ $template->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ count($template->mappings) }} mappings</span>
                                        </td>
                                        <td>{{ $template->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('templates.edit', $template->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form action="{{ route('templates.destroy', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
