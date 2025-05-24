<?php

namespace App\Http\Controllers;

use App\Services\AdditionalDocumentService;
use App\Http\Resources\AdditionalDocumentResource;
use App\Http\Requests\AdditionalDocumentRequest;
use Illuminate\Http\Request;

class AdditionalDocumentController extends Controller
{
    protected AdditionalDocumentService $additionalDocumentService;

    public function __construct(AdditionalDocumentService $additionalDocumentService)
    {
        $this->additionalDocumentService = $additionalDocumentService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $documents = $this->additionalDocumentService->getAll(['*'], $perPage);
        return AdditionalDocumentResource::collection($documents);
    }

    public function store(AdditionalDocumentRequest $request)
    {
        $document = $this->additionalDocumentService->create($request->validated());
        return new AdditionalDocumentResource($document);
    }

    public function show(int $id)
    {
        $document = $this->additionalDocumentService->getById($id);
        
        if (!$document) {
            return response()->json([
                'message' => 'Additional document not found'
            ], 404);
        }
        
        return new AdditionalDocumentResource($document);
    }

    public function update(int $id, Request $request)
    {
        $document = $this->additionalDocumentService->update($id, $request->all());
        return new AdditionalDocumentResource($document);
    }

    public function destroy(int $id)
    {
        $this->additionalDocumentService->delete($id);
        return response()->json(null, 204);
    }
} 