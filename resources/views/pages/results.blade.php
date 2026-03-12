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
        @if($batchStats)
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-info {{ $isFromUpload ? '' : 'collapsed-card' }}">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i> Batch Analytics
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-{{ $isFromUpload ? 'minus' : 'plus' }}"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Batch Statistics Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5><i class="fas fa-chart-bar"></i> Batch Statistics</h5>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>{{ $batchStats['total_rows'] }}</h3>
                                        <p>Total Rows Processed</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>{{ $batchStats['matched'] }}</h3>
                                        <p>Matched ({{ $batchStats['total_rows'] > 0 ? round(($batchStats['matched'] / $batchStats['total_rows']) * 100, 1) : 0 }}%)</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-warning">
                                    <div class="inner">
                                        <h3>{{ $batchStats['possible_duplicates'] }}</h3>
                                        <p>Possible Duplicates ({{ $batchStats['total_rows'] > 0 ? round(($batchStats['possible_duplicates'] / $batchStats['total_rows']) * 100, 1) : 0 }}%)</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3>{{ $batchStats['new_records'] }}</h3>
                                        <p>New Records ({{ $batchStats['total_rows'] > 0 ? round(($batchStats['new_records'] / $batchStats['total_rows']) * 100, 1) : 0 }}%)</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Match Status Distribution Pie Chart -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Match Status Distribution</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container" style="position: relative; height: 300px;">
                                            <canvas id="matchStatusChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trends Section -->
                            <div class="col-md-6">
                                <div id="trends-container" data-batch-id="{{ request('batch_id') }}">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading trends...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading trends...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        @if($columnMapping)
        <!-- Column Mapping Summary Section -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary {{ $isFromUpload ? '' : 'collapsed-card' }}">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-columns"></i> Column Mapping Summary
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-{{ $isFromUpload ? 'minus' : 'plus' }}"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Core Fields Mapped -->
                            @if(!empty($columnMapping['core_fields_mapped']))
                            <div class="col-md-4">
                                <h6><i class="fas fa-database"></i> Core Fields Mapped</h6>
                                <div class="mb-3">
                                    @foreach($columnMapping['core_fields_mapped'] as $field)
                                        <span class="badge badge-success mr-1 mb-1">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Dynamic Fields Captured -->
                            @if(!empty($columnMapping['dynamic_fields_captured']))
                            <div class="col-md-4">
                                <h6><i class="fas fa-cog"></i> Dynamic Fields Captured</h6>
                                <div class="mb-3">
                                    @foreach($columnMapping['dynamic_fields_captured'] as $field)
                                        <span class="badge badge-info mr-1 mb-1">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Skipped Columns -->
                            @if(!empty($columnMapping['skipped_columns']))
                            <div class="col-md-4">
                                <h6><i class="fas fa-ban"></i> Skipped Columns</h6>
                                <div class="mb-3">
                                    @foreach($columnMapping['skipped_columns'] as $field)
                                        <span class="badge badge-secondary mr-1 mb-1">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Column Statistics -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Core Fields</span>
                                        <span class="info-box-number">{{ count($columnMapping['core_fields_mapped'] ?? []) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-cog"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Dynamic Fields</span>
                                        <span class="info-box-number">{{ count($columnMapping['dynamic_fields_captured'] ?? []) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-secondary"><i class="fas fa-ban"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Skipped Columns</span>
                                        <span class="info-box-number">{{ count($columnMapping['skipped_columns'] ?? []) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-columns"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Columns</span>
                                        <span class="info-box-number">{{ count(session('column_mapping.core_fields_mapped', [])) + count(session('column_mapping.dynamic_fields_captured', [])) + count(session('column_mapping.skipped_columns', [])) }}</span>
                                    </div>
                                </div>
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
                        <div class="card-tools d-flex align-items-center">
                            <form method="GET" action="{{ route('results.index') }}" class="form-inline mr-2">
                                <div class="form-group mr-2">
                                    <div class="input-group input-group-sm" style="width: 220px;">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search by name or UID" 
                                               value="{{ request('search') }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
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
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </form>
                            <a href="{{ route('results.export-duplicates', request()->query()) }}" 
                               class="btn btn-sm btn-success"
                               title="Export all duplicates with their matched base records">
                                <i class="fas fa-file-download"></i> Export Duplicates
                            </a>
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
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $index => $result)
                                <tr>
                                    <td>{{ $results->firstItem() + $index }}</td>
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
                                            <span class="badge badge-danger">{{ $result->match_status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($result->confidence_score, 1) }}%</strong>
                                        @if($result->field_breakdown)
                                            <br>
                                            <small class="text-muted">
                                                {{ $result->field_breakdown['matched_fields'] ?? 0 }}/{{ $result->field_breakdown['total_fields'] ?? 0 }} fields
                                            </small>
                                        @endif
                                    </td>
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
                                    <td>
                                        @if($result->field_breakdown && $result->match_status !== 'NEW RECORD')
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#breakdownModal{{ $result->id }}">
                                                <i class="fas fa-eye"></i> View Breakdown
                                            </button>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No results found</td>
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

<!-- Field Breakdown Modals -->
@foreach($results as $result)
    @if($result->field_breakdown && $result->match_status !== 'NEW RECORD')
    <div class="modal fade" id="breakdownModal{{ $result->id }}" tabindex="-1" role="dialog" aria-labelledby="breakdownModalLabel{{ $result->id }}">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="breakdownModalLabel{{ $result->id }}">Field Breakdown - Match Result #{{ $result->id }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Match Summary -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6>Match Summary</h6>
                            <p>
                                <strong>Confidence Score:</strong> 
                                <span class="badge badge-primary">{{ number_format($result->confidence_score, 1) }}%</span>
                                <span class="ml-3">
                                    <strong>Matched Fields:</strong> <span id="matched-count-{{ $result->id }}">{{ $result->field_breakdown['matched_fields'] ?? 0 }}</span>
                                </span>
                                <span class="ml-3">
                                    <strong>Total Fields:</strong> <span id="total-count-{{ $result->id }}">{{ $result->field_breakdown['total_fields'] ?? 0 }}</span>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Filter Buttons and Export -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Field filter buttons">
                                <button type="button" class="btn btn-outline-primary active" data-filter="all" data-result-id="{{ $result->id }}">
                                    All <span class="badge badge-light" id="filter-all-count-{{ $result->id }}">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-success" data-filter="matched" data-result-id="{{ $result->id }}">
                                    Matched <span class="badge badge-light" id="filter-matched-count-{{ $result->id }}">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-filter="mismatched" data-result-id="{{ $result->id }}">
                                    Mismatched <span class="badge badge-light" id="filter-mismatched-count-{{ $result->id }}">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-info" data-filter="new" data-result-id="{{ $result->id }}">
                                    New <span class="badge badge-light" id="filter-new-count-{{ $result->id }}">0</span>
                                </button>
                            </div>
                            <small class="text-muted ml-2">
                                Showing <span id="visible-count-{{ $result->id }}">0</span> fields
                            </small>
                        </div>
                        <div class="col-md-4 text-right">
                            <button type="button" class="btn btn-sm btn-success" id="export-csv-{{ $result->id }}" data-result-id="{{ $result->id }}">
                                <i class="fas fa-download"></i> Export to CSV
                            </button>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="breakdown-loading-{{ $result->id }}" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading field breakdown...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading detailed field comparison...</p>
                    </div>

                    <!-- Field Breakdown Container -->
                    <div id="breakdown-container-{{ $result->id }}" style="display: none;">
                        <!-- Core Fields Section -->
                        <div class="mb-4" id="core-fields-section-{{ $result->id }}">
                            <h6><i class="fas fa-database"></i> Core Fields</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 15%;">Field Name</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 18%;">Uploaded Value</th>
                                            <th style="width: 18%;">Existing Value</th>
                                            <th style="width: 15%;">Normalized (Uploaded)</th>
                                            <th style="width: 15%;">Normalized (Existing)</th>
                                            <th style="width: 9%;">Confidence</th>
                                        </tr>
                                    </thead>
                                    <tbody id="core-fields-body-{{ $result->id }}">
                                        <!-- Populated via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Template Fields Section -->
                        <div class="mb-4" id="template-fields-section-{{ $result->id }}" style="display: none;">
                            <h6><i class="fas fa-cog"></i> Template Fields</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 15%;">Field Name</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 18%;">Uploaded Value</th>
                                            <th style="width: 18%;">Existing Value</th>
                                            <th style="width: 15%;">Normalized (Uploaded)</th>
                                            <th style="width: 15%;">Normalized (Existing)</th>
                                            <th style="width: 9%;">Confidence</th>
                                        </tr>
                                    </thead>
                                    <tbody id="template-fields-body-{{ $result->id }}">
                                        <!-- Populated via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- No Results Message -->
                        <div id="no-results-{{ $result->id }}" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle"></i> No fields match the selected filter.
                        </div>
                    </div>

                    <!-- Error Container -->
                    <div id="breakdown-error-{{ $result->id }}" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="error-message-{{ $result->id }}"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Match Status Analytics Module
class MatchStatusAnalytics {
    constructor() {
        this.batchId = null;
        this.matchStatusChart = null;
    }

    async initialize(batchId) {
        if (!batchId) {
            return;
        }

        this.batchId = batchId;

        try {
            const response = await fetch(`/api/batch-trends/${batchId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.renderMatchStatusChart(data.match_status_chart);
            this.renderTrends(data.trends, data.template_fields);
        } catch (error) {
            console.error('Failed to load analytics:', error);
        }
    }

    renderMatchStatusChart(chartData) {
        const ctx = document.getElementById('matchStatusChart');
        if (!ctx) return;

        const canvasCtx = ctx.getContext('2d');
        
        if (this.matchStatusChart) {
            this.matchStatusChart.destroy();
        }

        try {
            this.matchStatusChart = new Chart(canvasCtx, {
                type: 'pie',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Chart rendering failed:', error);
        }
    }

    renderTrends(trends, templateFields) {
        const trendsContainer = document.getElementById('trends-container');
        if (!trendsContainer) return;

        const getTrendIcon = (trend) => {
            if (trend === 'up') return '<i class="fas fa-arrow-up text-success"></i>';
            if (trend === 'down') return '<i class="fas fa-arrow-down text-danger"></i>';
            return '<i class="fas fa-minus text-muted"></i>';
        };

        const coreFieldsHtml = templateFields.core_fields.length > 0
            ? templateFields.core_fields.map(f => `<span class="badge badge-primary mr-1 mb-1">${f}</span>`).join('')
            : '<span class="text-muted">None</span>';

        const customFieldsHtml = templateFields.custom_fields.length > 0
            ? templateFields.custom_fields.map(f => `<span class="badge badge-info mr-1 mb-1">${f}</span>`).join('')
            : '<span class="text-muted">None</span>';

        const trendsHtml = `
            <div class="row">
                <div class="col-12">
                    <h5><i class="fas fa-chart-line"></i> Batch Trends</h5>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-star"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Quality Score</span>
                            <span class="info-box-number">${trends.quality_score.toFixed(1)}% ${getTrendIcon(trends.quality_trend)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-percentage"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Avg Confidence</span>
                            <span class="info-box-number">${trends.avg_confidence.toFixed(1)}% ${getTrendIcon(trends.confidence_trend)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-check-double"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Avg Matched Fields</span>
                            <span class="info-box-number">${trends.avg_matched_fields.toFixed(1)} ${getTrendIcon(trends.matched_fields_trend)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-times"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Avg Mismatched Fields</span>
                            <span class="info-box-number">${trends.avg_mismatched_fields.toFixed(1)} ${getTrendIcon(trends.mismatched_fields_trend)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-database"></i> Core Fields Used</h6>
                    <div class="mb-2">
                        ${coreFieldsHtml}
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-cog"></i> Custom Fields Used</h6>
                    <div class="mb-2">
                        ${customFieldsHtml}
                    </div>
                </div>
            </div>
        `;

        trendsContainer.innerHTML = trendsHtml;
    }
}

// Initialize analytics when page loads
let analyticsModule = null;

document.addEventListener('DOMContentLoaded', function() {
    analyticsModule = new MatchStatusAnalytics();
    
    // Load analytics if batch is selected
    const trendsContainer = document.getElementById('trends-container');
    if (trendsContainer) {
        const batchId = trendsContainer.dataset.batchId;
        if (batchId) {
            analyticsModule.initialize(batchId);
        }
    }
});

// Field Breakdown Modal Module
class FieldBreakdownModal {
    constructor() {
        this.currentResultId = null;
        this.currentFilter = 'all';
        this.breakdownData = null;
    }

    async loadBreakdown(resultId) {
        this.currentResultId = resultId;
        this.currentFilter = 'all';

        const loadingEl = document.getElementById(`breakdown-loading-${resultId}`);
        const containerEl = document.getElementById(`breakdown-container-${resultId}`);
        const errorEl = document.getElementById(`breakdown-error-${resultId}`);

        loadingEl.style.display = 'block';
        containerEl.style.display = 'none';
        errorEl.style.display = 'none';

        try {
            const response = await fetch(`/api/field-breakdown/${resultId}`);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (!data || !data.core_fields || Object.keys(data.core_fields).length === 0) {
                this.showEmptyState(resultId);
                return;
            }

            this.breakdownData = data;
            this.renderFieldTable(resultId, data);
            this.updateFilterCounts(resultId, data);

            loadingEl.style.display = 'none';
            containerEl.style.display = 'block';

            document.getElementById(`export-csv-${resultId}`).disabled = false;
        } catch (error) {
            console.error('Failed to load field breakdown:', error);
            this.showError(resultId, 'Unable to load field breakdown. Please try again.');
            loadingEl.style.display = 'none';
            document.getElementById(`export-csv-${resultId}`).disabled = true;
        }
    }

    renderFieldTable(resultId, data) {
        const coreFieldsBody = document.getElementById(`core-fields-body-${resultId}`);
        const templateFieldsBody = document.getElementById(`template-fields-body-${resultId}`);
        const templateFieldsSection = document.getElementById(`template-fields-section-${resultId}`);

        coreFieldsBody.innerHTML = '';
        templateFieldsBody.innerHTML = '';

        if (data.core_fields) {
            Object.entries(data.core_fields).forEach(([fieldName, fieldData]) => {
                const row = this.createFieldRow(fieldName, fieldData);
                coreFieldsBody.appendChild(row);
            });
        }

        if (data.template_fields && Object.keys(data.template_fields).length > 0) {
            templateFieldsSection.style.display = 'block';
            Object.entries(data.template_fields).forEach(([fieldName, fieldData]) => {
                const row = this.createFieldRow(fieldName, fieldData);
                templateFieldsBody.appendChild(row);
            });
        } else {
            templateFieldsSection.style.display = 'none';
        }

        this.applyFilter(resultId, this.currentFilter);
    }

    createFieldRow(fieldName, fieldData) {
        const row = document.createElement('tr');
        row.dataset.status = fieldData.status;

        const statusBadge = this.getStatusBadge(fieldData.status);
        const confidenceDisplay = fieldData.confidence !== null && fieldData.confidence !== undefined
            ? `<span class="badge badge-${this.getConfidenceBadgeColor(fieldData.confidence)}">${fieldData.confidence.toFixed(1)}%</span>`
            : '<span class="text-muted">N/A</span>';

        const uploadedClass = this.getValueClass(fieldData.status, true);
        const existingClass = this.getValueClass(fieldData.status, false);

        row.innerHTML = `
            <td><strong>${this.escapeHtml(fieldName)}</strong></td>
            <td>${statusBadge}</td>
            <td class="${uploadedClass}">${this.escapeHtml(fieldData.uploaded ?? 'N/A')}</td>
            <td class="${existingClass}">${this.escapeHtml(fieldData.existing ?? 'N/A')}</td>
            <td class="text-muted small">${this.escapeHtml(fieldData.uploaded_normalized ?? '-')}</td>
            <td class="text-muted small">${this.escapeHtml(fieldData.existing_normalized ?? '-')}</td>
            <td class="text-center">${confidenceDisplay}</td>
        `;

        return row;
    }

    getStatusBadge(status) {
        const badges = {
            'match': '<span class="badge badge-success"><i class="fas fa-check"></i> Match</span>',
            'mismatch': '<span class="badge badge-danger"><i class="fas fa-times"></i> Mismatch</span>',
            'new': '<span class="badge badge-info"><i class="fas fa-plus"></i> New</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }

    getValueClass(status, isUploaded) {
        if (status === 'match') return 'text-success';
        if (status === 'mismatch') return isUploaded ? 'text-danger font-weight-bold' : 'text-muted';
        if (status === 'new') return 'text-info';
        return '';
    }

    getConfidenceBadgeColor(confidence) {
        if (confidence >= 90) return 'success';
        if (confidence >= 75) return 'primary';
        if (confidence >= 60) return 'warning';
        return 'danger';
    }

    updateFilterCounts(resultId, data) {
        let allCount = 0;
        let matchedCount = 0;
        let mismatchedCount = 0;
        let newCount = 0;

        const countFields = (fields) => {
            Object.values(fields).forEach(field => {
                allCount++;
                if (field.status === 'match') matchedCount++;
                else if (field.status === 'mismatch') mismatchedCount++;
                else if (field.status === 'new') newCount++;
            });
        };

        if (data.core_fields) countFields(data.core_fields);
        if (data.template_fields) countFields(data.template_fields);

        document.getElementById(`filter-all-count-${resultId}`).textContent = allCount;
        document.getElementById(`filter-matched-count-${resultId}`).textContent = matchedCount;
        document.getElementById(`filter-mismatched-count-${resultId}`).textContent = mismatchedCount;
        document.getElementById(`filter-new-count-${resultId}`).textContent = newCount;
        document.getElementById(`matched-count-${resultId}`).textContent = matchedCount;
        document.getElementById(`total-count-${resultId}`).textContent = allCount;
    }

    applyFilter(resultId, filterType) {
        this.currentFilter = filterType;

        const coreFieldsBody = document.getElementById(`core-fields-body-${resultId}`);
        const templateFieldsBody = document.getElementById(`template-fields-body-${resultId}`);
        const noResultsEl = document.getElementById(`no-results-${resultId}`);

        let visibleCount = 0;

        const filterRows = (tbody) => {
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const status = row.dataset.status;
                const shouldShow = filterType === 'all' ||
                    (filterType === 'matched' && status === 'match') ||
                    (filterType === 'mismatched' && status === 'mismatch') ||
                    (filterType === 'new' && status === 'new');

                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });
        };

        filterRows(coreFieldsBody);
        filterRows(templateFieldsBody);

        document.getElementById(`visible-count-${resultId}`).textContent = visibleCount;

        if (visibleCount === 0) {
            noResultsEl.style.display = 'block';
        } else {
            noResultsEl.style.display = 'none';
        }

        const filterButtons = document.querySelectorAll(`button[data-result-id="${resultId}"][data-filter]`);
        filterButtons.forEach(btn => {
            if (btn.dataset.filter === filterType) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    exportToCSV(resultId) {
        if (!this.breakdownData) {
            alert('No data available to export.');
            return;
        }

        try {
            const csv = this.generateCSV(this.breakdownData);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = this.getFilename(resultId);
            link.click();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('CSV export failed:', error);
            alert('Export failed. Please try again.');
        }
    }

    generateCSV(data) {
        const headers = [
            'Field Name',
            'Category',
            'Status',
            'Uploaded Value',
            'Existing Value',
            'Uploaded Normalized',
            'Existing Normalized',
            'Confidence Score'
        ];

        let csv = headers.map(h => this.escapeCsvValue(h)).join(',') + '\n';

        const addRows = (fields, category) => {
            Object.entries(fields).forEach(([fieldName, fieldData]) => {
                const row = [
                    fieldName,
                    category,
                    fieldData.status,
                    fieldData.uploaded ?? '',
                    fieldData.existing ?? '',
                    fieldData.uploaded_normalized ?? '',
                    fieldData.existing_normalized ?? '',
                    fieldData.confidence !== null && fieldData.confidence !== undefined ? fieldData.confidence.toFixed(1) : ''
                ];
                csv += row.map(v => this.escapeCsvValue(v)).join(',') + '\n';
            });
        };

        if (data.core_fields) addRows(data.core_fields, 'core');
        if (data.template_fields) addRows(data.template_fields, 'template');

        return csv;
    }

    escapeCsvValue(value) {
        const stringValue = String(value);
        if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
            return '"' + stringValue.replace(/"/g, '""') + '"';
        }
        return stringValue;
    }

    getFilename(resultId) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
        return `field-breakdown-${resultId}-${timestamp}.csv`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showEmptyState(resultId) {
        const loadingEl = document.getElementById(`breakdown-loading-${resultId}`);
        const errorEl = document.getElementById(`breakdown-error-${resultId}`);
        const errorMessageEl = document.getElementById(`error-message-${resultId}`);

        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        errorMessageEl.textContent = 'No field comparison data available for this match result.';
        document.getElementById(`export-csv-${resultId}`).disabled = true;
    }

    showError(resultId, message) {
        const errorEl = document.getElementById(`breakdown-error-${resultId}`);
        const errorMessageEl = document.getElementById(`error-message-${resultId}`);

        errorEl.style.display = 'block';
        errorMessageEl.textContent = message;
    }
}

// Initialize field breakdown modal
const fieldBreakdownModal = new FieldBreakdownModal();

// Event listeners for modal open
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-toggle="modal"][data-target^="#breakdownModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.target.replace('#breakdownModal', '');
            fieldBreakdownModal.loadBreakdown(modalId);
        });
    });

    document.addEventListener('click', function(e) {
        if (e.target.matches('button[data-filter]')) {
            const resultId = e.target.dataset.resultId;
            const filter = e.target.dataset.filter;
            fieldBreakdownModal.applyFilter(resultId, filter);
        }

        if (e.target.matches('button[id^="export-csv-"]') || e.target.closest('button[id^="export-csv-"]')) {
            const button = e.target.matches('button[id^="export-csv-"]') ? e.target : e.target.closest('button[id^="export-csv-"]');
            const resultId = button.dataset.resultId;
            fieldBreakdownModal.exportToCSV(resultId);
        }
    });
});

</script>
@endpush
