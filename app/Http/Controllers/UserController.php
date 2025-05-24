<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\UserRoleResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $fields = ['id', 'name', 'email', 'project', 'department_id'];
        $users = $this->userService->getAll($fields ?: ['*']);
        return response()->json(UserResource::collection($users));
    }

    public function show(int $id)
    {
        $fields = ['id', 'name', 'email', 'project', 'department_id'];
        $user = $this->userService->getById($id, $fields ?: ['*']);
        return response()->json(new UserResource($user));
    }

    public function store(Request $request)
    {
        $user = $this->userService->create($request->validated());
        return response()->json(new UserResource($user));
    }

    public function update(Request $request, int $id)
    {
        $user = $this->userService->update($id, $request->validated());
        return response()->json(new UserResource($user));
    }

    public function destroy(int $id)
    {
        $this->userService->delete($id);
        return response()->json(['message' => 'User deleted successfully']);
    }
}
