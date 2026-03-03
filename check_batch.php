<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the most recent batch
$latestBatch = \App\Models\UploadBatch::orderBy('id', 'desc')->first();

if (!$latestBatch) {
    echo "No batches found!\n";
    exit;
}

$batchId = $latestBatch->id;

$results = \App\Models\MatchResult::where('batch_id', $batchId)->get();

echo "Latest Batch: {$batchId}\n";
echo "Filename: {$latestBatch->filename}\n";
echo "Total Results: " . $results->count() . "\n\n";

if ($results->isEmpty()) {
    echo "No results found!\n";
    exit;
}

$matched = $results->where('status', 'MATCHED');
$possible = $results->where('status', 'POSSIBLE DUPLICATE');
$newRecord = $results->where('status', 'NEW RECORD');

echo "MATCHED: " . $matched->count() . "\n";
echo "POSSIBLE DUPLICATE: " . $possible->count() . "\n";
echo "NEW RECORD: " . $newRecord->count() . "\n\n";

echo "First 5 MATCHED records:\n";
foreach ($matched->take(5) as $result) {
    $uploaded = json_decode($result->uploaded_data, true);
    
    if (!$uploaded) {
        echo "ID: {$result->id} - DATA IS NULL!\n\n";
        continue;
    }
    
    echo "ID: {$result->id}, Rule: {$result->match_rule}, Confidence: {$result->confidence}%\n";
    echo "  Name: {$uploaded['first_name']} {$uploaded['last_name']} {$uploaded['middle_name']}\n";
    echo "  Birthday: " . ($uploaded['birthday'] ?? 'NULL') . "\n";
    echo "  Middle Norm: '{$uploaded['middle_name_normalized']}'\n\n";
}
