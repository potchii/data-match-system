<?php

namespace App\Services;

use App\Models\TemplateField;
use App\Models\TemplateFieldValue;
use App\Models\MainSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class TemplateFieldPersistenceService
{
    /**
     * Cache for TemplateField definitions to avoid repeated queries
     */
    private array $fieldCache = [];

    /**
     * Constructor with dependency injection
     */
    public function __construct()
    {
    }

    /**
     * Persist template field values based on match confidence
     *
     * @param int $mainSystemId The MainSystem record ID
     * @param array $templateFields Array of template field data [field_id => value, ...]
     * @param int $batchId The upload batch ID
     * @param int $matchConfidence Match confidence percentage (0, 70, 80, 90, 100)
     * @param int|null $templateId The template ID for validation
     * @return array Summary of created, updated, and conflicted fields
     * @throws \Exception
     */
    public function persistTemplateFields(
        int $mainSystemId,
        array $templateFields,
        int $batchId,
        int $matchConfidence,
        ?int $templateId = null
    ): array {
        return DB::transaction(function () use ($mainSystemId, $templateFields, $batchId, $matchConfidence, $templateId) {
            try {
                // Route to appropriate merge strategy
                $result = match ($matchConfidence) {
                    0 => $this->handleNewRecord($mainSystemId, $templateFields, $batchId),
                    70 => $this->handlePossibleDuplicate($mainSystemId, $templateFields, $batchId),
                    80, 90, 100 => $this->handleMatchedRecord($mainSystemId, $templateFields, $batchId),
                    default => throw new \Exception("Invalid match confidence: {$matchConfidence}"),
                };

                // Log the operation
                Log::info('Template fields persisted', [
                    'main_system_id' => $mainSystemId,
                    'batch_id' => $batchId,
                    'match_confidence' => $matchConfidence,
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'conflicted' => $result['conflicted'],
                ]);

                return $result;
            } catch (\Exception $e) {
                Log::error('Template field persistence failed', [
                    'main_system_id' => $mainSystemId,
                    'batch_id' => $batchId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Handle NEW RECORD merge strategy (0% confidence)
     *
     * @param int $mainSystemId The MainSystem record ID
     * @param array $templateFields Array of template field data
     * @param int $batchId The upload batch ID
     * @return array Summary of created fields
     */
    private function handleNewRecord(int $mainSystemId, array $templateFields, int $batchId): array
    {
        $created = 0;
        $records = [];

        foreach ($templateFields as $fieldId => $value) {
            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Get field definition
            $field = $this->getFieldDefinition((int) $fieldId);
            if (!$field) {
                Log::warning("Template field not found", ['field_id' => $fieldId]);
                continue;
            }

            // Validate value
            $validation = $this->validateFieldValue($field, $value);
            if (!$validation['valid']) {
                Log::warning("Template field validation failed", [
                    'field_id' => $fieldId,
                    'error' => $validation['error'],
                ]);
                continue;
            }

            // Prepare record for bulk insert
            $records[] = [
                'main_system_id' => $mainSystemId,
                'template_field_id' => $fieldId,
                'value' => $value,
                'previous_value' => null,
                'batch_id' => $batchId,
                'needs_review' => false,
                'conflict_with' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $created++;
        }

        // Bulk insert all records
        if (!empty($records)) {
            TemplateFieldValue::insert($records);
        }

        return [
            'created' => $created,
            'updated' => 0,
            'conflicted' => 0,
        ];
    }

    /**
     * Handle MATCHED RECORD merge strategy (80%, 90%, 100% confidence)
     *
     * @param int $mainSystemId The MainSystem record ID
     * @param array $templateFields Array of template field data
     * @param int $batchId The upload batch ID
     * @return array Summary of created, updated, and conflicted fields
     */
    private function handleMatchedRecord(int $mainSystemId, array $templateFields, int $batchId): array
    {
        $created = 0;
        $updated = 0;
        $fieldIds = array_map('intval', array_keys($templateFields));

        // Get existing values for this record
        $existingValues = $this->getExistingValues($mainSystemId, $fieldIds);
        $existingMap = $existingValues->keyBy('template_field_id');

        foreach ($templateFields as $fieldId => $newValue) {
            // Skip empty values
            if ($newValue === null || $newValue === '') {
                continue;
            }

            // Get field definition
            $field = $this->getFieldDefinition((int) $fieldId);
            if (!$field) {
                Log::warning("Template field not found", ['field_id' => $fieldId]);
                continue;
            }

            // Validate value
            $validation = $this->validateFieldValue($field, $newValue);
            if (!$validation['valid']) {
                Log::warning("Template field validation failed", [
                    'field_id' => $fieldId,
                    'error' => $validation['error'],
                ]);
                continue;
            }

            if (isset($existingMap[$fieldId])) {
                // Existing record found
                $existing = $existingMap[$fieldId];

                // Check if value is identical
                if ($existing->value === $newValue) {
                    // Skip update if values are identical
                    continue;
                }

                // Update existing record
                if ($existing->value === null || $existing->value === '') {
                    // Update empty value without history
                    $existing->update([
                        'value' => $newValue,
                        'batch_id' => $batchId,
                        'needs_review' => false,
                    ]);
                } else {
                    // Update with history preservation
                    $existing->update([
                        'value' => $newValue,
                        'previous_value' => $existing->value,
                        'batch_id' => $batchId,
                        'needs_review' => false,
                    ]);
                }
                $updated++;
            } else {
                // Create new record
                TemplateFieldValue::create([
                    'main_system_id' => $mainSystemId,
                    'template_field_id' => $fieldId,
                    'value' => $newValue,
                    'previous_value' => null,
                    'batch_id' => $batchId,
                    'needs_review' => false,
                    'conflict_with' => null,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'conflicted' => 0,
        ];
    }

    /**
     * Handle POSSIBLE DUPLICATE merge strategy (70% confidence)
     *
     * @param int $mainSystemId The MainSystem record ID
     * @param array $templateFields Array of template field data
     * @param int $batchId The upload batch ID
     * @return array Summary of created and conflicted fields
     */
    private function handlePossibleDuplicate(int $mainSystemId, array $templateFields, int $batchId): array
    {
        $created = 0;
        $conflicted = 0;
        $fieldIds = array_map('intval', array_keys($templateFields));

        // Get existing values for this record
        $existingValues = $this->getExistingValues($mainSystemId, $fieldIds);
        $existingMap = $existingValues->keyBy('template_field_id');

        foreach ($templateFields as $fieldId => $newValue) {
            // Skip empty values
            if ($newValue === null || $newValue === '') {
                continue;
            }

            // Get field definition
            $field = $this->getFieldDefinition((int) $fieldId);
            if (!$field) {
                Log::warning("Template field not found", ['field_id' => $fieldId]);
                continue;
            }

            // Validate value
            $validation = $this->validateFieldValue($field, $newValue);
            if (!$validation['valid']) {
                Log::warning("Template field validation failed", [
                    'field_id' => $fieldId,
                    'error' => $validation['error'],
                ]);
                continue;
            }

            if (isset($existingMap[$fieldId])) {
                // Existing record found - mark as conflict
                $existing = $existingMap[$fieldId];
                $existing->update([
                    'needs_review' => true,
                    'previous_value' => $existing->value,
                    'value' => $newValue,
                    'batch_id' => $batchId,
                ]);
                $conflicted++;
            } else {
                // No existing record - create new with needs_review flag
                TemplateFieldValue::create([
                    'main_system_id' => $mainSystemId,
                    'template_field_id' => $fieldId,
                    'value' => $newValue,
                    'previous_value' => null,
                    'batch_id' => $batchId,
                    'needs_review' => true,
                    'conflict_with' => null,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => 0,
            'conflicted' => $conflicted,
        ];
    }

    /**
     * Resolve a conflict for a template field value
     *
     * @param int $templateFieldValueId The TemplateFieldValue ID to resolve
     * @param string $resolution The resolution action: 'keep_existing', 'use_new', or 'edit_manually'
     * @param string|null $customValue Custom value if resolution is 'edit_manually'
     * @return void
     * @throws \Exception
     */
    public function resolveConflict(
        int $templateFieldValueId,
        string $resolution,
        ?string $customValue = null
    ): void {
        $tfv = TemplateFieldValue::findOrFail($templateFieldValueId);
        $tfv->resolveConflict($resolution, $customValue);

        Log::info('Conflict resolved', [
            'template_field_value_id' => $templateFieldValueId,
            'resolution' => $resolution,
        ]);
    }

    /**
     * Bulk persist template field values for multiple records
     *
     * @param array $records Array of records to persist
     *   Each record: ['main_system_id' => int, 'template_fields' => [...], 'batch_id' => int, 'match_confidence' => int]
     * @return array Summary of all operations
     */
    public function bulkPersist(array $records): array
    {
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalConflicted = 0;

        // Pre-cache all template field definitions
        $allFieldIds = [];
        foreach ($records as $record) {
            $allFieldIds = array_merge($allFieldIds, array_keys($record['template_fields'] ?? []));
        }
        $this->preCacheFields(array_unique($allFieldIds));

        // Process each record
        foreach ($records as $record) {
            try {
                $result = $this->persistTemplateFields(
                    $record['main_system_id'],
                    $record['template_fields'] ?? [],
                    $record['batch_id'],
                    $record['match_confidence'],
                    $record['template_id'] ?? null
                );

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalConflicted += $result['conflicted'];
            } catch (\Exception $e) {
                Log::error('Bulk persist failed for record', [
                    'main_system_id' => $record['main_system_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'created' => $totalCreated,
            'updated' => $totalUpdated,
            'conflicted' => $totalConflicted,
            'total_records' => count($records),
        ];
    }

    /**
     * Validate a field value against its TemplateField definition
     *
     * @param TemplateField $field The field definition
     * @param mixed $value The value to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateFieldValue(TemplateField $field, mixed $value): array
    {
        return $field->validateValue($value);
    }

    /**
     * Get a TemplateField definition, using cache when available
     *
     * @param int $fieldId The field ID
     * @return TemplateField|null
     */
    private function getFieldDefinition(int $fieldId): ?TemplateField
    {
        if (!isset($this->fieldCache[$fieldId])) {
            $this->fieldCache[$fieldId] = TemplateField::find($fieldId);
        }

        return $this->fieldCache[$fieldId];
    }

    /**
     * Pre-cache multiple TemplateField definitions
     *
     * @param array $fieldIds Array of field IDs to cache
     * @return void
     */
    private function preCacheFields(array $fieldIds): void
    {
        $uncachedIds = array_filter($fieldIds, fn($id) => !isset($this->fieldCache[$id]));

        if (!empty($uncachedIds)) {
            $fields = TemplateField::whereIn('id', $uncachedIds)->get();
            foreach ($fields as $field) {
                $this->fieldCache[$field->id] = $field;
            }
        }
    }

    /**
     * Get existing TemplateFieldValue records for a MainSystem record
     *
     * @param int $mainSystemId The MainSystem record ID
     * @param array $fieldIds Array of field IDs to query
     * @return Collection Collection of existing TemplateFieldValue records
     */
    private function getExistingValues(int $mainSystemId, array $fieldIds): Collection
    {
        return TemplateFieldValue::where('main_system_id', $mainSystemId)
            ->whereIn('template_field_id', $fieldIds)
            ->get();
    }
}
