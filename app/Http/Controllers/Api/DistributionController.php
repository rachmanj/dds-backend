<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DistributionResource;
use App\Services\DistributionService;
use App\Services\TransmittalAdviceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DistributionController extends Controller
{
    protected DistributionService $distributionService;
    protected TransmittalAdviceService $transmittalAdviceService;

    public function __construct(
        DistributionService $distributionService,
        TransmittalAdviceService $transmittalAdviceService
    ) {
        $this->distributionService = $distributionService;
        $this->transmittalAdviceService = $transmittalAdviceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only([
                'status',
                'type_id',
                'origin_department_id',
                'destination_department_id',
                'created_by',
                'date_from',
                'date_to',
                'search'
            ]);

            // Add user department filter
            $user = Auth::user();
            if ($user && $user->department_id) {
                $filters['user_department_id'] = $user->department_id;
            }

            $distributions = $this->distributionService->getAll(['*'], $perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $distributions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distributions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string|in:invoice,additional_document',
            'type_id' => 'required|integer|exists:distribution_types,id',
            'origin_department_id' => 'required|integer|exists:departments,id',
            'destination_department_id' => 'required|integer|exists:departments,id|different:origin_department_id',
            'notes' => 'nullable|string|max:1000',
            'documents' => 'required|array|min:1',
            'documents.*.type' => 'required|string|in:invoice,additional_document',
            'documents.*.id' => 'required|integer'
        ]);

        // Validate document type consistency
        foreach ($request->documents as $document) {
            if ($document['type'] !== $request->document_type) {
                return response()->json([
                    'success' => false,
                    'message' => "All documents must match the selected document type: {$request->document_type}"
                ], 422);
            }
        }

        // Additional validation for document existence will be handled in the service
        try {
            // Get validated data manually
            $validatedData = $request->only([
                'document_type',
                'type_id',
                'origin_department_id',
                'destination_department_id',
                'notes',
                'documents'
            ]);

            $distribution = $this->distributionService->create($validatedData);

            $response = [
                'success' => true,
                'message' => 'Distribution created successfully',
                'data' => new DistributionResource($distribution)
            ];

            // Include warnings if any
            if (!empty($distribution->warnings)) {
                $response['warnings'] = $distribution->warnings;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            Log::error('Distribution creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Distribution not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type_id' => 'sometimes|required|integer|exists:distribution_types,id',
            'destination_department_id' => 'sometimes|required|integer|exists:departments,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            // Get validated data manually
            $validatedData = $request->only([
                'type_id',
                'destination_department_id',
                'notes'
            ]);

            $distribution = $this->distributionService->update($id, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Distribution updated successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->distributionService->delete($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete distribution. Only draft distributions can be deleted.'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Distribution deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attach documents to distribution
     */
    public function attachDocuments(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'documents' => 'required|array',
            'documents.*.type' => 'required|string|in:invoice,additional_document',
            'documents.*.id' => 'required|integer'
        ]);

        try {
            $distribution = $this->distributionService->attachDocuments($id, $request->documents);

            return response()->json([
                'success' => true,
                'message' => 'Documents attached successfully',
                'data' => $distribution
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detach document from distribution
     */
    public function detachDocument(int $id, string $documentType, int $documentId): JsonResponse
    {
        if (!in_array($documentType, ['invoice', 'additional_document'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type'
            ], 422);
        }

        try {
            $distribution = $this->distributionService->detachDocument($id, $documentType, $documentId);

            return response()->json([
                'success' => true,
                'message' => 'Document detached successfully',
                'data' => $distribution
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify distribution by sender
     */
    public function verifySender(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'document_verifications' => 'nullable|array',
            'document_verifications.*.document_type' => 'required|string|in:invoice,additional_document',
            'document_verifications.*.document_id' => 'required|integer'
        ]);

        try {
            $distribution = $this->distributionService->verifySender($id, $request->document_verifications ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Distribution verified by sender successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send distribution
     */
    public function send(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->send($id);

            return response()->json([
                'success' => true,
                'message' => 'Distribution sent successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Receive distribution
     */
    public function receive(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->receive($id);

            return response()->json([
                'success' => true,
                'message' => 'Distribution received successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify distribution by receiver
     */
    public function verifyReceiver(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'document_verifications' => 'nullable|array',
            'document_verifications.*.document_type' => 'required|string|in:invoice,additional_document',
            'document_verifications.*.document_id' => 'required|integer'
        ]);

        try {
            $distribution = $this->distributionService->verifyReceiver($id, $request->document_verifications ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Distribution verified by receiver successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete distribution
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->complete($id);

            return response()->json([
                'success' => true,
                'message' => 'Distribution completed successfully',
                'data' => new DistributionResource($distribution)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distribution history
     */
    public function history(int $id): JsonResponse
    {
        try {
            $history = $this->distributionService->getHistory($id);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distribution history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate transmittal advice PDF
     */
    public function transmittal(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->getById($id);
            $transmittalData = $this->transmittalAdviceService->getTransmittalData($distribution);

            return response()->json([
                'success' => true,
                'data' => $transmittalData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate transmittal advice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview transmittal advice
     */
    public function transmittalPreview(int $id): JsonResponse
    {
        try {
            $distribution = $this->distributionService->getById($id);
            $transmittalData = $this->transmittalAdviceService->getTransmittalData($distribution);

            return response()->json([
                'success' => true,
                'data' => $transmittalData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate transmittal preview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distributions by department
     */
    public function byDepartment(Request $request, int $departmentId): JsonResponse
    {
        $direction = $request->input('direction', 'both'); // origin, destination, both

        try {
            $distributions = $this->distributionService->getByDepartment($departmentId, $direction);

            return response()->json([
                'success' => true,
                'data' => $distributions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distributions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distributions by status
     */
    public function byStatus(string $status): JsonResponse
    {
        $validStatuses = ['draft', 'verified_by_sender', 'sent', 'received', 'verified_by_receiver', 'completed'];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 422);
        }

        try {
            $distributions = $this->distributionService->getByStatus($status);

            return response()->json([
                'success' => true,
                'data' => $distributions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distributions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get distributions by user
     */
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $role = $request->input('role', 'all'); // creator, sender_verifier, receiver_verifier, all

        try {
            $distributions = $this->distributionService->getByUser($userId, $role);

            return response()->json([
                'success' => true,
                'data' => $distributions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve distributions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
