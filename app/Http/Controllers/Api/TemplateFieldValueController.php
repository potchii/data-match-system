<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemplateFieldValue;
use App\Models\MainSystem;
use App\Models\UploadBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TemplateFieldValueController extends Controller
{
    /**
     * Get all template field values for a MainSystem record
     */
    public function getMainSystemTemplateFields(int $mainSystemId): JsonResponse
    {
        try {
            $mainSystem = MainSystem::findOrFail($mainSystemId);
            
            $templateFields = $mainSystem->templateFieldValues()
                ->with(['templateField', 'batch'])
                ->get()
                ->map(function ($tfv) {
                    return [
                        'id' => $tfv->id,
                        'field_name' => $tfv->templateField->field_name,
                        'field_type' => $tfv->templateField->field_type,
                        'value' => $tfv->value,
                        'previous_value' => $tfv->previous_value,
                        'batch_id' => $tfv->batch_id,
                        'batch_filename' => $tfv->batch?->filename,
                        'needs_review' => $tfv->needs_review,
                        'created_at' => $tfv->created_at,
                        'updated_at' => $tfv->updated_at,
                    ];
                });

            return response()->json([
                'main_system_id' => $mainSystemId,
                'template_fields' => $templateFields,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'MainSystem record not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching template fields', [
                'main_system_id' => $mainSystemId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch template fields'
            ], 500);
        }
    }

    /**
     * Get a specific template field value
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tfv = TemplateFieldValue::with(['mainSystem', 'templateField', 'batch'])
                ->findOrFail($id);

            return response()->json([
                'id' => $tfv->id,
                'main_system_id' => $tfv->main_system_id,
                'main_system_uid' => $tfv->mainSystem->uid,
                'field_name' => $tfv->templateField->field_name,
                'field_type' => $tfv->templateField->field_type,
                'value' => $tfv->value,
                'previous_value' => $tfv->previous_value,
                'batch_id' => $tfv->batch_id,
                'batch_filename' => $tfv->batch?->filename,
                'needs_review' => $tfv->needs_review,
                'conflict_with' => $tfv->conflict_with,
                'created_at' => $tfv->created_at,
                'updated_at' => $tfv->updated_at,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Template field value not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching template field value', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch template field value'
            ], 500);
        }
    }

    /**
     * Update a template field value
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string',
        ]);

        try {
            $tfv = TemplateFieldValue::findOrFail($id);
            
            // Validate value against field type
            $validation = $tfv->templateField->validateValue($validated['value']);
            if (!$validation['valid']) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => $validation['error']
                ], 422);
            }

            // Store previous value
            $tfv->update([
                'previous_value' => $tfv->value,
                'value' => $validated['value'],
                'needs_review' => false,
            ]);

            Log::info('Template field value updated', [
                'id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template field value updated',
                'data' => [
                    'id' => $tfv->id,
                    'value' => $tfv->value,
                    'previous_value' => $tfv->previous_value,
                    'updated_at' => $tfv->updated_at,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Template field value not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating template field value', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to update template field value'
            ], 500);
        }
    }

    /**
     * Get all template field values for a batch
     */
    public function getBatchTemplateFields(int $batchId): JsonResponse
    {
        try {
            $batch = UploadBatch::findOrFail($batchId);
            
            $templateFields = TemplateFieldValue::byBatch($batchId)
                ->with(['mainSystem', 'templateField'])
                ->paginate(50);

            return response()->json([
                'batch_id' => $batchId,
                'batch_filename' => $batch->filename,
                'data' => $templateFields->items(),
                'pagination' => [
                    'total' => $templateFields->total(),
                    'per_page' => $templateFields->perPage(),
                    'current_page' => $templateFields->currentPage(),
                    'last_page' => $templateFields->lastPage(),
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Batch not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching batch template fields', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch batch template fields'
            ], 500);
        }
    }

    /**
     * Get all template field values that need review
     */
    public function getConflicts(Request $request): JsonResponse
    {
        try {
            $query = TemplateFieldValue::where('needs_review', true)
                ->with(['mainSystem', 'templateField', 'batch', 'conflictingValue']);

            if ($request->filled('batch_id')) {
                $query->where('batch_id', $request->batch_id);
            }

            if ($request->filled('field_name')) {
                $query->whereHas('templateField', function ($q) use ($request) {
                    $q->where('field_name', $request->field_name);
                });
            }

            $conflicts = $query->paginate(50);

            return response()->json([
                'data' => $conflicts->items(),
                'pagination' => [
                    'total' => $conflicts->total(),
                    'per_page' => $conflicts->perPage(),
                    'current_page' => $conflicts->currentPage(),
                    'last_page' => $conflicts->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching conflicts', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Server error',
                'message' => 'Unable to fetch conflicts'
            ], 500);
        }
    }

    /**
     * Resolve a conflict
     */
    public function resolveConflict(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|in:keep_existing,use_new,edit_manually',
            'custom_value' => 'nullable|string',
        ]);

        try {
            $conflict = TemplateFieldValue::findOrFail($id);

            if (!$conflict->needs_review) {
                return response()->json([
                    'error' => 'Conflict already resolved',
                    'message' => 'This conflict has already been resolved.'
                ], 409);
            }

            $conflict->resolveConflict(
                $validated['resolution'],
                $validated['custom_value'] ?? null
            );

            Log::info('Conflict resolved via API', [
                'conflict_id' => $id,
                'resolution' => $validated['resolution'],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conflict resolved successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Conflict not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error resolving conflict via API', [
                'conflict_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Resolution failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
