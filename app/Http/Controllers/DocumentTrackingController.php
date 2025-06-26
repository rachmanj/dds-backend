<?php

namespace App\Http\Controllers;

use App\Services\DocumentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DocumentTrackingController extends Controller
{
    protected DocumentTrackingService $trackingService;

    public function __construct(DocumentTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Get document movement history
     * GET /api/tracking/{documentType}/{documentId}/history
     */
    public function getHistory(string $documentType, int $documentId): JsonResponse
    {
        try {
            $this->validateDocumentType($documentType);

            $history = $this->trackingService->getDocumentHistory($documentType, $documentId);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document timeline with detailed events
     * GET /api/tracking/{documentType}/{documentId}/timeline
     */
    public function getTimeline(string $documentType, int $documentId): JsonResponse
    {
        try {
            $this->validateDocumentType($documentType);

            $timeline = $this->trackingService->getLocationTimeline($documentType, $documentId);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'meta' => [
                    'total_events' => $timeline->count(),
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document timeline',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current location of a document
     * GET /api/tracking/{documentType}/{documentId}/location
     */
    public function getCurrentLocation(string $documentType, int $documentId): JsonResponse
    {
        try {
            $this->validateDocumentType($documentType);

            $currentLocation = $this->trackingService->getCurrentLocation($documentType, $documentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_location' => $currentLocation,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch current location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all documents in a specific location
     * GET /api/tracking/location/{locationCode}/documents
     */
    public function getLocationDocuments(string $locationCode, Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $documents = $this->trackingService->getDocumentsInLocation($locationCode, $limit);

            return response()->json([
                'success' => true,
                'data' => $documents,
                'meta' => [
                    'location_code' => $locationCode,
                    'total_documents' => $documents->count(),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch location documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track a document movement manually
     * POST /api/tracking/move
     */
    public function trackMovement(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'document_type' => 'required|in:invoice,additional_document',
                'document_id' => 'required|integer|min:1',
                'from_location' => 'required|string|max:10',
                'to_location' => 'required|string|max:10',
                'reason' => 'required|string|max:255',
                'distribution_id' => 'nullable|integer|exists:distributions,id',
            ]);

            $movement = $this->trackingService->trackMovement(
                $validated['document_type'],
                $validated['document_id'],
                $validated['from_location'],
                $validated['to_location'],
                $validated['reason'],
                $validated['distribution_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Document movement tracked successfully',
                'data' => $movement,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track document movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get movement statistics
     * GET /api/tracking/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $stats = $this->trackingService->getMovementStatistics($days);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'meta' => [
                    'period_days' => $days,
                    'total_locations' => $stats->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch movement statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get department location summary
     * GET /api/tracking/departments/summary
     */
    public function getDepartmentSummary(): JsonResponse
    {
        try {
            $summary = $this->trackingService->getDepartmentLocationSummary();

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch department summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search documents by location history
     * GET /api/tracking/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:2|max:100',
                'limit' => 'integer|min:1|max:100',
            ]);

            $results = $this->trackingService->searchByLocationHistory(
                $validated['q'],
                $validated['limit'] ?? 20
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'query' => $validated['q'],
                    'total_results' => $results->count(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize tracking for existing documents
     * POST /api/tracking/initialize
     */
    public function initializeTracking(): JsonResponse
    {
        try {
            $results = $this->trackingService->initializeTracking();

            return response()->json([
                'success' => true,
                'message' => 'Document tracking initialized successfully',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize tracking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate document type
     */
    private function validateDocumentType(string $documentType): void
    {
        if (!in_array($documentType, ['invoice', 'additional_document'])) {
            throw new \InvalidArgumentException('Invalid document type. Must be "invoice" or "additional_document".');
        }
    }
}
