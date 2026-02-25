<?php

namespace App\Http\Controllers;

use App\Http\Traits\AuthorizesTemplates;
use App\Models\TemplateField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TemplateFieldController extends Controller
{
    use AuthorizesTemplates;
    /**
     * Display a listing of template fields
     *
     * @param int $templateId
     * @return JsonResponse
     */
    public function index(int $templateId): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($templateId);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $fields = $template->fields;

        return response()->json([
            'success' => true,
            'data' => $fields,
        ]);
    }

    /**
     * Store a newly created template field
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function store(Request $request, int $templateId): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($templateId);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $validated = $request->validate([
            'field_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/i',
            ],
            'field_type' => 'required|in:string,integer,date,boolean,decimal',
            'is_required' => 'boolean',
        ], [
            'field_name.required' => 'Field name is required.',
            'field_name.regex' => 'Field name can only contain letters, numbers, and underscores (e.g., customer_age, order_total).',
            'field_type.required' => 'Field type is required.',
            'field_type.in' => 'Field type must be one of: string, integer, date, boolean, or decimal.',
        ]);

        // Check uniqueness within template
        $exists = TemplateField::where('template_id', $templateId)
            ->where('field_name', $validated['field_name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "A field named '{$validated['field_name']}' already exists in this template. Please use a different name.",
            ], 422);
        }

        $field = TemplateField::create([
            'template_id' => $templateId,
            'field_name' => $validated['field_name'],
            'field_type' => $validated['field_type'],
            'is_required' => $validated['is_required'] ?? false,
        ]);

        Log::info('Template field created', [
            'template_id' => $templateId,
            'field_name' => $field->field_name,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template field created successfully',
            'data' => $field,
        ], 201);
    }

    /**
     * Update the specified template field
     *
     * @param Request $request
     * @param int $templateId
     * @param int $fieldId
     * @return JsonResponse
     */
    public function update(Request $request, int $templateId, int $fieldId): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($templateId);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $field = TemplateField::where('id', $fieldId)
            ->where('template_id', $templateId)
            ->first();

        if (!$field) {
            return response()->json([
                'success' => false,
                'message' => 'Template field not found.',
            ], 404);
        }

        $validated = $request->validate([
            'field_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/i',
            ],
            'field_type' => 'sometimes|required|in:string,integer,date,boolean,decimal',
            'is_required' => 'sometimes|boolean',
        ], [
            'field_name.regex' => 'Field name can only contain letters, numbers, and underscores (e.g., customer_age, order_total).',
            'field_type.in' => 'Field type must be one of: string, integer, date, boolean, or decimal.',
        ]);

        // Check uniqueness if field_name is being updated
        if (isset($validated['field_name']) && $validated['field_name'] !== $field->field_name) {
            $exists = TemplateField::where('template_id', $templateId)
                ->where('field_name', $validated['field_name'])
                ->where('id', '!=', $fieldId)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => "A field named '{$validated['field_name']}' already exists in this template. Please use a different name.",
                ], 422);
            }
        }

        $field->update($validated);

        Log::info('Template field updated', [
            'template_id' => $templateId,
            'field_id' => $fieldId,
            'field_name' => $field->field_name,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template field updated successfully',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Remove the specified template field
     *
     * @param int $templateId
     * @param int $fieldId
     * @return JsonResponse
     */
    public function destroy(int $templateId, int $fieldId): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($templateId);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $field = TemplateField::where('id', $fieldId)
            ->where('template_id', $templateId)
            ->first();

        if (!$field) {
            return response()->json([
                'success' => false,
                'message' => 'Template field not found.',
            ], 404);
        }

        $fieldName = $field->field_name;
        $field->delete();

        Log::info('Template field deleted', [
            'template_id' => $templateId,
            'field_id' => $fieldId,
            'field_name' => $fieldName,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template field deleted successfully',
        ]);
    }
}
