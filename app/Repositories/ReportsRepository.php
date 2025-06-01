<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportsRepository
{
    /**
     * Get invoices report with filtering and pagination
     */
    public function getInvoicesReport(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::with(['supplier', 'type', 'creator', 'attachments.uploader']);

        // Apply search filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('faktur_no', 'like', "%{$search}%")
                    ->orWhere('po_no', 'like', "%{$search}%");
            });
        }

        // Apply invoice date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }

        // Apply receive date filters
        if (!empty($filters['receive_date_from'])) {
            $query->whereDate('receive_date', '>=', $filters['receive_date_from']);
        }

        if (!empty($filters['receive_date_to'])) {
            $query->whereDate('receive_date', '<=', $filters['receive_date_to']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply supplier filter
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // Apply type filter
        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        // Apply creator filter
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->latest('invoice_date')->paginate($perPage);
    }

    /**
     * Get invoice details by ID
     */
    public function getInvoiceDetails(int $id)
    {
        return Invoice::with([
            'supplier',
            'type',
            'creator',
            'additionalDocuments.type',
            'attachments.uploader',
            'distributions.type',
            'distributions.originDepartment',
            'distributions.destinationDepartment',
            'distributions.histories.user'
        ])->find($id);
    }

    /**
     * Get additional documents report with filtering and pagination
     */
    public function getAdditionalDocumentsReport(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AdditionalDocument::with(['type', 'creator']);

        // Apply search filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('po_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('document_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('document_date', '<=', $filters['date_to']);
        }

        // Apply type filter
        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        // Apply creator filter
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Get additional document details by ID
     */
    public function getAdditionalDocumentDetails(int $id)
    {
        return AdditionalDocument::with([
            'type',
            'creator',
            'invoices.supplier',
            'invoices.type',
            'invoices.attachments.uploader',
            'distributions.type',
            'distributions.originDepartment',
            'distributions.destinationDepartment',
            'distributions.histories.user'
        ])->find($id);
    }

    /**
     * Get distributions report with filtering and pagination
     */
    public function getDistributionsReport(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Distribution::with([
            'type',
            'creator',
            'originDepartment',
            'destinationDepartment',
            'invoices',
            'additionalDocuments',
            'histories.user'
        ]);

        // Apply search filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('distribution_number', 'like', "%{$search}%");
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply type filter
        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        // Apply origin department filter
        if (!empty($filters['origin_department_id'])) {
            $query->where('origin_department_id', $filters['origin_department_id']);
        }

        // Apply destination department filter
        if (!empty($filters['destination_department_id'])) {
            $query->where('destination_department_id', $filters['destination_department_id']);
        }

        // Apply creator filter
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Get distribution details by ID
     */
    public function getDistributionDetails(int $id)
    {
        return Distribution::with([
            'type',
            'creator',
            'originDepartment',
            'destinationDepartment',
            'invoices.supplier',
            'invoices.type',
            'invoices.attachments.uploader',
            'additionalDocuments.type',
            'histories.user'
        ])->find($id);
    }
}
