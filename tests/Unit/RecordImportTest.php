<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\MainSystem;
use App\Models\MatchResult;
use App\Models\UploadBatch;
use App\Services\DataMappingService;
use App\Services\DataMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecordImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_extracts_core_fields_from_mapped_data()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        $import->collection($rows);

        $this->assertDatabaseHas('main_system', [
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'gender' => 'Male',
        ]);
    }

    public function test_it_stores_dynamic_fields_in_additional_attributes()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Garcia',
                'firstname' => 'Maria',
                'birthday' => '1985-12-20',
                'gender' => 'Female',
                'employee_id' => 'EMP-002',
                'department' => 'HR',
                'position' => 'Manager',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Garcia')->first();
        
        $this->assertNotNull($record);
        $this->assertNotNull($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
        $this->assertArrayHasKey('position', $record->additional_attributes);
        $this->assertEquals('EMP-002', $record->additional_attributes['employee_id']);
        $this->assertEquals('HR', $record->additional_attributes['department']);
        $this->assertEquals('Manager', $record->additional_attributes['position']);
    }

    public function test_it_validates_using_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        // Row with missing first_name should be skipped
        $rows = new Collection([
            [
                'lastname' => 'Santos',
                'birthday' => '1992-03-10',
                'gender' => 'Male',
                'employee_id' => 'EMP-003',
            ],
        ]);

        $import->collection($rows);

        // Record should not be created because first_name is missing
        $this->assertDatabaseMissing('main_system', [
            'last_name' => 'Santos',
        ]);
    }

    public function test_it_passes_structured_data_to_match_service()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Reyes',
                'firstname' => 'Pedro',
                'birthday' => '1988-07-25',
                'gender' => 'Male',
                'employee_id' => 'EMP-004',
                'salary_grade' => 'SG-10',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Reyes')->first();
        
        $this->assertNotNull($record);
        $this->assertEquals('Pedro', $record->first_name);
        $this->assertNotNull($record->additional_attributes);
        $this->assertEquals('EMP-004', $record->additional_attributes['employee_id']);
        $this->assertEquals('SG-10', $record->additional_attributes['salary_grade']);
    }

    public function test_it_creates_match_result_with_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Lopez',
                'firstname' => 'Ana',
                'middlename' => 'Santos',
                'birthday' => '1995-11-30',
                'gender' => 'Female',
                'employee_id' => 'EMP-005',
            ],
        ]);

        $import->collection($rows);

        $this->assertDatabaseHas('match_results', [
            'batch_id' => $batch->id,
            'uploaded_last_name' => 'Lopez',
            'uploaded_first_name' => 'Ana',
            'uploaded_middle_name' => 'Santos',
            'match_status' => 'NEW RECORD',
        ]);
    }

    public function test_it_handles_rows_with_only_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Cruz',
                'firstname' => 'Jose',
                'birthday' => '1980-01-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Cruz')->first();
        
        $this->assertNotNull($record);
        $this->assertEquals('Jose', $record->first_name);
        // additional_attributes should be null or empty array
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }
}
