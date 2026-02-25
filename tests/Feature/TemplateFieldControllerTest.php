<?php

namespace Tests\Feature;

use App\Models\ColumnMappingTemplate;
use App\Models\TemplateField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ColumnMappingTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => ['FirstName' => 'first_name', 'LastName' => 'last_name'],
        ]);
    }

    public function test_index_returns_template_fields()
    {
        $field1 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id',
            'field_type' => 'integer',
            'is_required' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/templates/{$this->template->id}/fields");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_returns_404_for_nonexistent_template()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/templates/999/fields');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Template not found or you do not have permission to access it.',
            ]);
    }

    public function test_index_denies_access_to_other_users_template()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/templates/{$this->template->id}/fields");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Template not found or you do not have permission to access it.',
            ]);
    }

    public function test_store_creates_template_field()
    {
        $data = [
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$this->template->id}/fields", $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Template field created successfully',
                'data' => [
                    'field_name' => 'department',
                    'field_type' => 'string',
                    'is_required' => true,
                ],
            ]);

        $this->assertDatabaseHas('template_fields', [
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => true,
        ]);
    }

    public function test_store_validates_field_name_format()
    {
        $data = [
            'field_name' => 'invalid-name!',
            'field_type' => 'string',
            'is_required' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$this->template->id}/fields", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('field_name');
    }

    public function test_store_validates_field_type()
    {
        $data = [
            'field_name' => 'department',
            'field_type' => 'invalid_type',
            'is_required' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$this->template->id}/fields", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('field_type');
    }

    public function test_store_prevents_duplicate_field_names()
    {
        TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $data = [
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/templates/{$this->template->id}/fields", $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => "A field named 'department' already exists in this template. Please use a different name.",
            ]);
    }

    public function test_store_denies_access_to_other_users_template()
    {
        $otherUser = User::factory()->create();

        $data = [
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ];

        $response = $this->actingAs($otherUser)
            ->postJson("/api/templates/{$this->template->id}/fields", $data);

        $response->assertStatus(404);
    }

    public function test_update_modifies_template_field()
    {
        $field = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $data = [
            'field_name' => 'dept',
            'field_type' => 'string',
            'is_required' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/templates/{$this->template->id}/fields/{$field->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Template field updated successfully',
                'data' => [
                    'field_name' => 'dept',
                    'is_required' => true,
                ],
            ]);

        $this->assertDatabaseHas('template_fields', [
            'id' => $field->id,
            'field_name' => 'dept',
            'is_required' => true,
        ]);
    }

    public function test_update_prevents_duplicate_field_names()
    {
        $field1 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $field2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $data = ['field_name' => 'department'];

        $response = $this->actingAs($this->user)
            ->putJson("/api/templates/{$this->template->id}/fields/{$field2->id}", $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => "A field named 'department' already exists in this template. Please use a different name.",
            ]);
    }

    public function test_update_returns_404_for_nonexistent_field()
    {
        $data = ['field_name' => 'new_name'];

        $response = $this->actingAs($this->user)
            ->putJson("/api/templates/{$this->template->id}/fields/999", $data);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Template field not found.',
            ]);
    }

    public function test_destroy_deletes_template_field()
    {
        $field = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/templates/{$this->template->id}/fields/{$field->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Template field deleted successfully',
            ]);

        $this->assertDatabaseMissing('template_fields', [
            'id' => $field->id,
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent_field()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/templates/{$this->template->id}/fields/999");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Template field not found.',
            ]);
    }

    public function test_destroy_denies_access_to_other_users_template()
    {
        $otherUser = User::factory()->create();
        
        $field = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/templates/{$this->template->id}/fields/{$field->id}");

        $response->assertStatus(404);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/fields");
        $response->assertStatus(401);

        $response = $this->postJson("/api/templates/{$this->template->id}/fields", []);
        $response->assertStatus(401);

        $response = $this->putJson("/api/templates/{$this->template->id}/fields/1", []);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/templates/{$this->template->id}/fields/1");
        $response->assertStatus(401);
    }
}
