<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $fields = ['id', 'name', 'guard_name'];

        if ($search) {
            $permissions = $this->permissionService->searchPermissions($search, $fields);
        } else {
            $permissions = $this->permissionService->getAll($fields);
        }

        return response()->json([
            'data' => PermissionResource::collection($permissions->items()),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ]
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $permission = $this->permissionService->getById($id);
        return response()->json(['data' => new PermissionResource($permission)]);
    }

    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->create($request->validated());
        return response()->json([
            'data' => new PermissionResource($permission),
            'message' => 'Permission created successfully'
        ], 201);
    }

    public function update(PermissionRequest $request, int $id): JsonResponse
    {
        $permission = $this->permissionService->update($id, $request->validated());
        return response()->json([
            'data' => new PermissionResource($permission),
            'message' => 'Permission updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->permissionService->delete($id);
        return response()->json(['message' => 'Permission deleted successfully']);
    }

    public function getAllPermissions(): JsonResponse
    {
        $permissions = $this->permissionService->getAllPermissions();
        return response()->json(['data' => PermissionResource::collection($permissions)]);
    }

    public function getPermissionsByGuard(Request $request): JsonResponse
    {
        $guard = $request->get('guard', 'web');
        $permissions = $this->permissionService->getPermissionsByGuard($guard);
        return response()->json(['data' => PermissionResource::collection($permissions)]);
    }
}
