<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    private RoleService $roleService;
    private PermissionService $permissionService;

    public function __construct(RoleService $roleService, PermissionService $permissionService)
    {
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $fields = ['id', 'name', 'guard_name'];

        if ($search) {
            $roles = $this->roleService->searchRoles($search, $fields);
        } else {
            $roles = $this->roleService->getAll($fields);
        }

        return response()->json([
            'data' => RoleResource::collection($roles->items()),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ]
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getById($id);
        return response()->json(['data' => new RoleResource($role)]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());
        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role created successfully'
        ], 201);
    }

    public function update(RoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->update($id, $request->validated());
        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->roleService->delete($id);
        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = $this->roleService->assignPermissions($id, $request->permissions);
        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Permissions assigned successfully'
        ]);
    }

    public function getPermissions(int $id): JsonResponse
    {
        $permissions = $this->roleService->getRolePermissions($id);
        return response()->json(['data' => $permissions]);
    }

    public function getAvailablePermissions(): JsonResponse
    {
        $permissions = $this->permissionService->getAllPermissions();
        return response()->json(['data' => $permissions]);
    }

    public function getAllRoles(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json(['data' => RoleResource::collection($roles)]);
    }
}
