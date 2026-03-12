<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MainSystem;
use App\Services\MainSystemCrudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainSystemController extends Controller
{
    private MainSystemCrudService $crudService;

    public function __construct(MainSystemCrudService $crudService)
    {
        $this->crudService = $crudService;
    }

    /**
     * Create a new Main System record
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->crudService->createRecord($request->all());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ], 201);
    }

    /**
     * Get a Main System record with template fields
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $record = $this->crudService->getRecord($id);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    /**
     * Update a Main System record
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $result = $this->crudService->updateRecord($id, $request->all());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Delete a Main System record
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->crudService->deleteRecord($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}
