<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class MainSystem extends Model
{
    use HasFactory;
    protected $table = 'main_system';

    protected $fillable = [
        'uid',
        'regs_no',
        'registration_date',
        'status',
        'category',
        'id_field',
        'origin_batch_id',
        'origin_match_result_id',
        'last_name',
        'first_name',
        'middle_name',
        'last_name_normalized',
        'first_name_normalized',
        'middle_name_normalized',
        'suffix',
        'birthday',
        'gender',
        'civil_status',
        'address',
        'barangay',
    ];

    protected $casts = [
        'birthday' => 'date',
        'registration_date' => 'date',
    ];

    public function originBatch()
    {
        return $this->belongsTo(UploadBatch::class, 'origin_batch_id');
    }

    /**
     * Get all audit trail entries for this record
     */
    public function auditTrail(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Get all template field values for this MainSystem record
     */
    public function templateFieldValues(): HasMany
    {
        return $this->hasMany(TemplateFieldValue::class, 'main_system_id');
    }

    /**
     * Get a specific template field value by field name
     *
     * @param string $fieldName The name of the template field
     * @return string|null The field value or null if not found
     */
    public function getTemplateFieldValue(string $fieldName): ?string
    {
        $value = $this->templateFieldValues()
            ->whereHas('templateField', function ($query) use ($fieldName) {
                $query->where('field_name', $fieldName);
            })
            ->first();

        return $value?->value;
    }

    /**
     * Get all template fields as an associative array
     *
     * @return array Associative array of field_name => value
     */
    public function getAllTemplateFields(): array
    {
        return $this->templateFieldValues()
            ->with('templateField')
            ->get()
            ->mapWithKeys(function ($tfv) {
                return [$tfv->templateField->field_name => $tfv->value];
            })
            ->toArray();
    }

    /**
     * Check if a template field exists for this record
     *
     * @param string $fieldName The name of the template field
     * @return bool True if field exists, false otherwise
     */
    public function hasTemplateField(string $fieldName): bool
    {
        return $this->templateFieldValues()
            ->whereHas('templateField', function ($query) use ($fieldName) {
                $query->where('field_name', $fieldName);
            })
            ->exists();
    }

    /**
     * Get all template field values that need review (conflicts)
     *
     * @return Collection Collection of TemplateFieldValue records with needs_review=true
     */
    public function getTemplateFieldsNeedingReview(): Collection
    {
        return $this->templateFieldValues()
            ->where('needs_review', true)
            ->get();
    }


}
