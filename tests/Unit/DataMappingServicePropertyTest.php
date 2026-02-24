<?php

namespace Tests\Unit;

use App\Services\DataMappingService;
use Tests\TestCase;

class DataMappingServicePropertyTest extends TestCase
{
    protected DataMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
    }

    // ========================================================================
    // Property-Based Tests for Task 4.7
    // Requirements: 3.1, 3.2, 3.5, 3.6, 3.7, 4.1, 4.3, 4.4, 7.2, 7.3
    // ========================================================================

    /**
     * Property 4: Core field identification
     * 
     * **Validates: Requirements 3.1, 4.2**
     * 
     * For any uploaded row containing known column name variations (surname, firstname, 
     * DOB, etc.), the DataMappingService should correctly classify them as core_fields.
     * 
     * @dataProvider coreFieldIdentificationProvider
     */
    public function test_property_core_field_identification(array $row, array $expectedCoreFields): void
    {
        $result = $this->service->mapUploadedData($row);

        // Verify all expected core fields are present
        foreach ($expectedCoreFields as $field) {
            $this->assertArrayHasKey($field, $result['core_fields'], 
                "Expected core field '$field' not found in result");
        }

        // Verify these fields are NOT in dynamic_fields
        foreach ($expectedCoreFields as $field) {
            $this->assertArrayNotHasKey($field, $result['dynamic_fields'],
                "Core field '$field' should not appear in dynamic_fields");
        }
    }

    public static function coreFieldIdentificationProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Core field mappings to test
        $coreFieldVariations = [
            // Last name variations
            ['surname' => 'last_name', 'Surname' => 'last_name', 'lastname' => 'last_name', 'LastName' => 'last_name'],
            // First name variations
            ['firstname' => 'first_name', 'FirstName' => 'first_name', 'fname' => 'first_name'],
            // Birthday variations
            ['dob' => 'birthday', 'DOB' => 'birthday', 'birthday' => 'birthday', 'birthdate' => 'birthday', 'date_of_birth' => 'birthday'],
            // Gender variations
            ['sex' => 'gender', 'Sex' => 'gender', 'gender' => 'gender', 'Gender' => 'gender'],
            // Civil status variations
            ['status' => 'civil_status', 'Status' => 'civil_status', 'civilstatus' => 'civil_status'],
            // Address variations
            ['address' => 'street', 'Address' => 'street', 'street' => 'street', 'Street' => 'street'],
            // City variations
            ['city' => 'city', 'City' => 'city'],
            // Barangay variations
            ['barangay' => 'barangay', 'Barangay' => 'barangay', 'brgydescription' => 'barangay'],
        ];

        $iteration = 0;
        // Test each variation group
        foreach ($coreFieldVariations as $variationGroup) {
            foreach ($variationGroup as $columnName => $expectedField) {
                $row = [];
                $expectedCoreFields = [];

                // Add the test column with appropriate value
                if ($expectedField === 'birthday') {
                    $row[$columnName] = $faker->date('Y-m-d');
                } elseif ($expectedField === 'gender') {
                    $row[$columnName] = $faker->randomElement(['M', 'F', 'Male', 'Female']);
                } else {
                    $row[$columnName] = $faker->word;
                }
                $expectedCoreFields[] = $expectedField;

                $testCases["iteration_{$iteration}_{$columnName}"] = [
                    'row' => $row,
                    'expectedCoreFields' => $expectedCoreFields,
                ];
                $iteration++;
            }
        }

        return $testCases;
    }

    /**
     * Property 5: Dynamic field identification
     * 
     * **Validates: Requirements 3.2, 4.3**
     * 
     * For any uploaded row containing unknown column names, the DataMappingService 
     * should classify them as dynamic_fields.
     * 
     * @dataProvider dynamicFieldIdentificationProvider
     */
    public function test_property_dynamic_field_identification(array $row, array $expectedDynamicFields): void
    {
        $result = $this->service->mapUploadedData($row);

        // Verify all expected dynamic fields are present
        foreach ($expectedDynamicFields as $field) {
            $this->assertArrayHasKey($field, $result['dynamic_fields'],
                "Expected dynamic field '$field' not found in result");
        }

        // Verify these fields are NOT in core_fields
        foreach ($expectedDynamicFields as $field) {
            $this->assertArrayNotHasKey($field, $result['core_fields'],
                "Dynamic field '$field' should not appear in core_fields");
        }
    }

    public static function dynamicFieldIdentificationProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases with random unknown column names
        for ($i = 0; $i < 100; $i++) {
            $numFields = $faker->numberBetween(1, 5);
            $row = [];
            $expectedDynamicFields = [];

            for ($j = 0; $j < $numFields; $j++) {
                // Generate unknown column names (not using unique() to avoid exhaustion)
                $columnName = 'unknown_field_' . $i . '_' . $j . '_' . $faker->word;
                $row[$columnName] = $faker->sentence;
                
                // Expected normalized key (snake_case)
                $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $columnName));
                $normalizedKey = preg_replace('/_+/', '_', $normalizedKey);
                $normalizedKey = trim($normalizedKey, '_');
                
                $expectedDynamicFields[] = $normalizedKey;
            }

            $testCases["iteration_$i"] = [
                'row' => $row,
                'expectedDynamicFields' => $expectedDynamicFields,
            ];
        }

        return $testCases;
    }

    /**
     * Property 8: Dynamic key normalization
     * 
     * **Validates: Requirements 3.5**
     * 
     * For any input column name (camelCase, PascalCase, kebab-case, etc.), the 
     * normalized dynamic attribute key should be in snake_case format.
     * 
     * @dataProvider dynamicKeyNormalizationProvider
     */
    public function test_property_dynamic_key_normalization(array $row): void
    {
        $result = $this->service->mapUploadedData($row);

        // All dynamic field keys should be snake_case
        foreach (array_keys($result['dynamic_fields']) as $key) {
            // Should be lowercase
            $this->assertEquals(strtolower($key), $key,
                "Dynamic key '$key' should be lowercase");
            
            // Should only contain alphanumeric and underscores
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $key,
                "Dynamic key '$key' should only contain lowercase letters, numbers, and underscores");
            
            // Should not contain hyphens
            $this->assertStringNotContainsString('-', $key,
                "Dynamic key '$key' should not contain hyphens");
            
            // Should not start or end with underscore
            $this->assertStringStartsNotWith('_', $key,
                "Dynamic key '$key' should not start with underscore");
            $this->assertStringEndsNotWith('_', $key,
                "Dynamic key '$key' should not end with underscore");
        }
    }

    public static function dynamicKeyNormalizationProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases with various naming conventions
        for ($i = 0; $i < 100; $i++) {
            $row = [];
            
            // camelCase
            $camelCase = $faker->word . ucfirst($faker->word);
            $row[$camelCase] = $faker->word;
            
            // PascalCase
            $pascalCase = ucfirst($faker->word) . ucfirst($faker->word);
            $row[$pascalCase] = $faker->word;
            
            // kebab-case
            $kebabCase = $faker->word . '-' . $faker->word;
            $row[$kebabCase] = $faker->word;
            
            // UPPER_CASE
            $upperCase = strtoupper($faker->word . '_' . $faker->word);
            $row[$upperCase] = $faker->word;
            
            // Mixed with spaces
            $withSpaces = $faker->word . ' ' . $faker->word;
            $row[$withSpaces] = $faker->word;
            
            // With special characters
            $withSpecial = $faker->word . '@' . $faker->word;
            $row[$withSpecial] = $faker->word;

            $testCases["iteration_$i"] = ['row' => $row];
        }

        return $testCases;
    }

    /**
     * Property 9: Core field priority
     * 
     * **Validates: Requirements 3.6**
     * 
     * For any uploaded row where a column name matches both a core field mapping 
     * and could be a dynamic field, the system should map it to core_fields (not dynamic_fields).
     * 
     * @dataProvider coreFieldPriorityProvider
     */
    public function test_property_core_field_priority(string $coreColumnName, string $expectedCoreField, $value): void
    {
        $row = [
            $coreColumnName => $value,
        ];

        $result = $this->service->mapUploadedData($row);

        // Should be in core_fields
        $this->assertArrayHasKey($expectedCoreField, $result['core_fields'],
            "Column '$coreColumnName' should map to core field '$expectedCoreField'");

        // Should NOT be in dynamic_fields (even with normalized key)
        $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $coreColumnName));
        $normalizedKey = preg_replace('/_+/', '_', trim($normalizedKey, '_'));
        
        $this->assertArrayNotHasKey($normalizedKey, $result['dynamic_fields'],
            "Core column '$coreColumnName' should not appear in dynamic_fields");
        $this->assertArrayNotHasKey($coreColumnName, $result['dynamic_fields'],
            "Core column '$coreColumnName' should not appear in dynamic_fields");
    }

    public static function coreFieldPriorityProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // All core field mappings
        $coreFieldMappings = [
            'surname' => 'last_name',
            'Surname' => 'last_name',
            'lastname' => 'last_name',
            'firstname' => 'first_name',
            'FirstName' => 'first_name',
            'dob' => 'birthday',
            'DOB' => 'birthday',
            'birthday' => 'birthday',
            'sex' => 'gender',
            'gender' => 'gender',
            'status' => 'civil_status',
            'address' => 'street',
            'city' => 'city',
            'barangay' => 'barangay',
        ];

        $iteration = 0;
        foreach ($coreFieldMappings as $columnName => $expectedField) {
            // Generate appropriate value based on field type
            if ($expectedField === 'birthday') {
                $value = $faker->date('Y-m-d');
            } elseif ($expectedField === 'gender') {
                $value = $faker->randomElement(['M', 'F', 'Male', 'Female']);
            } else {
                $value = $faker->word;
            }

            $testCases["iteration_{$iteration}_{$columnName}"] = [
                'coreColumnName' => $columnName,
                'expectedCoreField' => $expectedField,
                'value' => $value,
            ];
            $iteration++;
        }

        return $testCases;
    }

    /**
     * Property 10: JSON size validation
     * 
     * **Validates: Requirements 3.7, 7.2**
     * 
     * For any dynamic attributes that when JSON-encoded exceed 65,535 bytes, the 
     * system should reject the data with a descriptive error message.
     * 
     * @dataProvider jsonSizeValidationProvider
     */
    public function test_property_json_size_validation(array $row, bool $shouldFail): void
    {
        if ($shouldFail) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Dynamic attributes exceed maximum size');
        }

        $result = $this->service->mapUploadedData($row);

        if (!$shouldFail) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('core_fields', $result);
            $this->assertArrayHasKey('dynamic_fields', $result);
        }
    }

    public static function jsonSizeValidationProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Test cases that should pass (within limit)
        for ($i = 0; $i < 50; $i++) {
            $row = [
                'surname' => $faker->lastName,
                'firstname' => $faker->firstName,
            ];

            // Add reasonable amount of dynamic fields
            $numFields = $faker->numberBetween(1, 10);
            for ($j = 0; $j < $numFields; $j++) {
                $row["field_$j"] = $faker->sentence(10); // ~100 bytes each
            }

            $testCases["pass_iteration_$i"] = [
                'row' => $row,
                'shouldFail' => false,
            ];
        }

        // Test cases that should fail (exceed limit)
        for ($i = 0; $i < 50; $i++) {
            $row = [
                'surname' => $faker->lastName,
                'firstname' => $faker->firstName,
            ];

            // Add large amount of data to exceed 65KB
            $numFields = $faker->numberBetween(800, 1000);
            for ($j = 0; $j < $numFields; $j++) {
                $row["large_field_$j"] = $faker->paragraph(5); // ~500 bytes each
            }

            $testCases["fail_iteration_$i"] = [
                'row' => $row,
                'shouldFail' => true,
            ];
        }

        return $testCases;
    }

    /**
     * Property 11: Structured mapping output
     * 
     * **Validates: Requirements 4.1**
     * 
     * For any uploaded row, the DataMappingService output should contain both 
     * 'core_fields' and 'dynamic_fields' keys.
     * 
     * @dataProvider structuredMappingOutputProvider
     */
    public function test_property_structured_mapping_output(array $row): void
    {
        $result = $this->service->mapUploadedData($row);

        // Must be an array
        $this->assertIsArray($result);

        // Must have both keys
        $this->assertArrayHasKey('core_fields', $result,
            'Result must contain core_fields key');
        $this->assertArrayHasKey('dynamic_fields', $result,
            'Result must contain dynamic_fields key');

        // Both must be arrays
        $this->assertIsArray($result['core_fields'],
            'core_fields must be an array');
        $this->assertIsArray($result['dynamic_fields'],
            'dynamic_fields must be an array');
    }

    public static function structuredMappingOutputProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases with various combinations
        for ($i = 0; $i < 100; $i++) {
            $row = [];

            // Randomly include core fields
            if ($faker->boolean(70)) {
                $row['surname'] = $faker->lastName;
            }
            if ($faker->boolean(70)) {
                $row['firstname'] = $faker->firstName;
            }
            if ($faker->boolean(50)) {
                $row['DOB'] = $faker->date('Y-m-d');
            }

            // Randomly include dynamic fields
            $numDynamic = $faker->numberBetween(0, 5);
            for ($j = 0; $j < $numDynamic; $j++) {
                $row["dynamic_field_$j"] = $faker->word;
            }

            $testCases["iteration_$i"] = ['row' => $row];
        }

        return $testCases;
    }

    /**
     * Property 12: Empty value exclusion
     * 
     * **Validates: Requirements 4.4**
     * 
     * For any uploaded row with null or empty string values, those values should 
     * not appear in the dynamic_fields output.
     * 
     * @dataProvider emptyValueExclusionProvider
     */
    public function test_property_empty_value_exclusion(array $row, array $emptyKeys): void
    {
        $result = $this->service->mapUploadedData($row);

        // Verify empty/null values are excluded from dynamic_fields
        foreach ($emptyKeys as $key) {
            // Normalize the key to match what would be in dynamic_fields
            $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
            $normalizedKey = preg_replace('/_+/', '_', trim($normalizedKey, '_'));
            
            $this->assertArrayNotHasKey($normalizedKey, $result['dynamic_fields'],
                "Empty field '$key' should not appear in dynamic_fields");
        }
    }

    public static function emptyValueExclusionProvider(): array
    {
        $faker = \Faker\Factory::create();
        $testCases = [];

        // Generate 100 test cases
        for ($i = 0; $i < 100; $i++) {
            $row = [];
            $emptyKeys = [];

            // Add some valid fields
            $row['valid_field_1'] = $faker->word;
            $row['valid_field_2'] = $faker->sentence;

            // Add empty string fields
            $numEmpty = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $numEmpty; $j++) {
                $key = "empty_field_$j";
                $row[$key] = '';
                $emptyKeys[] = $key;
            }

            // Add null fields
            $numNull = $faker->numberBetween(1, 3);
            for ($j = 0; $j < $numNull; $j++) {
                $key = "null_field_$j";
                $row[$key] = null;
                $emptyKeys[] = $key;
            }

            $testCases["iteration_$i"] = [
                'row' => $row,
                'emptyKeys' => $emptyKeys,
            ];
        }

        return $testCases;
    }

    /**
     * Property 18: Key sanitization
     * 
     * **Validates: Requirements 7.3**
     * 
     * For any dynamic attribute key containing special characters or potential 
     * injection patterns, the normalized key should contain only alphanumeric 
     * characters and underscores.
     * 
     * @dataProvider keySanitizationProvider
     */
    public function test_property_key_sanitization(array $row): void
    {
        $result = $this->service->mapUploadedData($row);

        // All dynamic field keys should be sanitized
        foreach (array_keys($result['dynamic_fields']) as $key) {
            // Should only contain safe characters
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $key,
                "Key '$key' should only contain lowercase alphanumeric and underscores");

            // Should not contain any special characters
            $this->assertDoesNotMatchRegularExpression('/[^a-z0-9_]/', $key,
                "Key '$key' should not contain special characters");

            // Should not contain SQL injection patterns
            $this->assertStringNotContainsString('--', $key);
            $this->assertStringNotContainsString(';', $key);
            $this->assertStringNotContainsString('/*', $key);
            $this->assertStringNotContainsString('*/', $key);

            // Should not contain JSON injection patterns
            $this->assertStringNotContainsString('"', $key);
            $this->assertStringNotContainsString("'", $key);
            $this->assertStringNotContainsString('\\', $key);
        }
    }

    public static function keySanitizationProvider(): array
    {
        $testCases = [];

        // Dangerous patterns to test
        $dangerousPatterns = [
            // SQL injection attempts
            "field'; DROP TABLE users--",
            "field\"; DELETE FROM main_system--",
            "field/* comment */",
            
            // JSON injection attempts
            'field": "value", "injected": "data',
            "field\\\"injected",
            
            // XSS attempts
            "field<script>alert('xss')</script>",
            "field<img src=x onerror=alert(1)>",
            
            // Path traversal
            "field../../etc/passwd",
            "field..\\..\\windows\\system32",
            
            // Special characters
            "field@#$%^&*()",
            "field!@#$%",
            "field~`+=[]{}|\\:;\"'<>?,./",
            
            // Unicode and control characters
            "field\x00null",
            "field\nnewline",
            "field\ttab",
        ];

        $iteration = 0;
        foreach ($dangerousPatterns as $pattern) {
            $row = [
                $pattern => 'test_value',
                'normal_field' => 'normal_value',
            ];

            $testCases["iteration_{$iteration}_" . substr(md5($pattern), 0, 8)] = [
                'row' => $row,
            ];
            $iteration++;
        }

        // Add random test cases
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < 50; $i++) {
            $row = [];
            
            // Generate fields with random special characters
            $numFields = $faker->numberBetween(1, 5);
            for ($j = 0; $j < $numFields; $j++) {
                $specialChars = ['@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '+', '=', '[', ']', '{', '}', '|', '\\', ':', ';', '"', "'", '<', '>', ',', '.', '/', '?', '~', '`'];
                $key = $faker->word . $faker->randomElement($specialChars) . $faker->word;
                $row[$key] = $faker->word;
            }

            $testCases["random_iteration_$i"] = ['row' => $row];
        }

        return $testCases;
    }
}
