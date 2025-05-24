<?php

namespace App\Repositories;

use App\Models\Invoice;

class InvoiceRepository
{
    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return Invoice::select($fields)
            ->latest()
            ->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return Invoice::select($fields)->find($id);
    }

    public function create(array $data)
    {
        return Invoice::create($data);
    }

    public function update(int $id, array $data)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        return $invoice;
    }

    public function delete(int $id)
    {
        return Invoice::findOrFail($id)->delete();
    }
} 