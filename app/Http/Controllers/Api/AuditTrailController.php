<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MainSystem;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    private AuditTrailService $auditService;

    public function __construct(AuditTrailService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get audit trail entries for a record
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $recordId = $request->input('recordId');
        $actionType = $request->input('action');
        $limit = $request->input('limit', 50);

        if (!$recordId) {
            return response()->json([
                'success' => false,
                'message' => 'recordId parameter is required',
            ], 400);
        }

        // Verify the record exists
        $record = MainSystem::find($recordId);
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], 404);
        }

        $entries = $this->auditService->getAuditTrail(
            MainSystem::class,
            $recordId,
            $actionType,
            $limit
        );

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }
}
