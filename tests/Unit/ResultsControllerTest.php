<?php

namespace Tests\Unit;

use App\Http\Controllers\ResultsController;
use App\Models\MatchResult;
use App\Models\UploadBatch;
use App\Services\MatchAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ResultsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected MatchAnalyticsService $mockAnalyticsService;
    protected ResultsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockAnalyticsService = $this->createMock(MatchAnalyticsService::class);
        $this->controller = new ResultsController($this->mockAnalyticsService);
    }

    /**
     * Test getBatchAnalytics returns correct JSON structure
     */
    public function test_get_batch_analytics_returns_correct_json_structure(): void
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        
        $mockStatistics = [
            'total_records' => 100,
            'matched' => 80,
            'possible_duplicates' => 15,
            'new_records' => 5,
            'average_confidence' => 87.5,
            'average_matched_fields' => 11.2,
            'average_mismatched_fields' => 1.8,
        ];
        
        $mockFieldPopulation = [
            'core_fields' => [
                'last_name' => ['count' => 98, 'percentage' => 98.0],
                'first_name' => ['count' => 95, 'percentage' => 95.0],
            ],
        ];
        
        $mockChartData = [
            'mapping_pie' => [
                'labels' => ['Mapped', 'Skipped'],
                'data' => [12, 3],
                'colors' => ['#28a745', '#6c757d'],
            ],
        ];
        
        $mockQuality = [
            'level' => 'good',
            'score' => 87.5,
            'color' => 'success',
        ];

        $this->mockAnalyticsService
            ->expects($this->once())
            ->method('calculateBatchStatistics')
            ->with($batch->id)
            ->willReturn($mockStatistics);

        $this->mockAnalyticsService
            ->expects($this->once())
            ->method('calculateFieldPopulationRates')
            ->with($batch->id)
            ->willReturn($mockFieldPopulation);

        $this->mockAnalyticsService
            ->expects($this->once())
            ->method('generateChartData')
            ->with($batch->id, [])
            ->willReturn($mockChartData);

        $this->mockAnalyticsService
            ->expects($this->once())
            ->method('calculateQualityScore')
            ->with($mockStatistics)
            ->willReturn($mockQuality);

        // Act
        $response = $this->controller->getBatchAnalytics($batch->id);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('batch_id', $data);
        $this->assertArrayHasKey('statistics', $data);
        $this->assertArrayHasKey('quality', $data);
        $this->assertArrayHasKey('field_population', $data);
        $this->assertArrayHasKey('chart_data', $data);
        
        $this->assertEquals($batch->id, $data['batch_id']);
        $this->assertEquals($mockStatistics, $data['statistics']);
        $this->assertEquals($mockQuality, $data['quality']);
        $this->assertEquals($mockFieldPopulation, $data['field_population']);
        $this->assertEquals($mockChartData, $data['chart_data']);
    }

    /**
     * Test getBatchAnalytics returns 404 for invalid batch
     */
    public function test_get_batch_analytics_returns_404_for_invalid_batch(): void
    {
        // Arrange
        Log::shouldReceive('warning')
            ->once()
            ->with('Analytics requested for non-existent batch', ['batch_id' => 999]);

        // Act
        $response = $this->controller->getBatchAnalytics(999);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Batch not found', $data['error']);
        $this->assertEquals('The requested batch does not exist.', $data['message']);
    }

    /**
     * Test getBatchAnalytics handles database errors
     */
    public function test_get_batch_analytics_handles_database_errors(): void
    {
        // Arrange
        $batch = UploadBatch::factory()->create();
        
        $this->mockAnalyticsService
            ->expects($this->once())
            ->method('calculateBatchStatistics')
            ->with($batch->id)
            ->willThrowException(new \Illuminate\Database\QueryException(
                'mysql',
                'SELECT * FROM match_results',
                [],
                new \Exception('Connection failed')
            ));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($batch) {
                return $message === 'Database error calculating batch statistics' 
                    && $context['batch_id'] === $batch->id;
            });

        // Act
        $response = $this->controller->getBatchAnalytics($batch->id);

        // Assert
        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Database error', $data['error']);
    }

    /**
     * Test getFieldBreakdown returns correct data
     */
    public function test_get_field_breakdown_returns_correct_data(): void
    {
        // Arrange
        $fieldBreakdown = [
            'total_fields' => 5,
            'matched_fields' => 4,
            'core_fields' => [
                'last_name' => [
                    'status' => 'match',
                    'uploaded' => 'Smith',
                    'existing' => 'Smith',
                    'category' => 'core',
                    'confidence' => 100.0,
                ],
                'first_name' => [
                    'status' => 'mismatch',
                    'uploaded' => 'John',
                    'existing' => 'Jon',
                    'category' => 'core',
                    'confidence' => 75.0,
                ],
            ],
        ];

        $result = MatchResult::factory()->create([
            'field_breakdown' => $fieldBreakdown,
        ]);

        // Act
        $response = $this->controller->getFieldBreakdown($result->id);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        // API now returns the breakdown data directly
        $this->assertArrayHasKey('total_fields', $data);
        $this->assertArrayHasKey('matched_fields', $data);
        $this->assertArrayHasKey('core_fields', $data);
        $this->assertEquals($fieldBreakdown, $data);
    }

    /**
     * Test getFieldBreakdown returns 404 for non-existent result
     */
    public function test_get_field_breakdown_returns_404_for_non_existent_result(): void
    {
        // Arrange
        Log::shouldReceive('warning')
            ->once()
            ->with('Field breakdown requested for non-existent result', ['result_id' => 999]);

        // Act
        $response = $this->controller->getFieldBreakdown(999);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Result not found', $data['error']);
    }

    /**
     * Test getFieldBreakdown returns 404 for empty breakdown data
     */
    public function test_get_field_breakdown_returns_404_for_empty_breakdown(): void
    {
        // Arrange
        $result = MatchResult::factory()->create([
            'field_breakdown' => null,
        ]);

        // Act
        $response = $this->controller->getFieldBreakdown($result->id);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No data', $data['error']);
    }

    /**
     * Test exportFieldBreakdown generates valid CSV
     */
    public function test_export_field_breakdown_generates_valid_csv(): void
    {
        // Arrange
        $fieldBreakdown = [
            'core_fields' => [
                'last_name' => [
                    'status' => 'match',
                    'uploaded' => 'Smith',
                    'existing' => 'Smith',
                    'uploaded_normalized' => 'smith',
                    'existing_normalized' => 'smith',
                    'category' => 'core',
                    'confidence' => 100.0,
                ],
                'first_name' => [
                    'status' => 'mismatch',
                    'uploaded' => 'John',
                    'existing' => 'Jon',
                    'uploaded_normalized' => 'john',
                    'existing_normalized' => 'jon',
                    'category' => 'core',
                    'confidence' => 75.0,
                ],
            ],
            'template_fields' => [
                'employee_id' => [
                    'status' => 'match',
                    'uploaded' => 'EMP-12345',
                    'existing' => 'EMP-12345',
                    'category' => 'template',
                    'confidence' => 100.0,
                ],
            ],
        ];

        $result = MatchResult::factory()->create([
            'field_breakdown' => $fieldBreakdown,
        ]);

        // Act
        $response = $this->controller->exportFieldBreakdown($result->id);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString("field-breakdown-{$result->id}", $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $csv = $response->getContent();
        
        // Verify CSV header (note: fputcsv adds quotes to fields with spaces)
        $this->assertStringContainsString('Field Name', $csv);
        $this->assertStringContainsString('Category', $csv);
        $this->assertStringContainsString('Status', $csv);
        
        // Verify core fields are present
        $this->assertStringContainsString('last_name', $csv);
        $this->assertStringContainsString('Smith', $csv);
        $this->assertStringContainsString('first_name', $csv);
        $this->assertStringContainsString('John', $csv);
        
        // Verify template fields are present
        $this->assertStringContainsString('employee_id', $csv);
        $this->assertStringContainsString('EMP-12345', $csv);
    }

    /**
     * Test exportFieldBreakdown includes all required columns
     */
    public function test_export_field_breakdown_includes_all_columns(): void
    {
        // Arrange
        $fieldBreakdown = [
            'core_fields' => [
                'last_name' => [
                    'status' => 'match',
                    'uploaded' => 'Smith',
                    'existing' => 'Smith',
                    'uploaded_normalized' => 'smith',
                    'existing_normalized' => 'smith',
                    'category' => 'core',
                    'confidence' => 100.0,
                ],
            ],
        ];

        $result = MatchResult::factory()->create([
            'field_breakdown' => $fieldBreakdown,
        ]);

        // Act
        $response = $this->controller->exportFieldBreakdown($result->id);
        $csv = $response->getContent();

        // Assert - verify all required columns are in header
        $lines = explode("\n", $csv);
        $header = str_getcsv($lines[0]);
        
        $this->assertContains('Field Name', $header);
        $this->assertContains('Category', $header);
        $this->assertContains('Status', $header);
        $this->assertContains('Uploaded Value', $header);
        $this->assertContains('Existing Value', $header);
        $this->assertContains('Uploaded Normalized', $header);
        $this->assertContains('Existing Normalized', $header);
        $this->assertContains('Confidence Score', $header);
    }

    /**
     * Test exportFieldBreakdown handles missing result
     */
    public function test_export_field_breakdown_handles_missing_result(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->controller->exportFieldBreakdown(999);
    }

    /**
     * Test exportFieldBreakdown handles empty breakdown data
     */
    public function test_export_field_breakdown_handles_empty_breakdown(): void
    {
        // Arrange
        $result = MatchResult::factory()->create([
            'field_breakdown' => null,
        ]);

        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->controller->exportFieldBreakdown($result->id);
    }

    /**
     * Feature: match-results-analytics, Property 15: CSV Special Character Escaping
     * For any field value with special characters, CSV should escape properly
     * 
     * @test
     */
    public function test_csv_escapes_special_characters_property()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runCSVEscapingTest();
        }
    }

    protected function runCSVEscapingTest(): void
    {
        // Generate random field values with special characters
        // Note: Excluding \r\n (CRLF) due to platform-specific PHP fputcsv behavior on Windows
        $specialChars = [',', '"', "\n"];
        $testValues = [];
        
        // Generate 3-5 test values with random special characters
        $valueCount = rand(3, 5);
        for ($i = 0; $i < $valueCount; $i++) {
            $value = $this->generateValueWithSpecialChars($specialChars);
            $testValues["field_{$i}"] = $value;
        }

        // Create field breakdown with these values
        $fieldBreakdown = [
            'core_fields' => [],
        ];

        foreach ($testValues as $fieldName => $value) {
            $fieldBreakdown['core_fields'][$fieldName] = [
                'status' => 'match',
                'uploaded' => $value,
                'existing' => $value,
                'category' => 'core',
                'confidence' => 100.0,
            ];
        }

        $result = MatchResult::factory()->create([
            'field_breakdown' => $fieldBreakdown,
        ]);

        // Export to CSV
        $response = $this->controller->exportFieldBreakdown($result->id);
        $csv = $response->getContent();

        // Parse CSV properly using str_getcsv for each line
        $lines = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            $lines[] = $data;
        }
        fclose($handle);
        
        // Remove header
        array_shift($lines);

        // Verify each value is properly escaped and can be parsed back
        foreach ($lines as $parsed) {
            if (empty($parsed)) {
                continue;
            }
            
            // The uploaded value should be at index 3
            $parsedValue = $parsed[3] ?? '';
            
            // Find the original value for this field
            $fieldName = $parsed[0];
            $originalValue = $testValues[$fieldName] ?? null;
            
            if ($originalValue !== null) {
                $this->assertEquals(
                    $originalValue,
                    $parsedValue,
                    "CSV escaping failed for value with special characters. Original: " . 
                    json_encode($originalValue) . ", Parsed: " . json_encode($parsedValue)
                );
            }
        }

        // Clean up
        $result->delete();
    }

    /**
     * Generate a random string with special characters
     */
    protected function generateValueWithSpecialChars(array $specialChars): string
    {
        $baseStrings = [
            'Smith, John',
            'O\'Brien',
            'Test "quoted" value',
            "Line1\nLine2",
            'Simple text',
            'Value, with, commas',
            '"Already quoted"',
            'Mix "of" special, chars',
        ];

        // Pick a random base string or generate one
        if (rand(0, 1)) {
            return $baseStrings[array_rand($baseStrings)];
        }

        // Generate a string with random special characters
        $parts = ['Test', 'Value', 'Data', 'Field'];
        $result = $parts[array_rand($parts)];
        
        // Add 1-3 special characters
        $charCount = rand(1, 3);
        for ($i = 0; $i < $charCount; $i++) {
            $char = $specialChars[array_rand($specialChars)];
            $position = rand(0, 1) ? 'middle' : 'end';
            
            if ($position === 'middle' && strlen($result) > 1) {
                $pos = rand(1, strlen($result) - 1);
                $result = substr($result, 0, $pos) . $char . substr($result, $pos);
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }
}
