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
        
        return view('pages.results', compact('results', 'batches'));
    }
}
