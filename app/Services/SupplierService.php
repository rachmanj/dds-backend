<?php

namespace App\Services;

use App\Repositories\SupplierRepository;

class SupplierService
{
    protected SupplierRepository $supplierRepository;

    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    public function getAll(array $fields = ['*'])
    {
        return $this->supplierRepository->getAll($fields);
    }

    public function getPaginated(int $perPage = 10, string $search = '')
    {
        return $this->supplierRepository->getPaginated($perPage, $search);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->supplierRepository->getById($id, $fields);
    }

    public function create(array $data)
    {
        return $this->supplierRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->supplierRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->supplierRepository->delete($id);
    }
}
