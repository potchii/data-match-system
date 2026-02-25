<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_field_has_fillable_fields(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => true,
        ]);
    }

    public function test_is_required_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => 1,
        ]);

        $this->assertIsBool($field->is_required);
        $this->assertTrue($field->is_required);
    }

    public function test_template_field_belongs_to_template(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $this->assertInstanceOf(ColumnMappingTemplate::class, $field->template);
        $this->assertEquals($template->id, $field->template->id);
    }

    public function test_validate_value_accepts_valid_string(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'name',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $result = $field->validateValue('John Doe');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_accepts_valid_integer(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'age',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue('25');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_rejects_decimal_for_integer(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'age',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue('25.5');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'age' must be an integer", $result['error']);
    }

    public function test_validate_value_accepts_valid_decimal(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'price',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $result = $field->validateValue('99.99');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_rejects_non_numeric_decimal(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'price',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $result = $field->validateValue('not a number');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'price' must be a number", $result['error']);
    }

    public function test_validate_value_accepts_valid_date(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'start_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $result = $field->validateValue('2024-01-15');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_rejects_invalid_date(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'start_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $result = $field->validateValue('not a date');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'start_date' must be a valid date", $result['error']);
    }

    public function test_validate_value_accepts_boolean_true_variations(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        $validValues = ['true', 'TRUE', '1', 'yes', 'YES', 'y', 'Y'];

        foreach ($validValues as $value) {
            $result = $field->validateValue($value);
            $this->assertTrue($result['valid'], "Failed for value: {$value}");
            $this->assertNull($result['error']);
        }
    }

    public function test_validate_value_accepts_boolean_false_variations(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        $validValues = ['false', 'FALSE', '0', 'no', 'NO', 'n', 'N'];

        foreach ($validValues as $value) {
            $result = $field->validateValue($value);
            $this->assertTrue($result['valid'], "Failed for value: {$value}");
            $this->assertNull($result['error']);
        }
    }

    public function test_validate_value_rejects_invalid_boolean(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        $result = $field->validateValue('maybe');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'is_active' must be true/false, yes/no, or 1/0", $result['error']);
    }

    public function test_validate_value_allows_empty_for_optional_field(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'optional_field',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $result = $field->validateValue('');

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_rejects_empty_for_required_field(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'required_field',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $result = $field->validateValue('');

        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'required_field' is required", $result['error']);
    }

    public function test_validate_value_allows_null_for_optional_field(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'optional_field',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue(null);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    // Tests for isValidFieldName() - Task 18.2

    public function test_is_valid_field_name_accepts_lowercase(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('fieldname'));
        $this->assertTrue(TemplateField::isValidFieldName('field'));
        $this->assertTrue(TemplateField::isValidFieldName('myfield'));
    }

    public function test_is_valid_field_name_accepts_uppercase(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('FIELDNAME'));
        $this->assertTrue(TemplateField::isValidFieldName('FIELD'));
        $this->assertTrue(TemplateField::isValidFieldName('MYFIELD'));
    }

    public function test_is_valid_field_name_accepts_mixed_case(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('FieldName'));
        $this->assertTrue(TemplateField::isValidFieldName('Field_Name_123'));
        $this->assertTrue(TemplateField::isValidFieldName('MyFieldName'));
        $this->assertTrue(TemplateField::isValidFieldName('camelCaseField'));
    }

    public function test_is_valid_field_name_accepts_with_underscores(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('field_name'));
        $this->assertTrue(TemplateField::isValidFieldName('my_field_name'));
        $this->assertTrue(TemplateField::isValidFieldName('field_1_2_3'));
        $this->assertTrue(TemplateField::isValidFieldName('_field'));
        $this->assertTrue(TemplateField::isValidFieldName('field_'));
        $this->assertTrue(TemplateField::isValidFieldName('___field___'));
    }

    public function test_is_valid_field_name_accepts_with_numbers(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('field123'));
        $this->assertTrue(TemplateField::isValidFieldName('field1'));
        $this->assertTrue(TemplateField::isValidFieldName('123field'));
        $this->assertTrue(TemplateField::isValidFieldName('field_123'));
        $this->assertTrue(TemplateField::isValidFieldName('123'));
        $this->assertTrue(TemplateField::isValidFieldName('field1name2'));
    }

    public function test_is_valid_field_name_rejects_spaces(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName('field name'));
        $this->assertFalse(TemplateField::isValidFieldName('my field'));
        $this->assertFalse(TemplateField::isValidFieldName(' field'));
        $this->assertFalse(TemplateField::isValidFieldName('field '));
        $this->assertFalse(TemplateField::isValidFieldName('field  name'));
    }

    public function test_is_valid_field_name_rejects_hyphens(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName('field-name'));
        $this->assertFalse(TemplateField::isValidFieldName('my-field'));
        $this->assertFalse(TemplateField::isValidFieldName('-field'));
        $this->assertFalse(TemplateField::isValidFieldName('field-'));
    }

    public function test_is_valid_field_name_rejects_special_characters(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName('field.name'));
        $this->assertFalse(TemplateField::isValidFieldName('field@name'));
        $this->assertFalse(TemplateField::isValidFieldName('field!'));
        $this->assertFalse(TemplateField::isValidFieldName('field#name'));
        $this->assertFalse(TemplateField::isValidFieldName('field$name'));
        $this->assertFalse(TemplateField::isValidFieldName('field%name'));
        $this->assertFalse(TemplateField::isValidFieldName('field^name'));
        $this->assertFalse(TemplateField::isValidFieldName('field&name'));
        $this->assertFalse(TemplateField::isValidFieldName('field*name'));
        $this->assertFalse(TemplateField::isValidFieldName('field(name)'));
        $this->assertFalse(TemplateField::isValidFieldName('field+name'));
        $this->assertFalse(TemplateField::isValidFieldName('field=name'));
        $this->assertFalse(TemplateField::isValidFieldName('field[name]'));
        $this->assertFalse(TemplateField::isValidFieldName('field{name}'));
        $this->assertFalse(TemplateField::isValidFieldName('field|name'));
        $this->assertFalse(TemplateField::isValidFieldName('field\\name'));
        $this->assertFalse(TemplateField::isValidFieldName('field/name'));
        $this->assertFalse(TemplateField::isValidFieldName('field:name'));
        $this->assertFalse(TemplateField::isValidFieldName('field;name'));
        $this->assertFalse(TemplateField::isValidFieldName('field"name'));
        $this->assertFalse(TemplateField::isValidFieldName("field'name"));
        $this->assertFalse(TemplateField::isValidFieldName('field<name>'));
        $this->assertFalse(TemplateField::isValidFieldName('field,name'));
        $this->assertFalse(TemplateField::isValidFieldName('field?name'));
    }

    public function test_is_valid_field_name_rejects_empty_string(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName(''));
    }

    public function test_is_valid_field_name_accepts_single_character(): void
    {
        $this->assertTrue(TemplateField::isValidFieldName('a'));
        $this->assertTrue(TemplateField::isValidFieldName('Z'));
        $this->assertTrue(TemplateField::isValidFieldName('_'));
        $this->assertTrue(TemplateField::isValidFieldName('1'));
    }

    public function test_is_valid_field_name_accepts_very_long_names(): void
    {
        $longName = str_repeat('a', 100);
        $this->assertTrue(TemplateField::isValidFieldName($longName));

        $veryLongName = str_repeat('field_', 42) . 'name';
        $this->assertTrue(TemplateField::isValidFieldName($veryLongName));

        $mixedLongName = str_repeat('Field_123_', 25);
        $this->assertTrue(TemplateField::isValidFieldName($mixedLongName));
    }

    public function test_is_valid_field_name_rejects_unicode_characters(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName('field_名前'));
        $this->assertFalse(TemplateField::isValidFieldName('поле'));
        $this->assertFalse(TemplateField::isValidFieldName('champ_été'));
        $this->assertFalse(TemplateField::isValidFieldName('field_ñame'));
        $this->assertFalse(TemplateField::isValidFieldName('field_café'));
        $this->assertFalse(TemplateField::isValidFieldName('field_日本'));
        $this->assertFalse(TemplateField::isValidFieldName('field_🎉'));
        $this->assertFalse(TemplateField::isValidFieldName('field_ü'));
    }

    public function test_is_valid_field_name_rejects_newlines_and_tabs(): void
    {
        $this->assertFalse(TemplateField::isValidFieldName("field\nname"));
        $this->assertFalse(TemplateField::isValidFieldName("field\tname"));
        $this->assertFalse(TemplateField::isValidFieldName("field\rname"));
        $this->assertFalse(TemplateField::isValidFieldName("field\r\nname"));
    }


    public function test_template_field_cascade_deletes_with_template(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $fieldId = $field->id;

        $template->delete();

        $this->assertDatabaseMissing('template_fields', [
            'id' => $fieldId,
        ]);
    }

    public function test_unique_constraint_on_template_id_and_field_name(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'custom_field',
            'field_type' => 'integer',
            'is_required' => true,
        ]);
    }

    public function test_same_field_name_allowed_for_different_templates(): void
    {
        $user = User::factory()->create();
        $template1 = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template 1',
            'mappings' => ['Col' => 'field'],
        ]);
        $template2 = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Template 2',
            'mappings' => ['Col' => 'field'],
        ]);

        $field1 = TemplateField::create([
            'template_id' => $template1->id,
            'field_name' => 'custom_field',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $template2->id,
            'field_name' => 'custom_field',
            'field_type' => 'integer',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field1->id,
            'template_id' => $template1->id,
            'field_name' => 'custom_field',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field2->id,
            'template_id' => $template2->id,
            'field_name' => 'custom_field',
        ]);
    }

    // Additional comprehensive tests for validateValue() edge cases

    public function test_validate_value_string_accepts_empty_string_for_optional(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'description',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $result = $field->validateValue('');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_string_accepts_special_characters(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'description',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $result = $field->validateValue('Hello! @#$%^&*()_+-=[]{}|;:,.<>?');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_string_accepts_numbers(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'code',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $result = $field->validateValue('12345');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_integer_accepts_zero(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'count',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue('0');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_integer_accepts_negative(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'balance',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue('-100');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_integer_rejects_non_numeric(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'age',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $result = $field->validateValue('abc');
        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'age' must be an integer", $result['error']);
    }

    public function test_validate_value_decimal_accepts_integer(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'price',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $result = $field->validateValue('100');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_decimal_accepts_zero(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'amount',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $result = $field->validateValue('0.00');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_decimal_accepts_negative(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'balance',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        $result = $field->validateValue('-50.25');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_date_accepts_various_formats(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'event_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $validDates = [
            '2024-01-15',
            '01/15/2024',
            'January 15, 2024',
            '15-Jan-2024',
            '2024/01/15',
        ];

        foreach ($validDates as $date) {
            $result = $field->validateValue($date);
            $this->assertTrue($result['valid'], "Failed for date format: {$date}");
            $this->assertNull($result['error']);
        }
    }

    public function test_validate_value_date_accepts_edge_dates(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'event_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $result = $field->validateValue('2024-02-29');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);

        $result = $field->validateValue('2000-01-01');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_validate_value_date_rejects_invalid_dates(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'event_date',
            'field_type' => 'date',
            'is_required' => false,
        ]);

        $invalidDates = [
            'not-a-date',
            'abc123',
            'invalid',
            'xyz',
            'hello world',
        ];

        foreach ($invalidDates as $date) {
            $result = $field->validateValue($date);
            $this->assertFalse($result['valid'], "Should reject invalid date: {$date}");
            $this->assertEquals("Field 'event_date' must be a valid date", $result['error']);
        }
    }

    public function test_validate_value_boolean_accepts_with_whitespace(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        $valuesWithWhitespace = [' true ', '  yes  ', ' 1 ', '  no  '];

        foreach ($valuesWithWhitespace as $value) {
            $result = $field->validateValue($value);
            $this->assertTrue($result['valid'], "Failed for value with whitespace: '{$value}'");
            $this->assertNull($result['error']);
        }
    }

    public function test_validate_value_boolean_rejects_partial_matches(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
            'is_required' => false,
        ]);

        $invalidValues = ['t', 'f', 'ye', 'tr', 'fals', 'yep', 'nope', '2', '-1'];

        foreach ($invalidValues as $value) {
            $result = $field->validateValue($value);
            $this->assertFalse($result['valid'], "Should reject partial match: {$value}");
            $this->assertEquals("Field 'is_active' must be true/false, yes/no, or 1/0", $result['error']);
        }
    }

    public function test_validate_value_required_field_rejects_null(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'required_field',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $result = $field->validateValue(null);
        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'required_field' is required", $result['error']);
    }

    public function test_validate_value_required_integer_rejects_empty(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'required_count',
            'field_type' => 'integer',
            'is_required' => true,
        ]);

        $result = $field->validateValue('');
        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'required_count' is required", $result['error']);
    }

    public function test_validate_value_required_date_rejects_empty(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'required_date',
            'field_type' => 'date',
            'is_required' => true,
        ]);

        $result = $field->validateValue('');
        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'required_date' is required", $result['error']);
    }

    public function test_validate_value_required_boolean_rejects_empty(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'required_flag',
            'field_type' => 'boolean',
            'is_required' => true,
        ]);

        $result = $field->validateValue('');
        $this->assertFalse($result['valid']);
        $this->assertEquals("Field 'required_flag' is required", $result['error']);
    }

    public function test_validate_value_optional_fields_accept_null_for_all_types(): void
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $types = ['string', 'integer', 'decimal', 'date', 'boolean'];

        foreach ($types as $type) {
            $field = TemplateField::create([
                'template_id' => $template->id,
                'field_name' => "optional_{$type}",
                'field_type' => $type,
                'is_required' => false,
            ]);

            $result = $field->validateValue(null);
            $this->assertTrue($result['valid'], "Failed for optional {$type} with null");
            $this->assertNull($result['error']);
        }
    }
}
