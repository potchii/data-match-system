<?php

namespace App\Http\Controllers;

use App\Models\UploadBatch;

class BatchController extends Controller
{
    public function index()
    {
        $batches = UploadBatch::orderBy('uploaded_at', 'desc')->paginate(20);
        
        return view('pages.batches', compact('batches'));
    }
}
