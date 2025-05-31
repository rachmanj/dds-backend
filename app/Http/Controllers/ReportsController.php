<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    /**
     * Get comprehensive invoices report with basic relationships
     */
    public function invoicesReport(Request $request): JsonResponse
    {
        try {
            $query = Invoice::with(['supplier', 'type', 'creator']);

            // Apply search filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('faktur_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            // Apply date filters
            if ($request->has('date_from')) {
                $query->whereDate('invoice_date', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('invoice_date', '<=', $request->input('date_to'));
            }

            // Apply status filter
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $perPage = $request->input('per_page', 15);
            $invoices = $query->latest('invoice_date')->paginate($perPage);

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
            $invoice = Invoice::with(['supplier', 'type', 'creator'])->find($id);

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
            $query = AdditionalDocument::with(['type', 'creator']);

            // Apply search filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 15);
            $documents = $query->latest('created_at')->paginate($perPage);

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
            $document = AdditionalDocument::with(['type', 'creator', 'invoices'])->find($id);

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
            $query = Distribution::with(['type', 'creator', 'originDepartment', 'destinationDepartment']);

            // Apply search filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where('distribution_number', 'like', "%{$search}%");
            }

            $perPage = $request->input('per_page', 15);
            $distributions = $query->latest('created_at')->paginate($perPage);

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
            $distribution = Distribution::with([
                'type',
                'creator',
                'originDepartment',
                'destinationDepartment',
                'invoices',
                'additionalDocuments'
            ])->find($id);

            if (!$distribution) {
                return response()->json([
                    'success' => false,
                    'message' => 'Distribution not found'
                ], 404);
            }

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
