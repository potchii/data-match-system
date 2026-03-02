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

class TemplateFieldValuePropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ColumnMappingTemplate $template;
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

        $this->mainSystem = MainSystem::factory()->create();

        $this->batch = UploadBatch::factory()->create([
            'uploaded_by' => $this->user->id,
        ]);
    }

    /**
     * Feature: template-field-persistence, Property 4: Model Relationship Integrity
     * For any TemplateFieldValue record, calling the mainSystem(), templateField(), batch(), 
     * and conflictingValue() relationship methods should return the correct related model instances or null.
     * 
     * **Validates: Requirements 2.2, 2.3, 2.4, 2.5**
     * 
     * @test
     */
    public function test_model_relationship_integrity()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runRelationshipIntegrityTest();
        }
    }

    protected function runRelationshipIntegrityTest(): void
    {
        $templateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_' . rand(1, 1000),
            'field_type' => ['string', 'integer', 'decimal', 'date', 'boolean'][rand(0, 4)],
            'is_required' => (bool) rand(0, 1),
        ]);

        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField->id,
            'value' => 'test_value_' . rand(1, 1000),
            'batch_id' => $this->batch->id,
        ]);

        // Verify all relationships return correct instances
        $this->assertNotNull($tfv->mainSystem);
        $this->assertEquals($this->mainSystem->id, $tfv->mainSystem->id);
        $this->assertInstanceOf(MainSystem::class, $tfv->mainSystem);

        $this->assertNotNull($tfv->templateField);
        $this->assertEquals($templateField->id, $tfv->templateField->id);
        $this->assertInstanceOf(TemplateField::class, $tfv->templateField);

        $this->assertNotNull($tfv->batch);
        $this->assertEquals($this->batch->id, $tfv->batch->id);
        $this->assertInstanceOf(UploadBatch::class, $tfv->batch);

        // Verify conflictingValue is null when no conflict
        $this->assertNull($tfv->conflictingValue);

        // Clean up
        $tfv->delete();
        $templateField->delete();
    }

    /**
     * Feature: template-field-persistence, Property 5: Type Casting Correctness
     * For any TemplateFieldValue record, the needs_review attribute should be cast to boolean, 
     * and created_at/updated_at should be cast to datetime instances.
     * 
     * **Validates: Requirements 2.6, 2.7**
     * 
     * @test
     */
    public function test_type_casting_correctness()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runTypeCastingTest();
        }
    }

    protected function runTypeCastingTest(): void
    {
        $templateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_' . rand(1, 1000),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $needsReviewValue = rand(0, 1);

        $tfv = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField->id,
            'value' => 'test_value',
            'batch_id' => $this->batch->id,
            'needs_review' => $needsReviewValue,
        ]);

        // Verify needs_review is boolean
        $this->assertIsBool($tfv->needs_review);
        $this->assertEquals((bool) $needsReviewValue, $tfv->needs_review);

        // Verify timestamps are datetime instances
        $this->assertInstanceOf(\DateTimeInterface::class, $tfv->created_at);
        $this->assertInstanceOf(\DateTimeInterface::class, $tfv->updated_at);

        // Verify timestamps are valid dates
        $this->assertNotNull($tfv->created_at);
        $this->assertNotNull($tfv->updated_at);

        // Clean up
        $tfv->delete();
        $templateField->delete();
    }

    /**
     * Feature: template-field-persistence, Property 6: Conflict Detection
     * For any TemplateFieldValue record, hasConflict() should return true if and only if 
     * conflict_with is not null.
     * 
     * **Validates: Requirements 2.8**
     * 
     * @test
     */
    public function test_conflict_detection()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runConflictDetectionTest($iteration);
        }
    }

    protected function runConflictDetectionTest(int $iteration): void
    {
        // Use unique field names to avoid constraint violations
        $timestamp = microtime(true) * 10000;
        $fieldName1 = 'field_' . $timestamp . '_' . $iteration . '_1';
        $fieldName2 = 'field_' . $timestamp . '_' . $iteration . '_2';

        $templateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => $fieldName1,
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => $fieldName2,
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Test case 1: No conflict
        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField->id,
            'value' => 'value1',
            'batch_id' => $this->batch->id,
            'conflict_with' => null,
        ]);

        $this->assertFalse($tfv1->hasConflict());
        $this->assertNull($tfv1->conflict_with);

        // Test case 2: With conflict
        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'value2',
            'batch_id' => $this->batch->id,
            'conflict_with' => $tfv1->id,
        ]);

        $this->assertTrue($tfv2->hasConflict());
        $this->assertEquals($tfv1->id, $tfv2->conflict_with);

        // Verify the property: hasConflict() == (conflict_with !== null)
        $this->assertEquals($tfv2->hasConflict(), $tfv2->conflict_with !== null);

        // Clean up
        $tfv1->delete();
        $tfv2->delete();
        $templateField->delete();
        $templateField2->delete();
    }

    /**
     * Feature: template-field-persistence, Property 7: History Retrieval
     * For any TemplateFieldValue record with a non-null previous_value, getHistory() should 
     * return an array containing both the current value and previous value.
     * 
     * **Validates: Requirements 2.9**
     * 
     * @test
     */
    public function test_history_retrieval()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runHistoryRetrievalTest();
        }
    }

    protected function runHistoryRetrievalTest(): void
    {
        $templateField = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_' . rand(1, 1000),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_' . rand(1, 1000),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $currentValue = 'current_' . rand(1, 1000);
        $previousValue = 'previous_' . rand(1, 1000);

        // Test case 1: With previous value
        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField->id,
            'value' => $currentValue,
            'previous_value' => $previousValue,
            'batch_id' => $this->batch->id,
        ]);

        $history1 = $tfv1->getHistory();
        $this->assertArrayHasKey('current', $history1);
        $this->assertArrayHasKey('previous', $history1);
        $this->assertEquals($currentValue, $history1['current']);
        $this->assertEquals($previousValue, $history1['previous']);

        // Test case 2: Without previous value
        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $this->mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => $currentValue,
            'previous_value' => null,
            'batch_id' => $this->batch->id,
        ]);

        $history2 = $tfv2->getHistory();
        $this->assertArrayHasKey('current', $history2);
        $this->assertArrayNotHasKey('previous', $history2);
        $this->assertEquals($currentValue, $history2['current']);

        // Verify the property: if previous_value is not null, it should be in history
        if ($tfv1->previous_value !== null) {
            $this->assertArrayHasKey('previous', $tfv1->getHistory());
        }

        // Clean up
        $tfv1->delete();
        $tfv2->delete();
        $templateField->delete();
        $templateField2->delete();
    }
}
