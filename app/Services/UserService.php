<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAll(array $fields = ['*'])
    {
        return $this->userRepository->getAll($fields);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->userRepository->getById($id, $fields);
    }

    public function create(array $data)
    {
        $data['password'] = bcrypt($data['password']);

        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $user = $this->getById($id);

        return $this->userRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $user = $this->getById($id);

        return $this->userRepository->delete($id);
    }
}
