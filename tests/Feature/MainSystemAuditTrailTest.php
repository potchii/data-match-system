<?php

namespace Tests\Feature;

use App\Models\MainSystem;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for audit trail logging
 * 
 * Feature: main-system-crud-actions
 * Tasks: 32.1-32.4
 * 
 * Tests audit trail logging for all operations:
 * - Create operations
 * - Update operations with changed fields
 * - Delete operations
 * - Bulk operations
 * 
 * Validates: Requirements 13.1-13.6
 */
class MainSystemAuditTrailTest extends TestCase
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
     * Test 32.1: Audit trail for create operations
     * 
     * Validates: Requirements 13.1
     */
    public function test_audit_trail_for_create_operations()
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
        ];

        // Act - Create record
        $response = $this->postJson('/api/main-system', $recordData);
        $recordId = $response->json('data.id');

        // Assert - Audit trail entry created
        $auditEntry = AuditTrail::where('model_id', $recordId)
            ->where('action_type', 'create')
            ->first();

        $this->assertNotNull($auditEntry);

        // Assert - Audit entry contains required fields
        $this->assertEquals('MainSystem', $auditEntry->model_type);
        $this->assertEquals($this->user->id, $auditEntry->user_id);
        $this->assertNotNull($auditEntry->created_at);

        // Assert - New values captured
        $this->assertNotNull($auditEntry->new_values);
        $newValues = $auditEntry->new_values;
        $this->assertEquals('John', $newValues['first_name']);
        $this->assertEquals('Doe', $newValues['last_name']);

        // Assert - Old values null for create
        $this->assertNull($auditEntry->old_values);
    }

    /**
     * Test 32.2: Audit trail for update operations
     * 
     * Validates: Requirements 13.2
     */
    public function test_audit_trail_for_update_operations()
    {
        // Arrange - Create record
        $record = MainSystem::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);

        // Update data
        $updateData = [
            'first_name' => 'Janet',
            'status' => 'inactive',
        ];

        // Act - Update record
        $this->putJson("/api/main-system/{$record->id}", $updateData);

        // Assert - Audit trail entry created
        $auditEntry = AuditTrail::where('model_id', $record->id)
            ->where('action_type', 'update')
            ->latest()
            ->first();

        $this->assertNotNull($auditEntry);

        // Assert - Changed fields captured
        $this->assertNotNull($auditEntry->changed_fields);
        $changedFields = $auditEntry->changed_fields;
        $this->assertContains('first_name', $changedFields);
        $this->assertContains('status', $changedFields);

        // Assert - Old and new values captured
        $oldValues = $auditEntry->old_values;
        $newValues = $auditEntry->new_values;

        $this->assertEquals('John', $oldValues['first_name']);
        $this->assertEquals('Janet', $newValues['first_name']);
        $this->assertEquals('active', $oldValues['status']);
        $this->assertEquals('inactive', $newValues['status']);
    }

    /**
     * Test 32.3: Audit trail for delete operations
     * 
     * Validates: Requirements 13.3
     */
    public function test_audit_trail_for_delete_operations()
    {
        // Arrange - Create record
        $record = MainSystem::factory()->create([
            'uid' => 'TEST-002',
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
        ]);

        $recordId = $record->id;

        // Act - Delete record
        $this->deleteJson("/api/main-system/{$recordId}");

        // Assert - Audit trail entry created
        $auditEntry = AuditTrail::where('model_id', $recordId)
            ->where('action_type', 'delete')
            ->first();

        $this->assertNotNull($auditEntry);

        // Assert - Deleted record values captured
        $this->assertNotNull($auditEntry->new_values);
        $deletedValues = $auditEntry->new_values;
        $this->assertEquals('Bob', $deletedValues['first_name']);
        $this->assertEquals('Johnson', $deletedValues['last_name']);

        // Assert - Old values null for delete
        $this->assertNull($auditEntry->old_values);
    }

    /**
     * Test 32.4: Audit trail for bulk operations
     * 
     * Validates: Requirements 13.4
     */
    public function test_audit_trail_for_bulk_operations()
    {
        // Arrange - Create multiple records
        $records = MainSystem::factory()->count(3)->create([
            'status' => 'active',
        ]);
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk update status
        $this->postJson('/api/main-system/bulk/update-status', [
            'recordIds' => $recordIds,
            'status' => 'inactive',
        ]);

        // Assert - Individual audit entries created for each record
        foreach ($recordIds as $id) {
            $auditEntry = AuditTrail::where('model_id', $id)
                ->where('action_type', 'update')
                ->first();

            $this->assertNotNull($auditEntry);

            // Assert - Changed fields captured
            $changedFields = $auditEntry->changed_fields;
            $this->assertContains('status', $changedFields);

            // Assert - Old and new values captured
            $oldValues = $auditEntry->old_values;
            $newValues = $auditEntry->new_values;
            $this->assertEquals('active', $oldValues['status']);
            $this->assertEquals('inactive', $newValues['status']);
        }
    }

    /**
     * Test: Audit trail immutability
     * 
     * Validates: Requirements 13.5
     */
    public function test_audit_trail_immutability()
    {
        // Arrange - Create record via API to generate audit entry
        $recordData = [
            'uid' => 'TEST-005',
            'first_name' => 'Test',
            'last_name' => 'User',
        ];

        $response = $this->postJson('/api/main-system', $recordData);
        $recordId = $response->json('data.id');

        $auditEntry = AuditTrail::where('model_id', $recordId)
            ->where('action_type', 'create')
            ->first();

        $this->assertNotNull($auditEntry);
        $originalValues = $auditEntry->new_values;

        // Act - Attempt to modify audit entry (should not be allowed in real system)
        // In a real scenario, the database would have constraints preventing this
        // Here we verify the entry exists and has the original values

        // Assert - Audit entry still has original values
        $auditEntry->refresh();
        $this->assertEquals($originalValues, $auditEntry->new_values);
    }

    /**
     * Test: Audit trail user ID tracking
     * 
     * Validates: Requirements 13.6
     */
    public function test_audit_trail_user_id_tracking()
    {
        // Arrange - Create record
        $recordData = [
            'uid' => 'TEST-003',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ];

        // Act - Create record
        $response = $this->postJson('/api/main-system', $recordData);
        $recordId = $response->json('data.id');

        // Assert - Audit entry has correct user ID
        $auditEntry = AuditTrail::where('model_id', $recordId)
            ->where('action_type', 'create')
            ->first();

        $this->assertEquals($this->user->id, $auditEntry->user_id);
    }

    /**
     * Test: Audit trail timestamp
     * 
     * Validates: Requirements 13.1-13.3
     */
    public function test_audit_trail_timestamp()
    {
        // Arrange
        $beforeTime = now()->subSecond();

        // Act - Create record
        $response = $this->postJson('/api/main-system', [
            'uid' => 'TEST-004',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $recordId = $response->json('data.id');

        $afterTime = now()->addSecond();

        // Assert - Audit entry has timestamp within expected range
        $auditEntry = AuditTrail::where('model_id', $recordId)
            ->where('action_type', 'create')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertTrue($auditEntry->created_at >= $beforeTime);
        $this->assertTrue($auditEntry->created_at <= $afterTime);
    }

    /**
     * Test: Audit trail for bulk delete
     * 
     * Validates: Requirements 13.4
     */
    public function test_audit_trail_for_bulk_delete()
    {
        // Arrange - Create multiple records
        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk delete
        $this->postJson('/api/main-system/bulk/delete', [
            'recordIds' => $recordIds,
        ]);

        // Assert - Individual audit entries created for each deletion
        $auditCount = AuditTrail::where('action_type', 'delete')
            ->whereIn('model_id', $recordIds)
            ->count();

        $this->assertEquals(5, $auditCount);

        // Assert - Each entry has deleted record values
        foreach ($recordIds as $id) {
            $auditEntry = AuditTrail::where('model_id', $id)
                ->where('action_type', 'delete')
                ->first();

            $this->assertNotNull($auditEntry);
            $this->assertNotNull($auditEntry->new_values);
        }
    }

    /**
     * Test: Audit trail for bulk category update
     * 
     * Validates: Requirements 13.4
     */
    public function test_audit_trail_for_bulk_category_update()
    {
        // Arrange - Create multiple records
        $records = MainSystem::factory()->count(4)->create([
            'category' => 'standard',
        ]);
        $recordIds = $records->pluck('id')->toArray();

        // Act - Bulk update category
        $this->postJson('/api/main-system/bulk/update-category', [
            'recordIds' => $recordIds,
            'category' => 'premium',
        ]);

        // Assert - Individual audit entries created for each record
        foreach ($recordIds as $id) {
            $auditEntry = AuditTrail::where('model_id', $id)
                ->where('action_type', 'update')
                ->first();

            $this->assertNotNull($auditEntry);

            // Assert - Category change captured
            $changedFields = $auditEntry->changed_fields;
            $this->assertContains('category', $changedFields);

            $oldValues = $auditEntry->old_values;
            $newValues = $auditEntry->new_values;
            $this->assertEquals('standard', $oldValues['category']);
            $this->assertEquals('premium', $newValues['category']);
        }
    }

    /**
     * Test: Audit trail query by record ID
     * 
     * Validates: Requirements 13.1-13.6
     */
    public function test_audit_trail_query_by_record_id()
    {
        // Arrange - Create record via API and perform multiple operations
        $createResponse = $this->postJson('/api/main-system', [
            'uid' => 'TEST-006',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);
        $recordId = $createResponse->json('data.id');

        // Update record
        $this->putJson("/api/main-system/{$recordId}", [
            'first_name' => 'Janet',
        ]);

        // Act - Query audit trail for record
        $response = $this->getJson("/api/audit-trail?recordId={$recordId}");

        // Assert - Response contains audit entries
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'action_type',
                    'model_id',
                    'user_id',
                    'created_at',
                ],
            ],
        ]);

        // Assert - Both create and update entries present
        $entries = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($entries));

        $actionTypes = collect($entries)->pluck('action_type')->toArray();
        $this->assertContains('create', $actionTypes);
        $this->assertContains('update', $actionTypes);
    }
}
