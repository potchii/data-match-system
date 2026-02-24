<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\DataMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Tests for DataMatchService Task 6.2
 * 
 * Requirements: 3.4, 5.4
 * 
 * These tests verify the insertNewRecord method:
 * - Accepts new structured format with 'core_fields' and 'dynamic_fields' keys
 * - Accepts old flat array format (backward compatibility)
 * - Stores dynamic fields in additional_attributes JSON column
 * - Correctly handles empty dynamic fields
 */
class DataMatchServiceTask62Test extends TestCase
{
    use RefreshDatabase;

    protected DataMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMatchService();
    }

    // ========================================================================
    // Test new structured format with dynamic fields
    // Requirements: 3.4, 5.4
    // ========================================================================

    /** @test */
    public function it_accepts_new_structured_format_with_core_and_dynamic_fields()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertNotNull($record->uid);
        $this->assertEquals('Dela Cruz', $record->last_name);
        $this->assertEquals('Juan', $record->first_name);
        $this->assertEquals('Santos', $record->middle_name);
        $this->assertEquals('1990-05-15', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
    }

    /** @test */
    public function it_stores_dynamic_fields_in_additional_attributes()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Garcia',
                'first_name' => 'Maria',
                'birthday' => '1985-12-20',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-002',
                'department' => 'HR',
                'position' => 'Manager',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertArrayHasKey('employee_id', $record->additional_attributes);
        $this->assertArrayHasKey('department', $record->additional_attributes);
        $this->assertArrayHasKey('position', $record->additional_attributes);
        $this->assertEquals('EMP-002', $record->additional_attributes['employee_id']);
        $this->assertEquals('HR', $record->additional_attributes['department']);
        $this->assertEquals('Manager', $record->additional_attributes['position']);
    }

    /** @test */
    public function it_handles_empty_dynamic_fields_in_new_format()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Lopez',
                'first_name' => 'Pedro',
                'birthday' => '1992-03-10',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertEquals('Lopez', $record->last_name);
        $this->assertEquals('Pedro', $record->first_name);
        // additional_attributes should be null or empty when no dynamic fields
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_handles_missing_dynamic_fields_key()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Reyes',
                'first_name' => 'Ana',
                'birthday' => '1988-07-25',
                'gender' => 'Female',
            ],
            // No 'dynamic_fields' key
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertEquals('Reyes', $record->last_name);
        $this->assertEquals('Ana', $record->first_name);
        // Should handle gracefully with no dynamic fields
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_stores_multiple_dynamic_fields_correctly()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Santos',
                'first_name' => 'Jose',
                'birthday' => '1995-11-30',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-003',
                'department' => 'Finance',
                'position' => 'Analyst',
                'salary_grade' => 'SG-12',
                'hire_date' => '2020-01-15',
                'contact_number' => '+63-912-345-6789',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertCount(6, $record->additional_attributes);
        $this->assertEquals('EMP-003', $record->additional_attributes['employee_id']);
        $this->assertEquals('Finance', $record->additional_attributes['department']);
        $this->assertEquals('Analyst', $record->additional_attributes['position']);
        $this->assertEquals('SG-12', $record->additional_attributes['salary_grade']);
        $this->assertEquals('2020-01-15', $record->additional_attributes['hire_date']);
        $this->assertEquals('+63-912-345-6789', $record->additional_attributes['contact_number']);
    }

    // ========================================================================
    // Test old flat array format (backward compatibility)
    // Requirements: 5.4
    // ========================================================================

    /** @test */
    public function it_accepts_old_flat_array_format()
    {
        $data = [
            'last_name' => 'Mendoza',
            'first_name' => 'Luis',
            'birthday' => '1987-09-05',
            'gender' => 'Male',
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertNotNull($record->uid);
        $this->assertEquals('Mendoza', $record->last_name);
        $this->assertEquals('Luis', $record->first_name);
        $this->assertEquals('1987-09-05', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
    }

    /** @test */
    public function it_treats_entire_array_as_core_data_in_old_format()
    {
        $data = [
            'last_name' => 'Ramos',
            'first_name' => 'Carlos',
            'middle_name' => 'Cruz',
            'birthday' => '1993-06-18',
            'gender' => 'Male',
            'civil_status' => 'Single',
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('Ramos', $record->last_name);
        $this->assertEquals('Carlos', $record->first_name);
        $this->assertEquals('Cruz', $record->middle_name);
        $this->assertEquals('1993-06-18', $record->birthday->format('Y-m-d'));
        $this->assertEquals('Male', $record->gender);
        $this->assertEquals('Single', $record->civil_status);
        // No dynamic fields in old format
        $this->assertTrue(
            $record->additional_attributes === null || 
            $record->additional_attributes === [] ||
            empty($record->additional_attributes)
        );
    }

    /** @test */
    public function it_handles_old_format_with_minimal_fields()
    {
        $data = [
            'last_name' => 'Cruz',
            'first_name' => 'Elena',
            'gender' => 'Female',
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertInstanceOf(MainSystem::class, $record);
        $this->assertEquals('Cruz', $record->last_name);
        $this->assertEquals('Elena', $record->first_name);
        $this->assertEquals('Female', $record->gender);
    }

    // ========================================================================
    // Test normalized fields are generated correctly
    // Requirements: 3.4, 5.4
    // ========================================================================

    /** @test */
    public function it_generates_normalized_fields_for_new_format()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'DELA CRUZ',
                'first_name' => 'JUAN',
                'middle_name' => 'SANTOS',
                'birthday' => '1990-05-15',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-004',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('dela cruz', $record->last_name_normalized);
        $this->assertEquals('juan', $record->first_name_normalized);
        $this->assertEquals('santos', $record->middle_name_normalized);
    }

    /** @test */
    public function it_generates_normalized_fields_for_old_format()
    {
        $data = [
            'last_name' => 'GARCIA',
            'first_name' => 'MARIA',
            'middle_name' => 'LOPEZ',
            'birthday' => '1985-12-20',
            'gender' => 'Female',
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('garcia', $record->last_name_normalized);
        $this->assertEquals('maria', $record->first_name_normalized);
        $this->assertEquals('lopez', $record->middle_name_normalized);
    }

    /** @test */
    public function it_generates_unique_uid_for_each_record()
    {
        $data1 = [
            'core_fields' => [
                'last_name' => 'Test1',
                'first_name' => 'User1',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [],
        ];

        $data2 = [
            'core_fields' => [
                'last_name' => 'Test2',
                'first_name' => 'User2',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [],
        ];

        $record1 = $this->service->insertNewRecord($data1);
        $record2 = $this->service->insertNewRecord($data2);

        $this->assertNotNull($record1->uid);
        $this->assertNotNull($record2->uid);
        $this->assertNotEquals($record1->uid, $record2->uid);
        $this->assertStringStartsWith('UID-', $record1->uid);
        $this->assertStringStartsWith('UID-', $record2->uid);
    }

    // ========================================================================
    // Test record persistence and retrieval
    // Requirements: 3.4, 5.4
    // ========================================================================

    /** @test */
    public function it_persists_record_to_database()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Persistent',
                'first_name' => 'Test',
                'birthday' => '2000-01-01',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'test_field' => 'test_value',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        // Verify record exists in database
        $this->assertDatabaseHas('main_system', [
            'uid' => $record->uid,
            'last_name' => 'Persistent',
            'first_name' => 'Test',
        ]);

        // Retrieve and verify dynamic attributes
        $retrieved = MainSystem::where('uid', $record->uid)->first();
        $this->assertNotNull($retrieved);
        $this->assertEquals('test_value', $retrieved->additional_attributes['test_field']);
    }

    /** @test */
    public function it_can_retrieve_dynamic_fields_after_save()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Retrieve',
                'first_name' => 'Test',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'custom_field_1' => 'value1',
                'custom_field_2' => 'value2',
                'custom_field_3' => 'value3',
            ],
        ];

        $record = $this->service->insertNewRecord($data);
        $uid = $record->uid;

        // Fresh query from database
        $retrieved = MainSystem::where('uid', $uid)->first();

        $this->assertNotNull($retrieved->additional_attributes);
        $this->assertArrayHasKey('custom_field_1', $retrieved->additional_attributes);
        $this->assertArrayHasKey('custom_field_2', $retrieved->additional_attributes);
        $this->assertArrayHasKey('custom_field_3', $retrieved->additional_attributes);
        $this->assertEquals('value1', $retrieved->additional_attributes['custom_field_1']);
        $this->assertEquals('value2', $retrieved->additional_attributes['custom_field_2']);
        $this->assertEquals('value3', $retrieved->additional_attributes['custom_field_3']);
    }

    // ========================================================================
    // Test edge cases
    // Requirements: 3.4, 5.4
    // ========================================================================

    /** @test */
    public function it_handles_null_values_in_core_fields()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'NullTest',
                'first_name' => 'User',
                'middle_name' => null,
                'birthday' => null,
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'field' => 'value',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('NullTest', $record->last_name);
        $this->assertEquals('User', $record->first_name);
        $this->assertNull($record->middle_name);
        $this->assertNull($record->birthday);
        $this->assertEquals('value', $record->additional_attributes['field']);
    }

    /** @test */
    public function it_handles_special_characters_in_dynamic_field_values()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Special',
                'first_name' => 'Chars',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [
                'email' => 'test@example.com',
                'phone' => '+63-912-345-6789',
                'notes' => 'Special chars: !@#$%^&*()',
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals('test@example.com', $record->additional_attributes['email']);
        $this->assertEquals('+63-912-345-6789', $record->additional_attributes['phone']);
        $this->assertEquals('Special chars: !@#$%^&*()', $record->additional_attributes['notes']);
    }

    /** @test */
    public function it_handles_numeric_values_in_dynamic_fields()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Numeric',
                'first_name' => 'Test',
                'gender' => 'Female',
            ],
            'dynamic_fields' => [
                'age' => 30,
                'salary' => 50000.50,
                'years_of_service' => 5,
            ],
        ];

        $record = $this->service->insertNewRecord($data);

        $this->assertEquals(30, $record->additional_attributes['age']);
        $this->assertEquals(50000.50, $record->additional_attributes['salary']);
        $this->assertEquals(5, $record->additional_attributes['years_of_service']);
    }

    /** @test */
    public function it_adds_record_to_candidate_cache()
    {
        $data = [
            'core_fields' => [
                'last_name' => 'Cache',
                'first_name' => 'Test',
                'gender' => 'Male',
            ],
            'dynamic_fields' => [],
        ];

        $record = $this->service->insertNewRecord($data);

        // Insert another record and try to match against the first
        $matchData = [
            'core_fields' => [
                'last_name' => 'Cache',
                'first_name' => 'Test',
            ],
        ];

        $result = $this->service->findMatch($matchData);

        // Should find the previously inserted record
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($record->uid, $result['matched_id']);
    }
}
