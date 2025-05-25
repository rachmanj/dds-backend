<?php

namespace App\Http\Controllers;

use App\Services\AdditionalDocumentService;
use App\Http\Resources\AdditionalDocumentResource;
use App\Http\Requests\AdditionalDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $document = $this->additionalDocumentService->create($data);
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

    public function update(int $id, AdditionalDocumentRequest $request)
    {
        $data = $request->validated();
        unset($data['created_by']); // Don't allow updating created_by

        $document = $this->additionalDocumentService->update($id, $data);
        return new AdditionalDocumentResource($document);
    }

    public function destroy(int $id)
    {
        $this->additionalDocumentService->delete($id);
        return response()->json(null, 204);
    }
}
