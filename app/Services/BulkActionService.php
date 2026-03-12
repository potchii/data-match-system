<?php

namespace App\Services;

use App\Models\MainSystem;
use Illuminate\Support\Facades\DB;

class BulkActionService
{
    private AuditTrailService $auditService;

    public function __construct(AuditTrailService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Bulk delete multiple records
     *
     * @param array $recordIds Array of record IDs to delete
     * @return array Result with deleted count, failed count, and errors
     */
    public function bulkDelete(array $recordIds): array
    {
        $deleted = 0;
        $failed = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($recordIds as $recordId) {
                try {
                    $record = MainSystem::find($recordId);

                    if (!$record) {
                        $failed++;
                        $errors[$recordId] = 'Record not found';
                        continue;
                    }

                    // Log the deletion before deleting
                    $this->auditService->logDelete(
                        MainSystem::class,
                        $recordId,
                        $record->toArray()
                    );

                    // Delete the record
                    $record->delete();
                    $deleted++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[$recordId] = $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'deleted' => 0,
                'failed' => count($recordIds),
                'errors' => ['general' => $e->getMessage()],
            ];
        }

        return [
            'success' => $failed === 0,
            'deleted' => $deleted,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk update status for multiple records
     *
     * @param array $recordIds Array of record IDs to update
     * @param string $status New status value
     * @return array Result with updated count, failed count, and errors
     */
    public function bulkUpdateStatus(array $recordIds, string $status): array
    {
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($recordIds as $recordId) {
                try {
                    $record = MainSystem::find($recordId);

                    if (!$record) {
                        $failed++;
                        $errors[$recordId] = 'Record not found';
                        continue;
                    }

                    $oldStatus = $record->status;

                    // Log the update
                    $this->auditService->logUpdate(
                        MainSystem::class,
                        $recordId,
                        ['status' => $oldStatus],
                        ['status' => $status]
                    );

                    // Update the record
                    $record->update(['status' => $status]);
                    $updated++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[$recordId] = $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'updated' => 0,
                'failed' => count($recordIds),
                'errors' => ['general' => $e->getMessage()],
            ];
        }

        return [
            'success' => $failed === 0,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk update category for multiple records
     *
     * @param array $recordIds Array of record IDs to update
     * @param string $category New category value
     * @return array Result with updated count, failed count, and errors
     */
    public function bulkUpdateCategory(array $recordIds, string $category): array
    {
        $updated = 0;
        $failed = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($recordIds as $recordId) {
                try {
                    $record = MainSystem::find($recordId);

                    if (!$record) {
                        $failed++;
                        $errors[$recordId] = 'Record not found';
                        continue;
                    }

                    $oldCategory = $record->category;

                    // Log the update
                    $this->auditService->logUpdate(
                        MainSystem::class,
                        $recordId,
                        ['category' => $oldCategory],
                        ['category' => $category]
                    );

                    // Update the record
                    $record->update(['category' => $category]);
                    $updated++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[$recordId] = $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'updated' => 0,
                'failed' => count($recordIds),
                'errors' => ['general' => $e->getMessage()],
            ];
        }

        return [
            'success' => $failed === 0,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
