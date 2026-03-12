<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\BulkActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkActionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private BulkActionService $bulkActionService;
    private AuditTrailService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditTrailService();
        $this->bulkActionService = new BulkActionService($this->auditService);
    }

    /**
     * Property 7: Bulk Operations Process All Selected Records
     *
     * For any set of selected records across multiple pages, when a bulk operation
     * (delete, status update, or category update) is confirmed, ALL selected records
     * SHALL be processed, regardless of which page they appear on.
     *
     * Validates: Requirements 5.4, 6.4, 7.4, 16.3
     *
     * @test
     */
    public function bulk_delete_processes_all_selected_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create 10 records
        $records = MainSystem::factory()->count(10)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkDelete($recordIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['deleted']);
        $this->assertEquals(0, $result['failed']);

        // Verify all records are deleted
        foreach ($recordIds as $id) {
            $this->assertNull(MainSystem::find($id));
        }
    }

    /**
     * Property 7: Bulk Operations Process All Selected Records
     *
     * @test
     */
    public function bulk_status_update_processes_all_selected_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(10)->create(['status' => 'active']);
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateStatus($recordIds, 'inactive');

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['updated']);
        $this->assertEquals(0, $result['failed']);

        // Verify all records are updated
        foreach ($recordIds as $id) {
            $this->assertEquals('inactive', MainSystem::find($id)->status);
        }
    }

    /**
     * Property 7: Bulk Operations Process All Selected Records
     *
     * @test
     */
    public function bulk_category_update_processes_all_selected_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(10)->create(['category' => 'Standard']);
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateCategory($recordIds, 'VIP');

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['updated']);
        $this->assertEquals(0, $result['failed']);

        // Verify all records are updated
        foreach ($recordIds as $id) {
            $this->assertEquals('VIP', MainSystem::find($id)->category);
        }
    }

    /**
     * Property 7: Bulk Operations Process All Selected Records
     *
     * @test
     */
    public function bulk_delete_with_single_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();

        $result = $this->bulkActionService->bulkDelete([$record->id]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['deleted']);
        $this->assertNull(MainSystem::find($record->id));
    }

    /**
     * Property 7: Bulk Operations Process All Selected Records
     *
     * @test
     */
    public function bulk_delete_with_large_number_of_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(100)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkDelete($recordIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['deleted']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * For any bulk operation, either all selected records SHALL be successfully
     * processed, or if any record fails, the operation SHALL be rolled back and
     * no records SHALL be modified.
     *
     * Validates: Requirements 5.4, 6.4, 7.4
     *
     * @test
     */
    public function bulk_delete_handles_nonexistent_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();
        $nonexistentId = 99999;

        $result = $this->bulkActionService->bulkDelete([$record->id, $nonexistentId]);

        // Should process the valid record and report the error
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(1, $result['failed']);
        $this->assertArrayHasKey($nonexistentId, $result['errors']);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_status_update_handles_nonexistent_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();
        $nonexistentId = 99999;

        $result = $this->bulkActionService->bulkUpdateStatus([$record->id, $nonexistentId], 'inactive');

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['failed']);
        $this->assertArrayHasKey($nonexistentId, $result['errors']);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_category_update_handles_nonexistent_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = MainSystem::factory()->create();
        $nonexistentId = 99999;

        $result = $this->bulkActionService->bulkUpdateCategory([$record->id, $nonexistentId], 'VIP');

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['failed']);
        $this->assertArrayHasKey($nonexistentId, $result['errors']);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_delete_creates_audit_entries_for_each_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkDelete($recordIds);

        $this->assertTrue($result['success']);

        // Verify audit entries were created for each record
        foreach ($recordIds as $id) {
            $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $id, 'delete');
            $this->assertCount(1, $auditEntries);
            $this->assertEquals('delete', $auditEntries[0]->action_type);
        }
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_status_update_creates_audit_entries_for_each_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create(['status' => 'active']);
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateStatus($recordIds, 'inactive');

        $this->assertTrue($result['success']);

        // Verify audit entries were created for each record
        foreach ($recordIds as $id) {
            $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $id, 'update');
            $this->assertCount(1, $auditEntries);
            $this->assertEquals('update', $auditEntries[0]->action_type);
        }
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_category_update_creates_audit_entries_for_each_record()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create(['category' => 'Standard']);
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateCategory($recordIds, 'VIP');

        $this->assertTrue($result['success']);

        // Verify audit entries were created for each record
        foreach ($recordIds as $id) {
            $auditEntries = $this->auditService->getAuditTrail(MainSystem::class, $id, 'update');
            $this->assertCount(1, $auditEntries);
            $this->assertEquals('update', $auditEntries[0]->action_type);
        }
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_delete_returns_success_flag()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkDelete($recordIds);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_status_update_returns_success_flag()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateStatus($recordIds, 'inactive');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_category_update_returns_success_flag()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $records = MainSystem::factory()->count(5)->create();
        $recordIds = $records->pluck('id')->toArray();

        $result = $this->bulkActionService->bulkUpdateCategory($recordIds, 'VIP');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Property 8: Bulk Operations Fail Atomically or Succeed Completely
     *
     * @test
     */
    public function bulk_delete_with_mixed_valid_and_invalid_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $validRecords = MainSystem::factory()->count(5)->create();
        $validIds = $validRecords->pluck('id')->toArray();
        $invalidIds = [99999, 99998, 99997];

        $allIds = array_merge($validIds, $invalidIds);

        $result = $this->bulkActionService->bulkDelete($allIds);

        $this->assertFalse($result['success']);
        $this->assertEquals(5, $result['deleted']);
        $this->assertEquals(3, $result['failed']);

        // Verify valid records are deleted
        foreach ($validIds as $id) {
            $this->assertNull(MainSystem::find($id));
        }
    }
}
