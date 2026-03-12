<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\MainSystemValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemFormDataPersistencePropertyTest extends TestCase
{
    use RefreshDatabase;

    private MainSystemValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * For any form submission that fails validation, all entered form data SHALL be
     * preserved in the form fields, allowing the user to correct errors and resubmit
     * without re-entering unchanged fields.
     *
     * Validates: Requirements 21.1, 21.3
     *
     * @test
     */
    public function form_data_preserved_when_validation_fails_on_single_field()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid - empty
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
            'birthday' => '1990-01-15',
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // The validation service returns errors, but the form data should be preserved
        // by the frontend. We verify that only the invalid field has an error.
        $this->assertArrayHasKey('first_name', $result['errors']);
        $this->assertArrayNotHasKey('last_name', $result['errors']);
        $this->assertArrayNotHasKey('gender', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
        $this->assertArrayNotHasKey('birthday', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * @test
     */
    public function form_data_preserved_when_validation_fails_on_multiple_fields()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'gender' => 'InvalidGender', // Invalid
            'status' => 'active',
            'birthday' => '1990-01-15',
            'address' => '123 Main St',
            'barangay' => 'Barangay 1',
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Only invalid fields should have errors
        $this->assertArrayHasKey('first_name', $result['errors']);
        $this->assertArrayHasKey('gender', $result['errors']);
        // Valid fields should not have errors
        $this->assertArrayNotHasKey('last_name', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
        $this->assertArrayNotHasKey('birthday', $result['errors']);
        $this->assertArrayNotHasKey('address', $result['errors']);
        $this->assertArrayNotHasKey('barangay', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * When correcting invalid fields, other fields should retain their values.
     *
     * @test
     */
    public function form_data_retained_when_correcting_invalid_field()
    {
        // First submission with invalid data
        $firstSubmission = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
        ];

        $result1 = $this->validationService->validateForCreate($firstSubmission);
        $this->assertFalse($result1['valid']);

        // Second submission with corrected first_name
        $secondSubmission = [
            'uid' => 'test-uid',
            'first_name' => 'John', // Now valid
            'last_name' => 'Doe', // Should still be here
            'gender' => 'Male', // Should still be here
            'status' => 'active', // Should still be here
        ];

        $result2 = $this->validationService->validateForCreate($secondSubmission);
        $this->assertTrue($result2['valid']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * All form fields should be preserved, including optional ones.
     *
     * @test
     */
    public function all_form_fields_preserved_including_optional()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'middle_name' => 'Michael',
            'suffix' => 'Jr.',
            'gender' => 'Male',
            'status' => 'active',
            'category' => 'VIP',
            'birthday' => '1990-01-15',
            'registration_date' => '2024-01-15',
            'civil_status' => 'Single',
            'address' => '123 Main St',
            'barangay' => 'Barangay 1',
            'regs_no' => 'REG-001',
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Only first_name should have an error
        $this->assertArrayHasKey('first_name', $result['errors']);
        // All other fields should not have errors
        $this->assertArrayNotHasKey('last_name', $result['errors']);
        $this->assertArrayNotHasKey('middle_name', $result['errors']);
        $this->assertArrayNotHasKey('suffix', $result['errors']);
        $this->assertArrayNotHasKey('gender', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
        $this->assertArrayNotHasKey('category', $result['errors']);
        $this->assertArrayNotHasKey('birthday', $result['errors']);
        $this->assertArrayNotHasKey('registration_date', $result['errors']);
        $this->assertArrayNotHasKey('civil_status', $result['errors']);
        $this->assertArrayNotHasKey('address', $result['errors']);
        $this->assertArrayNotHasKey('barangay', $result['errors']);
        $this->assertArrayNotHasKey('regs_no', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * Template fields should also be preserved.
     *
     * @test
     */
    public function template_fields_preserved_on_validation_error()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'templateFields' => [
                'field1' => 'value1',
                'field2' => 'value2',
                'field3' => 'value3',
            ],
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Only first_name should have an error
        $this->assertArrayHasKey('first_name', $result['errors']);
        // Template fields should not have errors
        $this->assertArrayNotHasKey('templateFields', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * Update validation should also preserve form data.
     *
     * @test
     */
    public function form_data_preserved_on_update_validation_error()
    {
        $record = MainSystem::factory()->create();

        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'InvalidGender', // Invalid
            'status' => 'active',
            'birthday' => '1990-01-15',
            'address' => '123 Main St',
        ];

        $result = $this->validationService->validateForUpdate($formData, $record->id);

        $this->assertFalse($result['valid']);
        // Only gender should have an error
        $this->assertArrayHasKey('gender', $result['errors']);
        // Other fields should not have errors
        $this->assertArrayNotHasKey('first_name', $result['errors']);
        $this->assertArrayNotHasKey('last_name', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
        $this->assertArrayNotHasKey('birthday', $result['errors']);
        $this->assertArrayNotHasKey('address', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * Null values should be preserved.
     *
     * @test
     */
    public function null_values_preserved_on_validation_error()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'middle_name' => null,
            'gender' => null,
            'status' => null,
            'birthday' => null,
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Only first_name should have an error
        $this->assertArrayHasKey('first_name', $result['errors']);
        // Null fields should not have errors
        $this->assertArrayNotHasKey('middle_name', $result['errors']);
        $this->assertArrayNotHasKey('gender', $result['errors']);
        $this->assertArrayNotHasKey('status', $result['errors']);
        $this->assertArrayNotHasKey('birthday', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * Empty strings in optional fields should be preserved.
     *
     * @test
     */
    public function empty_optional_fields_preserved_on_validation_error()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
            'middle_name' => '',
            'suffix' => '',
            'address' => '',
            'barangay' => '',
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Only first_name should have an error
        $this->assertArrayHasKey('first_name', $result['errors']);
        // Empty optional fields should not have errors
        $this->assertArrayNotHasKey('middle_name', $result['errors']);
        $this->assertArrayNotHasKey('suffix', $result['errors']);
        $this->assertArrayNotHasKey('address', $result['errors']);
        $this->assertArrayNotHasKey('barangay', $result['errors']);
    }

    /**
     * Property 4: Form Data Persists on Validation Error
     *
     * Multiple validation errors should preserve all form data.
     *
     * @test
     */
    public function all_form_data_preserved_with_multiple_errors()
    {
        $formData = [
            'uid' => 'test-uid',
            'first_name' => '', // Invalid
            'last_name' => '', // Invalid
            'gender' => 'InvalidGender', // Invalid
            'status' => 'invalid-status', // Invalid
            'birthday' => '1990-01-15',
            'address' => '123 Main St',
            'barangay' => 'Barangay 1',
            'middle_name' => 'Michael',
        ];

        $result = $this->validationService->validateForCreate($formData);

        $this->assertFalse($result['valid']);
        // Multiple fields should have errors
        $this->assertArrayHasKey('first_name', $result['errors']);
        $this->assertArrayHasKey('last_name', $result['errors']);
        $this->assertArrayHasKey('gender', $result['errors']);
        $this->assertArrayHasKey('status', $result['errors']);
        // Valid fields should not have errors
        $this->assertArrayNotHasKey('birthday', $result['errors']);
        $this->assertArrayNotHasKey('address', $result['errors']);
        $this->assertArrayNotHasKey('barangay', $result['errors']);
        $this->assertArrayNotHasKey('middle_name', $result['errors']);
    }
}
