<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class UserRoleRepository
{
    public function getUserRoles(int $userId): Collection
    {
        return User::find($userId)->roles;
    }

    public function getUserPermissions(int $userId): Collection
    {
        /** @var User $user */
        $user = User::find($userId);
        return $user->getAllPermissions();
    }

    public function getAuthUserRoles(): Collection
    {
        return Auth::user()->roles;
    }

    public function getAuthUserPermissions(): Collection
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->getAllPermissions();
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
