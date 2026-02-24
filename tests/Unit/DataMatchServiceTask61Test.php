<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\DataMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Tests for DataMatchService Task 6.1
 * 
 * Requirements: 5.1, 9.3, 9.4
 * 
 * These tests verify backward compatibility of the findMatch method:
 * - Accepts new structured format with 'core_fields' key
 * - Accepts old flat array format (backward compatibility)
 * - Correctly extracts core fields for matching operations
 */
class DataMatchServiceTask61Test extends TestCase
{
    use RefreshDatabase;

    protected DataMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMatchService();
    }

    // ========================================================================
    // Test new structured format with 'core_fields' key
    // Requirements: 5.1, 9.3
    // ========================================================================

    /** @test */
    public function it_accepts_new_structured_format_with_core_fields_key()
    {
        // Create an existing record to match against
        MainSystem::factory()->create([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name_normalized' => 'dela cruz',
            'first_name_normalized' => 'juan',
            'middle_name_normalized' => 'santos',
            'birthday' => '1990-05-15',
            'gender' => 'Male',
        ]);

        // Use new structured format
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'birthday' => '1990-05-15',
            ],
            'dynamic_fields' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('matched_id', $result);
        $this->assertNotEquals('NEW RECORD', $result['status']);
    }

    /** @test */
    public function it_extracts_core_fields_from_structured_format()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Garcia',
            'first_name' => 'Maria',
            'last_name_normalized' => 'garcia',
            'first_name_normalized' => 'maria',
            'birthday' => '1985-12-20',
            'gender' => 'Female',
        ]);

        // Use new structured format
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Garcia',
                'first_name' => 'Maria',
                'birthday' => '1985-12-20',
            ],
            'dynamic_fields' => [
                'position' => 'Manager',
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should find a match
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_ignores_dynamic_fields_during_matching()
    {
        // Create existing record with different dynamic attributes
        $existing = MainSystem::factory()->create([
            'last_name' => 'Lopez',
            'first_name' => 'Pedro',
            'last_name_normalized' => 'lopez',
            'first_name_normalized' => 'pedro',
            'birthday' => '1992-03-10',
            'gender' => 'Male',
            'additional_attributes' => [
                'department' => 'IT',
            ],
        ]);

        // Upload with same core fields but different dynamic fields
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Lopez',
                'first_name' => 'Pedro',
                'birthday' => '1992-03-10',
            ],
            'dynamic_fields' => [
                'department' => 'HR', // Different!
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should still match despite different dynamic fields
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_returns_new_record_when_no_match_found_with_structured_format()
    {
        // No existing records

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Unique',
                'first_name' => 'Person',
                'birthday' => '2000-01-01',
            ],
            'dynamic_fields' => [
                'custom_field' => 'value',
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        $this->assertEquals('NEW RECORD', $result['status']);
        $this->assertEquals(0.0, $result['confidence']);
        $this->assertNull($result['matched_id']);
    }

    // ========================================================================
    // Test old flat array format (backward compatibility)
    // Requirements: 9.3, 9.4
    // ========================================================================

    /** @test */
    public function it_accepts_old_flat_array_format()
    {
        // Create an existing record
        MainSystem::factory()->create([
            'last_name' => 'Reyes',
            'first_name' => 'Ana',
            'last_name_normalized' => 'reyes',
            'first_name_normalized' => 'ana',
            'birthday' => '1988-07-25',
            'gender' => 'Female',
        ]);

        // Use old flat array format (no 'core_fields' key)
        $uploadedData = [
            'last_name' => 'Reyes',
            'first_name' => 'Ana',
            'birthday' => '1988-07-25',
        ];

        $result = $this->service->findMatch($uploadedData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('matched_id', $result);
        $this->assertNotEquals('NEW RECORD', $result['status']);
    }

    /** @test */
    public function it_treats_entire_array_as_core_data_in_old_format()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Santos',
            'first_name' => 'Jose',
            'middle_name' => 'Cruz',
            'last_name_normalized' => 'santos',
            'first_name_normalized' => 'jose',
            'middle_name_normalized' => 'cruz',
            'birthday' => '1995-11-30',
            'gender' => 'Male',
        ]);

        // Use old flat array format
        $uploadedData = [
            'last_name' => 'Santos',
            'first_name' => 'Jose',
            'middle_name' => 'Cruz',
            'birthday' => '1995-11-30',
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should find a match
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    /** @test */
    public function it_returns_new_record_when_no_match_found_with_old_format()
    {
        // No existing records

        $uploadedData = [
            'last_name' => 'NewPerson',
            'first_name' => 'Test',
            'birthday' => '1999-12-31',
        ];

        $result = $this->service->findMatch($uploadedData);

        $this->assertEquals('NEW RECORD', $result['status']);
        $this->assertEquals(0.0, $result['confidence']);
        $this->assertNull($result['matched_id']);
    }

    /** @test */
    public function it_handles_old_format_with_extra_fields()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Mendoza',
            'first_name' => 'Luis',
            'last_name_normalized' => 'mendoza',
            'first_name_normalized' => 'luis',
            'birthday' => '1987-09-05',
            'gender' => 'Male',
        ]);

        // Old format with extra fields (should be ignored for matching)
        $uploadedData = [
            'last_name' => 'Mendoza',
            'first_name' => 'Luis',
            'birthday' => '1987-09-05',
            'employee_id' => 'EMP-999', // Extra field
            'department' => 'Finance',  // Extra field
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should still match based on core fields
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }

    // ========================================================================
    // Test both formats work identically
    // Requirements: 9.3, 9.4
    // ========================================================================

    /** @test */
    public function it_produces_same_result_for_both_formats()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Ramos',
            'first_name' => 'Carlos',
            'last_name_normalized' => 'ramos',
            'first_name_normalized' => 'carlos',
            'birthday' => '1993-06-18',
            'gender' => 'Male',
        ]);

        // Test with old format
        $oldFormatData = [
            'last_name' => 'Ramos',
            'first_name' => 'Carlos',
            'birthday' => '1993-06-18',
        ];

        $oldResult = $this->service->findMatch($oldFormatData);

        // Test with new format
        $newFormatData = [
            'core_fields' => [
                'last_name' => 'Ramos',
                'first_name' => 'Carlos',
                'birthday' => '1993-06-18',
            ],
            'dynamic_fields' => [],
        ];

        $newResult = $this->service->findMatch($newFormatData);

        // Both should produce identical results
        $this->assertEquals($oldResult['status'], $newResult['status']);
        $this->assertEquals($oldResult['confidence'], $newResult['confidence']);
        $this->assertEquals($oldResult['matched_id'], $newResult['matched_id']);
        $this->assertEquals($existing->uid, $oldResult['matched_id']);
        $this->assertEquals($existing->uid, $newResult['matched_id']);
    }

    /** @test */
    public function it_handles_empty_core_fields_in_new_format()
    {
        $uploadedData = [
            'core_fields' => [],
            'dynamic_fields' => [
                'some_field' => 'value',
            ],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should return NEW RECORD since no core fields to match
        $this->assertEquals('NEW RECORD', $result['status']);
    }

    /** @test */
    public function it_handles_empty_array_in_old_format()
    {
        $uploadedData = [];

        $result = $this->service->findMatch($uploadedData);

        // Should return NEW RECORD since no data to match
        $this->assertEquals('NEW RECORD', $result['status']);
    }

    /** @test */
    public function it_handles_partial_name_match_with_new_format()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => '',
            'last_name_normalized' => 'dela cruz',
            'first_name_normalized' => 'juan',
            'middle_name_normalized' => '',
            'birthday' => '1990-05-15',
            'gender' => 'Male',
        ]);

        // Upload with same first and last name, same birthday
        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'birthday' => '1990-05-15',
            ],
            'dynamic_fields' => [],
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should find a match
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    /** @test */
    public function it_handles_fuzzy_match_with_old_format()
    {
        // Create an existing record
        $existing = MainSystem::factory()->create([
            'last_name' => 'Garcia',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name_normalized' => 'garcia',
            'first_name_normalized' => 'maria',
            'middle_name_normalized' => 'santos',
            'birthday' => null,
            'gender' => 'Female',
        ]);

        // Upload with similar name (old format)
        $uploadedData = [
            'last_name' => 'Garcia',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
        ];

        $result = $this->service->findMatch($uploadedData);

        // Should find a match (fuzzy or exact)
        $this->assertNotEquals('NEW RECORD', $result['status']);
        $this->assertEquals($existing->uid, $result['matched_id']);
    }
}
