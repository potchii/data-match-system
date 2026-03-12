<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditTrailService
{
    /**
     * Log a create operation to the audit trail
     *
     * @param string $modelType The model class name
     * @param int $modelId The ID of the created record
     * @param array $newValues The new field values
     * @param string|null $reason Optional reason for the change
     * @return AuditTrail The created audit trail entry
     */
    public function logCreate(string $modelType, int $modelId, array $newValues, ?string $reason = null): AuditTrail
    {
        // Extract just the class name from fully qualified class name
        $modelTypeShort = class_basename($modelType);
        
        return AuditTrail::create([
            'user_id' => Auth::id(),
            'action_type' => 'create',
            'model_type' => $modelTypeShort,
            'model_id' => $modelId,
            'old_values' => null,
            'new_values' => $newValues,
            'changed_fields' => array_keys($newValues),
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log an update operation to the audit trail
     *
     * @param string $modelType The model class name
     * @param int $modelId The ID of the updated record
     * @param array $oldValues The old field values
     * @param array $newValues The new field values
     * @param string|null $reason Optional reason for the change
     * @return AuditTrail The created audit trail entry
     */
    public function logUpdate(
        string $modelType,
        int $modelId,
        array $oldValues,
        array $newValues,
        ?string $reason = null
    ): AuditTrail {
        // Only include changed fields
        $changedFields = [];
        foreach ($newValues as $field => $value) {
            if (!isset($oldValues[$field]) || $oldValues[$field] !== $value) {
                $changedFields[] = $field;
            }
        }

        // Extract just the class name from fully qualified class name
        $modelTypeShort = class_basename($modelType);

        return AuditTrail::create([
            'user_id' => Auth::id(),
            'action_type' => 'update',
            'model_type' => $modelTypeShort,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a delete operation to the audit trail
     *
     * @param string $modelType The model class name
     * @param int $modelId The ID of the deleted record
     * @param array $deletedValues The field values of the deleted record
     * @param string|null $reason Optional reason for the deletion
     * @return AuditTrail The created audit trail entry
     */
    public function logDelete(
        string $modelType,
        int $modelId,
        array $deletedValues,
        ?string $reason = null
    ): AuditTrail {
        // Extract just the class name from fully qualified class name
        $modelTypeShort = class_basename($modelType);
        
        return AuditTrail::create([
            'user_id' => Auth::id(),
            'action_type' => 'delete',
            'model_type' => $modelTypeShort,
            'model_id' => $modelId,
            'old_values' => null,
            'new_values' => $deletedValues,
            'changed_fields' => null,
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Get audit trail entries for a specific record
     *
     * @param string $modelType The model class name
     * @param int $modelId The ID of the record
     * @param string|null $actionType Optional filter by action type
     * @param int $limit Maximum number of entries to retrieve
     * @return \Illuminate\Database\Eloquent\Collection The audit trail entries
     */
    public function getAuditTrail(
        string $modelType,
        int $modelId,
        ?string $actionType = null,
        int $limit = 50
    ) {
        $query = AuditTrail::forModel($modelType, $modelId);

        if ($actionType) {
            $query->byAction($actionType);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trail entries for a specific user
     *
     * @param int $userId The ID of the user
     * @param int $limit Maximum number of entries to retrieve
     * @return \Illuminate\Database\Eloquent\Collection The audit trail entries
     */
    public function getUserAuditTrail(int $userId, int $limit = 50)
    {
        return AuditTrail::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
