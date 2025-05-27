<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleRepository
{
    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return Role::with(['permissions'])
            ->select($fields)
            ->latest()
            ->paginate(50);
    }

    public function getAllRoles(): Collection
    {
        return Role::with(['permissions'])->get();
    }

    public function getById(int $id, array $fields = ['*']): Role
    {
        return Role::with(['permissions', 'users'])
            ->select($fields)
            ->findOrFail($id);
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(int $id, array $fields): Role
    {
        $role = Role::findOrFail($id);
        $role->update($fields);
        return $role->fresh();
    }

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);
        $role->delete();
    }

    public function assignPermissions(int $roleId, array $permissionIds): Role
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function removePermissions(int $roleId, array $permissionIds): Role
    {
        $role = Role::findOrFail($roleId);
        $role->revokePermissionTo($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function getRolePermissions(int $roleId): Collection
    {
        $role = Role::findOrFail($roleId);
        return $role->permissions;
    }

    public function searchRoles(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return Role::with(['permissions'])
            ->select($fields)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(50);
    }

    public function getRoleByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }
}
