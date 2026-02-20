<?php

namespace App\Http\Controllers;

use App\Imports\RecordImport;
use App\Models\UploadBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB safety
        ]);

        try {
            // Start the batch as PROCESSING
            $batch = UploadBatch::create([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => 'System Admin',
                'uploaded_at' => now(),
                'status' => 'PROCESSING',
            ]);

            // Execute the import
            Excel::import(new RecordImport($batch->id), $request->file('file'));

            $batch->update(['status' => 'COMPLETED']);

            return back()->with('success', "Batch #{$batch->id} (File: {$batch->file_name}) processed successfully.");

        } catch (\Exception $e) {
            if (isset($batch)) {
                $batch->update(['status' => 'FAILED']);
            }

            return back()->withErrors('Error processing file: '.$e->getMessage());
        }
    }
}
