<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdditionalDocumentTypeRequest;
use App\Http\Resources\AdditionalDocumentTypeResource;
use App\Services\AdditionalDocumentTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdditionalDocumentTypeController extends Controller
{
    private AdditionalDocumentTypeService $additionalDocumentTypeService;

    public function __construct(AdditionalDocumentTypeService $additionalDocumentTypeService)
    {
        $this->additionalDocumentTypeService = $additionalDocumentTypeService;
    }

    public function index()
    {
        $additionalDocumentTypes = $this->additionalDocumentTypeService->getAll();
        return response()->json(AdditionalDocumentTypeResource::collection($additionalDocumentTypes));
    }

    public function show(int $id)
    {
        try {
            $additionalDocumentType = $this->additionalDocumentTypeService->getById($id);
            return response()->json(new AdditionalDocumentTypeResource($additionalDocumentType));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Additional document type not found'
            ], 404);
        }
    }

    public function store(AdditionalDocumentTypeRequest $request)
    {
        $additionalDocumentType = $this->additionalDocumentTypeService->create($request->validated());
        return response()->json(new AdditionalDocumentTypeResource($additionalDocumentType), Response::HTTP_CREATED);
    }

    public function update(AdditionalDocumentTypeRequest $request, int $id)
    {
        try {
            $additionalDocumentType = $this->additionalDocumentTypeService->update($id, $request->validated());
            return response()->json(new AdditionalDocumentTypeResource($additionalDocumentType));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Additional document type not found'
            ], 404);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->additionalDocumentTypeService->delete($id);
            return response()->json(['message' => 'Additional document type deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Additional document type not found'
            ], 404);
        }
    }
}
