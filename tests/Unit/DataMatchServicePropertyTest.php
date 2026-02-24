<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\DataMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for DataMatchService
 * 
 * Task 6.3: Write property tests for DataMatchService
 * 
 * These tests verify universal properties across randomized inputs:
 * - Property 6: Core field storage
 * - Property 7: Dynamic field storage
 * - Property 13: Matching uses only core fields
 * 
 * Validates: Requirements 3.3, 3.4, 5.1, 5.2, 5.3, 5.4
 */
class DataMatchServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected DataMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMatchService();
    }

    // ========================================================================
    // Property 6: Core field storage
    // ========================================================================

    /**
     * Property 6: Core field storage
     * 
     * **Validates: Requirements 3.3**
     * 
     * For any valid core field data, creating a MainSystem record should store
     * each value in its corresponding database column (not in additional_attributes).
     */
    public function test_property_6_core_field_storage()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $faker = fake();

            // Generate random core field data
            $coreFields = [
                'last_name' => $faker->lastName(),
                'first_name' => $faker->firstName(),
                'middle_name' => $faker->optional()->firstName(),
                'birthday' => $faker->optional()->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement(['Male', 'Female']),
                'civil_status' => $faker->optional()->randomElement(['Single', 'Married', 'Widowed']),
                'street' => $faker->optional()->streetAddress(),
                'city' => $faker->optional()->city(),
                'province' => $faker->optional()->state(),
                'barangay' => $faker->optional()->word(),
            ];

            $data = [
                'core_fields' => $coreFields,
                'dynamic_fields' => [],
            ];

            $record = $this->service->insertNewRecord($data);

            // Verify each core field is stored in its respective column
            $this->assertEquals($coreFields['last_name'], $record->last_name);
            $this->assertEquals($coreFields['first_name'], $record->first_name);
            $this->assertEquals($coreFields['middle_name'], $record->middle_name);
            $this->assertEquals($coreFields['gender'], $record->gender);
            $this->assertEquals($coreFields['civil_status'], $record->civil_status);
            $this->assertEquals($coreFields['street'], $record->street);
            $this->assertEquals($coreFields['city'], $record->city);
            $this->assertEquals($coreFields['province'], $record->province);
            $this->assertEquals($coreFields['barangay'], $record->barangay);

            if ($coreFields['birthday']) {
                $this->assertEquals($coreFields['birthday'], $record->birthday->format('Y-m-d'));
            }

            // Verify core fields are NOT in additional_attributes
            if ($record->additional_attributes) {
                $this->assertArrayNotHasKey('last_name', $record->additional_attributes);
                $this->assertArrayNotHasKey('first_name', $record->additional_attributes);
                $this->assertArrayNotHasKey('middle_name', $record->additional_attributes);
                $this->assertArrayNotHasKey('birthday', $record->additional_attributes);
                $this->assertArrayNotHasKey('gender', $record->additional_attributes);
            }

            // Clean up for next iteration
            $record->delete();
        }
    }

    // ========================================================================
    // Property 7: Dynamic field storage
    // ========================================================================

    /**
     * Property 7: Dynamic field storage
     * 
     * **Validates: Requirements 3.4, 5.4**
     * 
     * For any dynamic field data, creating a MainSystem record should store
     * all key-value pairs in the additional_attributes JSON column.
     */
    public function test_property_7_dynamic_field_storage()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $faker = fake();

            // Generate random core fields (required)
            $coreFields = [
                'last_name' => $faker->lastName(),
                'first_name' => $faker->firstName(),
                'gender' => $faker->randomElement(['Male', 'Female']),
            ];

            // Generate random dynamic fields (1-5 fields)
            $dynamicFields = [];
            $fieldCount = $faker->numberBetween(1, 5);
            
            for ($j = 0; $j < $fieldCount; $j++) {
                $key = $faker->unique()->word() . '_' . $j;
                $value = $faker->randomElement([
                    $faker->word(),
                    $faker->sentence(),
                    $faker->numberBetween(1, 1000),
                    $faker->randomFloat(2, 0, 10000),
                ]);
                $dynamicFields[$key] = $value;
            }

            $data = [
                'core_fields' => $coreFields,
                'dynamic_fields' => $dynamicFields,
            ];

            $record = $this->service->insertNewRecord($data);

            // Verify all dynamic fields are stored in additional_attributes
            $this->assertNotNull($record->additional_attributes);
            $this->assertIsArray($record->additional_attributes);
            $this->assertCount(count($dynamicFields), $record->additional_attributes);

            foreach ($dynamicFields as $key => $value) {
                $this->assertArrayHasKey($key, $record->additional_attributes);
                $this->assertEquals($value, $record->additional_attributes[$key]);
            }

            // Verify dynamic fields are NOT in core columns
            // (They shouldn't create new columns or interfere with core fields)
            $this->assertEquals($coreFields['last_name'], $record->last_name);
            $this->assertEquals($coreFields['first_name'], $record->first_name);
            $this->assertEquals($coreFields['gender'], $record->gender);

            // Clean up for next iteration
            $record->delete();
            $faker->unique(true); // Reset unique generator
        }
    }

    // ========================================================================
    // Property 13: Matching uses only core fields
    // ========================================================================

    /**
     * Property 13: Matching uses only core fields
     * 
     * **Validates: Requirements 5.1, 5.2, 5.3**
     * 
     * For any matching operation (findMatch, batchFindMatches), the candidate
     * queries and rule evaluations should reference only core field columns
     * (last_name_normalized, first_name_normalized, birthday), never
     * additional_attributes.
     * 
     * This property verifies that records with identical core fields but
     * different dynamic attributes are still matched correctly.
     */
    public function test_property_13_matching_uses_only_core_fields()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $faker = fake();

            // Generate random core fields
            $coreFields = [
                'last_name' => $faker->lastName(),
                'first_name' => $faker->firstName(),
                'middle_name' => $faker->optional()->firstName(),
                'birthday' => $faker->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement(['Male', 'Female']),
            ];

            // Generate random dynamic fields for existing record
            $existingDynamicFields = [];
            $fieldCount = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $fieldCount; $j++) {
                $existingDynamicFields['field_' . $j] = $faker->word();
            }

            // Create existing record with core fields + dynamic fields
            $existingRecord = $this->service->insertNewRecord([
                'core_fields' => $coreFields,
                'dynamic_fields' => $existingDynamicFields,
            ]);

            // Generate DIFFERENT dynamic fields for uploaded record
            $uploadedDynamicFields = [];
            $fieldCount = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $fieldCount; $j++) {
                $uploadedDynamicFields['different_field_' . $j] = $faker->sentence();
            }

            // Try to match with same core fields but different dynamic fields
            $uploadedData = [
                'core_fields' => $coreFields,
                'dynamic_fields' => $uploadedDynamicFields,
            ];

            $result = $this->service->findMatch($uploadedData);

            // Should match despite different dynamic attributes
            $this->assertNotEquals('NEW RECORD', $result['status'], 
                "Failed on iteration $i: Should match based on core fields only");
            $this->assertEquals($existingRecord->uid, $result['matched_id'],
                "Failed on iteration $i: Should match the existing record");
            $this->assertGreaterThan(0, $result['confidence'],
                "Failed on iteration $i: Confidence should be greater than 0");

            // Verify the existing record's dynamic fields were not affected
            $existingRecord->refresh();
            foreach ($existingDynamicFields as $key => $value) {
                $this->assertArrayHasKey($key, $existingRecord->additional_attributes);
                $this->assertEquals($value, $existingRecord->additional_attributes[$key]);
            }

            // Verify uploaded dynamic fields were NOT stored (since it matched)
            foreach ($uploadedDynamicFields as $key => $value) {
                $this->assertArrayNotHasKey($key, $existingRecord->additional_attributes);
            }

            // Clean up for next iteration
            $existingRecord->delete();
        }
    }

    /**
     * Property 13 (variant): Matching ignores dynamic field differences
     * 
     * **Validates: Requirements 5.1, 5.2, 5.3**
     * 
     * This variant tests that even when dynamic fields have conflicting values
     * for the same keys, matching still works based on core fields only.
     */
    public function test_property_13_variant_matching_ignores_dynamic_field_conflicts()
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $faker = fake();

            // Generate random core fields
            $coreFields = [
                'last_name' => $faker->lastName(),
                'first_name' => $faker->firstName(),
                'birthday' => $faker->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement(['Male', 'Female']),
            ];

            // Generate dynamic fields with specific values
            $dynamicFieldKey = 'department';
            $existingValue = $faker->randomElement(['IT', 'HR', 'Finance', 'Marketing']);
            $conflictingValue = $faker->randomElement(['Sales', 'Operations', 'Legal', 'Admin']);

            // Ensure values are different
            while ($conflictingValue === $existingValue) {
                $conflictingValue = $faker->randomElement(['Sales', 'Operations', 'Legal', 'Admin']);
            }

            // Create existing record
            $existingRecord = $this->service->insertNewRecord([
                'core_fields' => $coreFields,
                'dynamic_fields' => [
                    $dynamicFieldKey => $existingValue,
                    'employee_id' => 'EMP-' . $faker->numberBetween(1000, 9999),
                ],
            ]);

            // Try to match with same core fields but conflicting dynamic field value
            $uploadedData = [
                'core_fields' => $coreFields,
                'dynamic_fields' => [
                    $dynamicFieldKey => $conflictingValue, // Different value!
                    'position' => $faker->jobTitle(),
                ],
            ];

            $result = $this->service->findMatch($uploadedData);

            // Should still match despite conflicting dynamic field values
            $this->assertNotEquals('NEW RECORD', $result['status'],
                "Failed on iteration $i: Should match despite conflicting dynamic field '$dynamicFieldKey'");
            $this->assertEquals($existingRecord->uid, $result['matched_id'],
                "Failed on iteration $i: Should match the existing record");

            // Verify existing record's dynamic fields remain unchanged
            $existingRecord->refresh();
            $this->assertEquals($existingValue, $existingRecord->additional_attributes[$dynamicFieldKey],
                "Failed on iteration $i: Existing dynamic field should not be modified");

            // Clean up for next iteration
            $existingRecord->delete();
        }
    }

    /**
     * Property 13 (edge case): Empty dynamic fields don't affect matching
     * 
     * **Validates: Requirements 5.1, 5.2, 5.3**
     * 
     * Tests that records with no dynamic fields can still match records
     * that have dynamic fields, and vice versa.
     */
    public function test_property_13_edge_case_empty_dynamic_fields_dont_affect_matching()
    {
        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $faker = fake();

            // Generate random core fields
            $coreFields = [
                'last_name' => $faker->lastName(),
                'first_name' => $faker->firstName(),
                'birthday' => $faker->date('Y-m-d', '2000-01-01'),
                'gender' => $faker->randomElement(['Male', 'Female']),
            ];

            // Test Case 1: Existing record WITH dynamic fields, upload WITHOUT
            $service1 = new DataMatchService();
            $existingWithDynamic = $service1->insertNewRecord([
                'core_fields' => $coreFields,
                'dynamic_fields' => [
                    'field1' => $faker->word(),
                    'field2' => $faker->word(),
                ],
            ]);

            $uploadWithoutDynamic = [
                'core_fields' => $coreFields,
                'dynamic_fields' => [],
            ];

            $result1 = $service1->findMatch($uploadWithoutDynamic);
            $this->assertNotEquals('NEW RECORD', $result1['status']);
            $this->assertEquals($existingWithDynamic->uid, $result1['matched_id']);

            $existingWithDynamic->delete();

            // Test Case 2: Existing record WITHOUT dynamic fields, upload WITH
            // Use a fresh service instance to avoid cache issues
            $service2 = new DataMatchService();
            $existingWithoutDynamic = $service2->insertNewRecord([
                'core_fields' => $coreFields,
                'dynamic_fields' => [],
            ]);

            $uploadWithDynamic = [
                'core_fields' => $coreFields,
                'dynamic_fields' => [
                    'new_field1' => $faker->word(),
                    'new_field2' => $faker->word(),
                ],
            ];

            $result2 = $service2->findMatch($uploadWithDynamic);
            $this->assertNotEquals('NEW RECORD', $result2['status']);
            $this->assertEquals($existingWithoutDynamic->uid, $result2['matched_id']);

            $existingWithoutDynamic->delete();
        }
    }
}
