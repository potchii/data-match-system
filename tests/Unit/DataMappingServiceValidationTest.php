<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

class DataMappingServiceValidationTest extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    public function test_it_validates_json_size_limit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dynamic attributes exceed maximum size');

        $largeData = [
            'surname' => 'Test',
            'firstname' => 'User',
        ];

        for ($i = 0; $i < 1000; $i++) {
            $largeData["large_field_$i"] = str_repeat('A', 100);
        }

        $this->service->mapUploadedData($largeData);
    }

    public function test_it_accepts_data_within_size_limit()
    {
        $row = [
            'surname' => 'Test',
            'firstname' => 'User',
            'field1' => 'value1',
            'field2' => 'value2',
            'field3' => 'value3',
        ];

        $result = $this->service->mapUploadedData($row);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('core_fields', $result);
        $this->assertArrayHasKey('dynamic_fields', $result);
    }

    /**
     * Property 17: JSON well-formedness
     * **Validates: Requirements 7.1, 7.4**
     * @dataProvider jsonWellFormednessProvider
     */
    public function test_property_json_well_formedness(array $row): void
    {
        $result = $this->service->mapUploadedData($row);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('dynamic_fields', $result);
        $json = json_encode($result['dynamic_fields']);
        $this->assertNotFalse($json);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($result['dynamic_fields'], $decoded);
    }

    public static function jsonWellFormednessProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];
        for ($i = 0; $i < 100; $i++) {
            $row = ['surname' => $faker->lastName];
            $numFields = $faker->numberBetween(1, 10);
            for ($j = 0; $j < $numFields; $j++) {
                $row["field_{$i}_{$j}"] = $faker->randomElement([
                    $faker->word, $faker->sentence, $faker->text(100),
                    $faker->numberBetween(1, 1000), $faker->randomFloat(2, 0, 1000),
                    'true', 'false', $faker->date('Y-m-d'),
                    'Value with "quotes"', "Value with 'apostrophes'",
                    'Ñoño', 'José',
                ]);
            }
            $testCases["iteration_$i"] = ['row' => $row];
        }
        return $testCases;
    }

    /**
     * Property 19: Non-serializable value handling
     * **Validates: Requirements 7.5**
     * @dataProvider nonSerializableValueProvider
     */
    public function test_property_non_serializable_value_handling(array $row, array $keys): void
    {
        $result = $this->service->mapUploadedData($row);
        $json = json_encode($result['dynamic_fields']);
        $this->assertNotFalse($json);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        foreach ($keys as $originalKey) {
            $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $originalKey));
            $normalizedKey = preg_replace('/_+/', '_', trim($normalizedKey, '_'));
            if (isset($result['dynamic_fields'][$normalizedKey])) {
                $value = $result['dynamic_fields'][$normalizedKey];
                $this->assertTrue(
                    is_string($value) || is_numeric($value) || is_bool($value) || is_array($value) || is_null($value)
                );
            }
        }
    }

    public static function nonSerializableValueProvider(): array
    {
        $testCases = [];
        $testCases['objects_with_toString'] = [
            'row' => ['surname' => 'Test', 'obj' => new class { public function __toString(): string { return 'Str'; } }],
            'keys' => ['obj'],
        ];
        $testCases['stdClass'] = [
            'row' => ['surname' => 'Test', 'std' => (object)['key' => 'value']],
            'keys' => ['std'],
        ];
        $testCases['datetime'] = [
            'row' => ['surname' => 'Test', 'date' => new \DateTime('2024-01-15')],
            'keys' => ['date'],
        ];
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 47; $i++) {
            $row = ['surname' => $faker->lastName];
            $keys = [];
            for ($j = 0; $j < $faker->numberBetween(1, 3); $j++) {
                $key = "obj_{$i}_{$j}";
                $row[$key] = $faker->randomElement([
                    new class($faker->word) { private $v; public function __construct($v) { $this->v = $v; } public function __toString(): string { return $this->v; } },
                    (object)['data' => $faker->word],
                    new \DateTime($faker->date()),
                ]);
                $keys[] = $key;
            }
            $testCases["iteration_$i"] = ['row' => $row, 'keys' => $keys];
        }
        return $testCases;
    }
}
