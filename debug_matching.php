<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\DataMatchService;
use App\Models\MainSystem;

echo "=== Debug Matching for John Emmanuel Torres ===\n\n";

// Simulate uploaded data
$uploadedData = [
    'first_name' => 'John Emmanuel',
    'middle_name' => null,
    'last_name' => 'Torres',
    'birthday' => null,
];

echo "Uploaded Data:\n";
print_r($uploadedData);
echo "\n";

// Check what's in database
echo "Database Records:\n";
$dbRecords = MainSystem::where('last_name', 'LIKE', '%Torres%')
    ->where('first_name', 'LIKE', '%John%')
    ->get(['id', 'first_name', 'middle_name', 'last_name', 'first_name_normalized', 'middle_name_normalized', 'last_name_normalized'])
    ->toArray();
print_r($dbRecords);
echo "\n";

// Test matching
$matchService = new DataMatchService();
$result = $matchService->findMatch($uploadedData);

echo "Match Result:\n";
print_r($result);
echo "\n";

// Check normalized values
echo "Normalized Comparison:\n";
echo "Uploaded first_name_normalized: " . strtolower(trim($uploadedData['first_name'])) . "\n";
echo "Uploaded middle_name_normalized: " . strtolower(trim($uploadedData['middle_name'] ?? '')) . "\n";
echo "Uploaded last_name_normalized: " . strtolower(trim($uploadedData['last_name'])) . "\n";
echo "\n";

if (!empty($dbRecords)) {
    $first = $dbRecords[0];
    echo "DB first_name_normalized: " . $first['first_name_normalized'] . "\n";
    echo "DB middle_name_normalized: " . $first['middle_name_normalized'] . "\n";
    echo "DB last_name_normalized: " . $first['last_name_normalized'] . "\n";
    echo "\n";
    
    echo "Match Check:\n";
    echo "First names match: " . (strtolower(trim($uploadedData['first_name'])) === $first['first_name_normalized'] ? 'YES' : 'NO') . "\n";
    echo "Middle names match: " . (strtolower(trim($uploadedData['middle_name'] ?? '')) === $first['middle_name_normalized'] ? 'YES' : 'NO') . "\n";
    echo "Last names match: " . (strtolower(trim($uploadedData['last_name'])) === $first['last_name_normalized'] ? 'YES' : 'NO') . "\n";
}
