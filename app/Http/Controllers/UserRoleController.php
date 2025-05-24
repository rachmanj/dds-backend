<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserRoleService;
use App\Http\Resources\UserRoleResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserPermissionResource;

class UserRoleController extends Controller
{
    private UserRoleService $userRoleService;

    public function __construct(UserRoleService $userRoleService)
    {
        $this->userRoleService = $userRoleService;
    }

    //get authenticated user roles
    public function getAuthUserRoles()
    {
        $roles = $this->userRoleService->getAuthUserRoles();

        return response()->json([
            'roles' => $roles->pluck('name')
        ]);
    }

    //get authenticated user permissions
    public function getAuthUserPermissions()
    {
        $permissions = $this->userRoleService->getAuthUserPermissions();

        return response()->json([
            'permissions' => $permissions->pluck('name')
        ]);
    }
}
