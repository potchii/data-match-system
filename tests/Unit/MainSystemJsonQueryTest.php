<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemJsonQueryTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================================
    // Unit Tests for Task 9.2
    // Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
    // ========================================================================

    /**
     * Test querying by dynamic attributes using JSON syntax
     * 
     * Validates: Requirement 6.1
     */
    public function test_query_by_dynamic_attributes_using_json_syntax(): void
    {
        // Create records with different dynamic attributes
        $record1 = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        $record2 = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-002',
                'department' => 'HR',
            ],
        ]);

        $record3 = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-003',
                'department' => 'IT',
            ],
        ]);

        // Query by employee_id
        $result = MainSystem::where('additional_attributes->employee_id', 'EMP-001')->first();
        $this->assertNotNull($result);
        $this->assertEquals($record1->id, $result->id);

        // Query by department - should return multiple records
        $results = MainSystem::where('additional_attributes->department', 'IT')->get();
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $record1->id));
        $this->assertTrue($results->contains('id', $record3->id));

        // Query for non-existent value
        $result = MainSystem::where('additional_attributes->employee_id', 'EMP-999')->first();
        $this->assertNull($result);
    }

    /**
     * Test object property access returns correct values
     * 
     * Validates: Requirement 6.3
     */
    public function test_object_property_access_returns_correct_values(): void
    {
        $attributes = [
            'employee_id' => 'EMP-123',
            'department' => 'Engineering',
            'position' => 'Senior Developer',
            'salary_grade' => 'SG-15',
        ];

        $record = MainSystem::factory()->create([
            'additional_attributes' => $attributes,
        ]);

        // Convert to object for property access
        $attributesObject = (object) $record->additional_attributes;

        // Test each property
        $this->assertEquals('EMP-123', $attributesObject->employee_id);
        $this->assertEquals('Engineering', $attributesObject->department);
        $this->assertEquals('Senior Developer', $attributesObject->position);
        $this->assertEquals('SG-15', $attributesObject->salary_grade);
    }

    /**
     * Test missing keys return null without errors
     * 
     * Validates: Requirement 6.4
     */
    public function test_missing_keys_return_null_without_errors(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        // Test array access with missing key
        $value = $record->additional_attributes['non_existent_key'] ?? null;
        $this->assertNull($value);

        // Test object property access with missing key
        $attributesObject = (object) $record->additional_attributes;
        $value = $attributesObject->non_existent_key ?? null;
        $this->assertNull($value);

        // Test getDynamicAttribute with missing key
        $value = $record->getDynamicAttribute('non_existent_key');
        $this->assertNull($value);

        // Test getDynamicAttribute with default value
        $value = $record->getDynamicAttribute('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    /**
     * Test isset() and array_key_exists() work correctly
     * 
     * Validates: Requirement 6.5
     */
    public function test_isset_and_array_key_exists_work_correctly(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'nullable_field' => null,
            ],
        ]);

        // Test isset() with existing key
        $this->assertTrue(isset($record->additional_attributes['employee_id']));
        $this->assertTrue(isset($record->additional_attributes['department']));

        // Test isset() with null value (should return false)
        $this->assertFalse(isset($record->additional_attributes['nullable_field']));

        // Test isset() with non-existent key
        $this->assertFalse(isset($record->additional_attributes['non_existent_key']));

        // Test array_key_exists() with existing key
        $this->assertTrue(array_key_exists('employee_id', $record->additional_attributes));
        $this->assertTrue(array_key_exists('department', $record->additional_attributes));

        // Test array_key_exists() with null value (should return true)
        $this->assertTrue(array_key_exists('nullable_field', $record->additional_attributes));

        // Test array_key_exists() with non-existent key
        $this->assertFalse(array_key_exists('non_existent_key', $record->additional_attributes));

        // Test hasDynamicAttribute() method
        $this->assertTrue($record->hasDynamicAttribute('employee_id'));
        $this->assertTrue($record->hasDynamicAttribute('department'));
        // Note: hasDynamicAttribute uses isset() which returns false for null values
        $this->assertFalse($record->hasDynamicAttribute('nullable_field'));
        $this->assertFalse($record->hasDynamicAttribute('non_existent_key'));
    }


    /**
     * Test querying with nested JSON paths
     * 
     * Validates: Requirement 6.1
     */
    public function test_query_with_nested_json_paths(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'contact' => [
                    'email' => 'test@example.com',
                    'phone' => '123-456-7890',
                ],
                'address' => [
                    'city' => 'Manila',
                    'zip' => '1000',
                ],
            ],
        ]);

        // Query nested values
        $result = MainSystem::where('additional_attributes->contact->email', 'test@example.com')->first();
        $this->assertNotNull($result);
        $this->assertEquals($record->id, $result->id);

        $result = MainSystem::where('additional_attributes->address->city', 'Manila')->first();
        $this->assertNotNull($result);
        $this->assertEquals($record->id, $result->id);
    }

    /**
     * Test querying with numeric values
     * 
     * Validates: Requirement 6.1
     */
    public function test_query_with_numeric_values(): void
    {
        $record1 = MainSystem::factory()->create([
            'additional_attributes' => [
                'salary_grade' => 15,
                'years_of_service' => 5,
            ],
        ]);

        $record2 = MainSystem::factory()->create([
            'additional_attributes' => [
                'salary_grade' => 20,
                'years_of_service' => 10,
            ],
        ]);

        // Query by numeric value
        $result = MainSystem::where('additional_attributes->salary_grade', 15)->first();
        $this->assertNotNull($result);
        $this->assertEquals($record1->id, $result->id);

        // Query with comparison operators
        $results = MainSystem::where('additional_attributes->years_of_service', '>=', 10)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($record2->id, $results->first()->id);
    }

    /**
     * Test querying with boolean values
     * 
     * Validates: Requirement 6.1
     */
    public function test_query_with_boolean_values(): void
    {
        $record1 = MainSystem::factory()->create([
            'additional_attributes' => [
                'is_active' => true,
                'is_verified' => false,
            ],
        ]);

        $record2 = MainSystem::factory()->create([
            'additional_attributes' => [
                'is_active' => false,
                'is_verified' => true,
            ],
        ]);

        // Query by boolean value
        $results = MainSystem::where('additional_attributes->is_active', true)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($record1->id, $results->first()->id);

        $results = MainSystem::where('additional_attributes->is_verified', true)->get();
        $this->assertCount(1, $results);
        $this->assertEquals($record2->id, $results->first()->id);
    }

    /**
     * Test array access syntax with various data types
     * 
     * Validates: Requirement 6.2
     */
    public function test_array_access_syntax_with_various_data_types(): void
    {
        $attributes = [
            'string_value' => 'test string',
            'integer_value' => 42,
            'float_value' => 3.14,
            'boolean_value' => true,
            'null_value' => null,
            'array_value' => ['item1', 'item2'],
        ];

        $record = MainSystem::factory()->create([
            'additional_attributes' => $attributes,
        ]);

        // Test array access for each type
        $this->assertEquals('test string', $record->additional_attributes['string_value']);
        $this->assertEquals(42, $record->additional_attributes['integer_value']);
        $this->assertEquals(3.14, $record->additional_attributes['float_value']);
        $this->assertTrue($record->additional_attributes['boolean_value']);
        $this->assertNull($record->additional_attributes['null_value']);
        $this->assertEquals(['item1', 'item2'], $record->additional_attributes['array_value']);
    }

    /**
     * Test querying records with null additional_attributes
     * 
     * Validates: Requirements 6.4, 9.1, 9.2
     */
    public function test_query_records_with_null_additional_attributes(): void
    {
        // Create record with null additional_attributes
        $record = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        // Accessing additional_attributes should not throw error
        $attributes = $record->additional_attributes;
        $this->assertNull($attributes);

        // Array access should not throw error
        $value = $record->additional_attributes['any_key'] ?? null;
        $this->assertNull($value);

        // getDynamicAttribute should return null
        $this->assertNull($record->getDynamicAttribute('any_key'));

        // hasDynamicAttribute should return false
        $this->assertFalse($record->hasDynamicAttribute('any_key'));

        // getDynamicAttributeKeys should return empty array
        $this->assertEquals([], $record->getDynamicAttributeKeys());
    }

    /**
     * Test querying with whereNull and whereNotNull
     * 
     * Validates: Requirement 6.1
     */
    public function test_query_with_where_null_and_where_not_null(): void
    {
        $record1 = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
            ],
        ]);

        $record2 = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        // Query for records with additional_attributes
        $results = MainSystem::whereNotNull('additional_attributes')->get();
        $this->assertTrue($results->contains('id', $record1->id));

        // Query for records without additional_attributes
        $results = MainSystem::whereNull('additional_attributes')->get();
        $this->assertTrue($results->contains('id', $record2->id));
    }

    /**
     * Test combining JSON queries with regular column queries
     * 
     * Validates: Requirement 6.1
     */
    public function test_combining_json_queries_with_regular_column_queries(): void
    {
        $record1 = MainSystem::factory()->create([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'additional_attributes' => [
                'department' => 'IT',
            ],
        ]);

        $record2 = MainSystem::factory()->create([
            'last_name' => 'Garcia',
            'first_name' => 'Maria',
            'additional_attributes' => [
                'department' => 'IT',
            ],
        ]);

        $record3 = MainSystem::factory()->create([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Pedro',
            'additional_attributes' => [
                'department' => 'HR',
            ],
        ]);

        // Combine regular and JSON queries
        $results = MainSystem::where('last_name', 'Dela Cruz')
            ->where('additional_attributes->department', 'IT')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($record1->id, $results->first()->id);
    }
}

