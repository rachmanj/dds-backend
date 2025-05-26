<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\AdditionalDocumentResource;
use App\Http\Requests\InvoiceRequest;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $invoices = $this->invoiceService->getAll(['*'], $perPage);

        return InvoiceResource::collection($invoices);
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

    public function show(int $id)
    {
        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        return new InvoiceResource($invoice);
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
        $deleted = $this->invoiceService->delete($id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        return response()->json(null, 204);
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

    public function getAdditionalDocuments(int $id)
    {
        $invoice = $this->invoiceService->getById($id);

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found'
            ], 404);
        }

        return AdditionalDocumentResource::collection($invoice->additionalDocuments);
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
}
