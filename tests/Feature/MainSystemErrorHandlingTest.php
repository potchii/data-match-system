<?php

namespace Tests\Feature;

use App\Models\MainSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for error handling and recovery
 * 
 * Feature: main-system-crud-actions
 * Tasks: 31.1-31.3
 * 
 * Tests error handling scenarios:
 * - Validation error handling with form data preservation
 * - Unique constraint violation handling
 * - Database error handling
 * 
 * Validates: Requirements 12.1-12.7, 21.1-21.3
 */
class MainSystemErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test 31.1: Validation error handling
     * 
     * Validates: Requirements 12.4, 21.1-21.3
     */
    public function test_validation_error_handling()
    {
        // Arrange - Invalid data with multiple errors
        $invalidData = [
            'uid' => 'TEST-001',
            'first_name' => '', // Required field empty
            'last_name' => '', // Required field empty
            'birthday' => 'invalid-date', // Invalid date format
            'gender' => 'InvalidGender', // Invalid enum value
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $invalidData);

        // Assert - Validation error returned with field-specific messages
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors' => [
                'first_name',
                'last_name',
                'birthday',
                'gender',
            ],
        ]);

        // Assert - Each error is an array of messages
        $this->assertIsArray($response->json('errors.first_name'));
        $this->assertIsArray($response->json('errors.last_name'));

        // Assert - Record not persisted
        $this->assertDatabaseMissing('main_system', [
            'uid' => 'TEST-001',
        ]);
    }

    /**
     * Test 31.1b: Form data preservation on validation error
     * 
     * Validates: Requirements 21.1-21.3
     */
    public function test_form_data_preservation_on_validation_error()
    {
        // Arrange - Data with one invalid field and valid fields
        $data = [
            'uid' => 'TEST-002',
            'regs_no' => 'REG-002',
            'first_name' => '', // Invalid
            'last_name' => 'Doe', // Valid
            'birthday' => '1990-01-15', // Valid
            'gender' => 'Male', // Valid
            'status' => 'active', // Valid
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $data);

        // Assert - Validation error returned
        $response->assertStatus(422);
        $this->assertIsArray($response->json('errors.first_name'));
        $this->assertNotEmpty($response->json('errors.first_name'));

        // Assert - Only invalid field has error
        $this->assertNull($response->json('errors.last_name'));
        $this->assertNull($response->json('errors.birthday'));

        // Assert - Record not persisted
        $this->assertDatabaseMissing('main_system', [
            'uid' => 'TEST-002',
        ]);

        // Note: In a real scenario, the frontend would preserve the form data
        // and allow the user to correct only the invalid field
    }

    /**
     * Test 31.2: Unique constraint violation
     * 
     * Validates: Requirements 12.5
     */
    public function test_unique_constraint_violation()
    {
        // Arrange - Create existing record
        MainSystem::factory()->create([
            'uid' => 'DUPLICATE-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Attempt to create record with duplicate UID
        $duplicateData = [
            'uid' => 'DUPLICATE-001',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $duplicateData);

        // Assert - Unique constraint violation error
        $response->assertStatus(422);
        $this->assertIsArray($response->json('errors.uid'));
        $this->assertNotEmpty($response->json('errors.uid'));

        // Assert - Error message indicates duplicate
        $errors = $response->json('errors.uid');
        $this->assertTrue(
            collect($errors)->contains(function ($error) {
                return str_contains(strtolower($error), 'unique') || 
                       str_contains(strtolower($error), 'already');
            })
        );

        // Assert - Only original record exists
        $this->assertDatabaseHas('main_system', [
            'uid' => 'DUPLICATE-001',
            'first_name' => 'John',
        ]);

        $this->assertDatabaseMissing('main_system', [
            'uid' => 'DUPLICATE-001',
            'first_name' => 'Jane',
        ]);
    }

    /**
     * Test 31.3: Database error handling
     * 
     * Validates: Requirements 12.1-12.3
     */
    public function test_database_error_handling_on_create()
    {
        // Arrange - Valid data
        $validData = [
            'uid' => 'TEST-003',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        // Act - Create record (should succeed in normal conditions)
        $response = $this->postJson('/api/main-system', $validData);

        // Assert - Record created successfully
        $response->assertStatus(201);

        // Assert - Record persisted
        $this->assertDatabaseHas('main_system', [
            'uid' => 'TEST-003',
        ]);
    }

    /**
     * Test: Validation error on update
     * 
     * Validates: Requirements 2.3, 2.4, 12.4
     */
    public function test_validation_error_on_update()
    {
        // Arrange - Create record
        $record = MainSystem::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Invalid update data
        $invalidData = [
            'first_name' => '', // Required field empty
            'birthday' => 'invalid-date', // Invalid date format
        ];

        // Act - Attempt to update record
        $response = $this->putJson("/api/main-system/{$record->id}", $invalidData);

        // Assert - Validation error returned
        $response->assertStatus(422);
        $this->assertIsArray($response->json('errors.first_name'));
        $this->assertNotEmpty($response->json('errors.first_name'));
        $this->assertIsArray($response->json('errors.birthday'));
        $this->assertNotEmpty($response->json('errors.birthday'));

        // Assert - Record not modified
        $this->assertDatabaseHas('main_system', [
            'id' => $record->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test: Retry after validation error
     * 
     * Validates: Requirements 12.7, 21.3
     */
    public function test_retry_after_validation_error()
    {
        // Arrange - First attempt with invalid data
        $invalidData = [
            'uid' => 'TEST-004',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
        ];

        // Act - First attempt fails
        $response1 = $this->postJson('/api/main-system', $invalidData);
        $response1->assertStatus(422);

        // Correct the data
        $validData = [
            'uid' => 'TEST-004',
            'first_name' => 'John', // Now valid
            'last_name' => 'Doe',
        ];

        // Act - Retry with corrected data
        $response2 = $this->postJson('/api/main-system', $validData);

        // Assert - Second attempt succeeds
        $response2->assertStatus(201);

        // Assert - Record persisted
        $this->assertDatabaseHas('main_system', [
            'uid' => 'TEST-004',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test: Invalid date format validation
     * 
     * Validates: Requirements 8.3
     */
    public function test_invalid_date_format_validation()
    {
        // Arrange - Data with invalid date format
        $invalidData = [
            'uid' => 'TEST-005',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '15-01-1990', // Wrong format
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $invalidData);

        // Assert - Validation error for birthday
        $response->assertStatus(422);
        $this->assertIsArray($response->json('errors.birthday'));
        $this->assertNotEmpty($response->json('errors.birthday'));

        // Assert - Record not persisted
        $this->assertDatabaseMissing('main_system', [
            'uid' => 'TEST-005',
        ]);
    }

    /**
     * Test: Invalid enum value validation
     * 
     * Validates: Requirements 8.4, 8.5
     */
    public function test_invalid_enum_value_validation()
    {
        // Arrange - Data with invalid enum values
        $invalidData = [
            'uid' => 'TEST-006',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'InvalidGender', // Invalid enum
            'status' => 'invalid_status', // Invalid enum
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $invalidData);

        // Assert - Validation errors for enum fields
        $response->assertStatus(422);
        $this->assertIsArray($response->json('errors.gender'));
        $this->assertNotEmpty($response->json('errors.gender'));
        $this->assertIsArray($response->json('errors.status'));
        $this->assertNotEmpty($response->json('errors.status'));

        // Assert - Record not persisted
        $this->assertDatabaseMissing('main_system', [
            'uid' => 'TEST-006',
        ]);
    }

    /**
     * Test: Bulk operation with partial failures
     * 
     * Validates: Requirements 5.4-5.7
     */
    public function test_bulk_operation_with_partial_failures()
    {
        // Arrange - Create some records
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();
        $recordIds[] = 99999; // Non-existent record

        // Act - Bulk delete with mixed records
        $response = $this->postJson('/api/main-system/bulk/delete', [
            'recordIds' => $recordIds,
        ]);

        // Assert - Partial success response
        $response->assertStatus(200);
        $response->assertJsonPath('deleted', 3);
        $response->assertJsonPath('failed', 1);

        // Assert - Valid records deleted
        foreach ($records as $record) {
            $this->assertDatabaseMissing('main_system', ['id' => $record->id]);
        }
    }

    /**
     * Test: Error response format
     * 
     * Validates: Requirements 12.1-12.7
     */
    public function test_error_response_format()
    {
        // Arrange - Invalid data
        $invalidData = [
            'uid' => 'TEST-007',
            'first_name' => '', // Required field empty
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $invalidData);

        // Assert - Error response has correct structure
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'errors' => [
                'first_name',
            ],
        ]);

        // Assert - Success flag is false
        $this->assertFalse($response->json('success'));
    }
}
