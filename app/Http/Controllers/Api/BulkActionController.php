<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BulkActionService;
use App\Services\MainSystemValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkActionController extends Controller
{
    private BulkActionService $bulkActionService;
    private MainSystemValidationService $validationService;

    public function __construct(
        BulkActionService $bulkActionService,
        MainSystemValidationService $validationService
    ) {
        $this->bulkActionService = $bulkActionService;
        $this->validationService = $validationService;
    }

    /**
     * Bulk delete multiple records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $validation = $this->validationService->validateBulkDelete($request->all());

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors'],
            ], 422);
        }

        $result = $this->bulkActionService->bulkDelete($request->input('recordIds'));

        return response()->json($result);
    }

    /**
     * Bulk update status for multiple records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $validation = $this->validationService->validateBulkStatusUpdate($request->all());

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors'],
            ], 422);
        }

        $result = $this->bulkActionService->bulkUpdateStatus(
            $request->input('recordIds'),
            $request->input('status')
        );

        return response()->json($result);
    }

    /**
     * Bulk update category for multiple records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCategory(Request $request): JsonResponse
    {
        $validation = $this->validationService->validateBulkCategoryUpdate($request->all());

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'errors' => $validation['errors'],
            ], 422);
        }

        $result = $this->bulkActionService->bulkUpdateCategory(
            $request->input('recordIds'),
            $request->input('category')
        );

        return response()->json($result);
    }
}
