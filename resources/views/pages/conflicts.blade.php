@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Conflict Resolution</h1>
        <p class="text-gray-600 mt-2">Review and resolve conflicted template field values</p>
    </div>

    @if($conflicts->count() > 0)
        <div class="bg-white rounded-lg shadow">
            <!-- Filters -->
            <div class="p-6 border-b border-gray-200">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                        <select name="batch_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Batches</option>
                            @foreach(\App\Models\UploadBatch::all() as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                    Batch #{{ $batch->id }} - {{ $batch->filename }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Field Name</label>
                        <input type="text" name="field_name" placeholder="Filter by field name" 
                               value="{{ request('field_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Record ID</label>
                        <input type="text" name="main_system_id" placeholder="Filter by record ID" 
                               value="{{ request('main_system_id') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                </form>
                <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Apply Filters
                </button>
            </div>

            <!-- Conflicts Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Record</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Field</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Existing Value</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">New Value</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Batch</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conflicts as $conflict)
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <a href="{{ route('main-system.show', $conflict->mainSystem->id) }}" class="text-blue-600 hover:underline">
                                        {{ $conflict->mainSystem->uid }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $conflict->templateField->field_name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($conflict->conflictingValue)
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            {{ $conflict->conflictingValue->value }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">
                                        {{ $conflict->value }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($conflict->batch)
                                        Batch #{{ $conflict->batch->id }}
                                    @else
                                        <span class="text-gray-500">Deleted</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex gap-2">
                                        <button onclick="resolveConflict({{ $conflict->id }}, 'keep_existing')" 
                                                class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs">
                                            Keep Existing
                                        </button>
                                        <button onclick="resolveConflict({{ $conflict->id }}, 'use_new')" 
                                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                                            Use New
                                        </button>
                                        <button onclick="openEditModal({{ $conflict->id }})" 
                                                class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-xs">
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $conflicts->links() }}
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 text-lg">No conflicts to resolve</p>
            <p class="text-gray-500 mt-2">All template field values have been reviewed</p>
        </div>
    @endif
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h2 class="text-xl font-bold mb-4">Edit Value</h2>
        <form id="editForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Value</label>
                <input type="text" id="customValue" name="custom_value" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentConflictId = null;

function resolveConflict(conflictId, resolution) {
    fetch(`/conflicts/${conflictId}/resolve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ resolution })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function openEditModal(conflictId) {
    currentConflictId = conflictId;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    currentConflictId = null;
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const customValue = document.getElementById('customValue').value;
    
    fetch(`/conflicts/${currentConflictId}/resolve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
            resolution: 'edit_manually',
            custom_value: customValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});
</script>
@endsection
