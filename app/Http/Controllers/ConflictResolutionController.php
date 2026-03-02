<?php

namespace App\Http\Controllers;

use App\Models\TemplateFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConflictResolutionController extends Controller
{
    /**
     * List all template field values that need review
     */
    public function index(Request $request)
    {
        $query = TemplateFieldValue::where('needs_review', true)
            ->with(['mainSystem', 'templateField', 'batch', 'conflictingValue']);

        // Filter by batch
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Filter by field name
        if ($request->filled('field_name')) {
            $query->whereHas('templateField', function ($q) use ($request) {
                $q->where('field_name', $request->field_name);
            });
        }

        // Filter by MainSystem record
        if ($request->filled('main_system_id')) {
            $query->where('main_system_id', $request->main_system_id);
        }

        $conflicts = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('pages.conflicts', compact('conflicts'));
    }

    /**
     * Resolve a single conflict
     */
    public function resolve(Request $request, int $id)
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

            Log::info('Conflict resolved', [
                'conflict_id' => $id,
                'resolution' => $validated['resolution'],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conflict resolved successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error resolving conflict', [
                'conflict_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Resolution failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Resolve multiple conflicts at once
     */
    public function bulkResolve(Request $request)
    {
        $validated = $request->validate([
            'conflict_ids' => 'required|array',
            'conflict_ids.*' => 'integer|exists:template_field_values,id',
            'resolution' => 'required|in:keep_existing,use_new',
        ]);

        try {
            $conflicts = TemplateFieldValue::whereIn('id', $validated['conflict_ids'])
                ->where('needs_review', true)
                ->get();

            $resolved = 0;
            $failed = 0;

            foreach ($conflicts as $conflict) {
                try {
                    $conflict->resolveConflict($validated['resolution']);
                    $resolved++;
                } catch (\Exception $e) {
                    Log::warning('Failed to resolve conflict in bulk', [
                        'conflict_id' => $conflict->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            Log::info('Bulk conflict resolution completed', [
                'total' => count($validated['conflict_ids']),
                'resolved' => $resolved,
                'failed' => $failed,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Resolved {$resolved} conflicts" . ($failed > 0 ? ", {$failed} failed" : ''),
                'resolved' => $resolved,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulk conflict resolution', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Bulk resolution failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
