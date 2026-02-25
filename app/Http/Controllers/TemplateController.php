<?php

namespace App\Http\Controllers;

use App\Http\Traits\AuthorizesTemplates;
use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TemplateController extends Controller
{
    use AuthorizesTemplates;
    /**
     * Display a listing of the user's templates (web view)
     *
     * @return View
     */
    public function indexView(): View
    {
        $templates = ColumnMappingTemplate::forUser(Auth::id());
        
        return view('pages.templates', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     * 
     * @return View
     */
    public function create(): View
    {
        return view('pages.template-form');
    }

    /**
     * Show the form for editing a template
     *
     * @param int $id
     * @return View|RedirectResponse
     */
    public function edit(int $id): View|RedirectResponse
    {
        $template = $this->findAuthorizedTemplate($id, true);

        if (!$template) {
            return redirect()->route('templates.index')
                ->with('error', 'Template not found or you do not have permission to access it.');
        }

        return view('pages.template-form', compact('template'));
    }

    /**
     * Display a listing of the user's templates (API)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $templates = ColumnMappingTemplate::forUser(Auth::id());
        
        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Store a newly created template (web form)
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeWeb(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        
        $request->validate([
            'name' => 'required|string|max:255|unique:column_mapping_templates,name,NULL,id,user_id,' . $userId,
            'excel_columns' => 'required|array|min:1',
            'excel_columns.*' => 'required|string',
            'system_fields' => 'required|array|min:1',
            'system_fields.*' => 'required|string',
            'field_names' => 'nullable|array',
            'field_names.*' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/i'],
            'field_types' => 'nullable|array',
            'field_types.*' => 'required|in:string,integer,date,boolean,decimal',
            'field_required' => 'nullable|array',
        ], [
            'name.required' => 'Template name is required.',
            'name.unique' => 'You already have a template with this name. Please choose a different name.',
            'excel_columns.required' => 'At least one column mapping is required.',
            'excel_columns.min' => 'At least one column mapping is required.',
            'field_names.*.regex' => 'Field names can only contain letters, numbers, and underscores (e.g., customer_age, order_total).',
            'field_types.*.in' => 'Field type must be one of: string, integer, date, boolean, or decimal.',
        ]);

        // Build mappings object from arrays
        $mappings = [];
        $excelColumns = $request->input('excel_columns');
        $systemFields = $request->input('system_fields');
        
        foreach ($excelColumns as $index => $excelColumn) {
            if (isset($systemFields[$index])) {
                $mappings[$excelColumn] = $systemFields[$index];
            }
        }

        $template = ColumnMappingTemplate::create([
            'user_id' => $userId,
            'name' => $request->input('name'),
            'mappings' => $mappings,
        ]);

        // Handle custom fields
        $this->syncTemplateFields($template, $request);

        Log::info('Template created', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user' => Auth::user()->name,
            'field_count' => $template->fields()->count(),
        ]);

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully');
    }

    /**
     * Store a newly created template (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        $validated = $request->validate(
            ColumnMappingTemplate::validationRules($userId)
        );

        $template = ColumnMappingTemplate::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'mappings' => $validated['mappings'],
        ]);

        Log::info('Template created via API', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Display the specified template
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($id);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Update the specified template (web form)
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function updateWeb(Request $request, int $id): RedirectResponse
    {
        $template = $this->findAuthorizedTemplate($id);

        if (!$template) {
            return redirect()->route('templates.index')
                ->with('error', 'Template not found or you do not have permission to access it.');
        }

        $userId = Auth::id();
        
        $request->validate([
            'name' => 'required|string|max:255|unique:column_mapping_templates,name,' . $id . ',id,user_id,' . $userId,
            'excel_columns' => 'required|array|min:1',
            'excel_columns.*' => 'required|string',
            'system_fields' => 'required|array|min:1',
            'system_fields.*' => 'required|string',
            'field_names' => 'nullable|array',
            'field_names.*' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/i'],
            'field_types' => 'nullable|array',
            'field_types.*' => 'required|in:string,integer,date,boolean,decimal',
            'field_required' => 'nullable|array',
        ], [
            'name.required' => 'Template name is required.',
            'name.unique' => 'You already have a template with this name. Please choose a different name.',
            'excel_columns.required' => 'At least one column mapping is required.',
            'excel_columns.min' => 'At least one column mapping is required.',
            'field_names.*.regex' => 'Field names can only contain letters, numbers, and underscores (e.g., customer_age, order_total).',
            'field_types.*.in' => 'Field type must be one of: string, integer, date, boolean, or decimal.',
        ]);

        // Build mappings object from arrays
        $mappings = [];
        $excelColumns = $request->input('excel_columns');
        $systemFields = $request->input('system_fields');
        
        foreach ($excelColumns as $index => $excelColumn) {
            if (isset($systemFields[$index])) {
                $mappings[$excelColumn] = $systemFields[$index];
            }
        }

        $template->update([
            'name' => $request->input('name'),
            'mappings' => $mappings,
        ]);

        // Handle custom fields
        $this->syncTemplateFields($template, $request);

        Log::info('Template updated', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user' => Auth::user()->name,
            'field_count' => $template->fields()->count(),
        ]);

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully');
    }

    /**
     * Update the specified template (API)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($id);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $userId = Auth::id();
        
        $validated = $request->validate(
            ColumnMappingTemplate::validationRules($userId, $id)
        );

        $template->update([
            'name' => $validated['name'],
            'mappings' => $validated['mappings'],
        ]);

        Log::info('Template updated via API', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Remove the specified template (web)
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroyWeb(int $id): RedirectResponse
    {
        $template = $this->findAuthorizedTemplate($id);

        if (!$template) {
            return redirect()->route('templates.index')
                ->with('error', 'Template not found or you do not have permission to access it.');
        }

        $templateName = $template->name;
        $template->delete();

        Log::info('Template deleted', [
            'template_id' => $id,
            'template_name' => $templateName,
            'user' => Auth::user()->name,
        ]);

        return redirect()->route('templates.index')
            ->with('success', 'Template deleted successfully');
    }

    /**
     * Remove the specified template (API)
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $template = $this->findAuthorizedTemplate($id);

        if (!$template) {
            return $this->unauthorizedTemplateResponse();
        }

        $templateName = $template->name;
        $template->delete();

        Log::info('Template deleted via API', [
            'template_id' => $id,
            'template_name' => $templateName,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * Sync template fields from request
     *
     * @param ColumnMappingTemplate $template
     * @param Request $request
     * @return void
     */
    protected function syncTemplateFields(ColumnMappingTemplate $template, Request $request): void
    {
        $fieldNames = $request->input('field_names', []);
        $fieldTypes = $request->input('field_types', []);
        $fieldRequired = $request->input('field_required', []);

        // Delete all existing fields
        $template->fields()->delete();

        // Create new fields
        if (!empty($fieldNames)) {
            foreach ($fieldNames as $index => $fieldName) {
                if (empty($fieldName)) {
                    continue;
                }

                // Check for duplicate field names
                $exists = TemplateField::where('template_id', $template->id)
                    ->where('field_name', $fieldName)
                    ->exists();

                if ($exists) {
                    Log::warning('Duplicate field name skipped', [
                        'template_id' => $template->id,
                        'field_name' => $fieldName,
                    ]);
                    continue;
                }

                TemplateField::create([
                    'template_id' => $template->id,
                    'field_name' => $fieldName,
                    'field_type' => $fieldTypes[$index] ?? 'string',
                    'is_required' => in_array($index, $fieldRequired),
                ]);
            }
        }
    }
}
