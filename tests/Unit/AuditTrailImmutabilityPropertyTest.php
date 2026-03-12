<?php

namespace Tests\Unit;

use App\Models\AuditTrail;
use App\Models\MainSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailImmutabilityPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 10: Audit Trail is Immutable
     *
     * For any audit trail entry, once created, it SHALL NOT be modifiable or deletable,
     * ensuring an immutable record of all system changes.
     *
     * Validates: Requirements 13.5
     *
     * @test
     */
    public function audit_trail_entries_cannot_be_modified()
    {
        // Arrange
        $user = User::factory()->create();
        $record = MainSystem::factory()->create();

        $auditEntry = AuditTrail::create([
            'user_id' => $user->id,
            'action_type' => 'create',
            'model_type' => MainSystem::class,
            'model_id' => $record->id,
            'old_values' => null,
            'new_values' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            'changed_fields' => ['first_name', 'last_name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $originalId = $auditEntry->id;
        $originalActionType = $auditEntry->action_type;
        $originalNewValues = $auditEntry->new_values;

        // Act - Attempt to modify the audit entry
        $auditEntry->action_type = 'update';
        $auditEntry->new_values = ['first_name' => 'Jane'];
        $auditEntry->save();

        // Assert - Verify the entry was not actually modified in the database
        $retrievedEntry = AuditTrail::find($originalId);
        $this->assertEquals($originalActionType, $retrievedEntry->action_type);
        $this->assertEquals($originalNewValues, $retrievedEntry->new_values);
    }

    /**
     * Property 10: Audit Trail is Immutable
     *
     * Audit trail entries should not be deletable once created.
     *
     * Validates: Requirements 13.5
     *
     * @test
     */
    public function audit_trail_entries_cannot_be_deleted()
    {
        // Arrange
        $user = User::factory()->create();
        $record = MainSystem::factory()->create();

        $auditEntry = AuditTrail::create([
            'user_id' => $user->id,
            'action_type' => 'delete',
            'model_type' => MainSystem::class,
            'model_id' => $record->id,
            'old_values' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            'new_values' => null,
            'changed_fields' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $entryId = $auditEntry->id;

        // Act - Attempt to delete the audit entry
        $auditEntry->delete();

        // Assert - Verify the entry still exists in the database
        $retrievedEntry = AuditTrail::find($entryId);
        $this->assertNotNull($retrievedEntry);
        $this->assertEquals($entryId, $retrievedEntry->id);
    }

    /**
     * Property 10: Audit Trail is Immutable
     *
     * Multiple audit entries should maintain their integrity independently.
     *
     * Validates: Requirements 13.5
     *
     * @test
     */
    public function multiple_audit_entries_maintain_independent_integrity()
    {
        // Arrange
        $user = User::factory()->create();
        $record1 = MainSystem::factory()->create();
        $record2 = MainSystem::factory()->create();

        $entry1 = AuditTrail::create([
            'user_id' => $user->id,
            'action_type' => 'create',
            'model_type' => MainSystem::class,
            'model_id' => $record1->id,
            'new_values' => ['first_name' => 'John'],
            'changed_fields' => ['first_name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $entry2 = AuditTrail::create([
            'user_id' => $user->id,
            'action_type' => 'update',
            'model_type' => MainSystem::class,
            'model_id' => $record2->id,
            'old_values' => ['first_name' => 'Jane'],
            'new_values' => ['first_name' => 'Janet'],
            'changed_fields' => ['first_name'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $originalEntry1Values = $entry1->new_values;
        $originalEntry2Values = $entry2->new_values;

        // Act - Attempt to modify both entries
        $entry1->new_values = ['first_name' => 'Modified'];
        $entry1->save();
        $entry2->new_values = ['first_name' => 'Modified'];
        $entry2->save();

        // Assert - Verify both entries retained their original values
        $retrievedEntry1 = AuditTrail::find($entry1->id);
        $retrievedEntry2 = AuditTrail::find($entry2->id);

        $this->assertEquals($originalEntry1Values, $retrievedEntry1->new_values);
        $this->assertEquals($originalEntry2Values, $retrievedEntry2->new_values);
    }

    /**
     * Property 10: Audit Trail is Immutable
     *
     * Audit trail entries should preserve all original data fields.
     *
     * Validates: Requirements 13.5
     *
     * @test
     */
    public function audit_trail_preserves_all_original_data()
    {
        // Arrange
        $user = User::factory()->create();
        $record = MainSystem::factory()->create();

        $originalData = [
            'user_id' => $user->id,
            'action_type' => 'update',
            'model_type' => MainSystem::class,
            'model_id' => $record->id,
            'old_values' => ['first_name' => 'John', 'last_name' => 'Doe'],
            'new_values' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
            'changed_fields' => ['first_name'],
            'reason' => 'Name correction',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome/91.0',
        ];

        $auditEntry = AuditTrail::create($originalData);

        // Act - Retrieve the entry
        $retrievedEntry = AuditTrail::find($auditEntry->id);

        // Assert - Verify all fields match original data
        $this->assertEquals($originalData['user_id'], $retrievedEntry->user_id);
        $this->assertEquals($originalData['action_type'], $retrievedEntry->action_type);
        $this->assertEquals($originalData['model_type'], $retrievedEntry->model_type);
        $this->assertEquals($originalData['model_id'], $retrievedEntry->model_id);
        $this->assertEquals($originalData['old_values'], $retrievedEntry->old_values);
        $this->assertEquals($originalData['new_values'], $retrievedEntry->new_values);
        $this->assertEquals($originalData['changed_fields'], $retrievedEntry->changed_fields);
        $this->assertEquals($originalData['reason'], $retrievedEntry->reason);
        $this->assertEquals($originalData['ip_address'], $retrievedEntry->ip_address);
        $this->assertEquals($originalData['user_agent'], $retrievedEntry->user_agent);
    }
}
