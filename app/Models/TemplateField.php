<?php

namespace App\Models;

use App\Helpers\CoreFieldMappings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateField extends Model
{
    use HasFactory;
    protected $fillable = [
        'template_id',
        'field_name',
        'field_type',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the template that owns this field
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ColumnMappingTemplate::class, 'template_id');
    }

    /**
     * Validate value against field type
     *
     * @param mixed $value The value to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            if ($this->is_required) {
                return [
                    'valid' => false,
                    'error' => "Field '{$this->field_name}' is required",
                ];
            }
            return ['valid' => true, 'error' => null];
        }

        return match($this->field_type) {
            'string' => ['valid' => true, 'error' => null],
            'integer' => $this->validateInteger($value),
            'decimal' => $this->validateDecimal($value),
            'date' => $this->validateDate($value),
            'boolean' => $this->validateBoolean($value),
            default => ['valid' => false, 'error' => "Field type '{$this->field_type}' is not recognized"],
        };
    }

    /**
     * Validate integer value
     *
     * @param mixed $value The value to validate
     * @return array Validation result
     */
    protected function validateInteger(mixed $value): array
    {
        if (!is_numeric($value) || strpos((string) $value, '.') !== false) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be an integer",
            ];
        }
        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate decimal value
     *
     * @param mixed $value The value to validate
     * @return array Validation result
     */
    protected function validateDecimal(mixed $value): array
    {
        if (!is_numeric($value)) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be a number",
            ];
        }
        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate date value
     *
     * @param mixed $value The value to validate
     * @return array Validation result
     */
    protected function validateDate(mixed $value): array
    {
        try {
            $date = date('Y-m-d', strtotime($value));
            if ($date === '1970-01-01' && $value !== '1970-01-01') {
                throw new \Exception('Invalid date');
            }
            return ['valid' => true, 'error' => null];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => "Field '{$this->field_name}' must be a valid date",
            ];
        }
    }

    /**
     * Validate boolean value
     *
     * @param mixed $value The value to validate
     * @return array Validation result
     */
    protected function validateBoolean(mixed $value): array
    {
        $normalized = strtolower(trim((string) $value));
        
        if (in_array($normalized, ['true', '1', 'yes', 'y', 'false', '0', 'no', 'n'])) {
            return ['valid' => true, 'error' => null];
        }

        return [
            'valid' => false,
            'error' => "Field '{$this->field_name}' must be true/false, yes/no, or 1/0",
        ];
    }

    /**
     * Validate field name format (alphanumeric + underscores only)
     *
     * @param string $name The field name to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidFieldName(string $name): bool
    {
        return CoreFieldMappings::isValidFieldName($name);
    }
}
