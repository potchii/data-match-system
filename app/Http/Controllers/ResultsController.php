<?php

namespace App\Http\Controllers;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        $query = MatchResult::query()->with(['batch', 'matchedRecord.originBatch']);
        
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('match_status', $request->status);
        }
        
        $results = $query->orderBy('created_at', 'desc')->paginate(20);
        $batches = UploadBatch::orderBy('id', 'desc')->get();
        
        // Calculate statistics for the current batch if filtering by batch_id
        $batchStats = null;
        if ($request->filled('batch_id')) {
            $batchId = $request->batch_id;
            $batchStats = [
                'total_rows' => MatchResult::where('batch_id', $batchId)->count(),
                'new_records' => MatchResult::where('batch_id', $batchId)->where('match_status', 'NEW RECORD')->count(),
                'matched' => MatchResult::where('batch_id', $batchId)->where('match_status', 'MATCHED')->count(),
                'possible_duplicates' => MatchResult::where('batch_id', $batchId)->where('match_status', 'POSSIBLE DUPLICATE')->count(),
            ];
        }
        
        return view('pages.results', compact('results', 'batches', 'batchStats'));
    }
}
