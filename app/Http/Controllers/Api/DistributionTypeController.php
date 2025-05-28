<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DistributionTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DistributionTypeController extends Controller
{
    protected DistributionTypeService $distributionTypeService;

    public function __construct(DistributionTypeService $distributionTypeService)
    {
        $this->distributionTypeService = $distributionTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $distributionTypes = $this->distributionTypeService->getAll();

            return response()->json([
                'success' => true,
                'data' => $distributionTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distribution types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|size:1|unique:distribution_types,code',
            'color' => 'required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $distributionType = $this->distributionTypeService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Distribution type created successfully',
                'data' => $distributionType
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create distribution type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $distributionType = $this->distributionTypeService->getById($id);

            return response()->json([
                'success' => true,
                'data' => $distributionType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Distribution type not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|size:1',
            'color' => 'sometimes|required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'priority' => 'sometimes|required|integer|min:1|max:10',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            $distributionType = $this->distributionTypeService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Distribution type updated successfully',
                'data' => $distributionType
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update distribution type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->distributionTypeService->delete($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete distribution type that is being used by distributions'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Distribution type deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete distribution type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate distribution type code uniqueness
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:1',
            'type_id' => 'nullable|integer'
        ]);

        try {
            $isValid = $this->distributionTypeService->validateCode(
                $request->code,
                $request->type_id
            );

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'Code is available' : 'Code already exists'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
