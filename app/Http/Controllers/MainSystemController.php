<?php

namespace App\Http\Controllers;

use App\Models\MainSystem;
use Illuminate\Http\Request;

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
}

