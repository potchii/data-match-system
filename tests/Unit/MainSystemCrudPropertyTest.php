<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Models\TemplateField;
use App\Models\TemplateFieldValue;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\MainSystemCrudService;
use App\Services\MainSystemValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSystemCrudPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MainSystemCrudService $crudService;
    private MainSystemValidationService $validationService;
    private AuditTrailService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
        $this->auditService = new AuditTrailService();
        $this->crudService = new MainSystemCrudService($this->validationService, $this->auditService);
    }

    /**
     * Property 1: Record Creation Persists Valid Data
     *
     * For any valid Main System record data (with required fields populated), when the
     * record is created through the CRUD modal and validation succeeds, the record SHALL
     * be persisted to the database and queryable by ID.
     *
     * Validates: Requirements 1.3, 1.5, 2.5
     *
     * @test
     */
    public function record_creation_persists_valid_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'uid' => 'test-uid-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
        ];

        $result = $this->crudService->createRecord($data);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('test-uid-001', $result['data']->uid);
        $this->assertEquals('John', $result['data']->first_name);
        $this->assertEquals('Doe', $result['data']->last_name);

        // Verify record is queryable
        $retrieved = MainSystem::find($result['data']->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals('test-uid-001', $retrieved->uid);
    }

    /**
     * Property 1: Record Creation Persists Valid Data
     *
     * @test
     */
    public function record_creation_with_all_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'uid' => 'test-uid-002',
            'regs_no' => 'REG-001',
            'registration_date' => '2024-01-15',
            'first_name' => 'Jane',
            'middle_name' => 'Marie',
            'last_name' => 'Smith',
            'suffix' => 'Jr.',
            'birthday' => '1990-05-20',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'address' => '123 Main St',
            'barangay' => 'Barangay 1',
            'status' => 'active',
            'category' => 'VIP',
        ];

        $result = $this->crudService->createRecord($data);

        $this->assertTrue($result['success']);
        $retrieved = MainSystem::find($result['data']->id);
        $this->assertEquals('Jane', $retrieved->first_name);
        $this->assertEquals('Marie', $retrieved->middle_name);
        $this->assertEquals('Smith', $retrieved->last_name);
        $this->assertEquals('Jr.', $retrieved->suffix);
        $this->assertEquals('1990-05-20', $retrieved->birthday->format('Y-m-d'));
        $this->assertEquals('Female', $retrieved->gender);
        $this->assertEquals('Single', $retrieved->civil_status);
        $this->assertEquals('123 Main St', $retrieved->address);
        $this->assertEquals('Barangay 1', $retrieved->barangay);
        $this->assertEquals('active', $retrieved->status);
        $this->assertEquals('VIP', $retrieved->category);
    }

    /**
     * Property 1: Record Creation Persists Valid Data
     *
     * @test
     */
    public function record_creation_rejects_invalid_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'uid' => 'test-uid-003',
            'first_name' => '', // Invalid
            'last_name' => 'Doe',
        ];

        $result = $this->crudService->createRecord($data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('first_name', $result['errors']);
    }

    /**
     * Property 6: Record Deletion Removes from Database
     *
     * For any Main System record, when deletion is confirmed, the record SHALL be
     * removed from the database and no longer queryable by ID.
     *
     * Validates: Requirements 3.3, 3.4
     *
     * @test
     */
    public function record_deletion_removes_from_database()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();
        $recordId = $record->id;

        $result = $this->crudService->deleteRecord($recordId);

        $this->assertTrue($result['success']);
        $this->assertNull(MainSystem::find($recordId));
    }

    /**
     * Property 6: Record Deletion Removes from Database
     *
     * @test
     */
    public function record_deletion_creates_audit_entry()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();
        $recordId = $record->id;

        $result = $this->crudService->deleteRecord($recordId);

        $this->assertTrue($result['success']);

        // Verify audit entry was created
        $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $recordId, 'delete');
        $this->assertCount(1, $auditEntries);
        $this->assertEquals('delete', $auditEntries[0]->action_type);
    }

    /**
     * Property 6: Record Deletion Removes from Database
     *
     * @test
     */
    public function record_deletion_of_nonexistent_record_fails()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $result = $this->crudService->deleteRecord(99999);

        $this->assertFalse($result['success']);
    }

    /**
     * Property 21: Template Field Values Persist with Record
     *
     * For any record with associated template fields, when template field values are
     * entered and the record is saved, the template field values SHALL be persisted to
     * the database and retrievable when the record is edited.
     *
     * Validates: Requirements 17.2-17.3
     *
     * @test
     */
    public function template_field_values_persist_with_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create template fields
        $field1 = TemplateField::factory()->create(['field_name' => 'custom_field_1']);
        $field2 = TemplateField::factory()->create(['field_name' => 'custom_field_2']);

        $data = [
            'uid' => 'test-uid-004',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'templateFields' => [
                'custom_field_1' => 'value1',
                'custom_field_2' => 'value2',
            ],
        ];

        $result = $this->crudService->createRecord($data);

        $this->assertTrue($result['success']);
        $recordId = $result['data']->id;

        // Verify template field values are persisted
        $templateValues = TemplateFieldValue::where('main_system_id', $recordId)->get();
        $this->assertCount(2, $templateValues);

        // Verify values are correct
        $value1 = $templateValues->where('template_field_id', $field1->id)->first();
        $value2 = $templateValues->where('template_field_id', $field2->id)->first();
        $this->assertEquals('value1', $value1->value);
        $this->assertEquals('value2', $value2->value);
    }

    /**
     * Property 21: Template Field Values Persist with Record
     *
     * @test
     */
    public function template_field_values_retrievable_on_edit()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create template fields
        $field1 = TemplateField::factory()->create(['field_name' => 'custom_field_1']);

        $data = [
            'uid' => 'test-uid-005',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'templateFields' => [
                'custom_field_1' => 'original_value',
            ],
        ];

        $result = $this->crudService->createRecord($data);
        $recordId = $result['data']->id;

        // Retrieve the record
        $retrieved = $this->crudService->getRecord($recordId);

        $this->assertNotNull($retrieved);
        $this->assertCount(1, $retrieved->templateFieldValues);
        $this->assertEquals('original_value', $retrieved->templateFieldValues[0]->value);
    }

    /**
     * Property 21: Template Field Values Persist with Record
     *
     * @test
     */
    public function template_field_values_updated_on_record_update()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create template field
        $field1 = TemplateField::factory()->create(['field_name' => 'custom_field_1']);

        // Create record with template field
        $record = MainSystem::factory()->create();
        TemplateFieldValue::create([
            'main_system_id' => $record->id,
            'template_field_id' => $field1->id,
            'value' => 'original_value',
        ]);

        // Update the record with new template field value
        $updateData = [
            'first_name' => 'Updated',
            'templateFields' => [
                'custom_field_1' => 'updated_value',
            ],
        ];

        $result = $this->crudService->updateRecord($record->id, $updateData);

        $this->assertTrue($result['success']);

        // Verify template field value is updated
        $templateValue = TemplateFieldValue::where('main_system_id', $record->id)
            ->where('template_field_id', $field1->id)
            ->first();
        $this->assertEquals('updated_value', $templateValue->value);
    }

    /**
     * Property 1: Record Creation Persists Valid Data
     *
     * @test
     */
    public function record_creation_creates_audit_entry()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'uid' => 'test-uid-006',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $result = $this->crudService->createRecord($data);

        $this->assertTrue($result['success']);
        $recordId = $result['data']->id;

        // Verify audit entry was created
        $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $recordId, 'create');
        $this->assertCount(1, $auditEntries);
        $this->assertEquals('create', $auditEntries[0]->action_type);
    }

    /**
     * Property 5: Record Update Modifies Only Changed Fields
     *
     * For any existing record, when updated with new values and validation succeeds,
     * only the provided fields SHALL be updated in the database, and unchanged fields
     * SHALL retain their original values.
     *
     * Validates: Requirements 2.5
     *
     * @test
     */
    public function record_update_modifies_only_changed_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
        ]);

        $updateData = [
            'first_name' => 'Jane',
            // last_name, gender, status not provided - should remain unchanged
        ];

        $result = $this->crudService->updateRecord($record->id, $updateData);

        $this->assertTrue($result['success']);

        $updated = MainSystem::find($record->id);
        $this->assertEquals('Jane', $updated->first_name);
        $this->assertEquals('Doe', $updated->last_name); // Unchanged
        $this->assertEquals('Male', $updated->gender); // Unchanged
        $this->assertEquals('active', $updated->status); // Unchanged
    }

    /**
     * Property 5: Record Update Modifies Only Changed Fields
     *
     * @test
     */
    public function record_update_creates_audit_entry_with_changed_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
        ]);

        $updateData = [
            'first_name' => 'Jane',
            'gender' => 'Female',
        ];

        $result = $this->crudService->updateRecord($record->id, $updateData);

        $this->assertTrue($result['success']);

        // Verify audit entry was created with only changed fields
        $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $record->id, 'update');
        $this->assertCount(1, $auditEntries);
        $this->assertContains('first_name', $auditEntries[0]->changed_fields);
        $this->assertContains('gender', $auditEntries[0]->changed_fields);
        $this->assertNotContains('last_name', $auditEntries[0]->changed_fields);
    }
}
