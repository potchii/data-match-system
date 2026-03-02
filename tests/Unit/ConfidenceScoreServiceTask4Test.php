<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\MainSystem;
use App\Models\TemplateField;
use App\Services\ConfidenceScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfidenceScoreServiceTask4Test extends TestCase
{
    use RefreshDatabase;

    protected ConfidenceScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConfidenceScoreService();
    }

    /** @test */
    public function it_includes_template_fields_in_breakdown()
    {
        // Arrange
        $template = ColumnMappingTemplate::factory()->create();
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
        ]);
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
        ]);

        $existingRecord = MainSystem::factory()->create([
            'last_name' => 'Smith',
            'first_name' => 'John',
        ]);

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Smith',
                'first_name' => 'John',
            ],
            'template_fields' => [
                'employee_id' => 'EMP-12345',
                'salary' => '55000.00',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, $template->id);

        // Assert
        $this->assertArrayHasKey('template_fields', $breakdown);
        $this->assertCount(2, $breakdown['template_fields']);
        $this->assertArrayHasKey('employee_id', $breakdown['template_fields']);
        $this->assertArrayHasKey('salary', $breakdown['template_fields']);
        $this->assertEquals('new', $breakdown['template_fields']['employee_id']['status']);
    }

    /** @test */
    public function it_sets_category_property_for_core_fields()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'last_name' => 'Smith',
            'first_name' => 'John',
        ]);

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Smith',
                'first_name' => 'John',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertEquals('core', $breakdown['core_fields']['last_name']['category']);
        $this->assertEquals('core', $breakdown['core_fields']['first_name']['category']);
    }

    /** @test */
    public function it_sets_category_property_for_template_fields()
    {
        // Arrange
        $template = ColumnMappingTemplate::factory()->create();
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
        ]);

        $existingRecord = MainSystem::factory()->create();

        $uploadedData = [
            'core_fields' => [],
            'template_fields' => [
                'employee_id' => 'EMP-12345',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, $template->id);

        // Assert
        $this->assertEquals('template', $breakdown['template_fields']['employee_id']['category']);
    }

    /** @test */
    public function it_includes_normalized_values_for_name_fields()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'last_name' => 'Smith',
            'first_name' => 'John',
            'last_name_normalized' => 'smith',
            'first_name_normalized' => 'john',
        ]);

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'SMITH',
                'first_name' => 'JOHN',
                'last_name_normalized' => 'smith',
                'first_name_normalized' => 'john',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertArrayHasKey('uploaded_normalized', $breakdown['core_fields']['last_name']);
        $this->assertArrayHasKey('existing_normalized', $breakdown['core_fields']['last_name']);
        $this->assertEquals('smith', $breakdown['core_fields']['last_name']['uploaded_normalized']);
        $this->assertEquals('smith', $breakdown['core_fields']['last_name']['existing_normalized']);
    }

    /** @test */
    public function it_only_includes_normalized_values_when_available()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'birthday' => '1990-05-15',
        ]);

        $uploadedData = [
            'core_fields' => [
                'birthday' => '1990-05-15',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertArrayNotHasKey('uploaded_normalized', $breakdown['core_fields']['birthday']);
        $this->assertArrayNotHasKey('existing_normalized', $breakdown['core_fields']['birthday']);
    }

    /** @test */
    public function it_calculates_confidence_score_for_exact_match()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'last_name' => 'Smith',
        ]);

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Smith',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertEquals(100.0, $breakdown['core_fields']['last_name']['confidence']);
    }

    /** @test */
    public function it_calculates_confidence_score_for_fuzzy_match()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'first_name' => 'John',
        ]);

        $uploadedData = [
            'core_fields' => [
                'first_name' => 'Jon',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $confidence = $breakdown['core_fields']['first_name']['confidence'];
        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThan(100, $confidence);
    }

    /** @test */
    public function it_returns_null_confidence_for_new_fields()
    {
        // Arrange
        $template = ColumnMappingTemplate::factory()->create();
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
        ]);

        $existingRecord = MainSystem::factory()->create();

        $uploadedData = [
            'core_fields' => [],
            'template_fields' => [
                'department' => 'Engineering',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, $template->id);

        // Assert
        $this->assertNull($breakdown['template_fields']['department']['confidence']);
        $this->assertEquals('new', $breakdown['template_fields']['department']['status']);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_fields_key()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'last_name' => 'Smith',
            'first_name' => 'John',
        ]);

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Smith',
                'first_name' => 'John',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertArrayHasKey('fields', $breakdown);
        $this->assertArrayHasKey('last_name', $breakdown['fields']);
        $this->assertArrayHasKey('first_name', $breakdown['fields']);
    }

    /** @test */
    public function it_handles_different_field_types_for_confidence_calculation()
    {
        // Arrange
        $template = ColumnMappingTemplate::factory()->create();
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'age',
            'field_type' => 'integer',
        ]);
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
        ]);

        $existingRecord = MainSystem::factory()->create([
            'birthday' => '1990-05-15',
        ]);

        $uploadedData = [
            'core_fields' => [
                'birthday' => '1990-05-15',
            ],
            'template_fields' => [
                'age' => 34,
                'salary' => 50000.00,
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, $template->id);

        // Assert
        $this->assertEquals(100.0, $breakdown['core_fields']['birthday']['confidence']);
        // Template fields will be new since no existing values
        $this->assertNull($breakdown['template_fields']['age']['confidence']);
        $this->assertNull($breakdown['template_fields']['salary']['confidence']);
    }

    /** @test */
    public function it_returns_zero_confidence_for_non_string_mismatch()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create([
            'birthday' => '1990-05-15',
        ]);

        $uploadedData = [
            'core_fields' => [
                'birthday' => '1991-05-15',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord);

        // Assert
        $this->assertEquals(0.0, $breakdown['core_fields']['birthday']['confidence']);
    }

    /** @test */
    public function it_handles_missing_template_gracefully()
    {
        // Arrange
        $existingRecord = MainSystem::factory()->create();

        $uploadedData = [
            'core_fields' => [
                'last_name' => 'Smith',
            ],
            'template_fields' => [
                'employee_id' => 'EMP-12345',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, 99999);

        // Assert
        // When template is missing, template_fields should still be processed but without field type info
        $this->assertArrayHasKey('template_fields', $breakdown);
    }

    /** @test */
    public function it_includes_field_type_for_template_fields()
    {
        // Arrange
        $template = ColumnMappingTemplate::factory()->create();
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
        ]);

        $existingRecord = MainSystem::factory()->create();

        $uploadedData = [
            'core_fields' => [],
            'template_fields' => [
                'employee_id' => 'EMP-12345',
            ],
        ];

        // Act
        $breakdown = $this->service->generateBreakdown($uploadedData, $existingRecord, $template->id);

        // Assert
        $this->assertArrayHasKey('field_type', $breakdown['template_fields']['employee_id']);
        $this->assertEquals('string', $breakdown['template_fields']['employee_id']['field_type']);
    }
}
