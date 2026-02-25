<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\User;
use App\Services\DataMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Tests for DataMappingService::applyTemplate() (Task 6.1)
 * 
 * Requirements: 3.4, 3.7
 * 
 * These tests verify the applyTemplate() method functionality:
 * - Accept row and optional template
 * - Remap columns according to template mappings
 * - Handle missing columns gracefully
 */
class DataMappingServiceTask61Test extends TestCase
{
    use RefreshDatabase;

    protected DataMappingService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DataMappingService();
        $this->user = User::factory()->create();
    }

    // ========================================================================
    // Test accept row and optional template
    // Requirements: 3.4
    // ========================================================================

    /** @test */
    public function it_returns_row_unchanged_when_no_template_provided()
    {
        $row = [
            'Employee No' => 'EMP-001',
            'Surname' => 'Cruz',
            'Given Name' => 'Juan',
        ];

        $result = $this->service->applyTemplate($row, null);

        $this->assertEquals($row, $result);
    }

    /** @test */
    public function it_accepts_template_and_applies_mappings()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'HR Import',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $row = [
            'Employee No' => 'EMP-001',
            'Surname' => 'Cruz',
            'Given Name' => 'Juan',
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertArrayHasKey('first_name', $result);
        $this->assertEquals('EMP-001', $result['uid']);
        $this->assertEquals('Cruz', $result['last_name']);
        $this->assertEquals('Juan', $result['first_name']);
    }

    // ========================================================================
    // Test remap columns according to template mappings
    // Requirements: 3.4
    // ========================================================================

    /** @test */
    public function it_remaps_all_columns_present_in_template()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Full Mapping',
            'mappings' => [
                'Reg No' => 'uid',
                'Last Name' => 'last_name',
                'First Name' => 'first_name',
                'Birth Date' => 'birthday',
                'Department' => 'dept',
            ],
        ]);

        $row = [
            'Reg No' => 'REG-123',
            'Last Name' => 'Garcia',
            'First Name' => 'Maria',
            'Birth Date' => '1990-05-15',
            'Department' => 'IT',
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertEquals([
            'uid' => 'REG-123',
            'last_name' => 'Garcia',
            'first_name' => 'Maria',
            'birthday' => '1990-05-15',
            'dept' => 'IT',
        ], $result);
    }

    /** @test */
    public function it_remaps_only_columns_that_exist_in_row()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Partial Mapping',
            'mappings' => [
                'Employee ID' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $row = [
            'Employee ID' => 'EMP-456',
            'Surname' => 'Lopez',
            // 'Given Name' is missing
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertArrayNotHasKey('first_name', $result);
        $this->assertEquals('EMP-456', $result['uid']);
        $this->assertEquals('Lopez', $result['last_name']);
    }

    // ========================================================================
    // Test handle missing columns gracefully
    // Requirements: 3.7
    // ========================================================================

    /** @test */
    public function it_ignores_template_mappings_for_missing_columns()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Template with Extra Mappings',
            'mappings' => [
                'ID' => 'uid',
                'Name' => 'last_name',
                'Email' => 'email',
                'Phone' => 'phone',
                'Address' => 'street',
            ],
        ]);

        $row = [
            'ID' => '001',
            'Name' => 'Reyes',
            // Email, Phone, Address are missing
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayNotHasKey('phone', $result);
        $this->assertArrayNotHasKey('street', $result);
        $this->assertEquals('001', $result['uid']);
        $this->assertEquals('Reyes', $result['last_name']);
    }

    /** @test */
    public function it_handles_empty_row_gracefully()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Any Template',
            'mappings' => [
                'Field1' => 'field1',
                'Field2' => 'field2',
            ],
        ]);

        $row = [];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_handles_row_with_no_matching_columns()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Mismatched Template',
            'mappings' => [
                'Column A' => 'field_a',
                'Column B' => 'field_b',
            ],
        ]);

        $row = [
            'Column X' => 'value_x',
            'Column Y' => 'value_y',
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_preserves_null_values_in_remapped_columns()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Null Value Template',
            'mappings' => [
                'Field1' => 'field1',
                'Field2' => 'field2',
            ],
        ]);

        $row = [
            'Field1' => null,
            'Field2' => 'value',
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertArrayHasKey('field1', $result);
        $this->assertArrayHasKey('field2', $result);
        $this->assertNull($result['field1']);
        $this->assertEquals('value', $result['field2']);
    }

    /** @test */
    public function it_preserves_empty_string_values_in_remapped_columns()
    {
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Empty String Template',
            'mappings' => [
                'Field1' => 'field1',
                'Field2' => 'field2',
            ],
        ]);

        $row = [
            'Field1' => '',
            'Field2' => 'value',
        ];

        $result = $this->service->applyTemplate($row, $template);

        $this->assertArrayHasKey('field1', $result);
        $this->assertArrayHasKey('field2', $result);
        $this->assertEquals('', $result['field1']);
        $this->assertEquals('value', $result['field2']);
    }

    // ========================================================================
    // Integration test: applyTemplate followed by mapUploadedData
    // ========================================================================

    /** @test */
    public function it_works_with_mapUploadedData_after_template_application()
    {
        // Template maps to Excel column variations that mapUploadedData recognizes
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Standard Import',
            'mappings' => [
                'Employee No' => 'regsno',
                'Surname' => 'surname',
                'Given Name' => 'firstname',
                'Department' => 'dept',
            ],
        ]);

        $row = [
            'Employee No' => 'EMP-789',
            'Surname' => 'Santos',
            'Given Name' => 'Pedro',
            'Department' => 'HR',
        ];

        // Apply template first
        $remapped = $this->service->applyTemplate($row, $template);

        // Then map to core/dynamic fields
        $result = $this->service->mapUploadedData($remapped);

        // uid, last_name, first_name should be core fields
        $this->assertArrayHasKey('uid', $result['core_fields']);
        $this->assertArrayHasKey('last_name', $result['core_fields']);
        $this->assertArrayHasKey('first_name', $result['core_fields']);
        
        // dept should be a dynamic field
        $this->assertArrayHasKey('dept', $result['dynamic_fields']);

        $this->assertEquals('Emp-789', $result['core_fields']['uid']);
        $this->assertEquals('Santos', $result['core_fields']['last_name']);
        $this->assertEquals('Pedro', $result['core_fields']['first_name']);
        $this->assertEquals('HR', $result['dynamic_fields']['dept']);
    }
}
