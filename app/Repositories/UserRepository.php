<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return User::with(['department', 'roles'])
            ->select($fields)
            ->latest()
            ->paginate(50);
    }

    public function getById(int $id, array $fields = ['*']): User
    {
        return User::with(['department', 'roles', 'permissions'])
            ->select($fields)
            ->findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $fields): User
    {
        $user = User::findOrFail($id);
        $user->update($fields);
        return $user->fresh();
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $user->delete();
    }

    public function assignRoles(int $userId, array $roleIds): User
    {
        $user = User::findOrFail($userId);
        $user->syncRoles($roleIds);
        return $user->fresh(['roles']);
    }

    public function removeRoles(int $userId, array $roleIds): User
    {
        $user = User::findOrFail($userId);
        $user->removeRole($roleIds);
        return $user->fresh(['roles']);
    }

    public function getUserRoles(int $userId): Collection
    {
        $user = User::findOrFail($userId);
        return $user->roles;
    }

    public function getUserPermissions(int $userId): Collection
    {
        $user = User::findOrFail($userId);
        return $user->getAllPermissions();
    }

    public function searchUsers(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return User::with(['department', 'roles'])
            ->select($fields)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(50);
    }
}
