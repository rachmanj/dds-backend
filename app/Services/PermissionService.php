<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionService
{
    private PermissionRepository $permissionRepository;

    public function __construct(PermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return $this->permissionRepository->getAll($fields);
    }

    public function getAllPermissions(): Collection
    {
        return $this->permissionRepository->getAllPermissions();
    }

    public function getById(int $id, array $fields = ['*']): Permission
    {
        return $this->permissionRepository->getById($id, $fields);
    }

    public function create(array $data): Permission
    {
        // Set default guard if not provided
        if (!isset($data['guard_name'])) {
            $data['guard_name'] = 'web';
        }

        return $this->permissionRepository->create($data);
    }

    public function update(int $id, array $data): Permission
    {
        return $this->permissionRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->permissionRepository->delete($id);
    }

    public function searchPermissions(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return $this->permissionRepository->searchPermissions($search, $fields);
    }

    public function getPermissionByName(string $name): ?Permission
    {
        return $this->permissionRepository->getPermissionByName($name);
    }

    public function getPermissionsByGuard(string $guard = 'web'): Collection
    {
        return $this->permissionRepository->getPermissionsByGuard($guard);
    }
}
