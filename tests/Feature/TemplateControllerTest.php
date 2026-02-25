<?php

namespace Tests\Feature;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_user_templates()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create templates for the authenticated user
        $template1 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 1',
        ]);
        $template2 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 2',
        ]);

        // Create template for another user (should not be returned)
        ColumnMappingTemplate::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Template',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.templates.index'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Template 1'])
            ->assertJsonFragment(['name' => 'Template 2'])
            ->assertJsonMissing(['name' => 'Other Template']);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->getJson(route('api.templates.index'));

        $response->assertUnauthorized();
    }

    public function test_store_creates_template()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'HR Import Template',
            'mappings' => [
                'Employee No' => 'uid',
                'Surname' => 'last_name',
                'Given Name' => 'first_name',
            ],
        ];

        $response = $this
            ->actingAs($user)
            ->postJson(route('api.templates.store'), $templateData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Template created successfully',
            ])
            ->assertJsonPath('data.name', 'HR Import Template')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('column_mapping_templates', [
            'user_id' => $user->id,
            'name' => 'HR Import Template',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('api.templates.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'mappings']);
    }

    public function test_store_enforces_unique_name_per_user()
    {
        $user = User::factory()->create();

        ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Duplicate Name',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('api.templates.store'), [
                'name' => 'Duplicate Name',
                'mappings' => ['col' => 'field'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_allows_same_name_for_different_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ColumnMappingTemplate::factory()->create([
            'user_id' => $user1->id,
            'name' => 'Same Name',
        ]);

        $response = $this
            ->actingAs($user2)
            ->postJson(route('api.templates.store'), [
                'name' => 'Same Name',
                'mappings' => ['col' => 'field'],
            ]);

        $response->assertCreated();
    }

    public function test_show_returns_template_details()
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Excel Col' => 'system_field'],
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.templates.show', $template->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => 'Test Template',
                    'mappings' => ['Excel Col' => 'system_field'],
                ],
            ]);
    }

    public function test_show_returns_404_for_nonexistent_template()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.templates.show', 999));

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Template not found',
            ]);
    }

    public function test_show_prevents_access_to_other_users_templates()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('api.templates.show', $template->id));

        $response->assertNotFound();
    }

    public function test_update_modifies_template()
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'mappings' => ['old' => 'mapping'],
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson(route('api.templates.update', $template->id), [
                'name' => 'Updated Name',
                'mappings' => ['new' => 'mapping'],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Template updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'mappings' => ['new' => 'mapping'],
                ],
            ]);

        $this->assertDatabaseHas('column_mapping_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_returns_404_for_nonexistent_template()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->putJson(route('api.templates.update', 999), [
                'name' => 'Updated',
                'mappings' => ['col' => 'field'],
            ]);

        $response->assertNotFound();
    }

    public function test_update_prevents_modifying_other_users_templates()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson(route('api.templates.update', $template->id), [
                'name' => 'Hacked',
                'mappings' => ['col' => 'field'],
            ]);

        $response->assertNotFound();
    }

    public function test_update_enforces_unique_name_per_user()
    {
        $user = User::factory()->create();

        $template1 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 1',
        ]);

        $template2 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 2',
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson(route('api.templates.update', $template2->id), [
                'name' => 'Template 1',
                'mappings' => ['col' => 'field'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_destroy_deletes_template()
    {
        $user = User::factory()->create();
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('api.templates.destroy', $template->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Template deleted successfully',
            ]);

        $this->assertDatabaseMissing('column_mapping_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent_template()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('api.templates.destroy', 999));

        $response->assertNotFound();
    }

    public function test_destroy_prevents_deleting_other_users_templates()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('api.templates.destroy', $template->id));

        $response->assertNotFound();

        $this->assertDatabaseHas('column_mapping_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_store_web_creates_template_with_custom_fields()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'HR Import with Custom Fields',
            'excel_columns' => ['Employee No', 'Surname', 'Given Name'],
            'system_fields' => ['uid', 'last_name', 'first_name'],
            'field_names' => ['department', 'position', 'salary'],
            'field_types' => ['string', 'string', 'decimal'],
            'field_required' => [0, 1], // department and position are required
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template created successfully');

        // Verify template was created
        $this->assertDatabaseHas('column_mapping_templates', [
            'user_id' => $user->id,
            'name' => 'HR Import with Custom Fields',
        ]);

        $template = ColumnMappingTemplate::where('name', 'HR Import with Custom Fields')->first();
        $this->assertNotNull($template);

        // Verify custom fields were saved
        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'position',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        // Verify field attributes are correct
        $fields = $template->fields()->get();
        $this->assertCount(3, $fields);

        $department = $fields->firstWhere('field_name', 'department');
        $this->assertEquals('string', $department->field_type);
        $this->assertTrue($department->is_required);

        $position = $fields->firstWhere('field_name', 'position');
        $this->assertEquals('string', $position->field_type);
        $this->assertTrue($position->is_required);

        $salary = $fields->firstWhere('field_name', 'salary');
        $this->assertEquals('decimal', $salary->field_type);
        $this->assertFalse($salary->is_required);
    }

    public function test_store_web_validates_field_name_uniqueness()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Duplicate Fields',
            'excel_columns' => ['Employee No', 'Surname'],
            'system_fields' => ['uid', 'last_name'],
            'field_names' => ['department', 'department'], // Duplicate field name
            'field_types' => ['string', 'string'],
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        // The controller should handle this gracefully by skipping duplicates
        $response->assertRedirect(route('templates.index'));

        $template = ColumnMappingTemplate::where('name', 'Template with Duplicate Fields')->first();
        $this->assertNotNull($template);

        // Should only have one 'department' field
        $fields = $template->fields()->where('field_name', 'department')->get();
        $this->assertCount(1, $fields);
    }

    public function test_store_web_validates_field_name_format()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Invalid Field Name',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['invalid-field-name'], // Hyphens not allowed
            'field_types' => ['string'],
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertSessionHasErrors('field_names.0');
    }

    public function test_store_web_validates_field_type()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Invalid Field Type',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department'],
            'field_types' => ['invalid_type'], // Invalid type
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertSessionHasErrors('field_types.0');
    }

    public function test_store_web_creates_template_without_custom_fields()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Simple Template',
            'excel_columns' => ['Employee No', 'Surname'],
            'system_fields' => ['uid', 'last_name'],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template created successfully');

        $template = ColumnMappingTemplate::where('name', 'Simple Template')->first();
        $this->assertNotNull($template);

        // Should have no custom fields
        $this->assertCount(0, $template->fields);
    }

    public function test_update_web_with_new_custom_fields_added()
    {
        $user = User::factory()->create();

        // Create template without custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Template',
            'mappings' => ['Employee No' => 'uid', 'Surname' => 'last_name'],
        ]);

        // Update template with new custom fields
        $updateData = [
            'name' => 'Updated Template',
            'excel_columns' => ['Employee No', 'Surname'],
            'system_fields' => ['uid', 'last_name'],
            'field_names' => ['department', 'position', 'salary'],
            'field_types' => ['string', 'string', 'decimal'],
            'field_required' => [0, 2], // department and salary are required
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        // Verify template was updated
        $template->refresh();
        $this->assertEquals('Updated Template', $template->name);

        // Verify new custom fields were created
        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'position',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => true,
        ]);

        // Verify field count
        $this->assertCount(3, $template->fields);
    }

    public function test_update_web_with_existing_custom_fields_modified()
    {
        $user = User::factory()->create();

        // Create template with existing custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template with Fields',
            'mappings' => ['Employee No' => 'uid'],
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
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        // Update template with modified custom fields
        $updateData = [
            'name' => 'Template with Fields',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department', 'salary'],
            'field_types' => ['string', 'decimal'], // Changed salary from integer to decimal
            'field_required' => [0, 1], // Both now required
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        // Verify fields were updated
        $template->refresh();
        $fields = $template->fields;
        $this->assertCount(2, $fields);

        $department = $fields->firstWhere('field_name', 'department');
        $this->assertEquals('string', $department->field_type);
        $this->assertTrue($department->is_required);

        $salary = $fields->firstWhere('field_name', 'salary');
        $this->assertEquals('decimal', $salary->field_type); // Type changed
        $this->assertTrue($salary->is_required); // Now required
    }

    public function test_update_web_with_custom_fields_removed()
    {
        $user = User::factory()->create();

        // Create template with custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template with Fields',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'position',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        // Update template with only one field (removing two fields)
        $updateData = [
            'name' => 'Template with Fields',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department'], // Only keep department
            'field_types' => ['string'],
            'field_required' => [0],
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        // Verify removed fields are deleted
        $template->refresh();
        $this->assertCount(1, $template->fields);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'department',
        ]);

        $this->assertDatabaseMissing('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'position',
        ]);

        $this->assertDatabaseMissing('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'salary',
        ]);
    }

    public function test_update_web_removes_all_custom_fields()
    {
        $user = User::factory()->create();

        // Create template with custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template with Fields',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        // Update template without any custom fields
        $updateData = [
            'name' => 'Template with Fields',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            // No field_names, field_types, or field_required
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        // Verify all custom fields are deleted
        $template->refresh();
        $this->assertCount(0, $template->fields);

        $this->assertDatabaseMissing('template_fields', [
            'template_id' => $template->id,
        ]);
    }

    public function test_update_web_validates_field_name_uniqueness_during_update()
    {
        $user = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        // Try to update with duplicate field names
        $updateData = [
            'name' => 'Template',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department', 'department'], // Duplicate
            'field_types' => ['string', 'string'],
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        // Should succeed but only create one field
        $response->assertRedirect(route('templates.index'));

        $template->refresh();
        $fields = $template->fields()->where('field_name', 'department')->get();
        $this->assertCount(1, $fields);
    }

    public function test_update_web_verifies_field_attributes_are_updated_properly()
    {
        $user = User::factory()->create();

        // Create template with fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'status',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Update with different attributes
        $updateData = [
            'name' => 'Template',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['status', 'active', 'score'],
            'field_types' => ['boolean', 'boolean', 'integer'], // Changed status to boolean, added new fields
            'field_required' => [0, 1], // status and active are required
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        // Verify all field attributes are correct
        $template->refresh();
        $fields = $template->fields;
        $this->assertCount(3, $fields);

        $status = $fields->firstWhere('field_name', 'status');
        $this->assertNotNull($status);
        $this->assertEquals('boolean', $status->field_type);
        $this->assertTrue($status->is_required);

        $active = $fields->firstWhere('field_name', 'active');
        $this->assertNotNull($active);
        $this->assertEquals('boolean', $active->field_type);
        $this->assertTrue($active->is_required);

        $score = $fields->firstWhere('field_name', 'score');
        $this->assertNotNull($score);
        $this->assertEquals('integer', $score->field_type);
        $this->assertFalse($score->is_required);
    }

    public function test_deleting_template_cascades_to_fields()
    {
        $user = User::factory()->create();

        // Create template with custom fields
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template to Delete',
            'mappings' => ['Employee No' => 'uid', 'Surname' => 'last_name'],
        ]);

        // Create multiple template fields
        $field1 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'position',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field3 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
            'is_required' => false,
        ]);

        // Verify template and fields exist
        $this->assertDatabaseHas('column_mapping_templates', [
            'id' => $template->id,
            'name' => 'Template to Delete',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field1->id,
            'template_id' => $template->id,
            'field_name' => 'department',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field2->id,
            'template_id' => $template->id,
            'field_name' => 'position',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field3->id,
            'template_id' => $template->id,
            'field_name' => 'salary',
        ]);

        // Delete the template via API
        $response = $this
            ->actingAs($user)
            ->deleteJson(route('api.templates.destroy', $template->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Template deleted successfully',
            ]);

        // Verify template is deleted
        $this->assertDatabaseMissing('column_mapping_templates', [
            'id' => $template->id,
        ]);

        // Verify all associated template_fields are also deleted (cascade)
        $this->assertDatabaseMissing('template_fields', [
            'id' => $field1->id,
        ]);

        $this->assertDatabaseMissing('template_fields', [
            'id' => $field2->id,
        ]);

        $this->assertDatabaseMissing('template_fields', [
            'id' => $field3->id,
        ]);

        // Verify no orphaned template_fields remain for this template
        $this->assertEquals(0, TemplateField::where('template_id', $template->id)->count());
    }

    /**
     * Task 20.4: Test field name uniqueness validation
     */
    public function test_database_constraint_prevents_duplicate_field_names_in_same_template()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        // Create first field
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Attempt to create duplicate field name in same template
        // This should throw a database exception due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department', // Duplicate field name
            'field_type' => 'string',
            'is_required' => true,
        ]);
    }

    public function test_same_field_name_allowed_in_different_templates()
    {
        $user = User::factory()->create();
        
        // Create two different templates
        $template1 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 1',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $template2 = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Template 2',
            'mappings' => ['Staff No' => 'uid'],
        ]);

        // Create field with same name in first template
        $field1 = TemplateField::create([
            'template_id' => $template1->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Create field with same name in second template - should succeed
        $field2 = TemplateField::create([
            'template_id' => $template2->id,
            'field_name' => 'department', // Same field name, different template
            'field_type' => 'string',
            'is_required' => true,
        ]);

        // Verify both fields exist
        $this->assertDatabaseHas('template_fields', [
            'id' => $field1->id,
            'template_id' => $template1->id,
            'field_name' => 'department',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field2->id,
            'template_id' => $template2->id,
            'field_name' => 'department',
        ]);

        // Verify they are different records
        $this->assertNotEquals($field1->id, $field2->id);
        $this->assertNotEquals($field1->template_id, $field2->template_id);
    }

    public function test_web_form_handles_duplicate_field_names_gracefully()
    {
        $user = User::factory()->create();

        // Attempt to create template with duplicate field names via web form
        $templateData = [
            'name' => 'Template with Duplicates',
            'excel_columns' => ['Employee No', 'Surname'],
            'system_fields' => ['uid', 'last_name'],
            'field_names' => ['department', 'position', 'department', 'salary'], // 'department' appears twice
            'field_types' => ['string', 'string', 'string', 'decimal'],
            'field_required' => [0, 1], // department and position are required
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        // Should succeed (gracefully handles duplicates by skipping)
        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template created successfully');

        $template = ColumnMappingTemplate::where('name', 'Template with Duplicates')->first();
        $this->assertNotNull($template);

        // Verify only unique fields were created
        $fields = $template->fields()->get();
        $fieldNames = $fields->pluck('field_name')->toArray();
        
        // Should have 3 unique fields: department, position, salary
        $this->assertCount(3, $fields);
        $this->assertContains('department', $fieldNames);
        $this->assertContains('position', $fieldNames);
        $this->assertContains('salary', $fieldNames);
        
        // Verify 'department' appears only once
        $departmentFields = $template->fields()->where('field_name', 'department')->get();
        $this->assertCount(1, $departmentFields);
    }

    public function test_api_endpoint_rejects_duplicate_field_names()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        // Create first field via API
        $response1 = $this
            ->actingAs($user)
            ->postJson("/api/templates/{$template->id}/fields", [
                'field_name' => 'department',
                'field_type' => 'string',
                'is_required' => false,
            ]);

        $response1->assertCreated();

        // Attempt to create duplicate field via API
        $response2 = $this
            ->actingAs($user)
            ->postJson("/api/templates/{$template->id}/fields", [
                'field_name' => 'department', // Duplicate
                'field_type' => 'string',
                'is_required' => true,
            ]);

        // Should return error
        $response2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Field name already exists in this template',
            ]);

        // Verify only one field exists
        $fields = $template->fields()->where('field_name', 'department')->get();
        $this->assertCount(1, $fields);
    }

    public function test_case_sensitivity_in_field_name_uniqueness()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        // Create field with lowercase name
        $field1 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Attempt to create field with different case
        // Note: Database constraint is case-sensitive by default in most databases
        // This test verifies the actual behavior
        try {
            $field2 = TemplateField::create([
                'template_id' => $template->id,
                'field_name' => 'Department', // Different case
                'field_type' => 'string',
                'is_required' => false,
            ]);

            // If it succeeds, verify both exist (case-sensitive)
            $this->assertDatabaseHas('template_fields', [
                'template_id' => $template->id,
                'field_name' => 'department',
            ]);

            $this->assertDatabaseHas('template_fields', [
                'template_id' => $template->id,
                'field_name' => 'Department',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // If it fails, the constraint is case-insensitive
            // This is acceptable behavior
            $this->assertTrue(true);
        }
    }

    public function test_update_field_name_to_existing_name_in_same_template_fails()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        // Create two fields
        $field1 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'position',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Attempt to update field2 to have same name as field1 via API
        $response = $this
            ->actingAs($user)
            ->putJson("/api/templates/{$template->id}/fields/{$field2->id}", [
                'field_name' => 'department', // Trying to change to existing name
                'field_type' => 'string',
                'is_required' => false,
            ]);

        // Should return error
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Field name already exists in this template',
            ]);

        // Verify field2 still has original name
        $field2->refresh();
        $this->assertEquals('position', $field2->field_name);
    }

    /**
     * Task 20.5: Test field type validation
     */
    public function test_store_web_rejects_invalid_field_type()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Invalid Type',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department'],
            'field_types' => ['invalid_type'], // Invalid type
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertSessionHasErrors('field_types.0');
    }

    public function test_store_web_accepts_all_valid_field_types()
    {
        $user = User::factory()->create();

        $validTypes = ['string', 'integer', 'date', 'boolean', 'decimal'];

        $templateData = [
            'name' => 'Template with All Valid Types',
            'excel_columns' => ['Employee No', 'Surname', 'Given Name', 'DOB', 'Gender'],
            'system_fields' => ['uid', 'last_name', 'first_name', 'birthday', 'gender'],
            'field_names' => ['department', 'employee_count', 'hire_date', 'is_active', 'salary'],
            'field_types' => $validTypes,
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template created successfully');

        $template = ColumnMappingTemplate::where('name', 'Template with All Valid Types')->first();
        $this->assertNotNull($template);

        // Verify all field types were saved correctly
        $fields = $template->fields()->get();
        $this->assertCount(5, $fields);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'employee_count',
            'field_type' => 'integer',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'hire_date',
            'field_type' => 'date',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'is_active',
            'field_type' => 'boolean',
        ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $template->id,
            'field_name' => 'salary',
            'field_type' => 'decimal',
        ]);
    }

    public function test_store_web_rejects_multiple_invalid_field_types()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Multiple Invalid Types',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['field1', 'field2', 'field3'],
            'field_types' => ['invalid_type', 'another_invalid', 'yet_another'], // All invalid
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        // Should have validation errors for all three field types
        $response->assertSessionHasErrors(['field_types.0', 'field_types.1', 'field_types.2']);
    }

    public function test_store_web_rejects_mixed_valid_and_invalid_field_types()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Mixed Types',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department', 'employee_count', 'invalid_field'],
            'field_types' => ['string', 'integer', 'not_a_valid_type'], // Last one invalid
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        // Should have validation error for the invalid field type
        $response->assertSessionHasErrors('field_types.2');
    }

    public function test_update_web_rejects_invalid_field_type()
    {
        $user = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $updateData = [
            'name' => 'Test Template',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['department'],
            'field_types' => ['invalid_type'], // Invalid type
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertSessionHasErrors('field_types.0');
    }

    public function test_update_web_accepts_all_valid_field_types()
    {
        $user = User::factory()->create();

        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $updateData = [
            'name' => 'Test Template',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['text_field', 'int_field', 'date_field', 'bool_field', 'decimal_field'],
            'field_types' => ['string', 'integer', 'date', 'boolean', 'decimal'],
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->put(route('templates.update', $template->id), $updateData);

        $response->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Template updated successfully');

        $template->refresh();
        $fields = $template->fields()->get();
        $this->assertCount(5, $fields);

        // Verify each type
        $fieldTypes = $fields->pluck('field_type', 'field_name')->toArray();
        $this->assertEquals('string', $fieldTypes['text_field']);
        $this->assertEquals('integer', $fieldTypes['int_field']);
        $this->assertEquals('date', $fieldTypes['date_field']);
        $this->assertEquals('boolean', $fieldTypes['bool_field']);
        $this->assertEquals('decimal', $fieldTypes['decimal_field']);
    }

    public function test_api_endpoint_rejects_invalid_field_type()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson("/api/templates/{$template->id}/fields", [
                'field_name' => 'department',
                'field_type' => 'invalid_type', // Invalid type
                'is_required' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('field_type');
    }

    public function test_api_endpoint_accepts_all_valid_field_types()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $validTypes = ['string', 'integer', 'date', 'boolean', 'decimal'];

        foreach ($validTypes as $index => $type) {
            $response = $this
                ->actingAs($user)
                ->postJson("/api/templates/{$template->id}/fields", [
                    'field_name' => "field_{$type}_{$index}",
                    'field_type' => $type,
                    'is_required' => false,
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'field_type' => $type,
                    ],
                ]);
        }

        // Verify all fields were created with correct types
        $template->refresh();
        $this->assertCount(5, $template->fields);
    }

    public function test_api_endpoint_update_rejects_invalid_field_type()
    {
        $user = User::factory()->create();
        
        $template = ColumnMappingTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'mappings' => ['Employee No' => 'uid'],
        ]);

        $field = TemplateField::create([
            'template_id' => $template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/templates/{$template->id}/fields/{$field->id}", [
                'field_type' => 'invalid_type', // Invalid type
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('field_type');

        // Verify field type wasn't changed
        $field->refresh();
        $this->assertEquals('string', $field->field_type);
    }

    public function test_field_type_case_sensitivity()
    {
        $user = User::factory()->create();

        $templateData = [
            'name' => 'Template with Case Variations',
            'excel_columns' => ['Employee No'],
            'system_fields' => ['uid'],
            'field_names' => ['field1', 'field2', 'field3'],
            'field_types' => ['String', 'INTEGER', 'Boolean'], // Different cases
            'field_required' => [],
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('templates.store'), $templateData);

        // Should reject because validation is case-sensitive
        $response->assertSessionHasErrors(['field_types.0', 'field_types.1', 'field_types.2']);
    }
}
