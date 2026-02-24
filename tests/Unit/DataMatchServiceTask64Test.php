<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\DataMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Tests for DataMatchService Task 6.4
 * 
 * Requirements: 5.1, 5.4, 9.4
 * 
 * These tests verify the complete DataMatchService functionality:
 * 1. Backward compatibility with flat array input
 * 2. New structured input format
 * 3. Dynamic fields stored in additional_attributes
 * 4. Matching ignores dynamic attributes
 * 
 * Validates: Requirements 5.1, 5.4, 9.4
 */
class DataMatchServiceTask64Test extends TestCase
{
    use RefreshDatabase;

    protected DataMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMatchService();
    }

    // ========================================================================
    // Requirement 1: Backward compatibility with flat array input
    // ========================================================================

    /** @test */
    public function it_accepts_flat_array_for_findMatch()
    {
        $existing = MainSystem::factory()->create([
            'last_name' => 'Smith',
            'first_name' => 'John',
            'last_name_normalized' => 'smith',
            'first_name_normalized' => 'john',
            'birthday' => '1990-01-01',
            'gender' => 'Male',
        ]);

        $flatArray = [
            'last_name' => 'Smith',
            'first_name' => 'John',
            'birthday' => '1990-01-01',
        ];

        $result = $this->service->findMatch($flatArray);

        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_accepts_flat_array_for_insertNewRecord()
    {
        $flatArray = [
            'last_name' => 'Johnson',
            'first_name' => 'Jane',
            'birthday' => '1985-05-15',
            'gender' => 'Female',
        ];

        $record = $this->service->insertNewRecord($flatArray);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertEquals('Johnson', $record->last_name);
        $this->assertEquals('Jane', $record->first_name);
        $this->assertEquals('1985-05-15', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Female', $record->gender);
    }

    /** @test */
    public function it_handles_flat_array_with_all_core_fields()
    {
        $flatArray = [
            'last_name' => 'Williams',
            'first_name' => 'Robert',
            'middle_name' => 'James',
            'suffix' => 'Jr.',
            'birthday' => '1992-08-20',
            'gender' => 'Male',
            'civil_status' => 'Married',
            'street' => '123 Main St',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'barangay' => 'Poblacion',
        ];

        $record = $this->service->insertNewRecord($flatArray);

        $this->assertEquals('Williams', $record->last_name);
        $this->assertEquals('Robert', $record->first_name);
        $this->assertEquals('James', $record->middle_name);
        $this->assertEquals('Jr.', $record->suffix);
        $this->assertEquals('Married', $record->civil_status);
        $this->assertEquals('123 Main St', $record->street);
        $this->assertEquals('Manila', $record->city);
        $this->assertEquals('Metro Manila', $record->province);
        $this->assertEquals('Poblacion', $record->barangay);
    }

    // ========================================================================
    // Requirement 2: New structured input format
    // ========================================================================

    /** @test */
    public function it_accepts_structured_format_for_findMatch()
    {
        $existing = MainSystem::factory()->create([
            'last_name' => 'Brown',
            'first_name' => 'Emily',
            'last_name_normalized' => 'brown',
            'first_name_normalized' => 'emily',
            'birthday' => '1988-03-12',
            'gender' => 'Female',
        ]);

        $structuredData = [
            'core_fields' => [
                'last_name' => 'Brown',
                'first_name' => 'Emily',
                'birthday' => '1988-03-12',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-100',
            ],
        ];

        $result = $this->service->findMatch($structuredData);

        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_accepts_structured_format_for_insertNewRecord()
    {
        $structuredData = [
            'core_fields' => [
                'last_name' => 'Davis',
                'first_name' => 'Michael',
                'birthday' => '1995-11-08',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'department' => 'Engineering',
                'position' => 'Developer',
            ],
        ];

        $record = $this->service->insertNewRecord($structuredData);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertEquals('Davis', $record->last_name);
        $this->assertEquals('Michael', $record->first_name);
        $this->assertEquals('1995-11-08', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
    }

    /** @test */
    public function it_handles_structured_format_with_empty_dynamic_fields()
    {
        $structuredData = [
            'core_fields' => [
                'last_name' => 'Miller',
                'first_name' => 'Sarah',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [],
        ];

        $record = $this->service->insertNewRecord($structuredData);

        $this->assertEquals('Miller', $record->last_name);
        $this->assertEquals('Sarah', $record->first_name);
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_handles_structured_format_without_dynamic_fields_key()
    {
        $structuredData = [
            'core_fields' => [
                'last_name' => 'Wilson',
                'first_name' => 'David',
                'gender' => 'Male',
            ],
        ];

        $record = $this->service->insertNewRecord($structuredData);

        $this->assertEquals('Wilson', $record->last_name);
        $this->assertEquals('David', $record->first_name);
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    // ========================================================================
    // Requirement 3: Dynamic fields stored in additional_attributes
    // ========================================================================

    /** @test */
    public function it_stores_dynamic_fields_in_additional_attributes()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Moore',
                'first_name' => 'Jennifer',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-200',
                'department' => 'HR',
                'hire_date' => '2020-01-15',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
        $this->assertArrayHasKey('hire_date', $record->additional_attributes);
        $this->assertEquals('EMP-200', $record->additional_attributes['employee_id']);
        $this->assertEquals('HR', $record->additional_attributes['department']);
        $this->assertEquals('2020-01-15', $record->additional_attributes['hire_date']);
    }

    /** @test */
    public function it_persists_dynamic_fields_to_database()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Taylor',
                'first_name' => 'Christopher',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'badge_number' => 'BADGE-500',
                'office_location' => 'Building A',
            ],
        ];

        $record = $this->service->insertNewRecord($data);
        $uid = $record->uid;

        // Fresh query from database
        $retrieved = MainSystem::where('uid', $uid)->first();

        $this->assertNotNull($retrieved->additional_attributes);
        $this->assertEquals('BADGE-500', $retrieved->additional_attributes['badge_number']);
        $this->assertEquals('Building A', $retrieved->additional_attributes['office_location']);
    }

    /** @test */
    public function it_stores_multiple_dynamic_fields_with_various_types()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Anderson',
                'first_name' => 'Lisa',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'string_field' => 'text value',
                'numeric_field' => 42,
                'float_field' => 99.99,
                'boolean_field' => true,
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('text value', $record->additional_attributes['string_field']);
        $this->assertEquals(42, $record->additional_attributes['numeric_field']);
        $this->assertEquals(99.99, $record->additional_attributes['float_field']);
        $this->assertTrue($record->additional_attributes['boolean_field']);
    }

    /** @test */
    public function it_does_not_store_core_fields_in_additional_attributes()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Thomas',
                'first_name' => 'Daniel',
                'middle_name' => 'Lee',
                'birthday' => '1991-07-22',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'custom_field' => 'custom value',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        // Verify core fields are in their columns
        $this->assertEquals('Thomas', $record->last_name);
        $this->assertEquals('Daniel', $record->first_name);
        $this->assertEquals('Lee', $record->middle_name);

        // Verify core fields are NOT in additional_attributes
        $this->assertArrayNotHasKey('last_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('first_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('middle_name', $record->additional_attributes);
        $this->assertArrayNotHasKey('birthday', $record->additional_attributes);
        $this->assertArrayNotHasKey('gender', $record->additional_attributes);

        // Verify only dynamic field is in additional_attributes
        $this->assertArrayHasKey('custom_field', $record->additional_attributes);
    }

    // ========================================================================
    // Requirement 4: Matching ignores dynamic attributes
    // ========================================================================

    /** @test */
    public function it_matches_records_with_different_dynamic_attributes()
    {
        // Create existing record with dynamic attributes
        $existing = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'Jackson',
                'first_name' => 'Matthew',
                'birthday' => '1989-04-18',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'department' => 'IT',
                'position' => 'Manager',
            ],
        ]);

        // Try to match with same core fields but different dynamic fields
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Jackson',
                'first_name' => 'Matthew',
                'birthday' => '1989-04-18',
            ],
            'dynamic_fields' => [
                'department' => 'Finance', // Different!
                'position' => 'Director',  // Different!
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should match despite different dynamic fields
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_matches_records_with_conflicting_dynamic_field_values()
    {
        // Create existing record
        $existing = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'White',
                'first_name' => 'Jessica',
                'birthday' => '1993-09-25',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-999',
                'status' => 'Active',
            ],
        ]);

        // Upload with same core but conflicting dynamic values
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'White',
                'first_name' => 'Jessica',
                'birthday' => '1993-09-25',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-111', // Conflict!
                'status' => 'Inactive',     // Conflict!
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should still match based on core fields only
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);

        // Verify existing record's dynamic fields remain unchanged
        $existing->refresh();
        $this->assertEquals('EMP-999', $existing->additional_attributes['employee_id']);
        $this->assertEquals('Active', $existing->additional_attributes['status']);
    }

    /** @test */
    public function it_matches_record_with_dynamic_fields_to_record_without()
    {
        // Create existing record WITHOUT dynamic fields
        $existing = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'Harris',
                'first_name' => 'Kevin',
                'birthday' => '1987-12-05',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [],
        ]);

        // Upload WITH dynamic fields
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Harris',
                'first_name' => 'Kevin',
                'birthday' => '1987-12-05',
            ],
            'dynamic_fields' => [
                'new_field' => 'new value',
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should match despite one having dynamic fields and other not
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_matches_record_without_dynamic_fields_to_record_with()
    {
        // Create existing record WITH dynamic fields
        $existing = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'Martin',
                'first_name' => 'Amanda',
                'birthday' => '1994-06-30',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'existing_field' => 'existing value',
            ],
        ]);

        // Upload WITHOUT dynamic fields
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Martin',
                'first_name' => 'Amanda',
                'birthday' => '1994-06-30',
            ],
            'dynamic_fields' => [],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should match despite one having dynamic fields and other not
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_uses_only_core_fields_for_candidate_queries()
    {
        // Create multiple records with same core fields but different dynamic fields
        $record1 = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'Thompson',
                'first_name' => 'Brian',
                'birthday' => '1990-02-14',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'version' => 'v1',
            ],
        ]);

        // Try to match - should find record1 based on core fields only
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Thompson',
                'first_name' => 'Brian',
                'birthday' => '1990-02-14',
            ],
            'dynamic_fields' => [
                'version' => 'v2', // Different dynamic field
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should match the first record
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($record1->uid, $result['matched_id']);
    }

    // ========================================================================
    // Integration tests: All requirements together
    // ========================================================================

    /** @test */
    public function it_handles_complete_workflow_with_old_format()
    {
        // Insert with old format
        $record1 = $this->service->insertNewRecord([
            'last_name' => 'Clark',
            'first_name' => 'Rachel',
            'birthday' => '1986-10-10',
            'gender' => 'Female',
        ]);

        // Match with old format
        $result = $this->service->findMatch([
            'last_name' => 'Clark',
            'first_name' => 'Rachel',
            'birthday' => '1986-10-10',
        ]);

        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($record1->uid, $result['matched_id']);
    }

    /** @test */
    public function it_handles_complete_workflow_with_new_format()
    {
        // Insert with new format
        $record1 = $this->service->insertNewRecord([
            'core_fields' => [
                'last_name' => 'Lewis',
                'first_name' => 'Steven',
                'birthday' => '1992-05-20',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'team' => 'Alpha',
            ],
        ]);

        // Match with new format
        $result = $this->service->findMatch([
            'core_fields' => [
                'last_name' => 'Lewis',
                'first_name' => 'Steven',
                'birthday' => '1992-05-20',
            ],
            'dynamic_fields' => [
                'team' => 'Beta', // Different!
            ],
        ]);

        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($record1->uid, $result['matched_id']);

        // Verify dynamic fields stored correctly
        $record1->refresh();
        $this->assertEquals('Alpha', $record1->additional_attributes['team']);
    }

    /** @test */
    public function it_handles_mixed_format_workflow()
    {
        // Insert with old format
        $record1 = $this->service->insertNewRecord([
            'last_name' => 'Walker',
            'first_name' => 'Nicole',
            'birthday' => '1988-08-08',
            'gender' => 'Female',
        ]);

        // Match with new format
        $result = $this->service->findMatch([
            'core_fields' => [
                'last_name' => 'Walker',
                'first_name' => 'Nicole',
                'birthday' => '1988-08-08',
            ],
            'dynamic_fields' => [
                'new_info' => 'value',
            ],
        ]);

        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($record1->uid, $result['matched_id']);
    }
}
