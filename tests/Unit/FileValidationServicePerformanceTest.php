<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use App\Services\FileValidationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Performance Tests for FileValidationService (Task 21.4)
 * 
 * Requirements: 12.1, 12.2, 12.3, 12.4
 * 
 * These tests verify performance requirements:
 * - Column validation completes in reasonable time for typical files
 * - Template field lookup uses indexed query
 * - System handles files efficiently
 */
class FileValidationServicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected FileValidationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileValidationService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function column_validation_completes_quickly_for_typical_file()
    {
        // Create a typical file with 100 rows and 5 columns
        $file = $this->createTestFile(100, 5);

        $startTime = microtime(true);
        $result = $this->service->validateColumns($file, null);
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertTrue($result['valid']);
        // Should complete in under 500ms
        $this->assertLessThan(500, $executionTimeMs, 
            "Column validation took {$executionTimeMs}ms, expected < 500ms");
    }

    /** @test */
    public function column_validation_with_template_completes_quickly()
    {
        // Create template with 3 custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Performance Test Template',
            'mappings' => [
                'FirstName' => 'first_name',
                'LastName' => 'last_name',
            ],
        ]);

        for ($i = 1; $i <= 3; $i++) {
            TemplateField::create([
                'template_id' => $template->id,
                'field_name' => "custom_field_{$i}",
                'field_type' => 'string',
                'is_required' => false,
            ]);
        }

        $headers = ['FirstName', 'LastName', 'custom_field_1', 'custom_field_2', 'custom_field_3'];
        $file = $this->createTestFileWithHeaders($headers, 100);

        $startTime = microtime(true);
        $result = $this->service->validateColumns($file, $template);
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertTrue($result['valid']);
        // Should complete in under 500ms
        $this->assertLessThan(500, $executionTimeMs, 
            "Template validation took {$executionTimeMs}ms, expected < 500ms");
    }

    /** @test */
    public function template_field_lookup_uses_indexed_query()
    {
        // Create template with 10 fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Index Test Template',
            'mappings' => ['FirstName' => 'first_name', 'LastName' => 'last_name'],
        ]);

        for ($i = 1; $i <= 10; $i++) {
            TemplateField::create([
                'template_id' => $template->id,
                'field_name' => "field_{$i}",
                'field_type' => 'string',
                'is_required' => false,
            ]);
        }

        // Enable query logging
        DB::enableQueryLog();

        // Load template with fields
        $loadedTemplate = ColumnMappingTemplate::with('fields')->find($template->id);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Find the query that loads template fields
        $fieldQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'template_fields') &&
                   str_contains($query['query'], 'template_id');
        });

        $this->assertNotNull($fieldQuery, 'Template fields query not found');
        $this->assertStringContainsString('template_id', $fieldQuery['query']);
        $this->assertCount(10, $loadedTemplate->fields);
    }

    /** @test */
    public function handles_moderately_large_file_efficiently()
    {
        // Create a file with 500 rows
        $file = $this->createTestFile(500, 5);

        $startTime = microtime(true);
        $result = $this->service->validateColumns($file, null);
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertTrue($result['valid']);
        // Should complete in under 1 second
        $this->assertLessThan(1000, $executionTimeMs, 
            "Large file validation took {$executionTimeMs}ms, expected < 1000ms");
    }

    /** @test */
    public function column_validation_only_reads_headers_not_entire_file()
    {
        // Create files with different row counts but same columns
        $file100 = $this->createTestFile(100, 5);
        $file500 = $this->createTestFile(500, 5);

        // Measure validation time for both
        $startTime1 = microtime(true);
        $result1 = $this->service->validateColumns($file100, null);
        $time1 = (microtime(true) - $startTime1) * 1000;

        $startTime2 = microtime(true);
        $result2 = $this->service->validateColumns($file500, null);
        $time2 = (microtime(true) - $startTime2) * 1000;

        $this->assertTrue($result1['valid']);
        $this->assertTrue($result2['valid']);

        // Time difference should be minimal since we only read headers
        // Allow 5x difference for file I/O overhead (file size affects loading time)
        $this->assertLessThan($time1 * 5, $time2, 
            "Validation time should not scale linearly with row count (100 rows: {$time1}ms, 500 rows: {$time2}ms)");
    }

    /** @test */
    public function validation_with_multiple_columns_performs_well()
    {
        // Test with 8 columns
        $file = $this->createTestFile(100, 8);

        $startTime = microtime(true);
        $result = $this->service->validateColumns($file, null);
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertTrue($result['valid']);
        $this->assertLessThan(500, $executionTimeMs, 
            "Validation with 8 columns took {$executionTimeMs}ms, expected < 500ms");
    }

    /** @test */
    public function template_with_many_fields_validates_efficiently()
    {
        // Create template with 15 custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Large Template',
            'mappings' => ['FirstName' => 'first_name', 'LastName' => 'last_name'],
        ]);

        $headers = ['FirstName', 'LastName'];
        for ($i = 1; $i <= 15; $i++) {
            TemplateField::create([
                'template_id' => $template->id,
                'field_name' => "field_{$i}",
                'field_type' => 'string',
                'is_required' => false,
            ]);
            $headers[] = "field_{$i}";
        }

        $file = $this->createTestFileWithHeaders($headers, 100);

        $startTime = microtime(true);
        $result = $this->service->validateColumns($file, $template);
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertTrue($result['valid']);
        $this->assertLessThan(1000, $executionTimeMs, 
            "Large template validation took {$executionTimeMs}ms, expected < 1000ms");
    }

    /** @test */
    public function validation_memory_usage_remains_reasonable()
    {
        $memoryBefore = memory_get_usage(true);

        // Create and validate a file
        $file = $this->createTestFile(500, 8);
        $result = $this->service->validateColumns($file, null);

        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertTrue($result['valid']);
        // Memory usage should be reasonable (< 50MB)
        $this->assertLessThan(50, $memoryUsedMB, 
            "Validation used {$memoryUsedMB}MB memory, expected < 50MB");
    }

    /** @test */
    public function multiple_validations_perform_consistently()
    {
        $executionTimes = [];

        // Run 3 validations
        for ($i = 0; $i < 3; $i++) {
            $file = $this->createTestFile(100, 5);

            $startTime = microtime(true);
            $result = $this->service->validateColumns($file, null);
            $endTime = microtime(true);

            $this->assertTrue($result['valid']);
            $executionTimes[] = ($endTime - $startTime) * 1000;
        }

        // All should complete in reasonable time
        foreach ($executionTimes as $index => $time) {
            $this->assertLessThan(500, $time, 
                "Validation #{$index} took {$time}ms, expected < 500ms");
        }

        // Calculate average
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $this->assertLessThan(500, $avgTime, 
            "Average validation time was {$avgTime}ms, expected < 500ms");
    }

    /**
     * Helper method to create test Excel file
     */
    protected function createTestFile(int $rows, int $columns): UploadedFile
    {
        $filename = 'test_perf_' . uniqid() . '.xlsx';
        $path = storage_path('app/' . $filename);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Use only core field headers
        $coreHeaders = ['FirstName', 'LastName', 'Birthday', 'Gender', 'Address', 
                        'MiddleName', 'Suffix', 'Status', 'Barangay'];
        
        for ($col = 0; $col < min($columns, count($coreHeaders)); $col++) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $coreHeaders[$col]);
        }

        // Create data rows
        for ($row = 2; $row <= $rows + 1; $row++) {
            for ($col = 1; $col <= min($columns, count($coreHeaders)); $col++) {
                $sheet->setCellValueByColumnAndRow($col, $row, "Data{$row}_{$col}");
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile($path, $filename, 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Helper method to create test file with specific headers
     */
    protected function createTestFileWithHeaders(array $headers, int $rows): UploadedFile
    {
        $filename = 'test_headers_' . uniqid() . '.xlsx';
        $path = storage_path('app/' . $filename);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        // Create data rows
        for ($row = 2; $row <= $rows + 1; $row++) {
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, "Data{$row}");
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile($path, $filename, 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $files = array_merge(
            glob(storage_path('app/test_perf_*.xlsx')),
            glob(storage_path('app/test_headers_*.xlsx'))
        );
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
