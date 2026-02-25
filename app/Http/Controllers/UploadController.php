<?php

namespace App\Http\Controllers;

use App\Http\Traits\AuthorizesTemplates;
use App\Imports\RecordImport;
use App\Models\ColumnMappingTemplate;
use App\Models\UploadBatch;
use App\Services\FileValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    use AuthorizesTemplates;
    /**
     * Display the upload form
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $templates = ColumnMappingTemplate::forUser(auth()->id());
        return view('pages.upload', compact('templates'));
    }

    /**
     * Process the uploaded file
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'template_id' => 'nullable|integer|exists:column_mapping_templates,id',
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'The file must be in CSV, XLSX, or XLS format.',
            'file.max' => 'The file size must not exceed 10MB. Please reduce the file size or split it into smaller files.',
            'template_id.exists' => 'The selected template does not exist.',
        ]);

        Log::info('File upload started', [
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_size' => $request->file('file')->getSize(),
            'template_id' => $request->input('template_id'),
            'user' => auth()->user()->name,
        ]);

        try {
            // Load template with fields relationship if provided
            $template = null;
            if ($request->has('template_id')) {
                $template = $this->findAuthorizedTemplate($request->template_id, true);
                
                if (!$template) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Template not found or you do not have permission to use it.',
                        ], 404);
                    }
                    
                    return redirect()->route('upload.index')
                        ->with('error', 'Template not found or you do not have permission to use it.');
                }
            }
            
            // Validate columns against expected schema (strict validation)
            $validator = new FileValidationService();
            $validation = $validator->validateColumns($request->file('file'), $template);
            
            if (!$validation['valid']) {
                Log::warning('File column validation failed', [
                    'errors' => $validation['errors'],
                    'info' => $validation['info'],
                    'user' => auth()->user()->name,
                    'template' => $template ? $template->name : 'none',
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'File validation failed. Please check the errors below and correct your file.',
                        'validation_errors' => $validation['errors'],
                        'validation_info' => $validation['info'],
                    ], 422);
                }
                
                return redirect()->route('upload.index')
                    ->with('error', 'File validation failed. Please check the errors below and correct your file.')
                    ->with('validation_errors', $validation['errors'])
                    ->with('validation_info', $validation['info']);
            }
            
            // Log successful validation
            Log::info('File column validation passed', [
                'info' => $validation['info'],
                'user' => auth()->user()->name,
                'template' => $template ? $template->name : 'none',
            ]);

            $batch = UploadBatch::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => auth()->user()->name,
                'uploaded_at' => now(),
                'status' => 'PROCESSING',
            ]);

            Log::info('Upload batch created', [
                'batch_id' => $batch->id,
                'file_name' => $batch->file_name,
                'user' => auth()->user()->name,
            ]);

            // Import and process the Excel file with optional template
            $import = new RecordImport($batch->id, $template);
            Excel::import($import, $request->file('file'));

            // Get column mapping summary from the import
            $mappingSummary = $import->getColumnMappingSummary();

            $batch->update(['status' => 'COMPLETED']);

            Log::info('Upload batch completed', [
                'batch_id' => $batch->id,
                'file_name' => $batch->file_name,
                'user' => auth()->user()->name,
                'template' => $template ? $template->name : 'none',
            ]);

            // If this is an API request, return JSON response with mapping summary
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'batch_id' => $batch->id,
                    'file_name' => $batch->file_name,
                    'column_mapping' => $mappingSummary,
                ]);
            }

            // For web requests, redirect with mapping summary in session
            return redirect()->route('results.index', ['batch_id' => $batch->id])
                ->with('success', "Batch #{$batch->id} (File: {$batch->file_name}) processed successfully. Showing match results below.")
                ->with('column_mapping', $mappingSummary);

        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update(['status' => 'FAILED']);
                
                Log::error('Upload batch failed', [
                    'batch_id' => $batch->id,
                    'file_name' => $batch->file_name,
                    'error' => $e->getMessage(),
                    'user' => auth()->user()->name,
                ]);
            } else {
                Log::error('File upload failed before batch creation', [
                    'file_name' => $request->file('file')->getClientOriginalName(),
                    'error' => $e->getMessage(),
                    'user' => auth()->user()->name,
                ]);
            }

            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An error occurred while processing your file. Please try again or contact support if the problem persists. Error: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('upload.index')
                ->with('error', 'An error occurred while processing your file. Please try again or contact support if the problem persists. Error: ' . $e->getMessage());
        }
    }
}
