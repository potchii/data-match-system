<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\MainSystemValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemValidationErrorMessagesPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MainSystemValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * For any invalid field in the CRUD modal, when validation fails, an error message
     * SHALL be displayed next to that field, and only invalid fields SHALL display errors.
     *
     * Validates: Requirements 1.4, 2.4, 8.7, 12.4, 21.2
     *
     * @test
     */
    public function validation_error_for_missing_uid()
    {
        $result = $this->validationService->validateForCreate([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
        $this->assertNotEmpty($result['errors']['uid']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_missing_first_name()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('first_name', $result['errors']);
        $this->assertNotEmpty($result['errors']['first_name']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_missing_last_name()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => 'John',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('last_name', $result['errors']);
        $this->assertNotEmpty($result['errors']['last_name']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_invalid_birthday_format()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => 'invalid-date',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
        $this->assertNotEmpty($result['errors']['birthday']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_invalid_gender()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'InvalidGender',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('gender', $result['errors']);
        $this->assertNotEmpty($result['errors']['gender']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_invalid_status()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'invalid-status',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('status', $result['errors']);
        $this->assertNotEmpty($result['errors']['status']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * @test
     */
    public function validation_error_for_duplicate_uid()
    {
        MainSystem::factory()->create(['uid' => 'existing-uid']);

        $result = $this->validationService->validateForCreate([
            'uid' => 'existing-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
        $this->assertNotEmpty($result['errors']['uid']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Only invalid fields should have errors, valid fields should not.
     *
     * @test
     */
    public function validation_only_shows_errors_for_invalid_fields()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe', // Valid
            'gender' => 'InvalidGender', // Invalid
            'status' => 'active', // Valid
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('first_name', $result['errors']);
        $this->assertArrayHasKey('gender', $result['errors']);
        $this->assertArrayNotHasKey('last_name', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Multiple errors on the same field should be included.
     *
     * @test
     */
    public function validation_includes_multiple_errors_per_field()
    {
        MainSystem::factory()->create(['uid' => 'duplicate-uid']);

        $result = $this->validationService->validateForCreate([
            'uid' => 'duplicate-uid', // Duplicate
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
        $this->assertIsArray($result['errors']['uid']);
        $this->assertNotEmpty($result['errors']['uid']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Valid data should have no errors.
     *
     * @test
     */
    public function validation_has_no_errors_for_valid_data()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => 'valid-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
            'birthday' => '1990-01-15',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Update validation should also show field-specific errors.
     *
     * @test
     */
    public function validation_update_shows_field_specific_errors()
    {
        $record = MainSystem::factory()->create();

        $result = $this->validationService->validateForUpdate([
            'gender' => 'InvalidGender', // Invalid
            'status' => 'invalid-status', // Invalid
        ], $record->id);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('gender', $result['errors']);
        $this->assertArrayHasKey('status', $result['errors']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Oversized fields should show specific errors.
     *
     * @test
     */
    public function validation_error_for_oversized_fields()
    {
        $result = $this->validationService->validateForCreate([
            'uid' => str_repeat('a', 256), // Exceeds max 255
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
        $this->assertNotEmpty($result['errors']['uid']);
    }

    /**
     * Property 3: Validation Errors Display Field-Specific Messages
     *
     * Future birthday should show specific error.
     *
     * @test
     */
    public function validation_error_for_future_birthday()
    {
        $futureDate = now()->addDays(1)->format('Y-m-d');

        $result = $this->validationService->validateForCreate([
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => $futureDate,
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
        $this->assertNotEmpty($result['errors']['birthday']);
    }
}
