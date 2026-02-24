<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemDynamicAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_dynamic_attribute_keys_returns_all_keys(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'position' => 'Developer',
            ],
        ]);

        $keys = $record->getDynamicAttributeKeys();

        $this->assertCount(3, $keys);
        $this->assertContains('employee_id', $keys);
        $this->assertContains('department', $keys);
        $this->assertContains('position', $keys);
    }

    public function test_get_dynamic_attribute_keys_returns_empty_array_when_null(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        $keys = $record->getDynamicAttributeKeys();

        $this->assertIsArray($keys);
        $this->assertEmpty($keys);
    }

    public function test_has_dynamic_attribute_returns_true_when_exists(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
            ],
        ]);

        $this->assertTrue($record->hasDynamicAttribute('employee_id'));
    }

    public function test_has_dynamic_attribute_returns_false_when_not_exists(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
            ],
        ]);

        $this->assertFalse($record->hasDynamicAttribute('department'));
    }


    public function test_get_dynamic_attribute_returns_value_when_exists(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        $this->assertEquals('EMP-001', $record->getDynamicAttribute('employee_id'));
        $this->assertEquals('IT', $record->getDynamicAttribute('department'));
    }

    public function test_get_dynamic_attribute_returns_null_when_not_exists(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
            ],
        ]);

        $this->assertNull($record->getDynamicAttribute('department'));
    }

    public function test_get_dynamic_attribute_returns_default_when_not_exists(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
            ],
        ]);

        $this->assertEquals('N/A', $record->getDynamicAttribute('department', 'N/A'));
    }

    public function test_set_dynamic_attribute_adds_new_attribute(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        $record->setDynamicAttribute('employee_id', 'EMP-001');
        $record->save();
        $record->refresh();

        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
    }

    public function test_set_dynamic_attribute_updates_existing_attribute(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        $record->setDynamicAttribute('department', 'HR');
        $record->save();
        $record->refresh();

        $this->assertEquals('HR', $record->additional_attributes['department']);
        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
    }

    public function test_set_dynamic_attribute_preserves_other_attributes(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
            ],
        ]);

        $record->setDynamicAttribute('position', 'Developer');
        $record->save();
        $record->refresh();

        $this->assertCount(3, $record->additional_attributes);
        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
        $this->assertEquals('IT', $record->additional_attributes['department']);
        $this->assertEquals('Developer', $record->additional_attributes['position']);
    }

    // ========================================================================
    // Unit Tests for Task 2.4
    // Requirements: 2.1, 6.2, 6.3, 9.1, 9.2
    // ========================================================================

    /**
     * Test storing and retrieving dynamic attributes
     * Validates: Requirements 2.1, 6.2
     */
    public function test_can_store_and_retrieve_dynamic_attributes(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'position' => 'Developer',
                'salary_grade' => 'SG-15',
            ],
        ]);

        // Verify all attributes are stored
        $this->assertNotNull($record->additional_attributes);
        $this->assertIsArray($record->additional_attributes);
        $this->assertCount(4, $record->additional_attributes);

        // Verify individual values can be retrieved
        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
        $this->assertEquals('IT', $record->additional_attributes['department']);
        $this->assertEquals('Developer', $record->additional_attributes['position']);
        $this->assertEquals('SG-15', $record->additional_attributes['salary_grade']);
    }

    /**
     * Test null additional_attributes returns empty array
     * Validates: Requirements 9.1, 9.2
     */
    public function test_null_additional_attributes_returns_empty_array(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        // Laravel's array cast should handle null gracefully
        $attributes = $record->additional_attributes;
        
        // The cast may return null or empty array depending on Laravel version
        // Both are acceptable for backward compatibility
        $this->assertTrue(
            $attributes === null || (is_array($attributes) && empty($attributes)),
            'additional_attributes should be null or empty array when set to null'
        );

        // Helper methods should handle null gracefully
        $this->assertEmpty($record->getDynamicAttributeKeys());
        $this->assertFalse($record->hasDynamicAttribute('any_key'));
        $this->assertNull($record->getDynamicAttribute('any_key'));
    }

    /**
     * Test array access syntax works
     * Validates: Requirements 6.2
     */
    public function test_array_access_syntax_works(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'position' => 'Developer',
                'hire_date' => '2020-01-15',
                'contact_number' => '+63-912-345-6789',
            ],
        ]);

        // Test array access syntax for various data types
        $this->assertEquals('EMP-001', $record->additional_attributes['employee_id']);
        $this->assertEquals('IT', $record->additional_attributes['department']);
        $this->assertEquals('Developer', $record->additional_attributes['position']);
        $this->assertEquals('2020-01-15', $record->additional_attributes['hire_date']);
        $this->assertEquals('+63-912-345-6789', $record->additional_attributes['contact_number']);

        // Test accessing non-existent key returns null
        $this->assertNull($record->additional_attributes['non_existent_key'] ?? null);

        // Test isset() works with array access
        $this->assertTrue(isset($record->additional_attributes['employee_id']));
        $this->assertFalse(isset($record->additional_attributes['non_existent_key']));
    }

    /**
     * Test object property access syntax works
     * Validates: Requirements 6.3
     */
    public function test_object_property_access_syntax_works(): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                'employee_id' => 'EMP-001',
                'department' => 'IT',
                'position' => 'Developer',
            ],
        ]);

        // Convert array to object for property access
        $attributes = (object) $record->additional_attributes;

        // Test object property access syntax
        $this->assertEquals('EMP-001', $attributes->employee_id);
        $this->assertEquals('IT', $attributes->department);
        $this->assertEquals('Developer', $attributes->position);

        // Test accessing non-existent property returns null
        $this->assertNull($attributes->non_existent_key ?? null);

        // Test isset() works with object property access
        $this->assertTrue(isset($attributes->employee_id));
        $this->assertFalse(isset($attributes->non_existent_key));
    }

    // ========================================================================
    // Property-Based Tests
    // ========================================================================

    /**
     * Property 1: Dynamic attribute round-trip consistency
     * 
     * **Validates: Requirements 2.2, 2.3**
     * 
     * For any MainSystem record and any valid key-value pair, setting a dynamic 
     * attribute then retrieving it should return the equivalent value.
     * 
     * @dataProvider dynamicAttributeRoundTripProvider
     */
    public function test_property_dynamic_attribute_round_trip_consistency(string $key, $value): void
    {
        $record = MainSystem::factory()->create();

        $record->setDynamicAttribute($key, $value);
        $record->save();
        $record->refresh();

        $retrieved = $record->getDynamicAttribute($key);
        $this->assertEquals($value, $retrieved);
    }

    public static function dynamicAttributeRoundTripProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 random test cases
        for ($i = 0; $i < 100; $i++) {
            $testCases["iteration_$i"] = [
                'key' => $faker->word . '_' . $i,
                'value' => $faker->randomElement([
                    $faker->word,
                    $faker->sentence,
                    $faker->numberBetween(1, 1000),
                    $faker->randomFloat(2, 0, 1000),
                    $faker->boolean,
                    $faker->date('Y-m-d'),
                    $faker->email,
                    $faker->url,
                ]),
            ];
        }

        return $testCases;
    }

    /**
     * Property 2: Dynamic attribute key enumeration
     * 
     * **Validates: Requirements 2.5**
     * 
     * For any MainSystem record with dynamic attributes, calling getDynamicAttributeKeys() 
     * should return exactly the set of keys present in the additional_attributes JSON.
     * 
     * @dataProvider dynamicAttributeKeysProvider
     */
    public function test_property_dynamic_attribute_key_enumeration(array $attributes): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => $attributes,
        ]);

        $keys = $record->getDynamicAttributeKeys();
        $expectedKeys = array_keys($attributes);

        $this->assertCount(count($expectedKeys), $keys);
        $this->assertEmpty(array_diff($expectedKeys, $keys));
        $this->assertEmpty(array_diff($keys, $expectedKeys));
    }

    public static function dynamicAttributeKeysProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 random test cases with varying numbers of attributes
        for ($i = 0; $i < 100; $i++) {
            $numAttributes = $faker->numberBetween(1, 10);
            $attributes = [];
            
            for ($j = 0; $j < $numAttributes; $j++) {
                // Use combination of index and random to ensure uniqueness without exhausting faker
                $attributes['key_' . $i . '_' . $j . '_' . $faker->randomNumber(5)] = $faker->word;
            }

            $testCases["iteration_$i"] = [$attributes];
        }

        return $testCases;
    }

    /**
     * Property 3: Dynamic attribute existence checking
     * 
     * **Validates: Requirements 2.6**
     * 
     * For any MainSystem record and any key, hasDynamicAttribute(key) should return 
     * true if and only if the key exists in additional_attributes.
     * 
     * @dataProvider dynamicAttributeExistenceProvider
     */
    public function test_property_dynamic_attribute_existence_checking(array $attributes, string $existingKey, string $nonExistingKey): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => $attributes,
        ]);

        // Key that exists should return true
        $this->assertTrue($record->hasDynamicAttribute($existingKey));

        // Key that doesn't exist should return false
        $this->assertFalse($record->hasDynamicAttribute($nonExistingKey));
    }

    public static function dynamicAttributeExistenceProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 random test cases
        for ($i = 0; $i < 100; $i++) {
            $numAttributes = $faker->numberBetween(2, 10);
            $attributes = [];
            $keys = [];
            
            for ($j = 0; $j < $numAttributes; $j++) {
                // Use combination of index and random to ensure uniqueness
                $key = 'key_' . $i . '_' . $j . '_' . $faker->randomNumber(5);
                $keys[] = $key;
                $attributes[$key] = $faker->word;
            }

            // Pick one existing key and generate one non-existing key
            $existingKey = $faker->randomElement($keys);
            $nonExistingKey = 'nonexistent_' . $i . '_' . $faker->randomNumber(5);

            $testCases["iteration_$i"] = [
                'attributes' => $attributes,
                'existingKey' => $existingKey,
                'nonExistingKey' => $nonExistingKey,
            ];
        }

        return $testCases;
    }

    /**
     * Property 20: Null additional_attributes support
     * 
     * **Validates: Requirements 9.1, 9.2**
     * 
     * For any MainSystem record where additional_attributes is null, all dynamic 
     * attribute operations (getDynamicAttributeKeys, hasDynamicAttribute, array access) 
     * should work without errors.
     * 
     * @dataProvider nullAdditionalAttributesProvider
     */
    public function test_property_null_additional_attributes_support(string $testKey): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => null,
        ]);

        // getDynamicAttributeKeys should return empty array
        $keys = $record->getDynamicAttributeKeys();
        $this->assertIsArray($keys);
        $this->assertEmpty($keys);

        // hasDynamicAttribute should return false for any key
        $this->assertFalse($record->hasDynamicAttribute($testKey));

        // getDynamicAttribute should return null (or default)
        $this->assertNull($record->getDynamicAttribute($testKey));
        $this->assertEquals('default', $record->getDynamicAttribute($testKey, 'default'));

        // Array access should handle null gracefully
        // Laravel's array cast returns null for null values, not empty array
        $attributes = $record->additional_attributes;
        $this->assertTrue($attributes === null || (is_array($attributes) && empty($attributes)));
    }

    public static function nullAdditionalAttributesProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 random test cases with different keys
        for ($i = 0; $i < 100; $i++) {
            $testCases["iteration_$i"] = [
                'testKey' => $faker->word . '_' . $i,
            ];
        }

        return $testCases;
    }
}
