<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batches = \App\Models\UploadBatch::orderBy('id', 'desc')->take(10)->get();

echo "Recent Upload Batches:\n";
echo "=====================\n\n";

foreach ($batches as $batch) {
    $resultCount = \App\Models\MatchResult::where('batch_id', $batch->id)->count();
    echo "Batch ID: {$batch->id}\n";
    echo "Filename: {$batch->filename}\n";
    echo "Records: {$batch->total_records}\n";
    echo "Results: {$resultCount}\n";
    echo "Created: {$batch->created_at}\n";
    echo "---\n\n";
}
