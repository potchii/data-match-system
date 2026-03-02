<?php

namespace Tests\Unit;

use App\Models\ColumnMappingTemplate;
use App\Models\MainSystem;
use App\Models\TemplateField;
use App\Models\TemplateFieldValue;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateFieldValueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ColumnMappingTemplate $template;
    private TemplateField $templateField;
    private MainSystem $mainSystem;
    private UploadBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->template = ColumnMappingTemplate::create([
            'user_id' => $this->user->id,
            'name' => 'Test Template',
            'mappings' => ['Col' => 'field'],
        ]);

        $this->templateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $this->mainSystem = MainSystem::factory()->create();

        $this->batch = UploadBatch::factory()->create([
            'uploaded_by' => $this->user->id,
        ]);
    }

    // Property 4: Model Relationship Integrity
    public function test_template_field_value_belongs_to_main_system(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(MainSystem::class, $tfv->mainSystem);
        $this->assertEquals($this->mainSystem->id, $tfv->mainSystem->id);
    }

    public function test_template_field_value_belongs_to_template_field(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(TemplateField::class, $tfv->templateField);
        $this->assertEquals($this->templateField->id, $tfv->templateField->id);
    }

    public function test_template_field_value_belongs_to_batch(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(UploadBatch::class, $tfv->batch);
        $this->assertEquals($this->batch->id, $tfv->batch->id);
    }

    public function test_template_field_value_belongs_to_conflicting_value(): void
    {
        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_2',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'EMP-002',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $this->assertInstanceOf(TemplateFieldValue::class, $tfv2->conflictingValue);
        $this->assertEquals($tfv1->id, $tfv2->conflictingValue->id);
    }

    public function test_template_field_value_batch_relationship_returns_null_when_batch_deleted(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->batch->delete();
        $tfv->refresh();

        $this->assertNull($tfv->batch_id);
        $this->assertNull($tfv->batch);
    }

    // Property 5: Type Casting Correctness
    public function test_needs_review_is_cast_to_boolean(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
            'needs_review' => 1,
        ]);

        $this->assertIsBool($tfv->needs_review);
        $this->assertTrue($tfv->needs_review);
    }

    public function test_needs_review_false_is_cast_to_boolean(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
            'needs_review' => 0,
        ]);

        $this->assertIsBool($tfv->needs_review);
        $this->assertFalse($tfv->needs_review);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $tfv->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $tfv->updated_at);
    }

    // Property 6: Conflict Detection
    public function test_has_conflict_returns_true_when_conflict_with_is_set(): void
    {
        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_2',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'EMP-002',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $this->assertTrue($tfv2->hasConflict());
    }

    public function test_has_conflict_returns_false_when_conflict_with_is_null(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
            'conflict_with' => null,
        ]);

        $this->assertFalse($tfv->hasConflict());
    }

    // Property 7: History Retrieval
    public function test_get_history_returns_current_value(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $history = $tfv->getHistory();

        $this->assertArrayHasKey('current', $history);
        $this->assertEquals('EMP-001', $history['current']);
    }

    public function test_get_history_returns_previous_value_when_set(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-002',
            'previous_value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $history = $tfv->getHistory();

        $this->assertArrayHasKey('current', $history);
        $this->assertArrayHasKey('previous', $history);
        $this->assertEquals('EMP-002', $history['current']);
        $this->assertEquals('EMP-001', $history['previous']);
    }

    public function test_get_history_does_not_include_previous_when_null(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'previous_value' => null,
            'batch_id' => $this->batch->id,
        ]);

        $history = $tfv->getHistory();

        $this->assertArrayHasKey('current', $history);
        $this->assertArrayNotHasKey('previous', $history);
    }

    // Resolve Conflict Tests
    public function test_resolve_conflict_with_keep_existing_deletes_conflicting_record(): void
    {
        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_2',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'EMP-002',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $tfv2->resolveConflict('keep_existing');

        $this->assertDatabaseMissing('template_field_values', ['id' => $tfv2->id]);
        $this->assertDatabaseHas('template_field_values', ['id' => $tfv1->id, 'value' => 'EMP-001']);
    }

    public function test_resolve_conflict_with_use_new_updates_existing_record(): void
    {
        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_2',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'EMP-002',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $tfv2->resolveConflict('use_new');

        $this->assertDatabaseMissing('template_field_values', ['id' => $tfv2->id]);
        $this->assertDatabaseHas('template_field_values', [
            'id' => $tfv1->id,
            'value' => 'EMP-002',
            'previous_value' => 'EMP-001',
            'needs_review' => false,
        ]);
    }

    public function test_resolve_conflict_with_edit_manually_updates_with_custom_value(): void
    {
        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_2',
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'EMP-002',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $tfv2->resolveConflict('edit_manually', 'EMP-003');

        $this->assertDatabaseMissing('template_field_values', ['id' => $tfv2->id]);
        $this->assertDatabaseHas('template_field_values', [
            'id' => $tfv1->id,
            'value' => 'EMP-003',
            'previous_value' => 'EMP-001',
            'needs_review' => false,
        ]);
    }

    public function test_resolve_conflict_with_invalid_resolution_throws_exception(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid resolution action');

        $tfv->resolveConflict('invalid_action');
    }

    public function test_resolve_conflict_with_edit_manually_without_custom_value_throws_exception(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'batch_id' => $this->batch->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Custom value is required');

        $tfv->resolveConflict('edit_manually');
    }

    public function test_resolve_conflict_with_invalid_custom_value_throws_exception(): void
    {
        $integerField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'age',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'age_2',
            'field_type' => 'integer',
            'is_required' => false,
        ]);

        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $integerField->id,
            'value' => '25',
            'batch_id' => $this->batch->id,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => '30',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Validation failed');

        $tfv2->resolveConflict('edit_manually', 'not_a_number');
    }

    public function test_template_field_value_has_fillable_fields(): void
    {
        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'previous_value' => 'EMP-000',
            'batch_id' => $this->batch->id,
            'needs_review' => true,
            'conflict_with' => null,
        ]);

        $this->assertDatabaseHas('template_field_values', [
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $this->templateField->id,
            'value' => 'EMP-001',
            'previous_value' => 'EMP-000',
            'batch_id' => $this->batch->id,
            'needs_review' => true,
        ]);
    }

    public function test_template_field_value_table_name_is_correct(): void
    {
        $tfv = new TemplateFieldValue();
        $this->assertEquals('template_field_values', $tfv->getTable());
    }
}
