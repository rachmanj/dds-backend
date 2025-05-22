<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdditionalDocumentTypeRequest;
use App\Http\Resources\AdditionalDocumentTypeResource;
use App\Services\AdditionalDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdditionalDocumentTypeController extends Controller
{
    private AdditionalDocumentService $additionalDocumentService;

    public function __construct(AdditionalDocumentService $additionalDocumentService)
    {
        $this->additionalDocumentService = $additionalDocumentService;
    }

    public function index()
    {
        $additionalDocumentTypes = $this->additionalDocumentService->getAll();
        return response()->json(AdditionalDocumentTypeResource::collection($additionalDocumentTypes));
    }

    public function show(int $id)
    {
        try {
            $additionalDocumentType = $this->additionalDocumentService->getById($id);
            return response()->json(new AdditionalDocumentTypeResource($additionalDocumentType));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Additional document type not found'
            ], 404);
        }
    }

    public function store(AdditionalDocumentTypeRequest $request)
    {
        $additionalDocumentType = $this->additionalDocumentService->create($request->validated());
        return response()->json(new AdditionalDocumentTypeResource($additionalDocumentType), Response::HTTP_CREATED);
    }

    public function update(AdditionalDocumentTypeRequest $request, int $id)
    {
        try {
            $additionalDocumentType = $this->additionalDocumentService->update($id, $request->validated());
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
            $this->additionalDocumentService->delete($id);
            return response()->json(['message' => 'Additional document type deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Additional document type not found'
            ], 404);
        }
    }
}
