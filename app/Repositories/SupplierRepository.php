<?php

namespace App\Repositories;

use App\Models\Supplier;

class SupplierRepository
{
    public function getAll(array $fields = ['*'])
    {
        return Supplier::with('createdBy')->select($fields)->get();
    }

    public function getPaginated(int $perPage = 10, string $search = '')
    {
        $query = Supplier::with('createdBy');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('sap_code', 'like', "%{$search}%")
                    ->orWhere('npwp', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return Supplier::with('createdBy')->select($fields)->find($id);
    }

    public function create(array $data)
    {
        return Supplier::create($data);
    }

    public function update(int $id, array $data)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return $supplier;
    }

    public function delete(int $id)
    {
        return Supplier::findOrFail($id)->delete();
    }
}
