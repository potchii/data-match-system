<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\MainSystem;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Task 7.2: Update record creation logic
 * 
 * Requirements:
 * - Reconstruct full data structure for insertNewRecord
 * - Include both core_fields and dynamic_fields
 * - Ensure origin_batch_id added to core_fields
 * - Validates: Requirements 3.3, 3.4
 */
class RecordImportTask72Test extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_reconstructs_full_data_structure_for_insert()
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

        $record = MainSystem::where('last_name', 'Dela Cruz')->first();
        
        // Verify core fields are stored in columns
        $this->assertNotNull($record);
        $this->assertEquals('Dela Cruz', $record->last_name);
        $this->assertEquals('Juan', $record->first_name);
        $this->assertEquals('Male', $record->gender);
        
        // Verify dynamic fields are stored in additional_attributes
        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
    }

    /** @test */
    public function it_includes_both_core_and_dynamic_fields_in_insert()
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
                'middlename' => 'Santos',
                'birthday' => '1985-12-20',
                'gender' => 'Female',
                'employee_id' => 'EMP-002',
                'position' => 'Manager',
                'salary_grade' => 'SG-15',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Garcia')->first();
        
        // Verify all core fields
        $this->assertEquals('Garcia', $record->last_name);
        $this->assertEquals('Maria', $record->first_name);
        $this->assertEquals('Santos', $record->middle_name);
        $this->assertEquals('Female', $record->gender);
        
        // Verify all dynamic fields
        $this->assertEquals('EMP-002', $record->additional_attributes['employee_id']);
        $this->assertEquals('Manager', $record->additional_attributes['position']);
        $this->assertEquals('SG-15', $record->additional_attributes['salary_grade']);
    }

    /** @test */
    public function it_adds_origin_batch_id_to_core_fields_before_insertion()
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
                'employee_id' => 'EMP-003',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Reyes')->first();
        
        // Verify origin_batch_id is set correctly
        $this->assertNotNull($record);
        $this->assertEquals($batch->id, $record->origin_batch_id);
        
        // Verify the record is linked to the correct batch
        $this->assertNotNull($record->originBatch);
        $this->assertEquals('test.xlsx', $record->originBatch->file_name);
    }

    /** @test */
    public function it_handles_complete_data_flow_correctly()
    {
        $batch = UploadBatch::create([
            'file_name' => 'complete-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Lopez',
                'firstname' => 'Ana',
                'middlename' => 'Cruz',
                'birthday' => '1995-11-30',
                'gender' => 'Female',
                'civilstatus' => 'Single',
                'employee_id' => 'EMP-004',
                'department' => 'HR',
                'position' => 'Specialist',
                'hire_date' => '2020-01-15',
                'contact_number' => '+63-912-345-6789',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Lopez')->first();
        
        // Verify complete core fields
        $this->assertEquals('Lopez', $record->last_name);
        $this->assertEquals('Ana', $record->first_name);
        $this->assertEquals('Cruz', $record->middle_name);
        $this->assertEquals('Female', $record->gender);
        $this->assertEquals('Single', $record->civil_status);
        $this->assertEquals($batch->id, $record->origin_batch_id);
        
        // Verify complete dynamic fields
        $this->assertCount(5, $record->additional_attributes);
        $this->assertEquals('EMP-004', $record->additional_attributes['employee_id']);
        $this->assertEquals('HR', $record->additional_attributes['department']);
        $this->assertEquals('Specialist', $record->additional_attributes['position']);
        $this->assertEquals('2020-01-15', $record->additional_attributes['hire_date']);
        $this->assertEquals('+63-912-345-6789', $record->additional_attributes['contact_number']);
    }

    /** @test */
    public function it_handles_records_with_only_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'core-only.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Santos',
                'firstname' => 'Jose',
                'birthday' => '1980-01-15',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Santos')->first();
        
        // Verify core fields
        $this->assertEquals('Santos', $record->last_name);
        $this->assertEquals('Jose', $record->first_name);
        $this->assertEquals($batch->id, $record->origin_batch_id);
        
        // Verify no dynamic fields (or empty array)
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_preserves_data_structure_through_mapping_service()
    {
        $batch = UploadBatch::create([
            'file_name' => 'structure-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);
        
        $import = new RecordImport($batch->id);

        // Use various column name formats
        $rows = new Collection([
            [
                'Surname' => 'Fernandez',
                'FirstName' => 'Carlos',
                'DOB' => '1992-03-10',
                'Sex' => 'M',
                'EmployeeNumber' => 'EMP-005',
                'DepartmentCode' => 'FIN',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Fernandez')->first();
        
        // Verify mapping worked correctly
        $this->assertEquals('Fernandez', $record->last_name);
        $this->assertEquals('Carlos', $record->first_name);
        $this->assertEquals('Male', $record->gender);
        
        // Verify dynamic fields with normalized keys
        $this->assertArrayHasKey('employee_number', $record->additional_attributes);
        $this->assertArrayHasKey('department_code', $record->additional_attributes);
        $this->assertEquals('EMP-005', $record->additional_attributes['employee_number']);
        $this->assertEquals('FIN', $record->additional_attributes['department_code']);
    }
}
