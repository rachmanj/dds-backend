<?php

namespace App\Services;

use App\Repositories\InvoiceRepository;

class InvoiceService
{
    protected InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return $this->invoiceRepository->getAll($fields, $perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->invoiceRepository->getById($id, $fields);
    }

    public function create(array $data)
    {
        return $this->invoiceRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->invoiceRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->invoiceRepository->delete($id);
    }
} 