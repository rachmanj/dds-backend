<?php

namespace App\Services;

use App\Repositories\InvoiceTypeRepository;

class InvoiceTypeService
{
    private InvoiceTypeRepository $invoiceTypeRepository;

    public function __construct(InvoiceTypeRepository $invoiceTypeRepository)
    {
        $this->invoiceTypeRepository = $invoiceTypeRepository;
    }

    public function getAll()
    {
        return $this->invoiceTypeRepository->getAll();
    }

    public function getById($id)
    {
        return $this->invoiceTypeRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->invoiceTypeRepository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->invoiceTypeRepository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->invoiceTypeRepository->delete($id);
    }
} 