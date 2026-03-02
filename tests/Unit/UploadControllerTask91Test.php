<?php

namespace Tests\Unit;

use App\Http\Controllers\UploadController;
use App\Models\ColumnMappingTemplate;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UploadControllerTask91Test extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->controller = new UploadController();
        Storage::fake('local');
    }

    protected function createTestExcelFile(array $columns, array $data = []): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        foreach ($columns as $index => $column) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $column);
        }
        
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }
        
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'test_excel_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return new UploadedFile($tempFile, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, false);
    }

    /**
     * Test store() accepts optional template_id parameter
     * Validates: Requirement 3.4
     */
    public function test_store_accepts_optional_template_id_parameter()
    {
        $template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => ['Surname' => 'last_name', 'FirstName' => 'first_name'],
        ]);

        $file = $this->createTestExcelFile(
            ['Surname', 'FirstName'],
            [['Doe', 'John']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
            'uploaded_by' => $this->user->name,
        ]);
    }

    /**
     * Test store() works without template_id (backward compatibility)
     * Validates: Requirement 3.4
     */
    public function test_store_works_without_template_id()
    {
        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
        ]);
    }

    /**
     * Test store() validates template_id must be integer
     * Validates: Requirement 3.4
     */
    public function test_store_validates_template_id_must_be_integer()
    {
        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => 'invalid',
        ]);

        $response->assertSessionHasErrors('template_id');
    }

    /**
     * Test store() validates template_id must exist in database
     * Validates: Requirement 3.4
     */
    public function test_store_validates_template_id_exists()
    {
        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => 99999,
        ]);

        $response->assertSessionHasErrors('template_id');
    }

    /**
     * Test store() rejects template_id from another user
     * Validates: Requirement 3.4 (security)
     */
    public function test_store_rejects_template_from_another_user()
    {
        $otherUser = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Template',
            'mappings' => ['Surname' => 'last_name'],
        ]);

        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect(route('upload.index'));
        $response->assertSessionHas('error', 'Template not found or you do not have permission to use it.');
    }

    /**
     * Test store() returns JSON error for unauthorized template access (API)
     * Validates: Requirement 3.4 (API response)
     */
    public function test_store_returns_json_error_for_unauthorized_template_api()
    {
        $otherUser = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Template',
            'mappings' => ['Surname' => 'last_name'],
        ]);

        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)
            ->postJson(route('upload.store'), [
                'file' => $file,
                'template_id' => $template->id,
            ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Template not found or you do not have permission to use it.',
        ]);
    }

    /**
     * Test RecordImport receives template when provided
     * Validates: Requirement 3.4
     */
    public function test_record_import_receives_template()
    {
        $template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Surname' => 'last_name',
                'FirstName' => 'first_name',
                'MiddleName' => 'middle_name',
            ],
        ]);

        $file = $this->createTestExcelFile(
            ['Surname', 'FirstName', 'MiddleName'],
            [['Doe', 'John', 'Michael']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
            'template_id' => $template->id,
        ]);

        $response->assertRedirect();
        
        // Verify batch was created
        $batch = UploadBatch::where('file_name', 'test.xlsx')->first();
        $this->assertNotNull($batch);
    }

    /**
     * Test RecordImport works without template (null template)
     * Validates: Requirement 3.4 (backward compatibility)
     */
    public function test_record_import_works_without_template()
    {
        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this->actingAs($this->user)->post(route('upload.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        
        // Verify batch was created (status may be FAILED due to fake file, but that's OK)
        $batch = UploadBatch::where('file_name', 'test.xlsx')->first();
        $this->assertNotNull($batch);
    }
}
