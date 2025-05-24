<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class UserRoleRepository
{
    public function getUserRoles(int $userId)
    {
        return User::find($userId)->roles;
    }

    public function getUserPermissions(int $userId)
    {
        return User::find($userId)->permissions;
    }

    public function getAuthUserRoles()
    {
        return Auth::user()->roles;
    }

    public function getAuthUserPermissions()
    {
        return Auth::user()->permissions;
    }

    public function assignRoleToUser(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        return $user->assignRole($roleId);
    }

    public function assignPermissionsToRole(int $roleId, int $permissionId)
    {
        $role = Role::findOrFail($roleId);
        return $role->givePermissionTo($permissionId);
    }

    public function removeRoleFromUser(int $userId, int $roleId)
    {
        $user = User::findOrFail($userId);
        return $user->removeRole($roleId);
    }

    public function removePermissionFromRole(int $roleId, int $permissionId)
    {
        $role = Role::findOrFail($roleId);
        return $role->revokePermissionTo($permissionId);
    }
}
