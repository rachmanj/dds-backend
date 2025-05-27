<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAll(array $fields = ['*']): LengthAwarePaginator
    {
        return $this->userRepository->getAll($fields);
    }

    public function getById(int $id, array $fields = ['*']): User
    {
        return $this->userRepository->getById($id, $fields);
    }

    public function create(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->create($data);

        // Assign roles if provided
        if (isset($data['roles']) && is_array($data['roles'])) {
            $this->assignRoles($user->id, $data['roles']);
        }

        return $user->fresh(['roles', 'department']);
    }

    public function update(int $id, array $data): User
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user = $this->userRepository->update($id, $data);

        // Update roles if provided
        if (isset($data['roles']) && is_array($data['roles'])) {
            $this->assignRoles($id, $data['roles']);
        }

        return $user->fresh(['roles', 'department']);
    }

    public function delete(int $id): void
    {
        $this->userRepository->delete($id);
    }

    public function assignRoles(int $userId, array $roleIds): User
    {
        return $this->userRepository->assignRoles($userId, $roleIds);
    }

    public function removeRoles(int $userId, array $roleIds): User
    {
        return $this->userRepository->removeRoles($userId, $roleIds);
    }

    public function getUserRoles(int $userId): Collection
    {
        return $this->userRepository->getUserRoles($userId);
    }

    public function getUserPermissions(int $userId): Collection
    {
        return $this->userRepository->getUserPermissions($userId);
    }

    public function searchUsers(string $search, array $fields = ['*']): LengthAwarePaginator
    {
        return $this->userRepository->searchUsers($search, $fields);
    }

    public function changePassword(int $userId, string $newPassword): User
    {
        return $this->userRepository->update($userId, [
            'password' => Hash::make($newPassword)
        ]);
    }
}
