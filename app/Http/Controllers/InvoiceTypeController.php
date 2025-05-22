<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceTypeRequest;
use App\Http\Resources\InvoiceTypeResource;
use App\Services\InvoiceTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceTypeController extends Controller
{
    private InvoiceTypeService $invoiceTypeService;

    public function __construct(InvoiceTypeService $invoiceTypeService)
    {
        $this->invoiceTypeService = $invoiceTypeService;
    }

    public function index()
    {
        $invoiceTypes = $this->invoiceTypeService->getAll();
        return response()->json(InvoiceTypeResource::collection($invoiceTypes));
    }

    public function show(int $id)
    {
        try {
            $invoiceType = $this->invoiceTypeService->getById($id);
            return response()->json(new InvoiceTypeResource($invoiceType));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invoice type not found'
            ], 404);
        }
    }

    public function store(InvoiceTypeRequest $request)
    {
        $invoiceType = $this->invoiceTypeService->create($request->validated());
        return response()->json(new InvoiceTypeResource($invoiceType), Response::HTTP_CREATED);
    }

    public function update(InvoiceTypeRequest $request, int $id)
    {
        try {
            $invoiceType = $this->invoiceTypeService->update($id, $request->validated());
            return response()->json(new InvoiceTypeResource($invoiceType));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invoice type not found'
            ], 404);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->invoiceTypeService->delete($id);
            return response()->json(['message' => 'Invoice type deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invoice type not found'
            ], 404);
        }
    }
} 