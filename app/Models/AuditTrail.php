<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    use HasFactory;

    protected $table = 'audit_trail';

    protected $fillable = [
        'user_id',
        'action_type',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'changed_fields',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Prevent updates to audit trail entries (immutability)
     */
    public function save(array $options = [])
    {
        // If the model exists in the database, prevent the save
        if ($this->exists) {
            return false;
        }
        return parent::save($options);
    }

    /**
     * Prevent deletion of audit trail entries (immutability)
     */
    public function delete()
    {
        return false;
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by model type and ID
     */
    public function scopeForModel($query, string $modelType, int $modelId)
    {
        $modelTypeShort = class_basename($modelType);
        return $query->where('model_type', $modelTypeShort)
            ->where('model_id', $modelId);
    }

    /**
     * Scope to filter by action type
     */
    public function scopeByAction($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
