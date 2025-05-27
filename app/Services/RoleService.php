<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return $this->roleRepository->getAll($fields);
    }

    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAllRoles();
    }

    public function getById(int $id, array $fields = ['*']): Role
    {
        return $this->roleRepository->getById($id, $fields);
    }

    public function create(array $data): Role
    {
        // Set default guard if not provided
        if (!isset($data['guard_name'])) {
            $data['guard_name'] = 'web';
        }

        $role = $this->roleRepository->create($data);

        // Assign permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $this->assignPermissions($role->id, $data['permissions']);
        }

        return $role->fresh(['permissions']);
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->roleRepository->update($id, $data);

        // Update permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $this->assignPermissions($id, $data['permissions']);
        }

        return $role->fresh(['permissions']);
    }

    public function delete(int $id): void
    {
        $this->roleRepository->delete($id);
    }

    public function assignPermissions(int $roleId, array $permissionIds): Role
    {
        return $this->roleRepository->assignPermissions($roleId, $permissionIds);
    }

    public function removePermissions(int $roleId, array $permissionIds): Role
    {
        return $this->roleRepository->removePermissions($roleId, $permissionIds);
    }

    public function getRolePermissions(int $roleId): Collection
    {
        return $this->roleRepository->getRolePermissions($roleId);
    }

    public function searchRoles(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return $this->roleRepository->searchRoles($search, $fields);
    }

    public function getRoleByName(string $name): ?Role
    {
        return $this->roleRepository->getRoleByName($name);
    }
}
