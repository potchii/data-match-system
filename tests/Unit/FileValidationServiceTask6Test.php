<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\User;
use App\Services\FileValidationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit Tests for FileValidationService::validateColumns (Task 6)
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
 * 
 * These tests verify column validation functionality:
 * - Validates file columns against core fields only
 * - Validates file columns against template fields
 * - Detects missing required columns
 * - Detects extra/unexpected columns
 * - Returns structured validation results
 */
class FileValidationServiceTask6Test extends TestCase
{
    use RefreshDatabase;

    protected FileValidationService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileValidationService();
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    /** @test */
    public function it_validates_file_with_valid_core_fields_only()
    {
        $file = $this->createTestFile([
            ['FirstName', 'LastName', 'Birthday'],
            ['Juan', 'Cruz', '1990-01-01'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(['FirstName', 'LastName', 'Birthday'], $result['info']['found_columns']);
    }

    /** @test */
    public function it_detects_missing_required_first_name_column()
    {
        $file = $this->createTestFile([
            ['LastName', 'Birthday'],
            ['Cruz', '1990-01-01'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertFalse($result['valid']);
        $this->assertContains('Missing required column: first_name', $result['errors']);
        $this->assertContains('first_name', $result['info']['missing_columns']);
    }

    /** @test */
    public function it_detects_missing_required_last_name_column()
    {
        $file = $this->createTestFile([
            ['FirstName', 'Birthday'],
            ['Juan', '1990-01-01'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertFalse($result['valid']);
        $this->assertContains('Missing required column: last_name', $result['errors']);
        $this->assertContains('last_name', $result['info']['missing_columns']);
    }

    /** @test */
    public function it_detects_extra_columns_not_in_core_fields()
    {
        $file = $this->createTestFile([
            ['FirstName', 'LastName', 'Department', 'Position'],
            ['Juan', 'Cruz', 'IT', 'Developer'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertFalse($result['valid']);
        $this->assertContains('Unexpected column: Department', $result['errors']);
        $this->assertContains('Unexpected column: Position', $result['errors']);
        $this->assertContains('Department', $result['info']['extra_columns']);
        $this->assertContains('Position', $result['info']['extra_columns']);
    }

    /** @test */
    public function it_accepts_various_first_name_variations()
    {
        $variations = ['firstname', 'FirstName', 'first_name', 'fname'];

        foreach ($variations as $variation) {
            $file = $this->createTestFile([
                [$variation, 'LastName'],
                ['Juan', 'Cruz'],
            ]);

            $result = $this->service->validateColumns($file, null);

            $this->assertTrue($result['valid'], "Failed for variation: {$variation}");
        }
    }

    /** @test */
    public function it_accepts_various_last_name_variations()
    {
        $variations = ['surname', 'Surname', 'lastname', 'LastName', 'last_name'];

        foreach ($variations as $variation) {
            $file = $this->createTestFile([
                ['FirstName', $variation],
                ['Juan', 'Cruz'],
            ]);

            $result = $this->service->validateColumns($file, null);

            $this->assertTrue($result['valid'], "Failed for variation: {$variation}");
        }
    }

    /** @test */
    public function it_accepts_optional_core_fields()
    {
        $file = $this->createTestFile([
            ['FirstName', 'LastName', 'MiddleName', 'Birthday', 'Gender', 'Address'],
            ['Juan', 'Cruz', 'Dela', '1990-01-01', 'Male', 'Manila'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_validates_file_with_template_successfully()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => [
                'FirstName' => 'first_name',
                'LastName' => 'last_name',
            ],
        ]);

        $file = $this->createTestFile([
            ['FirstName', 'LastName'],
            ['Juan', 'Cruz'],
        ]);

        $result = $this->service->validateColumns($file, $template);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_returns_structured_validation_result()
    {
        $file = $this->createTestFile([
            ['FirstName', 'LastName'],
            ['Juan', 'Cruz'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('expected_columns', $result['info']);
        $this->assertArrayHasKey('found_columns', $result['info']);
        $this->assertArrayHasKey('missing_columns', $result['info']);
        $this->assertArrayHasKey('extra_columns', $result['info']);
    }

    /** @test */
    public function it_handles_file_reading_errors_gracefully()
    {
        $file = UploadedFile::fake()->create('invalid.txt', 100);

        $result = $this->service->validateColumns($file, null);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('File reading error', $result['errors'][0]);
    }

    /** @test */
    public function it_accepts_all_birthday_field_variations()
    {
        $variations = ['dob', 'DOB', 'birthday', 'Birthday', 'birthdate', 'BirthDate'];

        foreach ($variations as $variation) {
            $file = $this->createTestFile([
                ['FirstName', 'LastName', $variation],
                ['Juan', 'Cruz', '1990-01-01'],
            ]);

            $result = $this->service->validateColumns($file, null);

            $this->assertTrue($result['valid'], "Failed for variation: {$variation}");
        }
    }

    /** @test */
    public function it_accepts_all_gender_field_variations()
    {
        $variations = ['sex', 'Sex', 'gender', 'Gender'];

        foreach ($variations as $variation) {
            $file = $this->createTestFile([
                ['FirstName', 'LastName', $variation],
                ['Juan', 'Cruz', 'Male'],
            ]);

            $result = $this->service->validateColumns($file, null);

            $this->assertTrue($result['valid'], "Failed for variation: {$variation}");
        }
    }

    /** @test */
    public function it_accepts_all_address_field_variations()
    {
        $variations = ['address', 'Address', 'street', 'Street'];

        foreach ($variations as $variation) {
            $file = $this->createTestFile([
                ['FirstName', 'LastName', $variation],
                ['Juan', 'Cruz', 'Manila'],
            ]);

            $result = $this->service->validateColumns($file, null);

            $this->assertTrue($result['valid'], "Failed for variation: {$variation}");
        }
    }

    /** @test */
    public function it_reports_all_validation_errors_at_once()
    {
        $file = $this->createTestFile([
            ['Department', 'Position'],
            ['IT', 'Developer'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(4, count($result['errors'])); // 2 missing + 2 extra
        $this->assertContains('Missing required column: first_name', $result['errors']);
        $this->assertContains('Missing required column: last_name', $result['errors']);
        $this->assertContains('Unexpected column: Department', $result['errors']);
        $this->assertContains('Unexpected column: Position', $result['errors']);
    }

    /** @test */
    public function it_trims_whitespace_from_headers()
    {
        $file = $this->createTestFile([
            ['  FirstName  ', '  LastName  '],
            ['Juan', 'Cruz'],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertTrue($result['valid']);
        $this->assertEquals(['FirstName', 'LastName'], $result['info']['found_columns']);
    }

    /** @test */
    public function it_ignores_empty_header_cells()
    {
        $file = $this->createTestFile([
            ['FirstName', '', 'LastName', ''],
            ['Juan', '', 'Cruz', ''],
        ]);

        $result = $this->service->validateColumns($file, null);

        $this->assertTrue($result['valid']);
        $this->assertEquals(['FirstName', 'LastName'], $result['info']['found_columns']);
    }

    /**
     * Helper method to create test Excel file
     */
    protected function createTestFile(array $data): UploadedFile
    {
        $filename = 'test_' . uniqid() . '.xlsx';
        $path = storage_path('app/' . $filename);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $files = glob(storage_path('app/test_*.xlsx'));
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }
}
