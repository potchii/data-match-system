<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\MainSystemValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemValidationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MainSystemValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * For any Main System record data with invalid fields (empty required fields,
     * invalid date format, invalid enum values, duplicate UID), the validation engine
     * SHALL reject the data and prevent persistence to the database.
     *
     * Validates: Requirements 1.3, 8.1-8.8
     *
     * @test
     */
    public function validation_rejects_empty_required_fields()
    {
        // Test empty first_name
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-001',
            'first_name' => '',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('first_name', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_empty_last_name()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-002',
            'first_name' => 'John',
            'last_name' => '',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('last_name', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_missing_uid()
    {
        $result = $this->validationService->validateForCreate([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_invalid_date_format()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-003',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '01/01/1990', // Invalid format
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_future_birthday()
    {
        $futureDate = now()->addDays(1)->format('Y-m-d');

        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-004',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => $futureDate,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_invalid_gender()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-005',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Unknown',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('gender', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_invalid_status()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-006',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'pending',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('status', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_duplicate_uid()
    {
        // Create a record with a UID
        MainSystem::factory()->create(['uid' => 'duplicate-uid']);

        // Try to create another with the same UID
        $result = $this->validationService->validateForCreate([
            'uid' => 'duplicate-uid',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_accepts_valid_gender_values()
    {
        foreach (['Male', 'Female', 'Other'] as $gender) {
            $result = $this->validationService->validateForCreate([
                'uid' => "test-uid-gender-{$gender}",
                'first_name' => 'John',
                'last_name' => 'Doe',
                'gender' => $gender,
            ]);

            $this->assertTrue($result['valid'], "Gender '{$gender}' should be valid");
        }
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_accepts_valid_status_values()
    {
        foreach (['active', 'inactive', 'archived'] as $status) {
            $result = $this->validationService->validateForCreate([
                'uid' => "test-uid-status-{$status}",
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => $status,
            ]);

            $this->assertTrue($result['valid'], "Status '{$status}' should be valid");
        }
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_accepts_valid_date_format()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-date',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '1990-01-15',
            'registration_date' => '2024-01-15',
        ]);

        $this->assertTrue($result['valid']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_accepts_nullable_fields()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid-nullable',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => null,
            'birthday' => null,
            'gender' => null,
            'status' => null,
            'category' => null,
        ]);

        $this->assertTrue($result['valid']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_rejects_oversized_fields()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => str_repeat('a', 256), // Exceeds max 255
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_for_update_allows_same_uid()
    {
        $record = MainSystem::factory()->create(['uid' => 'original-uid']);

        $result = $this->validationService->validateForUpdate([
            'uid' => 'original-uid',
            'first_name' => 'Updated',
        ], $record->id);

        $this->assertTrue($result['valid']);
    }

    /**
     * Property 2: Validation Rejects Invalid Data
     *
     * @test
     */
    public function validation_for_update_rejects_duplicate_uid()
    {
        $record1 = MainSystem::factory()->create(['uid' => 'uid-1']);
        $record2 = MainSystem::factory()->create(['uid' => 'uid-2']);

        $result = $this->validationService->validateForUpdate([
            'uid' => 'uid-1', // Try to change record2's UID to record1's UID
        ], $record2->id);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }
}
