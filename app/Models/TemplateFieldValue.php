<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateFieldValue extends Model
{
    use HasFactory;

    protected $table = 'template_field_values';

    protected $fillable = [
        'main_system_id',
        'template_field_id',
        'value',
        'previous_value',
        'batch_id',
        'needs_review',
        'conflict_with',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the MainSystem record that owns this template field value
     */
    public function mainSystem(): BelongsTo
    {
        return $this->belongsTo(MainSystem::class, 'main_system_id');
    }

    /**
     * Get the TemplateField definition for this value
     */
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class, 'template_field_id');
    }

    /**
     * Get the UploadBatch that created or last modified this value
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    /**
     * Get the conflicting TemplateFieldValue record (self-referencing)
     */
    public function conflictingValue(): BelongsTo
    {
        return $this->belongsTo(TemplateFieldValue::class, 'conflict_with');
    }

    /**
     * Check if this template field value has a conflict
     *
     * @return bool True if conflict_with is not null, false otherwise
     */
    public function hasConflict(): bool
    {
        return $this->conflict_with !== null;
    }

    /**
     * Get the history of value changes for this template field
     *
     * @return array Array containing current and previous values
     */
    public function getHistory(): array
    {
        $history = [
            'current' => $this->value,
        ];

        if ($this->previous_value !== null) {
            $history['previous'] = $this->previous_value;
        }

        return $history;
    }

    /**
     * Resolve a conflict by applying the chosen resolution
     *
     * @param string $resolution The resolution action: 'keep_existing', 'use_new', or 'edit_manually'
     * @param string|null $customValue Custom value if resolution is 'edit_manually'
     * @return void
     * @throws \Exception If resolution is invalid or custom value fails validation
     */
    public function resolveConflict(string $resolution, ?string $customValue = null): void
    {
        if (!in_array($resolution, ['keep_existing', 'use_new', 'edit_manually'])) {
            throw new \Exception("Invalid resolution action: {$resolution}");
        }

        if ($resolution === 'keep_existing') {
            // Delete the conflicting record (this record)
            $this->delete();
        } elseif ($resolution === 'use_new') {
            // Update the existing record with the new value
            if ($this->conflictingValue) {
                $this->conflictingValue->update([
                    'value' => $this->value,
                    'previous_value' => $this->conflictingValue->value,
                    'needs_review' => false,
                ]);
            }
            // Delete this conflicting record
            $this->delete();
        } elseif ($resolution === 'edit_manually') {
            if ($customValue === null) {
                throw new \Exception('Custom value is required for edit_manually resolution');
            }

            // Validate custom value against field type
            $templateField = $this->templateField;
            $validation = $templateField->validateValue($customValue);

            if (!$validation['valid']) {
                throw new \Exception("Validation failed: {$validation['error']}");
            }

            // Update the existing record with the custom value
            if ($this->conflictingValue) {
                $this->conflictingValue->update([
                    'value' => $customValue,
                    'previous_value' => $this->conflictingValue->value,
                    'needs_review' => false,
                ]);
            }
            // Delete this conflicting record
            $this->delete();
        }
    }

    /**
     * Get all template field values added by a specific batch
     *
     * @param int $batchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeByBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Get all template field values modified by a specific batch
     *
     * @param int $batchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeModifiedByBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId)->whereNotNull('previous_value');
    }

    /**
     * Get audit timeline for a MainSystem record
     *
     * @param int $mainSystemId
     * @return \Illuminate\Support\Collection
     */
    public static function getAuditTimeline(int $mainSystemId)
    {
        return self::where('main_system_id', $mainSystemId)
            ->with(['templateField', 'batch'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($tfv) {
                return [
                    'id' => $tfv->id,
                    'field_name' => $tfv->templateField->field_name,
                    'value' => $tfv->value,
                    'previous_value' => $tfv->previous_value,
                    'batch_id' => $tfv->batch_id,
                    'batch_filename' => $tfv->batch?->filename,
                    'created_at' => $tfv->created_at,
                    'updated_at' => $tfv->updated_at,
                ];
            });
    }
}
