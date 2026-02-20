<?php

namespace App\Http\Controllers;

use App\Models\MatchResult;
use App\Models\UploadBatch;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBatches = UploadBatch::count();
        $matchedRecords = MatchResult::where('match_status', 'MATCHED')->count();
        $newRecords = MatchResult::where('match_status', 'NEW RECORD')->count();
        $possibleDuplicates = MatchResult::where('match_status', 'POSSIBLE DUPLICATE')->count();
        $recentBatches = UploadBatch::orderBy('uploaded_at', 'desc')->take(10)->get();

        return view('pages.dashboard', compact(
            'totalBatches',
            'matchedRecords',
            'newRecords',
            'possibleDuplicates',
            'recentBatches'
        ));
    }
}
