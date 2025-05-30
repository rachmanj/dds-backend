<?php

namespace App\Repositories;

use App\Models\Invoice;

class InvoiceRepository
{
    public function getAll(array $fields = ['*'], int $perPage = 15)
    {
        return Invoice::select($fields)
            ->with(['supplier', 'type', 'creator', 'additionalDocuments', 'attachments.uploader'])
            ->latest()
            ->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return Invoice::select($fields)
            ->with(['supplier', 'type', 'creator', 'additionalDocuments', 'attachments.uploader'])
            ->find($id);
    }

    public function create(array $data)
    {
        $invoice = Invoice::create($data);
        return $invoice->load(['supplier', 'type', 'creator', 'additionalDocuments', 'attachments.uploader']);
    }

    public function update(int $id, array $data)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update($data);
        return $invoice->load(['supplier', 'type', 'creator', 'additionalDocuments', 'attachments.uploader']);
    }

    public function delete(int $id)
    {
        return Invoice::findOrFail($id)->delete();
    }

    public function validateInvoiceNumber(string $invoiceNumber, int $supplierId, ?int $invoiceId = null): bool
    {
        $query = Invoice::where('invoice_number', $invoiceNumber)
            ->where('supplier_id', $supplierId);

        // If we're updating an existing invoice, exclude it from the check
        if ($invoiceId) {
            $query->where('id', '!=', $invoiceId);
        }

        return !$query->exists();
    }
}
