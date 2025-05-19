<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class ProjectService
{
    private ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    public function getAll(array $fields)
    {
        return $this->projectRepository->getAll($fields);
    }

    public function getById(int $id, array $fields)
    {
        return $this->projectRepository->getById($id, $fields ?? ['*']);
    }

    public function create(array $data)
    {
        return $this->projectRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->projectRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->projectRepository->delete($id);
    }
}
