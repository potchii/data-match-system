<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

class DataMappingServiceValidationPropertyTest extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    /**
     * Property 17: JSON well-formedness
     * 
     * **Validates: Requirements 7.1, 7.4**
     * 
     * @dataProvider jsonWellFormednessProvider
     */
    public function test_property_json_well_formedness(array $row): void
    {
        $result = $this->service->mapUploadedData($row);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dynamic_fields', $result);

        $json = json_encode($result['dynamic_fields']);
        
        $this->assertNotFalse($json, 'Dynamic fields should be JSON-encodable');
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 
            'JSON encoding should not produce errors: ' . json_last_error_msg());

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($result['dynamic_fields'], $decoded,
            'Round-trip JSON encoding should preserve data');
    }

    public static function jsonWellFormednessProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        for ($i = 0; $i < 100; $i++) {
            $row = [];
            
            $row['surname'] = $faker->lastName;
            
            $numFields = $faker->numberBetween(1, 10);
            for ($j = 0; $j < $numFields; $j++) {
                $fieldName = "dynamic_field_{$i}_{$j}";
                
                $row[$fieldName] = $faker->randomElement([
                    $faker->word,
                    $faker->sentence,
                    $faker->text(100),
                    $faker->numberBetween(1, 1000),
                    $faker->randomFloat(2, 0, 1000),
                    'true',
                    'false',
                    '1',
                    '0',
                    $faker->date('Y-m-d'),
                    $faker->dateTime->format('Y-m-d H:i:s'),
                    'Value with "quotes"',
                    "Value with 'apostrophes'",
                    'Value with \backslash',
                    'Value with /forward slash',
                    'Value with newline\ncharacter',
                    'Value with tab\tcharacter',
                    'Ñoño',
                    'José',
                    'Peña',
                ]);
            }

            $testCases["iteration_$i"] = ['row' => $row];
        }

        return $testCases;
    }

    /**
     * Property 19: Non-serializable value handling
     * 
     * **Validates: Requirements 7.5**
     * 
     * @dataProvider nonSerializableValueProvider
     */
    public function test_property_non_serializable_value_handling(array $row, array $nonSerializableKeys): void
    {
        $result = $this->service->mapUploadedData($row);

        $json = json_encode($result['dynamic_fields']);
        $this->assertNotFalse($json, 'Dynamic fields should be JSON-encodable after sanitization');
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(),
            'JSON encoding should not produce errors after sanitization: ' . json_last_error_msg());

        foreach ($nonSerializableKeys as $originalKey) {
            $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $originalKey));
            $normalizedKey = preg_replace('/_+/', '_', trim($normalizedKey, '_'));
            
            if (isset($result['dynamic_fields'][$normalizedKey])) {
                $value = $result['dynamic_fields'][$normalizedKey];
                
                $this->assertTrue(
                    is_string($value) || is_numeric($value) || is_bool($value) || is_array($value) || is_null($value),
                    "Non-serializable value for key '$normalizedKey' should be converted to JSON-serializable type"
                );
                
                if (is_array($value)) {
                    $this->assertJsonSerializable($value, $normalizedKey);
                }
            }
        }

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    protected function assertJsonSerializable($value, string $context = ''): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->assertJsonSerializable($item, $context . "[$key]");
            }
        } else {
            $this->assertTrue(
                is_string($value) || is_numeric($value) || is_bool($value) || is_null($value),
                "Value at $context should be JSON-serializable"
            );
        }
    }

    public static function nonSerializableValueProvider(): array
    {
        $testCases = [];

        $testCases['objects_with_toString'] = [
            'row' => [
                'surname' => 'TestUser',
                'object_field' => new class {
                    public function __toString(): string {
                        return 'StringifiedObject';
                    }
                },
            ],
            'nonSerializableKeys' => ['object_field'],
        ];

        $testCases['stdClass_objects'] = [
            'row' => [
                'surname' => 'TestUser',
                'std_object' => (object)['key' => 'value'],
            ],
            'nonSerializableKeys' => ['std_object'],
        ];

        $testCases['mixed_array_with_objects'] = [
            'row' => [
                'surname' => 'TestUser',
                'mixed_field' => [
                    'string' => 'value',
                    'number' => 123,
                    'object' => new class {
                        public function __toString(): string {
                            return 'NestedObject';
                        }
                    },
                ],
            ],
            'nonSerializableKeys' => ['mixed_field'],
        ];

        $testCases['datetime_objects'] = [
            'row' => [
                'surname' => 'TestUser',
                'date_field' => new \DateTime('2024-01-15'),
            ],
            'nonSerializableKeys' => ['date_field'],
        ];

        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 46; $i++) {
            $row = [
                'surname' => $faker->lastName,
            ];
            $nonSerializableKeys = [];

            $numObjects = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $numObjects; $j++) {
                $fieldName = "object_field_{$i}_{$j}";
                
                $row[$fieldName] = $faker->randomElement([
                    new class($faker->word) {
                        private $value;
                        public function __construct($value) {
                            $this->value = $value;
                        }
                        public function __toString(): string {
                            return $this->value;
                        }
                    },
                    (object)['data' => $faker->word],
                    new \DateTime($faker->date()),
                ]);
                
                $nonSerializableKeys[] = $fieldName;
            }

            $row['normal_field'] = $faker->word;
            $row['number_field'] = $faker->numberBetween(1, 100);

            $testCases["random_iteration_$i"] = [
                'row' => $row,
                'nonSerializableKeys' => $nonSerializableKeys,
            ];
        }

        return $testCases;
    }
}
