<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\Request;


class DepartmentController extends Controller
{
    private DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function index()
    {
        $fields = ['id', 'name', 'project', 'location_code', 'transit_code', 'akronim', 'sap_code'];
        $departments = $this->departmentService->getAll($fields);

        return response()->json(DepartmentResource::collection($departments));
    }

    public function show(int $id)
    {
        try {
            $fields = ['id', 'name', 'project', 'location_code', 'transit_code', 'akronim', 'sap_code'];
            $department = $this->departmentService->getById($id, $fields);

            return response()->json(new DepartmentResource($department));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Department not found'
            ], 404);
        }
    }

    public function store(DepartmentRequest $request)
    {
        $department = $this->departmentService->create($request->validated());

        return response()->json(new DepartmentResource($department), 201);
    }

    public function update(Request $request, int $id)
    {
        try {
            $department = $this->departmentService->update($id, $request->all());
            return response()->json(new DepartmentResource($department));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Department not found'
            ], 404);
        }

    }

    public function destroy(int $id)
    {
        try {
            $this->departmentService->delete($id);
            return response()->json(['message' => 'Department deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Department not found'
            ], 404);
        }
    }
}

