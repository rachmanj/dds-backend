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
        $data['created_by'] = request()->user()->id;
        $data['receive_project'] = $data['receive_project'] ?? request()->user()->project;
        return $this->invoiceRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        try {
            return $this->invoiceRepository->update($id, $data);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function delete(int $id)
    {
        try {
            return $this->invoiceRepository->delete($id);
        } catch (\Exception $e) {
            return false;
        }
    }
}
