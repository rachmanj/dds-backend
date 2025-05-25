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
        $invoice = $this->invoiceService->create($request->validated());
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

    public function update(int $id, Request $request)
    {
        $invoice = $this->invoiceService->update($id, $request->all());
        return new InvoiceResource($invoice);
    }

    public function destroy(int $id)
    {
        $this->invoiceService->delete($id);
        return response()->json(null, 204);
    }
}
