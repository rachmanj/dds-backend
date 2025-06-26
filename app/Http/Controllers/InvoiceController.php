<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\AdditionalDocumentResource;
use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        // Check if this is an API request that needs department filtering
        if ($request->expectsJson() && $request->has('filter_by_department')) {
            return $this->indexWithDepartmentFilter($request);
        }

        $perPage = $request->input('per_page', 15);
        $invoices = $this->invoiceService->getAll(['*'], $perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Display a listing of invoices filtered by user's department location
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

            $query = Invoice::with(['supplier', 'type', 'creator', 'attachments.uploader'])
                ->where('cur_loc', $userDepartment->location_code);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('faktur_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('date_from')) {
                $query->whereDate('invoice_date', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('invoice_date', '<=', $request->input('date_to'));
            }

            $perPage = $request->input('per_page', 15);
            $invoices = $query->latest('invoice_date')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(InvoiceRequest $request)
    {
        $data = $request->validated();

        // Auto-populate receive_project with authenticated user's project
        $user = $request->user();
        $data['receive_project'] = $user->project ?? null;

        $invoice = $this->invoiceService->create($data);
        return new InvoiceResource($invoice);
    }

    public function show(int $id, Request $request)
    {
        // Check if this is an API request that needs department filtering
        if ($request->expectsJson() && $request->has('filter_by_department')) {
            return $this->showWithDepartmentFilter($id);
        }

        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        return new InvoiceResource($invoice);
    }

    /**
     * Display the specified invoice with department filtering
     */
    public function showWithDepartmentFilter(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            $invoice = Invoice::with(['supplier', 'type', 'creator', 'additionalDocuments', 'attachments.uploader'])
                ->where('id', $id)
                ->where('cur_loc', $userDepartment->location_code)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found or not in your location'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(int $id, InvoiceRequest $request)
    {
        // Set the invoice ID in the request for validation
        $request->merge(['invoice_id' => $id]);

        $validatedData = $request->validated();

        // Remove invoice_id from the data as it's only used for validation
        unset($validatedData['invoice_id']);

        $invoice = $this->invoiceService->update($id, $validatedData);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        return new InvoiceResource($invoice);
    }

    public function destroy(int $id)
    {
        $this->invoiceService->delete($id);
        return response()->json(null, 204);
    }

    /**
     * Get additional documents for an invoice
     */
    public function getAdditionalDocuments(int $id)
    {
        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        // Load additional documents with their type and other relationships
        $additionalDocuments = $invoice->additionalDocuments()
            ->with(['type', 'creator', 'invoices.supplier'])
            ->get();

        return AdditionalDocumentResource::collection($additionalDocuments);
    }

    public function validateInvoiceNumber(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'supplier_id' => 'required|integer',
            'invoice_id' => 'nullable|integer' // For edit mode
        ]);

        $isValid = $this->invoiceService->validateInvoiceNumber(
            $request->invoice_number,
            $request->supplier_id,
            $request->invoice_id
        );

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Invoice number is available' : 'This invoice number already exists for the selected supplier'
        ]);
    }

    public function attachAdditionalDocument(int $id, Request $request)
    {
        $request->validate([
            'additional_document_id' => 'required|integer|exists:additional_documents,id'
        ]);

        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        $invoice->additionalDocuments()->attach($request->additional_document_id);

        return response()->json([
            'message' => 'Additional document attached successfully'
        ]);
    }

    public function detachAdditionalDocument(int $id, int $documentId)
    {
        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        $invoice->additionalDocuments()->detach($documentId);

        return response()->json([
            'message' => 'Additional document detached successfully'
        ]);
    }

    public function syncAdditionalDocuments(int $id, Request $request)
    {
        $request->validate([
            'additional_document_ids' => 'required|array',
            'additional_document_ids.*' => 'integer|exists:additional_documents,id'
        ]);

        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        $invoice->additionalDocuments()->sync($request->additional_document_ids);

        return response()->json([
            'message' => 'Additional documents synchronized successfully'
        ]);
    }

    /**
     * Get invoices available for distribution (with attached documents info)
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

            $query = Invoice::with(['supplier', 'type', 'additionalDocuments', 'attachments.uploader'])
                ->where('cur_loc', $userDepartment->location_code)
                ->where('status', '!=', 'cancelled'); // Exclude cancelled invoices

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('faktur_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            $invoices = $query->latest('invoice_date')->get();

            // Add additional info for distribution
            $invoices->each(function ($invoice) use ($userDepartment) {
                $attachedDocs = $invoice->additionalDocuments;
                $invoice->attached_documents_count = $attachedDocs->count();
                $invoice->attached_documents_in_location = $attachedDocs->where('cur_loc', $userDepartment->location_code)->count();
                $invoice->has_location_mismatch = $invoice->attached_documents_count > $invoice->attached_documents_in_location;
            });

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices for distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
