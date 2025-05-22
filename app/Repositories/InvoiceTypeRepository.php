<?php

namespace App\Repositories;

use App\Models\InvoiceType;

class InvoiceTypeRepository
{
    public function getAll()
    {
        return InvoiceType::orderBy('type_name', 'asc')->get();
    }

    public function getById($id)
    {
        return InvoiceType::findOrFail($id);
    }

    public function create(array $data)
    {
        return InvoiceType::create($data);
    }

    public function update($id, array $data)
    {
        $invoiceType = InvoiceType::findOrFail($id);
        $invoiceType->update($data);

        return $invoiceType;
    }

    public function delete($id)
    {
        $invoiceType = InvoiceType::findOrFail($id);
        $invoiceType->delete();
    }
} 