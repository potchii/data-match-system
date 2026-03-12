<?php

namespace App\Http\Controllers;

use App\Models\MainSystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MainSystemController extends Controller
{
    /**
     * Display a listing of main system records.
     */
    public function index(Request $request)
    {
        $query = MainSystem::with('originBatch');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uid', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('id', 'desc')->paginate(20);

        return view('pages.main-system', compact('records'));
    }

    /**
     * Show form for creating a new record
     */
    public function create()
    {
        return view('pages.main-system-form', ['record' => null]);
    }

    /**
     * Show form for editing a record
     */
    public function edit(MainSystem $record)
    {
        return view('pages.main-system-form', compact('record'));
    }

    /**
     * Store a newly created record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'regs_no' => 'nullable|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'suffix' => 'nullable|string',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string',
            'civil_status' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
            'category' => 'nullable|string',
            'registration_date' => 'nullable|date',
        ]);

        $validated['uid'] = $this->generateUid();
        MainSystem::create($validated);

        return redirect()->route('main-system.index')->with('success', 'Record created successfully');
    }

    /**
     * Update a record
     */
    public function update(Request $request, MainSystem $record)
    {
        $validated = $request->validate([
            'uid' => 'required|string|unique:main_system,uid,' . $record->id,
            'regs_no' => 'nullable|string',
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'suffix' => 'nullable|string',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string',
            'civil_status' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
            'category' => 'nullable|string',
            'registration_date' => 'nullable|date',
        ]);

        $record->update($validated);

        return redirect()->route('main-system.index')->with('success', 'Record updated successfully');
    }

    /**
     * Delete a record
     */
    public function destroy(MainSystem $record)
    {
        $record->delete();

        return redirect()->route('main-system.index')->with('success', 'Record deleted successfully');
    }

    /**
     * Export all main system records as CSV
     * 
     * @param Request $request
     * @return Response
     */
    public function export(Request $request): Response
    {
        try {
            $query = MainSystem::query()->with('originBatch');

            // Apply search filter if present
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('uid', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('id', 'desc')->get();

            // Generate CSV content
            $csv = $this->generateMainSystemCSV($records);

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_His');
            $searchSuffix = $request->filled('search') ? '-search' : '-all';
            $filename = "main-system-export{$searchSuffix}-{$timestamp}.csv";

            return response($csv, 200)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('Error exporting main system records', [
                'error' => $e->getMessage()
            ]);

            abort(500, 'Unable to export main system records');
        }
    }

    /**
     * Generate CSV content for main system records
     * 
     * @param \Illuminate\Support\Collection $records
     * @return string
     */
    protected function generateMainSystemCSV($records): string
    {
        $output = fopen('php://temp', 'r+');

        // Write CSV header
        fputcsv($output, [
            'Row ID',
            'UID',
            'First Name',
            'Middle Name',
            'Last Name',
            'Suffix',
            'Birthday',
            'Gender',
            'Address',
            'Barangay',
            'City',
            'Province',
            'Postal Code',
            'Status',
            'Category',
            'Registration Date',
            'Origin Batch ID',
            'Origin Batch File'
        ]);

        // Write data rows
        $rowId = 1;
        foreach ($records as $record) {
            fputcsv($output, [
                $rowId++,
                $record->uid ?? '',
                $record->first_name ?? '',
                $record->middle_name ?? '',
                $record->last_name ?? '',
                $record->suffix ?? '',
                $record->birthday ? $record->birthday->format('Y-m-d') : '',
                $record->gender ?? '',
                $record->address ?? '',
                $record->barangay ?? '',
                $record->city ?? '',
                $record->province ?? '',
                $record->postal_code ?? '',
                $record->status ?? '',
                $record->category ?? '',
                $record->registration_date ? $record->registration_date->format('Y-m-d') : '',
                $record->origin_batch_id ?? '',
                $record->originBatch->file_name ?? ''
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate a unique UID for a new record
     */
    private function generateUid(): string
    {
        do {
            $uid = 'UID-' . strtoupper(bin2hex(random_bytes(8)));
        } while (MainSystem::where('uid', $uid)->exists());

        return $uid;
    }
}

