<?php

namespace App\Services;

use App\Models\MainSystem;
use App\Models\TemplateFieldValue;
use Illuminate\Support\Facades\DB;

class MainSystemCrudService
{
    private MainSystemValidationService $validationService;
    private AuditTrailService $auditService;

    public function __construct(
        MainSystemValidationService $validationService,
        AuditTrailService $auditService
    ) {
        $this->validationService = $validationService;
        $this->auditService = $auditService;
    }

    /**
     * Create a new Main System record
     *
     * @param array $data The record data
     * @return array Result with success flag, record data, and errors
     */
    public function createRecord(array $data): array
    {
        // Validate the data
        $validation = $this->validationService->validateForCreate($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        try {
            $inTransaction = DB::transactionLevel() > 0;
            if (!$inTransaction) {
                DB::beginTransaction();
            }

            // Extract template fields if present
            $templateFields = $data['templateFields'] ?? [];
            unset($data['templateFields']);

            // Create the record
            $record = MainSystem::create($data);

            // Save template field values
            if (!empty($templateFields)) {
                $this->saveTemplateFields($record->id, $templateFields);
            }

            // Log the creation
            $this->auditService->logCreate(
                MainSystem::class,
                $record->id,
                $record->toArray()
            );

            if (!$inTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'data' => $record->load('templateFieldValues'),
            ];
        } catch (\Exception $e) {
            if (!$inTransaction) {
                DB::rollBack();
            }
            return [
                'success' => false,
                'errors' => ['general' => $e->getMessage()],
            ];
        }
    }

    /**
     * Update an existing Main System record
     *
     * @param int $recordId The ID of the record to update
     * @param array $data The updated data
     * @return array Result with success flag, record data, and errors
     */
    public function updateRecord(int $recordId, array $data): array
    {
        // Validate the data
        $validation = $this->validationService->validateForUpdate($data, $recordId);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        try {
            $inTransaction = DB::transactionLevel() > 0;
            if (!$inTransaction) {
                DB::beginTransaction();
            }

            $record = MainSystem::find($recordId);
            if (!$record) {
                if (!$inTransaction) {
                    DB::rollBack();
                }
                return [
                    'success' => false,
                    'errors' => ['general' => 'Record not found'],
                ];
            }

            // Store old values for audit trail
            $oldValues = $record->toArray();

            // Extract template fields if present
            $templateFields = $data['templateFields'] ?? [];
            unset($data['templateFields']);

            // Update the record
            $record->update($data);

            // Update template field values
            if (!empty($templateFields)) {
                $this->saveTemplateFields($record->id, $templateFields);
            }

            // Log the update
            $this->auditService->logUpdate(
                MainSystem::class,
                $record->id,
                $oldValues,
                $record->fresh()->toArray()
            );

            if (!$inTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'data' => $record->fresh()->load('templateFieldValues'),
            ];
        } catch (\Exception $e) {
            if (!$inTransaction) {
                DB::rollBack();
            }
            return [
                'success' => false,
                'errors' => ['general' => $e->getMessage()],
            ];
        }
    }

    /**
     * Delete a Main System record
     *
     * @param int $recordId The ID of the record to delete
     * @return array Result with success flag and message
     */
    public function deleteRecord(int $recordId): array
    {
        try {
            $inTransaction = DB::transactionLevel() > 0;
            if (!$inTransaction) {
                DB::beginTransaction();
            }

            $record = MainSystem::find($recordId);
            if (!$record) {
                if (!$inTransaction) {
                    DB::rollBack();
                }
                return [
                    'success' => false,
                    'message' => 'Record not found',
                ];
            }

            // Store record data for audit trail
            $recordData = $record->toArray();

            // Delete the record
            $record->delete();

            // Log the deletion
            $this->auditService->logDelete(
                MainSystem::class,
                $recordId,
                $recordData
            );

            if (!$inTransaction) {
                DB::commit();
            }

            return [
                'success' => true,
                'message' => 'Record deleted successfully',
            ];
        } catch (\Exception $e) {
            if (!$inTransaction) {
                DB::rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a Main System record with template fields
     *
     * @param int $recordId The ID of the record to retrieve
     * @return MainSystem|null The record or null if not found
     */
    public function getRecord(int $recordId): ?MainSystem
    {
        return MainSystem::with('templateFieldValues.templateField')->find($recordId);
    }

    /**
     * Save template field values for a record
     *
     * @param int $recordId The ID of the record
     * @param array $templateFields Array of field_name => value pairs
     * @return void
     */
    private function saveTemplateFields(int $recordId, array $templateFields): void
    {
        foreach ($templateFields as $fieldName => $value) {
            // Find the template field by name
            $templateField = \App\Models\TemplateField::where('field_name', $fieldName)->first();

            if ($templateField) {
                // Update or create the template field value
                TemplateFieldValue::updateOrCreate(
                    [
                        'main_system_id' => $recordId,
                        'template_field_id' => $templateField->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
