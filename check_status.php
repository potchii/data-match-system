<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$results = \App\Models\MatchResult::where('batch_id', 2)->take(10)->get();

echo "First 10 results from batch 2:\n\n";

foreach ($results as $result) {
    $uploaded = json_decode($result->uploaded_data, true);
    echo "ID: {$result->id}\n";
    echo "Status: '{$result->status}' (is null: " . ($result->status === null ? 'YES' : 'NO') . ")\n";
    echo "Confidence: {$result->confidence}%\n";
    echo "Rule: {$result->match_rule}\n";
    echo "Name: {$uploaded['first_name']} {$uploaded['last_name']} {$uploaded['middle_name']}\n";
    echo "---\n\n";
}
