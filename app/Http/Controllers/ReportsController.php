<?php

namespace App\Http\Controllers;

use App\Services\ReportsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportsController extends Controller
{
    protected ReportsService $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * Get comprehensive invoices report with basic relationships
     */
    public function invoicesReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'date_from',
                'date_to',
                'status',
                'supplier_id',
                'type_id',
                'created_by',
                'per_page'
            ]);

            $invoices = $this->reportsService->getInvoicesReport($filters);

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Invoices report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed invoice report
     */
    public function invoiceDetails(int $id): JsonResponse
    {
        try {
            $invoice = $this->reportsService->getInvoiceDetails($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get additional documents report
     */
    public function additionalDocumentsReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'date_from',
                'date_to',
                'type_id',
                'created_by',
                'per_page'
            ]);

            $documents = $this->reportsService->getAdditionalDocumentsReport($filters);

            return response()->json([
                'success' => true,
                'data' => $documents,
                'message' => 'Additional documents report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional documents report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed additional document report
     */
    public function additionalDocumentDetails(int $id): JsonResponse
    {
        try {
            $document = $this->reportsService->getAdditionalDocumentDetails($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Additional document not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document,
                'message' => 'Additional document details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional document details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distributions report
     */
    public function distributionsReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'date_from',
                'date_to',
                'status',
                'type_id',
                'origin_department_id',
                'destination_department_id',
                'created_by',
                'per_page'
            ]);

            $distributions = $this->reportsService->getDistributionsReport($filters);

            // Enhance distributions with summary data
            $distributions = $this->reportsService->enhanceDistributionsCollection($distributions);

            return response()->json([
                'success' => true,
                'data' => $distributions,
                'message' => 'Distributions report retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distributions report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed distribution report
     */
    public function distributionDetails(int $id): JsonResponse
    {
        try {
            $distribution = $this->reportsService->getDistributionDetails($id);

            if (!$distribution) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution not found'
                ], 404);
            }

            // Enhance distribution with summary data
            $distribution = $this->reportsService->enhanceDistributionData($distribution);

            return response()->json([
                'success' => true,
                'data' => $distribution,
                'message' => 'Distribution details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distribution details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
