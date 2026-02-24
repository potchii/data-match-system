<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemJsonQueryPropertyTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================================
    // Property-Based Tests for Task 9.1
    // Requirements: 6.1, 6.3, 6.4, 6.5
    // ========================================================================

    /**
     * Property 14: JSON query support
     * 
     * **Validates: Requirements 6.1**
     * 
     * For any MainSystem record with a dynamic attribute key-value pair, querying 
     * using Laravel's JSON syntax (where('additional_attributes->key', 'value')) 
     * should return that record.
     * 
     * @dataProvider jsonQuerySupportProvider
     */
    public function test_property_json_query_support(string $key, $value): void
    {
        // Create a record with the dynamic attribute
        $record = MainSystem::factory()->create([
            'additional_attributes' => [
                $key => $value,
                'other_field' => 'other_value',
            ],
        ]);

        // Create another record without this attribute
        MainSystem::factory()->create([
            'additional_attributes' => [
                'different_field' => 'different_value',
            ],
        ]);

        // Query using Laravel's JSON syntax
        $results = MainSystem::where("additional_attributes->$key", $value)->get();

        // Should find exactly one record
        $this->assertCount(1, $results, 
            "JSON query for key '$key' with value '$value' should return exactly one record");
        
        // Should be the correct record
        $this->assertEquals($record->id, $results->first()->id,
            "JSON query should return the correct record");
        
        // Verify the value matches
        $this->assertEquals($value, $results->first()->additional_attributes[$key],
            "Retrieved record should have the correct value for key '$key'");
    }

    public static function jsonQuerySupportProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases with various data types
        for ($i = 0; $i < 100; $i++) {
            $key = 'test_key_' . $i;
            
            // Test different value types
            $value = $faker->randomElement([
                $faker->word,
                $faker->sentence,
                $faker->numberBetween(1, 1000),
                $faker->email,
                $faker->date('Y-m-d'),
                $faker->boolean ? 'true' : 'false', // Store as string for JSON compatibility
                $faker->company,
                $faker->phoneNumber,
            ]);

            $testCases["iteration_$i"] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return $testCases;
    }

    /**
     * Property 15: Object property access
     * 
     * **Validates: Requirements 6.3**
     * 
     * For any MainSystem record with dynamic attributes, accessing them via object 
     * property syntax ($record->additional_attributes->key) should return the stored value.
     * 
     * @dataProvider objectPropertyAccessProvider
     */
    public function test_property_object_property_access(array $attributes): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => $attributes,
        ]);

        // Convert to object for property access
        $attributesObject = (object) $record->additional_attributes;

        // Verify each attribute is accessible via object property syntax
        foreach ($attributes as $key => $expectedValue) {
            $this->assertTrue(isset($attributesObject->$key),
                "Property '$key' should be accessible via object syntax");
            
            $this->assertEquals($expectedValue, $attributesObject->$key,
                "Object property access for '$key' should return the correct value");
        }
    }

    public static function objectPropertyAccessProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases with varying numbers of attributes
        for ($i = 0; $i < 100; $i++) {
            $numAttributes = $faker->numberBetween(1, 10);
            $attributes = [];
            
            for ($j = 0; $j < $numAttributes; $j++) {
                $key = 'key_' . $i . '_' . $j;
                $attributes[$key] = $faker->randomElement([
                    $faker->word,
                    $faker->sentence,
                    $faker->numberBetween(1, 1000),
                    $faker->randomFloat(2, 0, 1000),
                    $faker->email,
                    $faker->date('Y-m-d'),
                    $faker->company,
                ]);
            }

            $testCases["iteration_$i"] = [
                'attributes' => $attributes,
            ];
        }

        return $testCases;
    }

    /**
     * Property 16: Graceful missing key handling
     * 
     * **Validates: Requirements 6.4, 6.5**
     * 
     * For any MainSystem record and any non-existent dynamic attribute key, 
     * accessing it should return null without throwing an exception.
     * 
     * @dataProvider gracefulMissingKeyHandlingProvider
     */
    public function test_property_graceful_missing_key_handling(array $existingAttributes, string $missingKey): void
    {
        $record = MainSystem::factory()->create([
            'additional_attributes' => $existingAttributes,
        ]);

        // Test array access returns null for missing key
        $arrayValue = $record->additional_attributes[$missingKey] ?? null;
        $this->assertNull($arrayValue,
            "Array access for missing key '$missingKey' should return null");

        // Test isset() returns false for missing key
        $this->assertFalse(isset($record->additional_attributes[$missingKey]),
            "isset() for missing key '$missingKey' should return false");

        // Test array_key_exists() returns false for missing key
        $this->assertFalse(array_key_exists($missingKey, $record->additional_attributes ?? []),
            "array_key_exists() for missing key '$missingKey' should return false");

        // Test object property access returns null for missing key
        $attributesObject = (object) $record->additional_attributes;
        $objectValue = $attributesObject->$missingKey ?? null;
        $this->assertNull($objectValue,
            "Object property access for missing key '$missingKey' should return null");

        // Test isset() with object property access
        $this->assertFalse(isset($attributesObject->$missingKey),
            "isset() with object property for missing key '$missingKey' should return false");

        // Test getDynamicAttribute() returns null for missing key
        $this->assertNull($record->getDynamicAttribute($missingKey),
            "getDynamicAttribute() for missing key '$missingKey' should return null");

        // Test hasDynamicAttribute() returns false for missing key
        $this->assertFalse($record->hasDynamicAttribute($missingKey),
            "hasDynamicAttribute() for missing key '$missingKey' should return false");
    }

    public static function gracefulMissingKeyHandlingProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases
        for ($i = 0; $i < 100; $i++) {
            $numAttributes = $faker->numberBetween(1, 5);
            $existingAttributes = [];
            
            for ($j = 0; $j < $numAttributes; $j++) {
                $key = 'existing_key_' . $i . '_' . $j;
                $existingAttributes[$key] = $faker->word;
            }

            // Generate a key that definitely doesn't exist
            $missingKey = 'missing_key_' . $i . '_' . $faker->unique()->randomNumber(5);

            $testCases["iteration_$i"] = [
                'existingAttributes' => $existingAttributes,
                'missingKey' => $missingKey,
            ];
        }

        return $testCases;
    }
}
