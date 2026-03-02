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

class MainSystemTemplateFieldPropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ColumnMappingTemplate $template;
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

        $this->batch = UploadBatch::factory()->create([
            'uploaded_by' => $this->user->id,
        ]);
    }

    /**
     * Feature: template-field-persistence, Property 8: MainSystem Template Field Access
     * For any MainSystem record and field name, getTemplateFieldValue($fieldName) should return 
     * the value if a TemplateFieldValue exists for that field name, otherwise null. Similarly, 
     * hasTemplateField($fieldName) should return true if the field exists, false otherwise.
     * 
     * **Validates: Requirements 3.2, 3.4**
     * 
     * @test
     */
    public function test_main_system_template_field_access()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runTemplateFieldAccessTest();
        }
    }

    protected function runTemplateFieldAccessTest(): void
    {
        $mainSystem = MainSystem::factory()->create();
        $uniqueId = uniqid();

        $templateField1 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'employee_id_' . $uniqueId . '_' . rand(1000, 9999),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'department_' . $uniqueId . '_' . rand(1000, 9999),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $value1 = 'EMP-' . rand(1000, 9999);
        $value2 = 'DEPT-' . rand(1000, 9999);

        // Create template field values
        TemplateFieldValue::create([
            'main_system_id' => $mainSystem->id,
            'template_field_id' => $templateField1->id,
            'value' => $value1,
            'batch_id' => $this->batch->id,
        ]);

        TemplateFieldValue::create([
            'main_system_id' => $mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => $value2,
            'batch_id' => $this->batch->id,
        ]);

        // Test getTemplateFieldValue() returns correct value
        $this->assertEquals($value1, $mainSystem->getTemplateFieldValue($templateField1->field_name));
        $this->assertEquals($value2, $mainSystem->getTemplateFieldValue($templateField2->field_name));

        // Test getTemplateFieldValue() returns null for non-existent field
        $this->assertNull($mainSystem->getTemplateFieldValue('non_existent_field'));

        // Test hasTemplateField() returns true for existing fields
        $this->assertTrue($mainSystem->hasTemplateField($templateField1->field_name));
        $this->assertTrue($mainSystem->hasTemplateField($templateField2->field_name));

        // Test hasTemplateField() returns false for non-existent fields
        $this->assertFalse($mainSystem->hasTemplateField('non_existent_field'));

        // Verify the property: hasTemplateField() == (getTemplateFieldValue() !== null)
        $this->assertEquals(
            $mainSystem->hasTemplateField($templateField1->field_name),
            $mainSystem->getTemplateFieldValue($templateField1->field_name) !== null
        );
        $this->assertEquals(
            $mainSystem->hasTemplateField('non_existent_field'),
            $mainSystem->getTemplateFieldValue('non_existent_field') !== null
        );

        // Clean up
        $mainSystem->delete();
        $templateField1->delete();
        $templateField2->delete();
    }

    /**
     * Feature: template-field-persistence, Property 9: MainSystem All Template Fields Retrieval
     * For any MainSystem record, getAllTemplateFields() should return an associative array where 
     * keys are field names and values are the corresponding field values from all associated 
     * TemplateFieldValue records.
     * 
     * **Validates: Requirements 3.3**
     * 
     * @test
     */
    public function test_main_system_all_template_fields_retrieval()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runAllTemplateFieldsRetrievalTest();
        }
    }

    protected function runAllTemplateFieldsRetrievalTest(): void
    {
        $mainSystem = MainSystem::factory()->create();

        // Create multiple template fields with random values
        $fieldCount = rand(1, 5);
        $expectedFields = [];
        $uniqueId = uniqid();

        for ($i = 0; $i < $fieldCount; $i++) {
            // Use uniqid to ensure unique field names across iterations
            $fieldName = 'field_' . $i . '_' . $uniqueId . '_' . rand(1000, 9999);
            $fieldValue = 'value_' . $i . '_' . rand(1000, 9999);

            $templateField = TemplateField::create([
                'template_id' => $this->template->id,
                'field_name' => $fieldName,
                'field_type' => 'string',
                'is_required' => false,
            ]);

            TemplateFieldValue::create([
                'main_system_id' => $mainSystem->id,
                'template_field_id' => $templateField->id,
                'value' => $fieldValue,
                'batch_id' => $this->batch->id,
            ]);

            $expectedFields[$fieldName] = $fieldValue;
        }

        // Get all template fields
        $allFields = $mainSystem->getAllTemplateFields();

        // Verify it returns an associative array
        $this->assertIsArray($allFields);

        // Verify all expected fields are present
        foreach ($expectedFields as $fieldName => $fieldValue) {
            $this->assertArrayHasKey($fieldName, $allFields);
            $this->assertEquals($fieldValue, $allFields[$fieldName]);
        }

        // Verify the count matches
        $this->assertCount(count($expectedFields), $allFields);

        // Verify keys are field names
        foreach (array_keys($allFields) as $key) {
            $this->assertIsString($key);
        }

        // Verify values are field values
        foreach (array_values($allFields) as $value) {
            $this->assertIsString($value);
        }

        // Clean up
        $mainSystem->delete();
    }

    /**
     * Feature: template-field-persistence, Property 10: MainSystem Conflict Filtering
     * For any MainSystem record, getTemplateFieldsNeedingReview() should return only those 
     * TemplateFieldValue records where needs_review is true.
     * 
     * **Validates: Requirements 3.5**
     * 
     * @test
     */
    public function test_main_system_conflict_filtering()
    {
        // Run property test with multiple random datasets
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->runConflictFilteringTest();
        }
    }

    protected function runConflictFilteringTest(): void
    {
        $mainSystem = MainSystem::factory()->create();
        $uniqueId = uniqid();

        $templateField1 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_1_' . $uniqueId . '_' . rand(1000, 9999),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $templateField2 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_2_' . $uniqueId . '_' . rand(1000, 9999),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        $templateField3 = TemplateField::create([
            'template_id' => $this->template->id,
            'field_name' => 'field_3_' . $uniqueId . '_' . rand(1000, 9999),
            'field_type' => 'string',
            'is_required' => false,
        ]);

        // Create template field values with mixed needs_review states
        $tfv1 = TemplateFieldValue::create([
            'main_system_id' => $mainSystem->id,
            'template_field_id' => $templateField1->id,
            'value' => 'value1',
            'batch_id' => $this->batch->id,
            'needs_review' => false,
        ]);

        $tfv2 = TemplateFieldValue::create([
            'main_system_id' => $mainSystem->id,
            'template_field_id' => $templateField2->id,
            'value' => 'value2',
            'batch_id' => $this->batch->id,
            'needs_review' => true,
        ]);

        $tfv3 = TemplateFieldValue::create([
            'main_system_id' => $mainSystem->id,
            'template_field_id' => $templateField3->id,
            'value' => 'value3',
            'batch_id' => $this->batch->id,
            'needs_review' => true,
        ]);

        // Get fields needing review
        $conflictingFields = $mainSystem->getTemplateFieldsNeedingReview();

        // Verify it returns a collection
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $conflictingFields);

        // Verify only fields with needs_review=true are returned
        $this->assertCount(2, $conflictingFields);

        // Verify the returned records are the correct ones
        $conflictingIds = $conflictingFields->pluck('id')->toArray();
        $this->assertContains($tfv2->id, $conflictingIds);
        $this->assertContains($tfv3->id, $conflictingIds);
        $this->assertNotContains($tfv1->id, $conflictingIds);

        // Verify all returned records have needs_review=true
        foreach ($conflictingFields as $field) {
            $this->assertTrue($field->needs_review);
        }

        // Verify no records with needs_review=false are returned
        foreach ($conflictingFields as $field) {
            $this->assertNotEquals($tfv1->id, $field->id);
        }

        // Clean up
        $mainSystem->delete();
        $templateField1->delete();
        $templateField2->delete();
        $templateField3->delete();
    }
}
