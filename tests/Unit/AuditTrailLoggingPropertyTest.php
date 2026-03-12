<?php

namespace Tests\Unit;

use App\Models\AuditTrail;
use App\Models\MainSystem;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailLoggingPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AuditTrailService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditTrailService();
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * For any CRUD operation (create, update, delete), an audit trail entry SHALL be
     * created containing: timestamp, user ID, action type, record ID, and changed field
     * values. For bulk operations, each record SHALL have an individual audit entry.
     *
     * Validates: Requirements 2.8, 3.6, 5.7, 6.7, 7.7, 13.1-13.6
     *
     * @test
     */
    public function audit_trail_logs_create_operation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $newValues = [
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
        ];

        $auditEntry = $this->auditService->logCreate(MainSystem::class, 1, $newValues);

        $this->assertNotNull($auditEntry);
        $this->assertEquals('create', $auditEntry->action_type);
        $this->assertEquals('MainSystem', $auditEntry->model_type);
        $this->assertEquals(1, $auditEntry->model_id);
        $this->assertEquals($user->id, $auditEntry->user_id);
        $this->assertNull($auditEntry->old_values);
        $this->assertEquals($newValues, $auditEntry->new_values);
        $this->assertNotNull($auditEntry->created_at);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_logs_update_operation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $oldValues = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $newValues = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ];

        $auditEntry = $this->auditService->logUpdate(MainSystem::class, 1, $oldValues, $newValues);

        $this->assertNotNull($auditEntry);
        $this->assertEquals('update', $auditEntry->action_type);
        $this->assertEquals('MainSystem', $auditEntry->model_type);
        $this->assertEquals(1, $auditEntry->model_id);
        $this->assertEquals($user->id, $auditEntry->user_id);
        $this->assertEquals($oldValues, $auditEntry->old_values);
        $this->assertEquals($newValues, $auditEntry->new_values);
        $this->assertContains('first_name', $auditEntry->changed_fields);
        $this->assertNotNull($auditEntry->created_at);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_logs_delete_operation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deletedValues = [
            'uid' => 'test-uid',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $auditEntry = $this->auditService->logDelete(MainSystem::class, 1, $deletedValues);

        $this->assertNotNull($auditEntry);
        $this->assertEquals('delete', $auditEntry->action_type);
        $this->assertEquals('MainSystem', $auditEntry->model_type);
        $this->assertEquals(1, $auditEntry->model_id);
        $this->assertEquals($user->id, $auditEntry->user_id);
        $this->assertNull($auditEntry->old_values);
        $this->assertEquals($deletedValues, $auditEntry->new_values);
        $this->assertNotNull($auditEntry->created_at);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_includes_user_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $auditEntry = $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);

        $this->assertEquals($user->id, $auditEntry->user_id);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_includes_ip_address()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $auditEntry = $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);

        $this->assertNotNull($auditEntry->ip_address);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_includes_user_agent()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $auditEntry = $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);

        $this->assertNotNull($auditEntry->user_agent);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_tracks_changed_fields_on_update()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $oldValues = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
        ];

        $newValues = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'Male',
        ];

        $auditEntry = $this->auditService->logUpdate(MainSystem::class, 1, $oldValues, $newValues);

        // Only first_name changed
        $this->assertCount(1, $auditEntry->changed_fields);
        $this->assertContains('first_name', $auditEntry->changed_fields);
        $this->assertNotContains('last_name', $auditEntry->changed_fields);
        $this->assertNotContains('gender', $auditEntry->changed_fields);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_includes_all_changed_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $oldValues = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'status' => 'active',
        ];

        $newValues = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'status' => 'inactive',
        ];

        $auditEntry = $this->auditService->logUpdate(MainSystem::class, 1, $oldValues, $newValues);

        $this->assertCount(4, $auditEntry->changed_fields);
        $this->assertContains('first_name', $auditEntry->changed_fields);
        $this->assertContains('last_name', $auditEntry->changed_fields);
        $this->assertContains('gender', $auditEntry->changed_fields);
        $this->assertContains('status', $auditEntry->changed_fields);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_can_include_reason()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $reason = 'Data correction requested by admin';
        $auditEntry = $this->auditService->logCreate(
            MainSystem::class,
            1,
            ['first_name' => 'John'],
            $reason
        );

        $this->assertEquals($reason, $auditEntry->reason);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_retrieval_by_model()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create multiple audit entries
        $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);
        $this->auditService->logUpdate(MainSystem::class, 1, ['first_name' => 'John'], ['first_name' => 'Jane']);
        $this->auditService->logDelete(MainSystem::class, 1, ['first_name' => 'Jane']);

        $entries = $this->auditService->getAuditTrail(MainSystem::class, 1);

        $this->assertCount(3, $entries);
        $this->assertEquals('delete', $entries[0]->action_type); // Most recent first
        $this->assertEquals('update', $entries[1]->action_type);
        $this->assertEquals('create', $entries[2]->action_type);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_retrieval_by_action_type()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);
        $this->auditService->logUpdate(MainSystem::class, 1, ['first_name' => 'John'], ['first_name' => 'Jane']);
        $this->auditService->logUpdate(MainSystem::class, 1, ['first_name' => 'Jane'], ['first_name' => 'Janet']);

        $entries = $this->auditService->getAuditTrail(MainSystem::class, 1, 'update');

        $this->assertCount(2, $entries);
        foreach ($entries as $entry) {
            $this->assertEquals('update', $entry->action_type);
        }
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_respects_limit()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create 10 audit entries
        for ($i = 0; $i < 10; $i++) {
            $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => "Name{$i}"]);
        }

        $entries = $this->auditService->getAuditTrail(MainSystem::class, 1, null, 5);

        $this->assertCount(5, $entries);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_retrieval_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);
        $this->auditService->logCreate(MainSystem::class, 1, ['first_name' => 'John']);

        $this->actingAs($user2);
        $this->auditService->logCreate(MainSystem::class, 2, ['first_name' => 'Jane']);

        $user1Entries = $this->auditService->getUserAuditTrail($user1->id);
        $user2Entries = $this->auditService->getUserAuditTrail($user2->id);

        $this->assertCount(1, $user1Entries);
        $this->assertCount(1, $user2Entries);
        $this->assertEquals($user1->id, $user1Entries[0]->user_id);
        $this->assertEquals($user2->id, $user2Entries[0]->user_id);
    }

    /**
     * Property 9: Audit Trail Logs All CRUD Operations
     *
     * @test
     */
    public function audit_trail_preserves_json_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $complexData = [
            'first_name' => 'John',
            'nested' => [
                'field' => 'value',
            ],
            'array' => [1, 2, 3],
        ];

        $auditEntry = $this->auditService->logCreate(MainSystem::class, 1, $complexData);

        $this->assertEquals($complexData, $auditEntry->new_values);
    }
}
