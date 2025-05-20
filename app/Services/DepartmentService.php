<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;

class DepartmentService
{
    private DepartmentRepository $departmentRepository;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function getAll(array $fields)
    {
        return $this->departmentRepository->getAll($fields);
    }

    public function getById(int $id, array $fields)
    {
        return $this->departmentRepository->getById($id, $fields ?? ['*']);
    }

    public function create(array $data)
    {
        return $this->departmentRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->departmentRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->departmentRepository->delete($id);
    }
}


