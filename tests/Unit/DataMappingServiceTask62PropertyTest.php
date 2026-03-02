<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use App\Services\DataMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property Test for DataMappingService::mapUploadedData() (Task 6.2)
 * 
 * Requirements: 9.4, 9.5
 * 
 * This test validates that template field values are preserved during the mapping process
 * and that data types from the uploaded file are maintained.
 */
class DataMappingServiceTask62PropertyTest extends TestCase
{
    use RefreshDatabase;

    protected DataMappingService $service;
    protected User $user;
    protected ColumnMappingTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
        $this->user = User::factory()->create();
        
        $this->template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Surname' => 'last_name',
                'FirstName' => 'first_name',
            ],
        ]);
    }

    /**
     * Feature: template-field-persistence, Property 32: DataMatchService Preserves Template Field Values
     * 
     * For any template field value during the matching process, the value should remain unchanged 
     * (matching does not modify values). Template field data types from the uploaded file should be preserved.
     * 
     * **Validates: Requirements 9.4, 9.5**
     * 
     * @test
     */
    public function test_preserves_template_field_values_and_data_types()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $this->runTemplateFieldPreservationTest();
        }
    }

    protected function runTemplateFieldPreservationTest(): void
    {
        // Create template fields with various data types
        $stringField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $integerField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_number',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $decimalField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'salary_grade',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $dateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'hire_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $booleanField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        // Create test data with various data types
        $testValues = [
            'employee_id' => 'EMP-' . rand(1000, 9999),
            'employee_number' => (string) rand(1, 999),
            'salary_grade' => (string) (rand(1, 50) + rand(0, 99) / 100),
            'hire_date' => '2024-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
            'is_active' => rand(0, 1) ? 'true' : 'false',
        ];

        $row = array_merge([
            'Surname' => 'TestSurname',
            'FirstName' => 'TestFirst',
        ], $testValues);

        $templateFieldNames = array_keys($testValues);

        // Map the data
        $result = $this->service->mapUploadedData($row, $templateFieldNames);

        // Verify template fields are present
        $this->assertArrayHasKey('template_fields', $result);
        $this->assertIsArray($result['template_fields']);

        // Verify all template field values are preserved exactly as uploaded
        foreach ($testValues as $fieldName => $originalValue) {
            $this->assertArrayHasKey($fieldName, $result['template_fields'],
                "Template field '$fieldName' should be present in template_fields");
            
            $preservedValue = $result['template_fields'][$fieldName];
            
            // Verify the value is preserved exactly (no modification)
            $this->assertEquals($originalValue, $preservedValue,
                "Template field '$fieldName' value should be preserved exactly as uploaded. " .
                "Expected: '$originalValue', Got: '$preservedValue'");
            
            // Verify data type is preserved (string remains string, etc.)
            $this->assertIsString($preservedValue,
                "Template field '$fieldName' should remain as string type from uploaded file");
        }

        // Verify core fields are still mapped correctly
        $this->assertArrayHasKey('core_fields', $result);
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);

        // Verify template fields are NOT in core_fields
        foreach ($templateFieldNames as $fieldName) {
            $this->assertArrayNotHasKey($fieldName, $result['core_fields'],
                "Template field '$fieldName' should not be in core_fields");
        }

        // Verify template fields are NOT in dynamic_fields
        foreach ($templateFieldNames as $fieldName) {
            $this->assertArrayNotHasKey($fieldName, $result['dynamic_fields'],
                "Template field '$fieldName' should not be in dynamic_fields");
        }

        // Clean up
        $stringField->delete();
        $integerField->delete();
        $decimalField->delete();
        $dateField->delete();
        $booleanField->delete();
    }

    /**
     * Feature: template-field-persistence, Property 32: DataMatchService Preserves Template Field Values
     * 
     * Verify that when no template fields are specified, the method still works correctly
     * and all unknown fields become dynamic fields.
     * 
     * **Validates: Requirements 9.4, 9.5**
     * 
     * @test
     */
    public function test_handles_no_template_fields_gracefully()
    {
        $row = [
            'Surname' => 'TestSurname',
            'FirstName' => 'TestFirst',
            'employee_id' => 'EMP-001',
            'department' => 'IT',
        ];

        // Map without specifying template field names
        $result = $this->service->mapUploadedData($row, null);

        // Verify template_fields is empty
        $this->assertArrayHasKey('template_fields', $result);
        $this->assertEmpty($result['template_fields']);

        // Verify unknown fields become dynamic fields
        $this->assertArrayHasKey('dynamic_fields', $result);
        $this->assertArrayHasKey('employee_id', $result['dynamic_fields']);
        $this->assertArrayHasKey('department', $result['dynamic_fields']);

        // Verify core fields are still mapped
        $this->assertArrayHasKey('core_fields', $result);
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
    }

    /**
     * Feature: template-field-persistence, Property 32: DataMatchService Preserves Template Field Values
     * 
     * Verify that empty template field values are excluded from the result.
     * 
     * **Validates: Requirements 9.4, 9.5**
     * 
     * @test
     */
    public function test_excludes_empty_template_field_values()
    {
        $row = [
            'Surname' => 'TestSurname',
            'FirstName' => 'TestFirst',
            'employee_id' => 'EMP-001',
            'department' => '',  // Empty string
            'hire_date' => null,  // Null value
        ];

        $templateFieldNames = ['employee_id', 'department', 'hire_date'];

        $result = $this->service->mapUploadedData($row, $templateFieldNames);

        // Verify only non-empty template fields are included
        $this->assertArrayHasKey('employee_id', $result['template_fields']);
        $this->assertArrayNotHasKey('department', $result['template_fields']);
        $this->assertArrayNotHasKey('hire_date', $result['template_fields']);

        // Verify the value is preserved
        $this->assertEquals('EMP-001', $result['template_fields']['employee_id']);
    }

    /**
     * Feature: template-field-persistence, Property 32: DataMatchService Preserves Template Field Values
     * 
     * Verify that zero and false values in template fields are preserved (not treated as empty).
     * 
     * **Validates: Requirements 9.4, 9.5**
     * 
     * @test
     */
    public function test_preserves_zero_and_false_template_field_values()
    {
        $row = [
            'Surname' => 'TestSurname',
            'FirstName' => 'TestFirst',
            'count' => '0',
            'is_active' => 'false',
            'score' => '0.0',
        ];

        $templateFieldNames = ['count', 'is_active', 'score'];

        $result = $this->service->mapUploadedData($row, $templateFieldNames);

        // Verify zero and false values are preserved
        $this->assertArrayHasKey('count', $result['template_fields']);
        $this->assertArrayHasKey('is_active', $result['template_fields']);
        $this->assertArrayHasKey('score', $result['template_fields']);

        $this->assertEquals('0', $result['template_fields']['count']);
        $this->assertEquals('false', $result['template_fields']['is_active']);
        $this->assertEquals('0.0', $result['template_fields']['score']);
    }
}
