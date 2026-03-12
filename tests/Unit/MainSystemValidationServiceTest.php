<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\MainSystemValidationService;
use Tests\TestCase;

class MainSystemValidationServiceTest extends TestCase
{
    private MainSystemValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
    }

    // Creation validation tests

    public function test_validate_for_create_with_valid_data()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_for_create_requires_uid()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    public function test_validate_for_create_requires_first_name()
    {
        $data = [
            'uid' => 'UID-001',
            'last_name' => 'Doe',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('first_name', $result['errors']);
    }

    public function test_validate_for_create_requires_last_name()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('last_name', $result['errors']);
    }

    public function test_validate_for_create_rejects_duplicate_uid()
    {
        MainSystem::factory()->create(['uid' => 'UID-001']);

        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    public function test_validate_for_create_accepts_valid_gender()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_for_create_rejects_invalid_gender()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Invalid',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('gender', $result['errors']);
    }

    public function test_validate_for_create_accepts_valid_status()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_for_create_rejects_invalid_status()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'invalid',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('status', $result['errors']);
    }

    public function test_validate_for_create_accepts_valid_birthday()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '1990-01-15',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_for_create_rejects_invalid_birthday_format()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '01/15/1990',
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
    }

    public function test_validate_for_create_rejects_future_birthday()
    {
        $futureDate = now()->addDay()->format('Y-m-d');
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => $futureDate,
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('birthday', $result['errors']);
    }

    public function test_validate_for_create_accepts_nullable_fields()
    {
        $data = [
            'uid' => 'UID-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => null,
            'birthday' => null,
            'gender' => null,
        ];

        $result = $this->validationService->validateForCreate($data);

        $this->assertTrue($result['valid']);
    }

    // Update validation tests

    public function test_validate_for_update_with_valid_data()
    {
        $record = MainSystem::factory()->create();

        $data = [
            'first_name' => 'Jane',
        ];

        $result = $this->validationService->validateForUpdate($data, $record->id);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_for_update_allows_same_uid()
    {
        $record = MainSystem::factory()->create(['uid' => 'UID-001']);

        $data = [
            'uid' => 'UID-001',
            'first_name' => 'Jane',
        ];

        $result = $this->validationService->validateForUpdate($data, $record->id);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_for_update_rejects_duplicate_uid()
    {
        MainSystem::factory()->create(['uid' => 'UID-001']);
        $record = MainSystem::factory()->create(['uid' => 'UID-002']);

        $data = [
            'uid' => 'UID-001',
        ];

        $result = $this->validationService->validateForUpdate($data, $record->id);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('uid', $result['errors']);
    }

    // Bulk status update validation tests

    public function test_validate_bulk_status_update_with_valid_data()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
            'status' => 'active',
        ];

        $result = $this->validationService->validateBulkStatusUpdate($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_bulk_status_update_requires_record_ids()
    {
        $data = [
            'status' => 'active',
        ];

        $result = $this->validationService->validateBulkStatusUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds', $result['errors']);
    }

    public function test_validate_bulk_status_update_requires_status()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
        ];

        $result = $this->validationService->validateBulkStatusUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('status', $result['errors']);
    }

    public function test_validate_bulk_status_update_rejects_invalid_status()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
            'status' => 'invalid',
        ];

        $result = $this->validationService->validateBulkStatusUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('status', $result['errors']);
    }

    public function test_validate_bulk_status_update_rejects_nonexistent_record()
    {
        $data = [
            'recordIds' => [99999],
            'status' => 'active',
        ];

        $result = $this->validationService->validateBulkStatusUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds.0', $result['errors']);
    }

    // Bulk category update validation tests

    public function test_validate_bulk_category_update_with_valid_data()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
            'category' => 'Category A',
        ];

        $result = $this->validationService->validateBulkCategoryUpdate($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_bulk_category_update_requires_record_ids()
    {
        $data = [
            'category' => 'Category A',
        ];

        $result = $this->validationService->validateBulkCategoryUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds', $result['errors']);
    }

    public function test_validate_bulk_category_update_requires_category()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
        ];

        $result = $this->validationService->validateBulkCategoryUpdate($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('category', $result['errors']);
    }

    // Bulk delete validation tests

    public function test_validate_bulk_delete_with_valid_data()
    {
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();

        $data = [
            'recordIds' => $recordIds,
        ];

        $result = $this->validationService->validateBulkDelete($data);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_bulk_delete_requires_record_ids()
    {
        $data = [];

        $result = $this->validationService->validateBulkDelete($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds', $result['errors']);
    }

    public function test_validate_bulk_delete_requires_at_least_one_record()
    {
        $data = [
            'recordIds' => [],
        ];

        $result = $this->validationService->validateBulkDelete($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds', $result['errors']);
    }

    public function test_validate_bulk_delete_rejects_nonexistent_record()
    {
        $data = [
            'recordIds' => [99999],
        ];

        $result = $this->validationService->validateBulkDelete($data);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('recordIds.0', $result['errors']);
    }
}
