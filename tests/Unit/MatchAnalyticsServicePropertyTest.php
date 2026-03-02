<?php

namespace Tests\Unit;

use App\Models\MatchResult;
use App\Models\UploadBatch;
use App\Services\MatchAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchAnalyticsServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected MatchAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchAnalyticsService();
    }

    /**
     * Feature: match-results-analytics, Property 8: Batch Average Confidence Calculation
     * For any batch, average confidence = sum(scores) / count (excluding NEW RECORD)
     * 
     * @test
     */
    public function test_average_confidence_excludes_new_records()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runAverageConfidenceTest();
        }
    }

    protected function runAverageConfidenceTest(): void
    {
        // Create a batch
        $batch = UploadBatch::factory()->create();

        // Generate random number of results (5-50)
        $resultCount = rand(5, 50);
        $expectedSum = 0;
        $expectedCount = 0;

        for ($i = 0; $i < $resultCount; $i++) {
            $confidence = rand(0, 10000) / 100; // Random float 0-100
            $status = ['MATCHED', 'POSSIBLE DUPLICATE', 'NEW RECORD'][rand(0, 2)];

            MatchResult::factory()->create([
                'batch_id' => $batch->id,
                'confidence_score' => $confidence,
                'match_status' => $status,
                'field_breakdown' => [
                    'total_fields' => 10,
                    'matched_fields' => 8,
                ],
            ]);

            // Only count non-NEW RECORD entries
            if ($status !== 'NEW RECORD') {
                $expectedSum += $confidence;
                $expectedCount++;
            }
        }

        $expectedAverage = $expectedCount > 0 ? round($expectedSum / $expectedCount, 2) : 0;

        // Calculate statistics
        $statistics = $this->service->calculateBatchStatistics($batch->id);

        // Assert average confidence matches expected
        $this->assertEquals(
            $expectedAverage,
            $statistics['average_confidence'],
            "Average confidence should exclude NEW RECORD entries. Expected: {$expectedAverage}, Got: {$statistics['average_confidence']}"
        );

        // Clean up for next iteration
        MatchResult::where('batch_id', $batch->id)->delete();
        $batch->delete();
    }

    /**
     * Feature: match-results-analytics, Property 9: Match Status Distribution
     * For any batch, sum of status percentages = 100%
     * 
     * @test
     */
    public function test_match_status_distribution_sums_to_100_percent()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runStatusDistributionTest();
        }
    }

    protected function runStatusDistributionTest(): void
    {
        // Create a batch
        $batch = UploadBatch::factory()->create();

        // Generate random number of results (10-100)
        $resultCount = rand(10, 100);
        $statusCounts = [
            'MATCHED' => 0,
            'POSSIBLE DUPLICATE' => 0,
            'NEW RECORD' => 0,
        ];

        for ($i = 0; $i < $resultCount; $i++) {
            $status = ['MATCHED', 'POSSIBLE DUPLICATE', 'NEW RECORD'][rand(0, 2)];
            $statusCounts[$status]++;

            MatchResult::factory()->create([
                'batch_id' => $batch->id,
                'match_status' => $status,
                'confidence_score' => rand(0, 10000) / 100,
                'field_breakdown' => [
                    'total_fields' => 10,
                    'matched_fields' => 8,
                ],
            ]);
        }

        // Calculate statistics
        $statistics = $this->service->calculateBatchStatistics($batch->id);

        // Calculate percentages
        $matchedPercent = ($statistics['matched'] / $statistics['total_records']) * 100;
        $possibleDuplicatesPercent = ($statistics['possible_duplicates'] / $statistics['total_records']) * 100;
        $newRecordsPercent = ($statistics['new_records'] / $statistics['total_records']) * 100;

        $totalPercent = $matchedPercent + $possibleDuplicatesPercent + $newRecordsPercent;

        // Assert sum equals 100% (with small tolerance for floating point)
        $this->assertEquals(
            100.0,
            round($totalPercent, 2),
            "Status distribution percentages should sum to 100%. Got: {$totalPercent}"
        );

        // Assert counts match expected
        $this->assertEquals($statusCounts['MATCHED'], $statistics['matched']);
        $this->assertEquals($statusCounts['POSSIBLE DUPLICATE'], $statistics['possible_duplicates']);
        $this->assertEquals($statusCounts['NEW RECORD'], $statistics['new_records']);
        $this->assertEquals($resultCount, $statistics['total_records']);

        // Clean up for next iteration
        MatchResult::where('batch_id', $batch->id)->delete();
        $batch->delete();
    }

    /**
     * Feature: match-results-analytics, Property 3: Field Population Rate Calculation
     * For any field, population % = (non-empty count / total) × 100
     * 
     * @test
     */
    public function test_field_population_rate_calculation()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runFieldPopulationTest();
        }
    }

    protected function runFieldPopulationTest(): void
    {
        // Create a batch
        $batch = UploadBatch::factory()->create();

        // Generate random number of results (10-50)
        $resultCount = rand(10, 50);
        
        // Track expected counts for a few core fields
        $expectedCounts = [
            'last_name' => 0,
            'first_name' => 0,
            'middle_name' => 0,
        ];

        for ($i = 0; $i < $resultCount; $i++) {
            // Randomly populate fields
            $hasLastName = rand(0, 1) === 1;
            $hasFirstName = rand(0, 1) === 1;
            $hasMiddleName = rand(0, 1) === 1;

            if ($hasLastName) $expectedCounts['last_name']++;
            if ($hasFirstName) $expectedCounts['first_name']++;
            if ($hasMiddleName) $expectedCounts['middle_name']++;

            MatchResult::factory()->create([
                'batch_id' => $batch->id,
                'confidence_score' => 85.0,
                'match_status' => 'MATCHED',
                'field_breakdown' => [
                    'total_fields' => 10,
                    'matched_fields' => 8,
                    'core_fields' => [
                        'last_name' => [
                            'uploaded' => $hasLastName ? 'Smith' : '',
                            'status' => 'match',
                        ],
                        'first_name' => [
                            'uploaded' => $hasFirstName ? 'John' : null,
                            'status' => 'match',
                        ],
                        'middle_name' => [
                            'uploaded' => $hasMiddleName ? 'Paul' : '',
                            'status' => 'match',
                        ],
                    ],
                ],
            ]);
        }

        // Calculate field population rates
        $result = $this->service->calculateFieldPopulationRates($batch->id);
        $coreFields = $result['core_fields'];

        // Verify population percentages
        foreach ($expectedCounts as $field => $count) {
            $expectedPercentage = round(($count / $resultCount) * 100, 1);
            
            $this->assertEquals(
                $count,
                $coreFields[$field]['count'],
                "Field '{$field}' count mismatch. Expected: {$count}, Got: {$coreFields[$field]['count']}"
            );

            $this->assertEquals(
                $expectedPercentage,
                $coreFields[$field]['percentage'],
                "Field '{$field}' percentage mismatch. Expected: {$expectedPercentage}%, Got: {$coreFields[$field]['percentage']}%"
            );
        }

        // Clean up for next iteration
        MatchResult::where('batch_id', $batch->id)->delete();
        $batch->delete();
    }

    /**
     * Feature: match-results-analytics, Property 11: Quality Score Mapping
     * For any confidence score, quality level should match defined thresholds
     * 
     * @test
     */
    public function test_quality_score_mapping_consistency()
    {
        // Run property test with multiple random confidence scores
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runQualityScoreMappingTest();
        }
    }

    protected function runQualityScoreMappingTest(): void
    {
        // Generate random confidence score (0-100)
        $confidence = rand(0, 10000) / 100;

        $statistics = ['average_confidence' => $confidence];
        $result = $this->service->calculateQualityScore($statistics);

        // Verify quality level matches expected threshold
        if ($confidence >= 90) {
            $this->assertEquals('excellent', $result['level'], "Confidence {$confidence} should map to 'excellent'");
            $this->assertEquals('success', $result['color']);
        } elseif ($confidence >= 75) {
            $this->assertEquals('good', $result['level'], "Confidence {$confidence} should map to 'good'");
            $this->assertEquals('success', $result['color']);
        } elseif ($confidence >= 60) {
            $this->assertEquals('fair', $result['level'], "Confidence {$confidence} should map to 'fair'");
            $this->assertEquals('warning', $result['color']);
        } else {
            $this->assertEquals('poor', $result['level'], "Confidence {$confidence} should map to 'poor'");
            $this->assertEquals('danger', $result['color']);
        }

        // Verify score is preserved
        $this->assertEquals($confidence, $result['score']);
    }

    /**
     * Feature: match-results-analytics, Property 4: Most/Least Common Field Identification
     * For any batch, fields should be ranked in descending order by population rate
     * 
     * @test
     */
    public function test_field_ranking_by_population_rate()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runFieldRankingTest();
        }
    }

    protected function runFieldRankingTest(): void
    {
        // Create a batch
        $batch = UploadBatch::factory()->create();

        // Generate random number of results (20-50)
        $resultCount = rand(20, 50);
        
        // Track expected counts for ALL core fields
        $expectedCounts = [
            'last_name' => 0,
            'first_name' => 0,
            'middle_name' => 0,
            'uid' => 0,
            'birthday' => 0,
            'suffix' => 0,
            'gender' => 0,
            'civil_status' => 0,
            'address' => 0,
            'barangay' => 0,
        ];

        for ($i = 0; $i < $resultCount; $i++) {
            // Randomly populate fields with different probabilities
            $hasLastName = rand(0, 100) < 90; // 90% probability
            $hasFirstName = rand(0, 100) < 85; // 85% probability
            $hasMiddleName = rand(0, 100) < 40; // 40% probability
            $hasUid = rand(0, 100) < 70; // 70% probability
            $hasBirthday = rand(0, 100) < 60; // 60% probability
            $hasSuffix = rand(0, 100) < 30; // 30% probability
            $hasGender = rand(0, 100) < 75; // 75% probability
            $hasCivilStatus = rand(0, 100) < 65; // 65% probability
            $hasAddress = rand(0, 100) < 80; // 80% probability
            $hasBarangay = rand(0, 100) < 55; // 55% probability

            if ($hasLastName) $expectedCounts['last_name']++;
            if ($hasFirstName) $expectedCounts['first_name']++;
            if ($hasMiddleName) $expectedCounts['middle_name']++;
            if ($hasUid) $expectedCounts['uid']++;
            if ($hasBirthday) $expectedCounts['birthday']++;
            if ($hasSuffix) $expectedCounts['suffix']++;
            if ($hasGender) $expectedCounts['gender']++;
            if ($hasCivilStatus) $expectedCounts['civil_status']++;
            if ($hasAddress) $expectedCounts['address']++;
            if ($hasBarangay) $expectedCounts['barangay']++;

            MatchResult::factory()->create([
                'batch_id' => $batch->id,
                'confidence_score' => 85.0,
                'match_status' => 'MATCHED',
                'field_breakdown' => [
                    'total_fields' => 10,
                    'matched_fields' => 8,
                    'core_fields' => [
                        'last_name' => ['uploaded' => $hasLastName ? 'Smith' : '', 'status' => 'match'],
                        'first_name' => ['uploaded' => $hasFirstName ? 'John' : '', 'status' => 'match'],
                        'middle_name' => ['uploaded' => $hasMiddleName ? 'Paul' : '', 'status' => 'match'],
                        'uid' => ['uploaded' => $hasUid ? '12345' : '', 'status' => 'match'],
                        'birthday' => ['uploaded' => $hasBirthday ? '1990-01-01' : '', 'status' => 'match'],
                        'suffix' => ['uploaded' => $hasSuffix ? 'Jr.' : '', 'status' => 'match'],
                        'gender' => ['uploaded' => $hasGender ? 'M' : '', 'status' => 'match'],
                        'civil_status' => ['uploaded' => $hasCivilStatus ? 'Single' : '', 'status' => 'match'],
                        'address' => ['uploaded' => $hasAddress ? '123 Main St' : '', 'status' => 'match'],
                        'barangay' => ['uploaded' => $hasBarangay ? 'Brgy 1' : '', 'status' => 'match'],
                    ],
                ],
            ]);
        }

        // Calculate expected percentages
        $expectedPercentages = [];
        foreach ($expectedCounts as $field => $count) {
            $expectedPercentages[$field] = round(($count / $resultCount) * 100, 1);
        }

        // Sort by percentage descending
        arsort($expectedPercentages);
        $expectedOrder = array_keys($expectedPercentages);

        // Get top/bottom fields from service
        $result = $this->service->identifyTopBottomFields($batch->id);
        $topFields = $result['top_fields'];

        // Verify top fields are in descending order
        for ($i = 0; $i < count($topFields) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $topFields[$i + 1]['percentage'],
                $topFields[$i]['percentage'],
                "Top fields should be in descending order by percentage"
            );
        }

        // Verify the first field has the highest percentage (or tied for highest)
        if (count($topFields) > 0 && count($expectedPercentages) > 0) {
            $highestPercentage = max($expectedPercentages);
            $this->assertEquals(
                $highestPercentage,
                $topFields[0]['percentage'],
                "First field should have the highest percentage"
            );
        }

        // Clean up for next iteration
        MatchResult::where('batch_id', $batch->id)->delete();
        $batch->delete();
    }
}
