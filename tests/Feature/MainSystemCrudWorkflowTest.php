<?php

namespace Tests\Feature;

use App\Models\MainSystem;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for complete CRUD workflows
 * 
 * Feature: main-system-crud-actions
 * Tasks: 29.1-29.6
 * 
 * Tests complete workflows for:
 * - Creating new records via modal
 * - Editing existing records
 * - Deleting individual records
 * - Bulk delete operations
 * - Bulk status updates
 * - Bulk category updates
 * 
 * Validates: Requirements 1.1-1.7, 2.1-2.8, 3.1-3.6, 5.1-5.7, 6.1-6.7, 7.1-7.7
 */
class MainSystemCrudWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test 29.1: Create record workflow
     * 
     * Validates: Requirements 1.1-1.7
     */
    public function test_create_record_workflow()
    {
        // Arrange
        $recordData = [
            'uid' => 'TEST-001',
            'regs_no' => 'REG-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birthday' => '1990-01-15',
            'gender' => 'Male',
            'status' => 'active',
            'category' => 'standard',
        ];

        // Act - Create record via API
        $response = $this->postJson('/api/main-system', $recordData);

        // Assert - Record created successfully
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'uid',
                'first_name',
                'last_name',
                'status',
            ],
        ]);

        // Assert - Record persisted to database
        $this->assertDatabaseHas('main_system', [
            'uid' => 'TEST-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Assert - Audit trail entry created
        $this->assertDatabaseHas('audit_trail', [
            'action_type' => 'create',
            'model_type' => 'MainSystem',
            'model_id' => $response->json('data.id'),
        ]);
    }

    /**
     * Test 29.2: Edit record workflow
     * 
     * Validates: Requirements 2.1-2.8
     */
    public function test_edit_record_workflow()
    {
        // Arrange - Create initial record
        $record = MainSystem::factory()->create([
            'uid' => 'TEST-002',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'status' => 'active',
        ]);

        $updateData = [
            'first_name' => 'Janet',
            'status' => 'inactive',
        ];

        // Act - Update record via API
        $response = $this->putJson("/api/main-system/{$record->id}", $updateData);

        // Assert - Update successful
        $response->assertStatus(200);
        $response->assertJsonPath('data.first_name', 'Janet');
        $response->assertJsonPath('data.status', 'inactive');

        // Assert - Database updated
        $this->assertDatabaseHas('main_system', [
            'id' => $record->id,
            'first_name' => 'Janet',
            'status' => 'inactive',
        ]);

        // Assert - Unchanged fields preserved
        $updated = MainSystem::find($record->id);
        $this->assertEquals('Smith', $updated->last_name);

        // Assert - Audit trail entry created with changed fields
        $auditEntry = AuditTrail::where('model_id', $record->id)
            ->where('action_type', 'update')
            ->latest()
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertContains('first_name', $auditEntry->changed_fields);
        $this->assertContains('status', $auditEntry->changed_fields);
    }

    /**
     * Test 29.3: Delete record workflow
     * 
     * Validates: Requirements 3.1-3.6
     */
    public function test_delete_record_workflow()
    {
        // Arrange - Create record to delete
        $record = MainSystem::factory()->create([
            'uid' => 'TEST-003',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
        ]);

        $recordId = $record->id;

        // Act - Delete record via API
        $response = $this->deleteJson("/api/main-system/{$recordId}");

        // Assert - Delete successful
        $response->assertStatus(200);

        // Assert - Record removed from database
        $this->assertDatabaseMissing('main_system', [
            'id' => $recordId,
        ]);

        // Assert - Audit trail entry created
        $this->assertDatabaseHas('audit_trail', [
            'action_type' => 'delete',
            'model_type' => 'MainSystem',
            'model_id' => $recordId,
        ]);
    }

    /**
     * Test 29.4: Bulk delete workflow
     * 
     * Validates: Requirements 5.1-5.7
     */
    public function test_bulk_delete_workflow()
    {
        // Arrange - Create multiple records
        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk delete via API
        $response = $this->postJson('/api/main-system/bulk/delete', [
            'recordIds' => $recordIds,
        ]);

        // Assert - Bulk delete successful
        $response->assertStatus(200);
        $response->assertJsonPath('deleted', 5);

        // Assert - All records removed from database
        foreach ($recordIds as $id) {
            $this->assertDatabaseMissing('main_system', ['id' => $id]);
        }

        // Assert - Audit trail entries created for each record
        $auditCount = AuditTrail::where('action_type', 'delete')
            ->whereIn('model_id', $recordIds)
            ->count();

        $this->assertEquals(5, $auditCount);
    }

    /**
     * Test 29.5: Bulk status update workflow
     * 
     * Validates: Requirements 6.1-6.7
     */
    public function test_bulk_status_update_workflow()
    {
        // Arrange - Create multiple records with active status
        $records = MainSystem::factory()->count(3)->create([
            'status' => 'active',
        ]);
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk update status via API
        $response = $this->postJson('/api/main-system/bulk/update-status', [
            'recordIds' => $recordIds,
            'status' => 'inactive',
        ]);

        // Assert - Bulk update successful
        $response->assertStatus(200);
        $response->assertJsonPath('updated', 3);

        // Assert - All records updated in database
        foreach ($recordIds as $id) {
            $this->assertDatabaseHas('main_system', [
                'id' => $id,
                'status' => 'inactive',
            ]);
        }

        // Assert - Audit trail entries created for each record
        $auditCount = AuditTrail::where('action_type', 'update')
            ->whereIn('model_id', $recordIds)
            ->count();

        $this->assertEquals(3, $auditCount);
    }

    /**
     * Test 29.6: Bulk category update workflow
     * 
     * Validates: Requirements 7.1-7.7
     */
    public function test_bulk_category_update_workflow()
    {
        // Arrange - Create multiple records with different categories
        $records = MainSystem::factory()->count(4)->create([
            'category' => 'standard',
        ]);
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk update category via API
        $response = $this->postJson('/api/main-system/bulk/update-category', [
            'recordIds' => $recordIds,
            'category' => 'premium',
        ]);

        // Assert - Bulk update successful
        $response->assertStatus(200);
        $response->assertJsonPath('updated', 4);

        // Assert - All records updated in database
        foreach ($recordIds as $id) {
            $this->assertDatabaseHas('main_system', [
                'id' => $id,
                'category' => 'premium',
            ]);
        }

        // Assert - Audit trail entries created for each record
        $auditCount = AuditTrail::where('action_type', 'update')
            ->whereIn('model_id', $recordIds)
            ->count();

        $this->assertEquals(4, $auditCount);
    }

    /**
     * Test: Create record with validation error
     * 
     * Validates: Requirements 1.3, 1.4, 8.1-8.8
     */
    public function test_create_record_with_validation_error()
    {
        // Arrange - Invalid data (missing required fields)
        $invalidData = [
            'uid' => 'TEST-004',
            'first_name' => '', // Required field empty
            'last_name' => 'Doe',
        ];

        // Act - Attempt to create record
        $response = $this->postJson('/api/main-system', $invalidData);

        // Assert - Validation error returned
        $response->assertStatus(422);
        $response->assertJsonPath('errors.first_name', true);

        // Assert - Record not persisted
        $this->assertDatabaseMissing('main_system', [
            'uid' => 'TEST-004',
        ]);
    }

    /**
     * Test: Create record with duplicate UID
     * 
     * Validates: Requirements 8.1, 12.5
     */
    public function test_create_record_with_duplicate_uid()
    {
        // Arrange - Create existing record
        MainSystem::factory()->create(['uid' => 'DUPLICATE-001']);

        $duplicateData = [
            'uid' => 'DUPLICATE-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        // Act - Attempt to create record with duplicate UID
        $response = $this->postJson('/api/main-system', $duplicateData);

        // Assert - Unique constraint violation error
        $response->assertStatus(422);
        $response->assertJsonPath('errors.uid', true);
    }

    /**
     * Test: Edit record with validation error
     * 
     * Validates: Requirements 2.3, 2.4, 8.1-8.8
     */
    public function test_edit_record_with_validation_error()
    {
        // Arrange - Create record
        $record = MainSystem::factory()->create();

        $invalidData = [
            'first_name' => '', // Required field empty
        ];

        // Act - Attempt to update record
        $response = $this->putJson("/api/main-system/{$record->id}", $invalidData);

        // Assert - Validation error returned
        $response->assertStatus(422);
        $response->assertJsonPath('errors.first_name', true);

        // Assert - Record not modified
        $this->assertDatabaseHas('main_system', [
            'id' => $record->id,
            'first_name' => $record->first_name,
        ]);
    }

    /**
     * Test: Delete non-existent record
     * 
     * Validates: Requirements 3.3
     */
    public function test_delete_nonexistent_record()
    {
        // Act - Attempt to delete non-existent record
        $response = $this->deleteJson('/api/main-system/99999');

        // Assert - 404 error returned
        $response->assertStatus(404);
    }

    /**
     * Test: Bulk delete with mixed valid and invalid records
     * 
     * Validates: Requirements 5.4-5.7
     */
    public function test_bulk_delete_with_mixed_records()
    {
        // Arrange - Create some records
        $records = MainSystem::factory()->count(3)->create();
        $recordIds = $records->pluck('id')->toArray();
        $recordIds[] = 99999; // Non-existent record

        // Act - Bulk delete with mixed records
        $response = $this->postJson('/api/main-system/bulk/delete', [
            'recordIds' => $recordIds,
        ]);

        // Assert - Partial success
        $response->assertStatus(200);
        $response->assertJsonPath('deleted', 3);
        $response->assertJsonPath('failed', 1);

        // Assert - Valid records deleted
        foreach ($records as $record) {
            $this->assertDatabaseMissing('main_system', ['id' => $record->id]);
        }
    }
}
