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
        $user = request()->user();
        $data['created_by'] = $user->id;
        $data['receive_project'] = $data['receive_project'] ?? $user->project;

        // Set default cur_loc to user's department location_code if not provided
        if (empty($data['cur_loc']) && $user->department && $user->department->location_code) {
            $data['cur_loc'] = $user->department->location_code;
        }

        return $this->invoiceRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        try {
            $user = request()->user();

            // Check if user is trying to edit cur_loc
            if (isset($data['cur_loc'])) {
                // Check if user has permission to edit cur_loc
                if (!$user->can('document.edit-cur_loc')) {
                    // Remove cur_loc from data if user doesn't have permission
                    unset($data['cur_loc']);
                }
            }

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

    public function validateInvoiceNumber(string $invoiceNumber, int $supplierId, ?int $invoiceId = null): bool
    {
        return $this->invoiceRepository->validateInvoiceNumber($invoiceNumber, $supplierId, $invoiceId);
    }
}
