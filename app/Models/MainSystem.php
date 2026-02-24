<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSystem extends Model
{
    use HasFactory;
    protected $table = 'main_system';

    protected $fillable = [
        'uid',
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
        'street_no',
        'street',
        'city',
        'province',
        'barangay',
        'additional_attributes',
    ];

    protected $casts = [
        'birthday' => 'date',
        'additional_attributes' => 'array',
    ];

    public function originBatch()
    {
        return $this->belongsTo(UploadBatch::class, 'origin_batch_id');
    }

    /**
     * Get all dynamic attribute keys
     */
    public function getDynamicAttributeKeys(): array
    {
        return array_keys($this->additional_attributes ?? []);
    }

    /**
     * Check if a dynamic attribute exists
     */
    public function hasDynamicAttribute(string $key): bool
    {
        return isset($this->additional_attributes[$key]);
    }

    /**
     * Get a dynamic attribute value with default
     */
    public function getDynamicAttribute(string $key, $default = null)
    {
        return $this->additional_attributes[$key] ?? $default;
    }

    /**
     * Set a dynamic attribute value
     */
    public function setDynamicAttribute(string $key, $value): void
    {
        $attributes = $this->additional_attributes ?? [];
        $attributes[$key] = $value;
        $this->additional_attributes = $attributes;
    }


}
