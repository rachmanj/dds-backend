<?php

namespace App\Repositories;

use App\Models\Department;

class DepartmentRepository
{
    public function getAll(array $fields)
    {
        return Department::select($fields)->get();
    }

    public function getById(int $id, array $fields)
    {
        return Department::select($fields)->findOrFail($id);
    }

    public function create(array $data)
    {
        return Department::create($data);
    }

    public function update(int $id, array $data)
    {
        $department = Department::findOrFail($id);
        $department->update($data);
        return $department;
    }

    public function delete(int $id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
    }
}
