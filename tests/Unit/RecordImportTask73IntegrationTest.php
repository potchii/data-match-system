<?php

namespace Tests\Unit;

use App\Imports\RecordImport;
use App\Models\MainSystem;
use App\Models\MatchResult;
use App\Models\UploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Task 7.3: Integration tests for RecordImport
 * 
 * Requirements:
 * - Test import with only core columns (backward compatibility)
 * - Test import with mixed core and dynamic columns
 * - Test dynamic attributes preserved after import
 * - Test matching ignores dynamic attributes during import
 * 
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 5.1, 9.5
 */
class RecordImportTask73IntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================================
    // Test 1: Import with only core columns (backward compatibility)
    // Requirements: 3.1, 9.5
    // ========================================================================

    /** @test */
    public function it_imports_records_with_only_core_columns()
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
                'lastname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'middlename' => 'Santos',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
                'civilstatus' => 'Single',
            ],
        ]);

        $import->collection($rows);

        // Verify record created with core fields
        $record = MainSystem::where('last_name', 'Dela Cruz')->first();
        
        $this->assertNotNull($record);
        $this->assertEquals('Dela Cruz', $record->last_name);
        $this->assertEquals('Juan', $record->first_name);
        $this->assertEquals('Santos', $record->middle_name);
        $this->assertEquals('1990-05-15', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
        $this->assertEquals('Single', $record->civil_status);
        
        // Verify no dynamic attributes (backward compatibility)
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_minimal_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'minimal.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Garcia',
                'firstname' => 'Maria',
                'gender' => 'Female',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Garcia')->first();
        
        $this->assertNotNull($record);
        $this->assertEquals('Garcia', $record->last_name);
        $this->assertEquals('Maria', $record->first_name);
        $this->assertEquals('Female', $record->gender);
        $this->assertNull($record->additional_attributes);
    }

    /** @test */
    public function it_imports_multiple_records_with_only_core_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'multiple-core.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Lopez',
                'firstname' => 'Pedro',
                'birthday' => '1985-03-20',
                'gender' => 'Male',
            ],
            [
                'lastname' => 'Reyes',
                'firstname' => 'Ana',
                'birthday' => '1992-07-15',
                'gender' => 'Female',
            ],
            [
                'lastname' => 'Santos',
                'firstname' => 'Jose',
                'birthday' => '1988-11-30',
                'gender' => 'Male',
            ],
        ]);

        $import->collection($rows);

        // Verify all records created
        $this->assertDatabaseHas('main_system', ['last_name' => 'Lopez', 'first_name' => 'Pedro']);
        $this->assertDatabaseHas('main_system', ['last_name' => 'Reyes', 'first_name' => 'Ana']);
        $this->assertDatabaseHas('main_system', ['last_name' => 'Santos', 'first_name' => 'Jose']);
        
        // Verify none have dynamic attributes
        $records = MainSystem::whereIn('last_name', ['Lopez', 'Reyes', 'Santos'])->get();
        foreach ($records as $record) {
            $this->assertTrue(
                $record->additional_attributes === null || 
                empty($record->additional_attributes)
            );
        }
    }

    // ========================================================================
    // Test 2: Import with mixed core and dynamic columns
    // Requirements: 3.1, 3.2, 3.3, 3.4
    // ========================================================================

    /** @test */
    public function it_imports_records_with_mixed_core_and_dynamic_columns()
    {
        $batch = UploadBatch::create([
            'file_name' => 'mixed-columns.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Mendoza',
                'firstname' => 'Carlos',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'position' => 'Developer',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Mendoza')->first();
        
        // Verify core fields in columns
        $this->assertNotNull($record);
        $this->assertEquals('Mendoza', $record->last_name);
        $this->assertEquals('Carlos', $record->first_name);
        $this->assertEquals('1990-05-15', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
        
        // Verify dynamic fields in additional_attributes
        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
        $this->assertArrayHasKey('position', $record->additional_attributes);
        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
        $this->assertEquals('IT', $record->additional_attributes['department']);
        $this->assertEquals('Developer', $record->additional_attributes['position']);
    }

    /** @test */
    public function it_correctly_separates_core_and_dynamic_fields()
    {
        $batch = UploadBatch::create([
            'file_name' => 'separation-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                // Core fields
                'lastname' => 'Ramos',
                'firstname' => 'Lisa',
                'middlename' => 'Cruz',
                'birthday' => '1988-08-20',
                'gender' => 'Female',
                'civilstatus' => 'Married',
                'city' => 'Manila',
                'barangay' => 'Poblacion',
                // Dynamic fields
                'employee_number' => 'EMP-2024-100',
                'salary_grade' => 'SG-15',
                'hire_date' => '2020-01-15',
                'contact_number' => '+63-912-345-6789',
                'emergency_contact' => 'Jane Ramos',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Ramos')->first();
        
        // Verify all core fields are in their columns
        $this->assertEquals('Ramos', $record->last_name);
        $this->assertEquals('Lisa', $record->first_name);
        $this->assertEquals('Cruz', $record->middle_name);
        $this->assertEquals('Female', $record->gender);
        $this->assertEquals('Married', $record->civil_status);
        $this->assertEquals('Manila', $record->city);
        $this->assertEquals('Poblacion', $record->barangay);
        
        // Verify all dynamic fields are in additional_attributes
        $this->assertCount(5, $record->additional_attributes);
        $this->assertEquals('EMP-2024-100', $record->additional_attributes['employee_number']);
        $this->assertEquals('SG-15', $record->additional_attributes['salary_grade']);
        $this->assertEquals('2020-01-15', $record->additional_attributes['hire_date']);
        $this->assertEquals('+63-912-345-6789', $record->additional_attributes['contact_number']);
        $this->assertEquals('Jane Ramos', $record->additional_attributes['emergency_contact']);
        
        // Verify core fields are NOT in additional_attributes
        $this->assertArrayNotHasKey('last_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('first_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('middle_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('gender', $record->additional_attributes);
        $this->assertArrayNotHasKey('city', $record->additional_attributes);
    }

    /** @test */
    public function it_handles_various_column_name_formats_in_mixed_import()
    {
        $batch = UploadBatch::create([
            'file_name' => 'format-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                // Core fields with various formats
                'Surname' => 'Fernandez',
                'FirstName' => 'Miguel',
                'DOB' => '1992-03-10',
                'Sex' => 'M',
                // Dynamic fields with various formats
                'EmployeeID' => 'EMP-200',
                'Department-Code' => 'FIN',
                'Job Title' => 'Analyst',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Fernandez')->first();
        
        // Verify core fields mapped correctly
        $this->assertEquals('Fernandez', $record->last_name);
        $this->assertEquals('Miguel', $record->first_name);
        $this->assertEquals('Male', $record->gender);
        
        // Verify dynamic fields with normalized keys
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department_code', $record->additional_attributes);
        $this->assertArrayHasKey('job_title', $record->additional_attributes);
    }

    // ========================================================================
    // Test 3: Dynamic attributes preserved after import
    // Requirements: 3.4
    // ========================================================================

    /** @test */
    public function it_preserves_dynamic_attributes_after_import()
    {
        $batch = UploadBatch::create([
            'file_name' => 'preservation-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Torres',
                'firstname' => 'Elena',
                'gender' => 'Female',
                'custom_field_1' => 'Value 1',
                'custom_field_2' => 'Value 2',
                'custom_field_3' => 'Value 3',
            ],
        ]);

        $import->collection($rows);

        // Retrieve from database
        $record = MainSystem::where('last_name', 'Torres')->first();
        $uid = $record->uid;
        
        // Fresh query to ensure data persisted
        $freshRecord = MainSystem::where('uid', $uid)->first();
        
        $this->assertNotNull($freshRecord->additional_attributes);
        $this->assertArrayHasKey('custom_field_1', $freshRecord->additional_attributes);
        $this->assertArrayHasKey('custom_field_2', $freshRecord->additional_attributes);
        $this->assertArrayHasKey('custom_field_3', $freshRecord->additional_attributes);
        $this->assertEquals('Value 1', $freshRecord->additional_attributes['custom_field_1']);
        $this->assertEquals('Value 2', $freshRecord->additional_attributes['custom_field_2']);
        $this->assertEquals('Value 3', $freshRecord->additional_attributes['custom_field_3']);
    }

    /** @test */
    public function it_preserves_dynamic_attributes_with_various_data_types()
    {
        $batch = UploadBatch::create([
            'file_name' => 'types-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Castillo',
                'firstname' => 'Roberto',
                'gender' => 'Male',
                'string_field' => 'text value',
                'numeric_field' => 42,
                'float_field' => 99.99,
                'date_field' => '2024-01-15',
            ],
        ]);

        $import->collection($rows);

        $record = MainSystem::where('last_name', 'Castillo')->first();
        
        $this->assertEquals('text value', $record->additional_attributes['string_field']);
        $this->assertEquals(42, $record->additional_attributes['numeric_field']);
        $this->assertEquals(99.99, $record->additional_attributes['float_field']);
        $this->assertEquals('2024-01-15', $record->additional_attributes['date_field']);
    }

    /** @test */
    public function it_preserves_dynamic_attributes_across_multiple_records()
    {
        $batch = UploadBatch::create([
            'file_name' => 'multiple-dynamic.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        $rows = new Collection([
            [
                'lastname' => 'Cruz',
                'firstname' => 'Anna',
                'gender' => 'Female',
                'badge_id' => 'BADGE-100',
                'office' => 'Building A',
            ],
            [
                'lastname' => 'Diaz',
                'firstname' => 'Marco',
                'gender' => 'Male',
                'badge_id' => 'BADGE-200',
                'office' => 'Building B',
            ],
        ]);

        $import->collection($rows);

        $record1 = MainSystem::where('last_name', 'Cruz')->first();
        $record2 = MainSystem::where('last_name', 'Diaz')->first();
        
        // Verify each record has its own dynamic attributes
        $this->assertEquals('BADGE-100', $record1->additional_attributes['badge_id']);
        $this->assertEquals('Building A', $record1->additional_attributes['office']);
        
        $this->assertEquals('BADGE-200', $record2->additional_attributes['badge_id']);
        $this->assertEquals('Building B', $record2->additional_attributes['office']);
    }

    // ========================================================================
    // Test 4: Matching ignores dynamic attributes during import
    // Requirements: 5.1
    // ========================================================================

    /** @test */
    public function it_matches_existing_record_ignoring_dynamic_attributes()
    {
        // Create existing record with dynamic attributes
        $existing = MainSystem::factory()->create([
            'last_name' => 'Gonzales',
            'first_name' => 'Patricia',
            'last_name_normalized' => 'gonzales',
            'first_name_normalized' => 'patricia',
            'birthday' => '1990-06-15',
            'gender' => 'Female',
            'additional_attributes' => [
                'department' => 'HR',
                'position' => 'Manager',
            ],
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'match-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        // Import with same core fields but different dynamic attributes
        $rows = new Collection([
            [
                'lastname' => 'Gonzales',
                'firstname' => 'Patricia',
                'birthday' => '1990-06-15',
                'gender' => 'Female',
                'department' => 'IT',      // Different!
                'position' => 'Director',  // Different!
            ],
        ]);

        $import->collection($rows);

        // Should match existing record, not create new one
        $records = MainSystem::where('last_name', 'Gonzales')->get();
        $this->assertCount(1, $records);
        $this->assertEquals($existing->uid, $records->first()->uid);
        
        // Verify match result created
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        $this->assertNotNull($matchResult);
        $this->assertNotEquals('NEW RECORD', $matchResult->match_status);
        $this->assertEquals($existing->uid, $matchResult->matched_system_id);
        
        // Verify existing record's dynamic attributes unchanged
        $existing->refresh();
        $this->assertEquals('HR', $existing->additional_attributes['department']);
        $this->assertEquals('Manager', $existing->additional_attributes['position']);
    }

    /** @test */
    public function it_matches_record_with_dynamic_fields_to_record_without()
    {
        // Create existing record WITHOUT dynamic attributes
        $existing = MainSystem::factory()->create([
            'last_name' => 'Villanueva',
            'first_name' => 'Ricardo',
            'last_name_normalized' => 'villanueva',
            'first_name_normalized' => 'ricardo',
            'birthday' => '1985-09-20',
            'gender' => 'Male',
            'additional_attributes' => null,
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'match-with-dynamic.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        // Import with same core fields but WITH dynamic attributes
        $rows = new Collection([
            [
                'lastname' => 'Villanueva',
                'firstname' => 'Ricardo',
                'birthday' => '1985-09-20',
                'gender' => 'Male',
                'employee_id' => 'EMP-500',
                'department' => 'Finance',
            ],
        ]);

        $import->collection($rows);

        // Should match existing record
        $records = MainSystem::where('last_name', 'Villanueva')->get();
        $this->assertCount(1, $records);
        $this->assertEquals($existing->uid, $records->first()->uid);
        
        // Verify match result
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        $this->assertNotEquals('NEW RECORD', $matchResult->match_status);
        $this->assertEquals($existing->uid, $matchResult->matched_system_id);
    }

    /** @test */
    public function it_creates_new_record_when_core_fields_differ_despite_same_dynamic_fields()
    {
        // Create existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Aquino',
            'first_name' => 'Sofia',
            'last_name_normalized' => 'aquino',
            'first_name_normalized' => 'sofia',
            'birthday' => '1992-04-10',
            'gender' => 'Female',
            'additional_attributes' => [
                'employee_id' => 'EMP-100',
            ],
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'new-record-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        // Import with different core fields but same dynamic field
        $rows = new Collection([
            [
                'lastname' => 'Bautista',  // Different!
                'firstname' => 'Carmen',   // Different!
                'birthday' => '1993-07-25', // Different!
                'gender' => 'Female',
                'employee_id' => 'EMP-100', // Same as existing!
            ],
        ]);

        $import->collection($rows);

        // Should create new record despite same employee_id
        $newRecord = MainSystem::where('last_name', 'Bautista')->first();
        $this->assertNotNull($newRecord);
        $this->assertNotEquals($existing->uid, $newRecord->uid);
        
        // Verify match result shows NEW RECORD
        $matchResult = MatchResult::where('batch_id', $batch->id)
            ->where('uploaded_last_name', 'Bautista')
            ->first();
        $this->assertEquals('NEW RECORD', $matchResult->match_status);
        $this->assertEquals($newRecord->uid, $matchResult->matched_system_id);
    }

    /** @test */
    public function it_matches_based_on_core_fields_with_conflicting_dynamic_values()
    {
        // Create existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Santiago',
            'first_name' => 'Luis',
            'middle_name' => 'Reyes',
            'last_name_normalized' => 'santiago',
            'first_name_normalized' => 'luis',
            'middle_name_normalized' => 'reyes',
            'birthday' => '1988-11-05',
            'gender' => 'Male',
            'additional_attributes' => [
                'status' => 'Active',
                'level' => '5',
            ],
        ]);

        $batch = UploadBatch::create([
            'file_name' => 'conflict-test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $import = new RecordImport($batch->id);

        // Import with same core but conflicting dynamic values
        $rows = new Collection([
            [
                'lastname' => 'Santiago',
                'firstname' => 'Luis',
                'middlename' => 'Reyes',
                'birthday' => '1988-11-05',
                'gender' => 'Male',
                'status' => 'Inactive',  // Conflict!
                'level' => '3',          // Conflict!
            ],
        ]);

        $import->collection($rows);

        // Should match existing record
        $records = MainSystem::where('last_name', 'Santiago')->get();
        $this->assertCount(1, $records);
        $this->assertEquals($existing->uid, $records->first()->uid);
        
        // Verify existing dynamic attributes remain unchanged
        $existing->refresh();
        $this->assertEquals('Active', $existing->additional_attributes['status']);
        $this->assertEquals('5', $existing->additional_attributes['level']);
    }

    // ========================================================================
    // Integration test: Complete end-to-end workflow
    // ========================================================================


}
