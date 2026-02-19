<?php

namespace App\Http\Controllers;

use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            // TODO: Integrate with Excel import and matching service
            // Excel::import(new RecordImport($batch->id), $request->file('file'));

            $batch->update(['status' => 'COMPLETED']);

            return redirect()->route('upload.index')
                ->with('success', "Batch #{$batch->id} (File: {$batch->file_name}) processed successfully.");

        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update(['status' => 'FAILED']);
            }

            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            return redirect()->route('upload.index')
                ->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }
}
