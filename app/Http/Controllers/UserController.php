<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    private UserService $userService;
    private RoleService $roleService;

    public function __construct(UserService $userService, RoleService $roleService)
    {
        $this->userService = $userService;
        $this->roleService = $roleService;
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $fields = ['id', 'name', 'email', 'username', 'nik', 'project', 'department_id'];

        if ($search) {
            $users = $this->userService->searchUsers($search, $fields);
        } else {
            $users = $this->userService->getAll($fields);
        }

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getById($id);
        return response()->json(['data' => new UserResource($user)]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully'
        ], 201);
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->delete($id);
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user = $this->userService->assignRoles($id, $request->roles);
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Roles assigned successfully'
        ]);
    }

    public function getRoles(int $id): JsonResponse
    {
        $roles = $this->userService->getUserRoles($id);
        return response()->json(['data' => $roles]);
    }

    public function getPermissions(int $id): JsonResponse
    {
        $permissions = $this->userService->getUserPermissions($id);
        return response()->json(['data' => $permissions]);
    }

    public function getAvailableRoles(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        return response()->json(['data' => $roles]);
    }
}
