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
 * Task 8: End-to-End Import Flow Verification
 * 
 * This test verifies the complete import workflow:
 * 1. Core fields are stored in database columns
 * 2. Dynamic fields are stored in additional_attributes JSON
 * 3. Matching logic works correctly with the new structure
 * 4. Data integrity is maintained throughout the process
 * 
 * Requirements: Complete verification of Requirements 1-10
 */
class Task8EndToEndImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_completes_end_to_end_import_with_mixed_columns()
    {
        // Step 1: Create upload batch
        $batch = UploadBatch::create([
            'file_name' => 'test_e2e_import.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        // Step 2: Create test data with mixed core and dynamic columns
        $rows = new Collection([
            [
                'regsno' => 'REG-001',
                'surname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'middlename' => 'Santos',
                'dob' => '1990-05-15',
                'sex' => 'M',
                'civilstatus' => 'Single',
                'city' => 'Manila',
                'barangay' => 'Poblacion',
                'employeeid' => 'EMP-2024-001',
                'department' => 'Information Technology',
                'position' => 'Senior Developer',
                'salarygrade' => 'SG-15',
                'hiredate' => '2020-01-15',
                'contactnumber' => '+63-912-345-6789',
            ],
            [
                'regsno' => 'REG-002',
                'surname' => 'Garcia',
                'firstname' => 'Maria',
                'middlename' => 'Lopez',
                'dob' => '1985-12-20',
                'sex' => 'F',
                'civilstatus' => 'Married',
                'city' => 'Quezon City',
                'barangay' => 'Bagong Silang',
                'employeeid' => 'EMP-2024-002',
                'department' => 'Human Resources',
                'position' => 'HR Manager',
                'salarygrade' => 'SG-18',
                'hiredate' => '2018-03-10',
                'contactnumber' => '+63-923-456-7890',
            ],
            [
                'regsno' => 'REG-003',
                'surname' => 'Reyes',
                'firstname' => 'Pedro',
                'middlename' => 'Ramos',
                'dob' => '1992-08-30',
                'sex' => 'M',
                'civilstatus' => 'Single',
                'city' => 'Makati',
                'barangay' => 'Poblacion',
                'employeeid' => 'EMP-2024-003',
                'department' => 'Finance',
                'position' => 'Accountant',
                'salarygrade' => 'SG-12',
                'hiredate' => '2021-06-01',
                'contactnumber' => '+63-934-567-8901',
            ],
        ]);

        // Step 3: Run import directly with collection
        $import = new RecordImport($batch->id);
        $import->collection($rows);

        // Step 4: Verify all records were created
        $this->assertDatabaseCount('main_system', 3);

        // Step 5: Verify first record - Juan Dela Cruz
        $juan = MainSystem::where('last_name', 'Dela Cruz')->first();
        $this->assertNotNull($juan);

        // Verify core fields are in database columns
        $this->assertNotNull($juan->uid); // UID is auto-generated
        $this->assertEquals('Dela Cruz', $juan->last_name);
        $this->assertEquals('Juan', $juan->first_name);
        $this->assertEquals('Santos', $juan->middle_name);
        $this->assertEquals('1990-05-15', $juan->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $juan->gender);
        $this->assertEquals('Single', $juan->civil_status);
        $this->assertEquals('Manila', $juan->city);
        $this->assertEquals('Poblacion', $juan->barangay);

        // Verify normalized fields are populated
        $this->assertEquals('dela cruz', $juan->last_name_normalized);
        $this->assertEquals('juan', $juan->first_name_normalized);
        $this->assertEquals('santos', $juan->middle_name_normalized);

        // Verify dynamic fields are in additional_attributes JSON
        $this->assertNotNull($juan->additional_attributes);
        $this->assertIsArray($juan->additional_attributes);
        
        // Keys are normalized to snake_case: employeeid -> employeeid (no underscore because it's one word)
        $this->assertArrayHasKey('employeeid', $juan->additional_attributes);
        $this->assertArrayHasKey('department', $juan->additional_attributes);
        $this->assertArrayHasKey('position', $juan->additional_attributes);
        $this->assertArrayHasKey('salarygrade', $juan->additional_attributes);
        $this->assertArrayHasKey('hiredate', $juan->additional_attributes);
        $this->assertArrayHasKey('contactnumber', $juan->additional_attributes);

        $this->assertEquals('EMP-2024-001', $juan->additional_attributes['employeeid']);
        $this->assertEquals('Information Technology', $juan->additional_attributes['department']);
        $this->assertEquals('Senior Developer', $juan->additional_attributes['position']);
        $this->assertEquals('SG-15', $juan->additional_attributes['salarygrade']);
        $this->assertEquals('2020-01-15', $juan->additional_attributes['hiredate']);
        $this->assertEquals('+63-912-345-6789', $juan->additional_attributes['contactnumber']);

        // Verify core fields are NOT in additional_attributes
        $this->assertArrayNotHasKey('last_name', $juan->additional_attributes);
        $this->assertArrayNotHasKey('first_name', $juan->additional_attributes);
        $this->assertArrayNotHasKey('birthday', $juan->additional_attributes);
        $this->assertArrayNotHasKey('gender', $juan->additional_attributes);

        // Step 6: Verify second record - Maria Garcia
        $maria = MainSystem::where('last_name', 'Garcia')->first();
        $this->assertNotNull($maria);
        $this->assertEquals('Garcia', $maria->last_name);
        $this->assertEquals('Maria', $maria->first_name);
        $this->assertEquals('Female', $maria->gender);
        $this->assertEquals('EMP-2024-002', $maria->additional_attributes['employeeid']);
        $this->assertEquals('Human Resources', $maria->additional_attributes['department']);

        // Step 7: Verify third record - Pedro Reyes
        $pedro = MainSystem::where('last_name', 'Reyes')->first();
        $this->assertNotNull($pedro);
        $this->assertEquals('Reyes', $pedro->last_name);
        $this->assertEquals('Pedro', $pedro->first_name);
        $this->assertEquals('Male', $pedro->gender);
        $this->assertEquals('EMP-2024-003', $pedro->additional_attributes['employeeid']);
        $this->assertEquals('Finance', $pedro->additional_attributes['department']);

        // Step 8: Verify match results were created
        $this->assertDatabaseCount('match_results', 3);
        
        $matchResults = MatchResult::where('batch_id', $batch->id)->get();
        $this->assertCount(3, $matchResults);
        
        foreach ($matchResults as $result) {
            $this->assertEquals('NEW RECORD', $result->match_status);
            $this->assertNotNull($result->matched_system_id);
        }

        // Step 9: Verify batch relationship
        $this->assertEquals($batch->id, $juan->origin_batch_id);
        $this->assertEquals($batch->id, $maria->origin_batch_id);
        $this->assertEquals($batch->id, $pedro->origin_batch_id);
    }

    /** @test */
    public function it_matches_existing_records_correctly_ignoring_dynamic_fields()
    {
        // Step 1: Create existing record with dynamic attributes
        $existing = MainSystem::factory()->create([
            'uid' => 'EXISTING-001',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name_normalized' => 'dela cruz',
            'first_name_normalized' => 'juan',
            'middle_name_normalized' => 'santos',
            'birthday' => '1990-05-15',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'city' => 'Manila',
            'barangay' => 'Poblacion',
            'additional_attributes' => [
                'employee_id' => 'OLD-EMP-001',
                'department' => 'Sales',
                'position' => 'Sales Rep',
            ],
        ]);

        // Step 2: Create batch and import with matching core fields but different dynamic fields
        $batch = UploadBatch::create([
            'file_name' => 'match_test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $rows = new Collection([
            [
                'surname' => 'Dela Cruz',
                'firstname' => 'Juan',
                'middlename' => 'Santos',
                'dob' => '1990-05-15',
                'sex' => 'M',
                'employeeid' => 'NEW-EMP-999',
                'department' => 'IT',
                'position' => 'Developer',
            ],
        ]);

        $import = new RecordImport($batch->id);
        $import->collection($rows);

        // Step 3: Verify no new record was created (should match existing)
        $records = MainSystem::where('last_name', 'Dela Cruz')
            ->where('first_name', 'Juan')
            ->get();
        
        $this->assertCount(1, $records, 'Should match existing record, not create new one');
        $this->assertEquals($existing->uid, $records->first()->uid);

        // Step 4: Verify existing record's dynamic attributes are unchanged
        $existing->refresh();
        $this->assertEquals('OLD-EMP-001', $existing->additional_attributes['employee_id']);
        $this->assertEquals('Sales', $existing->additional_attributes['department']);
        $this->assertEquals('Sales Rep', $existing->additional_attributes['position']);

        // Step 5: Verify match result shows it matched (not NEW RECORD)
        $matchResult = MatchResult::where('batch_id', $batch->id)->first();
        $this->assertNotNull($matchResult);
        $this->assertNotEquals('NEW RECORD', $matchResult->match_status);
        $this->assertEquals($existing->uid, $matchResult->matched_system_id);
    }

    /** @test */
    public function it_handles_records_with_only_core_fields_backward_compatibility()
    {
        // Create batch and import with ONLY core fields (no dynamic columns)
        $batch = UploadBatch::create([
            'file_name' => 'core_only.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $rows = new Collection([
            [
                'surname' => 'Santos',
                'firstname' => 'Ana',
                'dob' => '1995-03-20',
                'sex' => 'F',
            ],
            [
                'surname' => 'Lopez',
                'firstname' => 'Carlos',
                'dob' => '1988-07-15',
                'sex' => 'M',
            ],
        ]);

        $import = new RecordImport($batch->id);
        $import->collection($rows);

        // Verify records created
        $ana = MainSystem::where('last_name', 'Santos')->first();
        $carlos = MainSystem::where('last_name', 'Lopez')->first();

        $this->assertNotNull($ana);
        $this->assertNotNull($carlos);

        // Verify core fields populated
        $this->assertEquals('Santos', $ana->last_name);
        $this->assertEquals('Ana', $ana->first_name);
        $this->assertEquals('Female', $ana->gender);

        // Verify no dynamic attributes (backward compatibility)
        $this->assertTrue(
            $ana->additional_attributes === null || 
            $ana->additional_attributes === [] ||
            empty($ana->additional_attributes),
            'Should have no dynamic attributes for backward compatibility'
        );
        
        $this->assertTrue(
            $carlos->additional_attributes === null || 
            $carlos->additional_attributes === [] ||
            empty($carlos->additional_attributes),
            'Should have no dynamic attributes for backward compatibility'
        );
    }

    /** @test */
    public function it_can_query_records_by_dynamic_attributes()
    {
        // Create batch and import
        $batch = UploadBatch::create([
            'file_name' => 'query_test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $rows = new Collection([
            [
                'surname' => 'Ramos',
                'firstname' => 'Lisa',
                'sex' => 'F',
                'department' => 'IT',
                'level' => '5',
            ],
            [
                'surname' => 'Cruz',
                'firstname' => 'Mark',
                'sex' => 'M',
                'department' => 'HR',
                'level' => '3',
            ],
            [
                'surname' => 'Diaz',
                'firstname' => 'Nina',
                'sex' => 'F',
                'department' => 'IT',
                'level' => '4',
            ],
        ]);

        $import = new RecordImport($batch->id);
        $import->collection($rows);

        // Query by dynamic attribute: department = IT
        $itRecords = MainSystem::where('additional_attributes->department', 'IT')->get();
        $this->assertCount(2, $itRecords);
        $this->assertTrue($itRecords->pluck('last_name')->contains('Ramos'));
        $this->assertTrue($itRecords->pluck('last_name')->contains('Diaz'));

        // Query by dynamic attribute: level = 5
        $level5Records = MainSystem::where('additional_attributes->level', '5')->get();
        $this->assertCount(1, $level5Records);
        $this->assertEquals('Ramos', $level5Records->first()->last_name);

        // Query by dynamic attribute: department = HR
        $hrRecords = MainSystem::where('additional_attributes->department', 'HR')->get();
        $this->assertCount(1, $hrRecords);
        $this->assertEquals('Cruz', $hrRecords->first()->last_name);
    }

    /** @test */
    public function it_preserves_data_integrity_across_refresh()
    {
        // Create and import
        $batch = UploadBatch::create([
            'file_name' => 'integrity_test.xlsx',
            'uploaded_by' => 'Test User',
            'uploaded_at' => now(),
            'status' => 'processing',
        ]);

        $rows = new Collection([
            [
                'surname' => 'Fernandez',
                'firstname' => 'Miguel',
                'sex' => 'M',
                'badgeid' => 'BADGE-100',
                'office' => 'Building A',
            ],
        ]);

        $import = new RecordImport($batch->id);
        $import->collection($rows);

        // Get record
        $record = MainSystem::where('last_name', 'Fernandez')->first();
        $uid = $record->uid;

        // Verify initial state
        $this->assertEquals('BADGE-100', $record->additional_attributes['badgeid']);
        $this->assertEquals('Building A', $record->additional_attributes['office']);

        // Refresh from database
        $record->refresh();

        // Verify data persisted correctly
        $this->assertEquals('BADGE-100', $record->additional_attributes['badgeid']);
        $this->assertEquals('Building A', $record->additional_attributes['office']);

        // Query fresh from database
        $freshRecord = MainSystem::where('uid', $uid)->first();
        $this->assertEquals('BADGE-100', $freshRecord->additional_attributes['badgeid']);
        $this->assertEquals('Building A', $freshRecord->additional_attributes['office']);
    }
}
