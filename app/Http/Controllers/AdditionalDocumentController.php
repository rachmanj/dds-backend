<?php

namespace App\Http\Controllers;

use App\Services\AdditionalDocumentService;
use App\Http\Resources\AdditionalDocumentResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Requests\AdditionalDocumentRequest;
use App\Http\Requests\ImportAdditionalDocumentRequest;
use App\Models\AdditionalDocument;
use App\Imports\ItoImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AdditionalDocumentController extends Controller
{
    protected AdditionalDocumentService $additionalDocumentService;

    public function __construct(AdditionalDocumentService $additionalDocumentService)
    {
        $this->additionalDocumentService = $additionalDocumentService;
    }

    public function index(Request $request)
    {
        // Check if this is an API request that needs department filtering
        if ($request->expectsJson() && $request->has('filter_by_department')) {
            return $this->indexWithDepartmentFilter($request);
        }

        $perPage = $request->input('per_page', 15);
        $documents = $this->additionalDocumentService->getAll(['*'], $perPage);
        return AdditionalDocumentResource::collection($documents);
    }

    /**
     * Display a listing of additional documents filtered by user's department location
     */
    public function indexWithDepartmentFilter(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            if (!$userDepartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User department not found'
                ], 400);
            }

            $query = AdditionalDocument::with(['type'])
                ->where('cur_loc', $userDepartment->location_code);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('type_id')) {
                $query->where('type_id', $request->input('type_id'));
            }

            if ($request->has('date_from')) {
                $query->whereDate('document_date', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('document_date', '<=', $request->input('date_to'));
            }

            $perPage = $request->input('per_page', 15);
            $documents = $query->latest('document_date')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(AdditionalDocumentRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $document = $this->additionalDocumentService->create($data);
        return new AdditionalDocumentResource($document);
    }

    public function show(int $id, Request $request)
    {
        // Check if this is an API request that needs department filtering
        if ($request->expectsJson() && $request->has('filter_by_department')) {
            return $this->showWithDepartmentFilter($id);
        }

        $document = $this->additionalDocumentService->getById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }

        return new AdditionalDocumentResource($document);
    }

    /**
     * Display the specified additional document with department filtering
     */
    public function showWithDepartmentFilter(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            $document = AdditionalDocument::with(['type'])
                ->where('id', $id)
                ->where('cur_loc', $userDepartment->location_code)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Additional document not found or not in your location'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(int $id, AdditionalDocumentRequest $request)
    {
        $data = $request->validated();
        unset($data['created_by']); // Don't allow updating created_by

        $document = $this->additionalDocumentService->update($id, $data);
        return new AdditionalDocumentResource($document);
    }

    public function destroy(int $id)
    {
        $this->additionalDocumentService->delete($id);
        return response()->json(null, 204);
    }

    public function getInvoices(int $id)
    {
        $document = $this->additionalDocumentService->getById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }

        return InvoiceResource::collection($document->invoices);
    }

    public function attachInvoice(int $id, Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id'
        ]);

        $document = $this->additionalDocumentService->getById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }

        $document->invoices()->attach($request->invoice_id);

        return response()->json([
            'message' => 'Invoice attached successfully'
        ]);
    }

    public function detachInvoice(int $id, int $invoiceId)
    {
        $document = $this->additionalDocumentService->getById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }

        $document->invoices()->detach($invoiceId);

        return response()->json([
            'message' => 'Invoice detached successfully'
        ]);
    }

    public function syncInvoices(int $id, Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'integer|exists:invoices,id'
        ]);

        $document = $this->additionalDocumentService->getById($id);

        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }

        $document->invoices()->sync($request->invoice_ids);

        return response()->json([
            'message' => 'Invoices synchronized successfully'
        ]);
    }

    /**
     * Get additional documents available for distribution
     */
    public function forDistribution(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            if (!$userDepartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User department not found'
                ], 400);
            }

            $query = AdditionalDocument::with(['type'])
                ->where('cur_loc', $userDepartment->location_code)
                ->where('status', '!=', 'cancelled'); // Exclude cancelled documents

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            if ($request->has('type_id')) {
                $query->where('type_id', $request->input('type_id'));
            }

            $documents = $query->latest('document_date')->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional documents for distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import ITO additional documents from Excel file
     */
    public function import(ImportAdditionalDocumentRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $checkDuplicates = $request->boolean('check_duplicates', false);

            // Create import instance
            $import = new ItoImport($checkDuplicates);

            // Process the import
            Excel::import($import, $file);

            // Get import results
            $successCount = $import->getSuccessCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->getErrors();

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'data' => [
                    'imported' => $successCount,
                    'skipped' => $skippedCount,
                    'total_processed' => $successCount + $skippedCount,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
