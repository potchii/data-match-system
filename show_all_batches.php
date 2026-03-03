<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batches = \App\Models\UploadBatch::orderBy('id', 'desc')->get();

echo "All Batches:\n";
echo "============\n\n";

foreach ($batches as $batch) {
    $results = \App\Models\MatchResult::where('batch_id', $batch->id)->get();
    
    $matched = $results->where('match_status', 'MATCHED')->count();
    $possible = $results->where('match_status', 'POSSIBLE DUPLICATE')->count();
    $newRecord = $results->where('match_status', 'NEW RECORD')->count();
    $nullStatus = $results->where('match_status', null)->count();
    
    echo "Batch ID: {$batch->id}\n";
    echo "Filename: {$batch->filename}\n";
    echo "Created: {$batch->created_at}\n";
    echo "Total Results: " . $results->count() . "\n";
    echo "  MATCHED: {$matched}\n";
    echo "  POSSIBLE DUPLICATE: {$possible}\n";
    echo "  NEW RECORD: {$newRecord}\n";
    echo "  NULL status: {$nullStatus}\n";
    echo "---\n\n";
}
