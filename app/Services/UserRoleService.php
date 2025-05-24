<?php

namespace App\Services;

use App\Repositories\UserRoleRepository;

class UserRoleService
{
    private UserRoleRepository $userRoleRepository;

    public function __construct(UserRoleRepository $userRoleRepository)
    {
        $this->userRoleRepository = $userRoleRepository;
    }

    public function getUserRoles(int $userId)
    {
        return $this->userRoleRepository->getUserRoles($userId);
    }

    public function getUserPermissions(int $userId)
    {
        return $this->userRoleRepository->getUserPermissions($userId);
    }

    public function getAuthUserRoles()
    {
        return $this->userRoleRepository->getAuthUserRoles();
    }

    public function getAuthUserPermissions()
    {
        return $this->userRoleRepository->getAuthUserPermissions();
    }
}
