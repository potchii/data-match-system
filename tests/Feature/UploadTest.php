<?php

namespace Tests\Feature;

use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

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
        
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'test_excel_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return new UploadedFile($tempFile, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_upload_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('upload.index'));

        $response->assertOk();
        $response->assertViewIs('pages.upload');
    }

    public function test_file_upload_requires_authentication()
    {
        $response = $this->get(route('upload.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_valid_file_can_be_uploaded()
    {
        $user = User::factory()->create();

        $file = $this->createTestExcelFile(
            ['FirstName', 'LastName'],
            [['John', 'Doe']]
        );

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        
        // Check that a batch was created
        $this->assertDatabaseHas('upload_batches', [
            'file_name' => 'test.xlsx',
            'uploaded_by' => $user->name,
        ]);
    }

    public function test_upload_validates_file_is_required()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => null,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_validates_file_type()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_validates_file_size()
    {
        $user = User::factory()->create();

        // Create a file larger than 10MB (10240 KB)
        $file = UploadedFile::fake()->create('test.xlsx', 10241);

        $response = $this
            ->actingAs($user)
            ->post(route('upload.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }
}
