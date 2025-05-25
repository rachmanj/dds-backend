<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use App\Http\Resources\SupplierResource;
use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');

        if ($search) {
            $suppliers = $this->supplierService->getPaginated($perPage, $search);
        } else {
            $suppliers = $this->supplierService->getPaginated($perPage);
        }

        return SupplierResource::collection($suppliers);
    }

    public function store(SupplierRequest $request)
    {
        $supplier = $this->supplierService->create($request->validated());
        return new SupplierResource($supplier);
    }

    public function show(int $id)
    {
        $supplier = $this->supplierService->getById($id);

        if (!$supplier) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }

        return new SupplierResource($supplier);
    }

    public function update(int $id, SupplierRequest $request)
    {
        $supplier = $this->supplierService->update($id, $request->validated());
        return new SupplierResource($supplier);
    }

    public function destroy(int $id)
    {
        $this->supplierService->delete($id);
        return response()->json(null, 204);
    }
}
