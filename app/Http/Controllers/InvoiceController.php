<?php

namespace App\Http\Controllers;

use App\Services\InvoiceService;
use App\Http\Resources\InvoiceResource;
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
}
