<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ColumnMappingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'mappings',
    ];

    protected $casts = [
        'mappings' => 'array',
    ];

    /**
     * Get the user that owns the template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the custom fields for this template
     */
    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class, 'template_id');
    }

    /**
     * Get templates for a specific user
     */
    public static function forUser(int $userId): Collection
    {
        return static::where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Apply template mappings to uploaded data
     *
     * @param array $row Raw Excel row
     * @return array Remapped row
     */
    public function applyTo(array $row): array
    {
        $remapped = [];
        
        // Apply core field mappings from template
        foreach ($this->mappings as $excelColumn => $systemField) {
            if (array_key_exists($excelColumn, $row)) {
                $remapped[$systemField] = $row[$excelColumn];
            }
        }
        
        // Preserve template fields (custom fields) that are in the row
        $templateFieldNames = $this->fields->pluck('field_name')->toArray();
        foreach ($templateFieldNames as $fieldName) {
            if (array_key_exists($fieldName, $row)) {
                $remapped[$fieldName] = $row[$fieldName];
            }
        }
        
        return $remapped;
    }

    /**
     * Validate template mappings structure
     */
    public function validateMappings(): bool
    {
        if (!is_array($this->mappings)) {
            return false;
        }

        foreach ($this->mappings as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validation rules for creating/updating templates
     */
    public static function validationRules(int $userId, ?int $templateId = null): array
    {
        $uniqueRule = 'unique:column_mapping_templates,name,NULL,id,user_id,' . $userId;
        
        if ($templateId) {
            $uniqueRule = 'unique:column_mapping_templates,name,' . $templateId . ',id,user_id,' . $userId;
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'mappings' => ['required', 'array'],
            'mappings.*' => ['required', 'string'],
        ];
    }

    /**
     * Get all expected column names (core + template fields)
     *
     * @return array Array of expected column names
     */
    public function getExpectedColumns(): array
    {
        // Core field mappings (Excel column names from template)
        $coreColumns = array_keys($this->mappings);
        
        // Template field names
        $templateColumns = $this->fields->pluck('field_name')->toArray();
        
        return array_merge($coreColumns, $templateColumns);
    }

    /**
     * Validate file columns against template
     *
     * @param array $fileColumns Array of column names from uploaded file
     * @return array Validation result with errors and details
     */
    public function validateFileColumns(array $fileColumns): array
    {
        $expected = $this->getExpectedColumns();
        $expectedLower = array_map('strtolower', $expected);
        $fileLower = array_map('strtolower', $fileColumns);
        
        $missing = array_diff($expectedLower, $fileLower);
        $extra = array_diff($fileLower, $expectedLower);
        
        $errors = [];
        
        foreach ($missing as $col) {
            $errors[] = "Required column '{$col}' is missing from your file. Please add this column to proceed.";
        }
        
        foreach ($extra as $col) {
            $errors[] = "Column '{$col}' is not expected in this template. Please remove it or update your template.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'expected' => $expected,
            'missing' => array_values($missing),
            'extra' => array_values($extra),
        ];
    }
}
