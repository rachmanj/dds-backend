<?php

namespace App\Repositories;

use App\Models\Project;

class ProjectRepository
{
    public function getAll(array $fields)
    {
        return Project::select($fields)->get();
    }

    public function getById(int $id, array $fields)
    {
        return Project::select($fields)->findOrFail($id);
    }

    public function create(array $data)
    {
        return Project::create($data);
    }

    public function update(int $id, array $data)
    {
        $project = Project::findOrFail($id);
        $project->update($data);
        return $project;
    }

    public function delete(int $id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
    }
    
}