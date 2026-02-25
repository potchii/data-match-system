<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColumnMappingTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_has_fillable_fields(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Excel Col' => 'system_field'],
        ]);

        $this->assertDatabaseHas('column_mapping_templates', [
            'user_id' => $user->id,
            'name' => 'Test Template',
        ]);
        
        $this->assertEquals(['Excel Col' => 'system_field'], $template->mappings);
    }

    public function test_mappings_are_cast_to_array(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col1' => 'field1', 'Col2' => 'field2'],
        ]);

        $this->assertIsArray($template->mappings);
        $this->assertEquals('field1', $template->mappings['Col1']);
        $this->assertEquals('field2', $template->mappings['Col2']);
    }

    public function test_template_belongs_to_user(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $this->assertInstanceOf(User::class, $template->user);
        $this->assertEquals($user->id, $template->user->id);
    }

    public function test_for_user_returns_only_user_templates(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        ColumnMappingTemplate::create([
            'user_id' => $user1->id,
            'name' => 'User 1 Template',
            'mappings' => ['Col' => 'field'],
        ]);
        
        ColumnMappingTemplate::create([
            'user_id' => $user2->id,
            'name' => 'User 2 Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $user1Templates = ColumnMappingTemplate::forUser($user1->id);
        
        $this->assertCount(1, $user1Templates);
        $this->assertEquals('User 1 Template', $user1Templates->first()->name);
    }

    public function test_apply_to_remaps_columns_correctly(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $row = [
            'Employee No' => '12345',
            'Surname' => 'Cruz',
            'Given Name' => 'Juan',
            'Other Column' => 'Value',
        ];

        $remapped = $template->applyTo($row);

        $this->assertEquals('12345', $remapped['uid']);
        $this->assertEquals('Cruz', $remapped['last_name']);
        $this->assertEquals('Juan', $remapped['first_name']);
        $this->assertArrayNotHasKey('Other Column', $remapped);
    }

    public function test_apply_to_ignores_missing_columns(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Department' => 'dept',
            ],
        ]);

        $row = [
            'Employee No' => '12345',
            'Surname' => 'Cruz',
        ];

        $remapped = $template->applyTo($row);

        $this->assertEquals('12345', $remapped['uid']);
        $this->assertEquals('Cruz', $remapped['last_name']);
        $this->assertArrayNotHasKey('dept', $remapped);
    }

    public function test_validate_mappings_returns_true_for_valid_structure(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col1' => 'field1', 'Col2' => 'field2'],
        ]);

        $this->assertTrue($template->validateMappings());
    }

    public function test_validate_mappings_returns_false_for_non_array(): void
    {
        $user = User::factory()->create();
        
        $template = new ColumnMappingTemplate([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => 'not an array',
        ]);

        $this->assertFalse($template->validateMappings());
    }

    public function test_validate_mappings_returns_false_for_non_string_values(): void
    {
        $user = User::factory()->create();
        
        $template = new ColumnMappingTemplate([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col1' => 123],
        ]);

        $this->assertFalse($template->validateMappings());
    }

    public function test_validation_rules_enforce_unique_name_per_user(): void
    {
        $user = User::factory()->create();
        
        ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Existing Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $rules = ColumnMappingTemplate::validationRules($user->id);
        
        $this->assertStringContainsString('unique:column_mapping_templates', $rules['name'][3]);
        $this->assertStringContainsString('user_id,' . $user->id, $rules['name'][3]);
    }

    public function test_validation_rules_allow_same_name_for_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        ColumnMappingTemplate::create([
            'user_id' => $user1->id,
            'name' => 'Same Name',
            'mappings' => ['Col' => 'field'],
        ]);

        $template2 = ColumnMappingTemplate::create([
            'user_id' => $user2->id,
            'name' => 'Same Name',
            'mappings' => ['Col' => 'field'],
        ]);

        $this->assertDatabaseHas('column_mapping_templates', [
            'user_id' => $user2->id,
            'name' => 'Same Name',
        ]);
    }

    public function test_validation_rules_exclude_current_template_on_update(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $rules = ColumnMappingTemplate::validationRules($user->id, $template->id);
        
        $this->assertStringContainsString('unique:column_mapping_templates,name,' . $template->id, $rules['name'][3]);
    }

    public function test_user_has_column_mapping_templates_relationship(): void
    {
        $user = User::factory()->create();
        
        ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template 1',
            'mappings' => ['Col' => 'field'],
        ]);
        
        ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template 2',
            'mappings' => ['Col' => 'field'],
        ]);

        $this->assertCount(2, $user->columnMappingTemplates);
    }

    public function test_template_has_fields_relationship(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field1 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field_1',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field_2',
            'field_type' => 'integer',
            'is_required' => true,
        ]);

        $this->assertCount(2, $template->fields);
        $this->assertEquals('custom_field_1', $template->fields[0]->field_name);
        $this->assertEquals('custom_field_2', $template->fields[1]->field_name);
    }

    public function test_get_expected_columns_returns_core_columns_only(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $expected = $template->getExpectedColumns();

        $this->assertCount(3, $expected);
        $this->assertContains('Employee No', $expected);
        $this->assertContains('Surname', $expected);
        $this->assertContains('Given Name', $expected);
    }

    public function test_get_expected_columns_includes_template_fields(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $expected = $template->getExpectedColumns();

        $this->assertCount(4, $expected);
        $this->assertContains('Employee No', $expected);
        $this->assertContains('Surname', $expected);
        $this->assertContains('department', $expected);
        $this->assertContains('salary', $expected);
    }

    public function test_get_expected_columns_with_empty_mappings(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Empty Template',
            'mappings' => [],
        ]);

        $expected = $template->getExpectedColumns();

        $this->assertIsArray($expected);
        $this->assertCount(0, $expected);
        $this->assertEmpty($expected);
    }

    public function test_get_expected_columns_with_empty_mappings_but_template_fields(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template with Only Custom Fields',
            'mappings' => [],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field_1',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field_2',
            'field_type' => 'integer',
            'is_required' => true,
        ]);

        $expected = $template->getExpectedColumns();

        $this->assertIsArray($expected);
        $this->assertCount(2, $expected);
        $this->assertContains('custom_field_1', $expected);
        $this->assertContains('custom_field_2', $expected);
        $this->assertNotContains('Employee No', $expected);
    }

    public function test_get_expected_columns_merges_core_and_template_correctly(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Comprehensive Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'hire_date',
            'field_type' => 'date',
            'is_required' => true,
        ]);

        $expected = $template->getExpectedColumns();

        // Verify array structure
        $this->assertIsArray($expected);
        $this->assertCount(6, $expected);
        
        // Verify core columns come first
        $this->assertEquals('Employee No', $expected[0]);
        $this->assertEquals('Surname', $expected[1]);
        $this->assertEquals('Given Name', $expected[2]);
        
        // Verify template fields come after
        $this->assertContains('department', $expected);
        $this->assertContains('salary', $expected);
        $this->assertContains('hire_date', $expected);
        
        // Verify no duplicates
        $this->assertCount(6, array_unique($expected));
    }

    public function test_validate_file_columns_passes_with_exact_match(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        $fileColumns = ['Employee No', 'Surname'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['missing']);
        $this->assertEmpty($result['extra']);
    }

    public function test_validate_file_columns_detects_missing_columns(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $fileColumns = ['Employee No', 'Surname'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Missing required column: given name', $result['errors'][0]);
        $this->assertCount(1, $result['missing']);
        $this->assertContains('given name', $result['missing']);
    }

    public function test_validate_file_columns_detects_extra_columns(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        $fileColumns = ['Employee No', 'Surname', 'Extra Column'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Unexpected column: extra column', $result['errors'][0]);
        $this->assertCount(1, $result['extra']);
        $this->assertContains('extra column', $result['extra']);
    }

    public function test_validate_file_columns_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        $fileColumns = ['employee no', 'SURNAME'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_file_columns_with_template_fields(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $fileColumns = ['Employee No', 'Surname', 'department'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_file_columns_detects_missing_template_field(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
            ],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $fileColumns = ['Employee No'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Missing required column: department', $result['errors'][0]);
    }

    public function test_validate_file_columns_returns_all_errors(): void
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ]);

        $fileColumns = ['Employee No', 'Extra Column'];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertFalse($result['valid']);
        $this->assertCount(3, $result['errors']); // 2 missing + 1 extra
        $this->assertCount(2, $result['missing']);
        $this->assertCount(1, $result['extra']);
    }

    public function test_validate_file_columns_with_empty_array(): void
    {
        $user = User::factory()->create();

        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        $fileColumns = [];
        $result = $template->validateFileColumns($fileColumns);

        $this->assertFalse($result['valid']);
        $this->assertCount(2, $result['errors']);
        $this->assertCount(2, $result['missing']);
        $this->assertEmpty($result['extra']);
        $this->assertContains('employee no', $result['missing']);
        $this->assertContains('surname', $result['missing']);
    }

    public function test_validate_file_columns_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
            ],
        ]);

        $fileColumns = ['Employee No', 'Surname', 'Extra'];
        $result = $template->validateFileColumns($fileColumns);

        // Verify structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('expected', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertArrayHasKey('extra', $result);

        // Verify types
        $this->assertIsBool($result['valid']);
        $this->assertIsArray($result['errors']);
        $this->assertIsArray($result['expected']);
        $this->assertIsArray($result['missing']);
        $this->assertIsArray($result['extra']);

        // Verify content
        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['extra']);
        $this->assertContains('extra', $result['extra']);
    }

}
