<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRepository
{
    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return Permission::with(['roles'])
            ->select($fields)
            ->latest()
            ->paginate(50);
    }

    public function getAllPermissions(): Collection
    {
        return Permission::with(['roles'])->get();
    }

    public function getById(int $id, array $fields = ['*']): Permission
    {
        return Permission::with(['roles', 'users'])
            ->select($fields)
            ->findOrFail($id);
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(int $id, array $fields): Permission
    {
        $permission = Permission::findOrFail($id);
        $permission->update($fields);
        return $permission->fresh();
    }

    public function delete(int $id): void
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
    }

    public function searchPermissions(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return Permission::with(['roles'])
            ->select($fields)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(50);
    }

    public function getPermissionByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function getPermissionsByGuard(string $guard = 'web'): Collection
    {
        return Permission::where('guard_name', $guard)->get();
    }
}
