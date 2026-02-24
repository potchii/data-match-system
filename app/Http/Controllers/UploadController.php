<?php

namespace App\Http\Controllers;

use App\Imports\RecordImport;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    /**
     * Display the upload form
     */
    public function index()
    {
        return view('pages.upload');
    }

    /**
     * Process the uploaded file
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $batch = UploadBatch::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => auth()->user()->name,
                'uploaded_at' => now(),
                'status' => 'PROCESSING',
            ]);

            // Import and process the Excel file
            $import = new RecordImport($batch->id);
            Excel::import($import, $request->file('file'));

            // Get column mapping summary from the import
            $mappingSummary = $import->getColumnMappingSummary();

            $batch->update(['status' => 'COMPLETED']);

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
            }

            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('upload.index')
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
