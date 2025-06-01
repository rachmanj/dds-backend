<?php

namespace App\Services;

use App\Repositories\ReportsRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportsService
{
    protected ReportsRepository $reportsRepository;

    public function __construct(ReportsRepository $reportsRepository)
    {
        $this->reportsRepository = $reportsRepository;
    }

    /**
     * Get invoices report with filtering and pagination
     */
    public function getInvoicesReport(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']); // Remove per_page from filters array

        return $this->reportsRepository->getInvoicesReport($filters, $perPage);
    }

    /**
     * Get invoice details by ID
     */
    public function getInvoiceDetails(int $id)
    {
        return $this->reportsRepository->getInvoiceDetails($id);
    }

    /**
     * Get additional documents report with filtering and pagination
     */
    public function getAdditionalDocumentsReport(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']); // Remove per_page from filters array

        return $this->reportsRepository->getAdditionalDocumentsReport($filters, $perPage);
    }

    /**
     * Get additional document details by ID
     */
    public function getAdditionalDocumentDetails(int $id)
    {
        return $this->reportsRepository->getAdditionalDocumentDetails($id);
    }

    /**
     * Get distributions report with filtering and pagination
     */
    public function getDistributionsReport(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        unset($filters['per_page']); // Remove per_page from filters array

        return $this->reportsRepository->getDistributionsReport($filters, $perPage);
    }

    /**
     * Get distribution details by ID
     */
    public function getDistributionDetails(int $id)
    {
        return $this->reportsRepository->getDistributionDetails($id);
    }

    /**
     * Process and enhance distribution data with summary information
     */
    public function enhanceDistributionData($distribution)
    {
        if (!$distribution) {
            return null;
        }

        // Add timeline summary
        $histories = $distribution->histories ?? collect();
        $distribution->timeline_summary = [
            'total_actions' => $histories->count(),
            'current_status' => $distribution->status,
            'created_at' => $distribution->created_at,
            'last_action_at' => $histories->isNotEmpty() ? $histories->last()->created_at : null,
            'is_complete' => $distribution->status === 'completed',
            'has_discrepancies' => $this->checkForDiscrepancies($distribution),
        ];

        // Add document summary
        $invoices = $distribution->invoices ?? collect();
        $additionalDocuments = $distribution->additionalDocuments ?? collect();
        $distribution->document_summary = [
            'total_invoices' => $invoices->count(),
            'total_additional_documents' => $additionalDocuments->count(),
            'total_documents' => $invoices->count() + $additionalDocuments->count(),
        ];

        return $distribution;
    }

    /**
     * Check for discrepancies in distribution
     */
    private function checkForDiscrepancies($distribution): bool
    {
        // This is a placeholder for business logic to detect discrepancies
        // You can implement specific rules based on your business requirements

        // Example: Check if there are any verification failures or missing documents
        $histories = $distribution->histories ?? collect();

        return $histories->contains(function ($history) {
            return str_contains(strtolower($history->action), 'discrepancy') ||
                str_contains(strtolower($history->action), 'missing') ||
                str_contains(strtolower($history->action), 'error');
        });
    }

    /**
     * Process and enhance distributions collection with summary data
     */
    public function enhanceDistributionsCollection($distributions)
    {
        if ($distributions instanceof LengthAwarePaginator) {
            $distributions->getCollection()->transform(function ($distribution) {
                return $this->enhanceDistributionData($distribution);
            });
        } else {
            $distributions->transform(function ($distribution) {
                return $this->enhanceDistributionData($distribution);
            });
        }

        return $distributions;
    }
}
