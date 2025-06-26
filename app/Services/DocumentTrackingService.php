<?php

namespace App\Services;

use App\Models\DocumentLocation;
use App\Models\TrackingEvent;
use App\Models\Invoice;
use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentTrackingService
{
    /**
     * Track a document movement
     */
    public function trackMovement(
        string $documentType,
        int $documentId,
        string $fromLocation,
        string $toLocation,
        string $reason,
        ?int $distributionId = null
    ): DocumentLocation {
        $userId = Auth::id();

        // Create location record
        $locationRecord = DocumentLocation::trackMovement(
            $documentType,
            $documentId,
            $toLocation,
            $userId,
            $distributionId,
            $reason
        );

        // Log tracking event
        $document = $this->getDocument($documentType, $documentId);
        if ($document) {
            TrackingEvent::log(
                $document,
                'document_moved',
                $userId,
                [
                    'from_location' => $fromLocation,
                    'to_location' => $toLocation,
                    'reason' => $reason,
                    'distribution_id' => $distributionId,
                ]
            );

            // Update document's current location
            $document->update(['cur_loc' => $toLocation]);
        }

        return $locationRecord;
    }

    /**
     * Get document history with timeline
     */
    public function getDocumentHistory(string $documentType, int $documentId)
    {
        return DocumentLocation::getLocationHistory($documentType, $documentId);
    }

    /**
     * Get location timeline for a document
     */
    public function getLocationTimeline(string $documentType, int $documentId)
    {
        $locations = $this->getDocumentHistory($documentType, $documentId);
        $document = $this->getDocument($documentType, $documentId);

        if (!$document) {
            return collect();
        }

        // Add tracking events for more detailed timeline
        $trackingEvents = TrackingEvent::getTimeline($document);

        // Merge and sort by date
        $timeline = collect();

        foreach ($locations as $location) {
            $timeline->push([
                'type' => 'location_change',
                'date' => $location->moved_at,
                'location_code' => $location->location_code,
                'department_name' => $location->department?->name ?? 'Unknown',
                'moved_by' => $location->movedBy?->name ?? 'System',
                'reason' => $location->reason,
                'distribution_number' => $location->distribution?->distribution_number,
                'metadata' => [
                    'distribution_id' => $location->distribution_id,
                ],
            ]);
        }

        foreach ($trackingEvents as $event) {
            $timeline->push([
                'type' => 'tracking_event',
                'date' => $event->created_at,
                'event_type' => $event->event_type,
                'user' => $event->user?->name ?? 'System',
                'metadata' => $event->metadata,
            ]);
        }

        return $timeline->sortByDesc('date')->values();
    }

    /**
     * Get current location of a document
     */
    public function getCurrentLocation(string $documentType, int $documentId): ?string
    {
        return DocumentLocation::getCurrentLocation($documentType, $documentId);
    }

    /**
     * Get all documents in a specific location
     */
    public function getDocumentsInLocation(string $locationCode, int $limit = 50)
    {
        // Get the latest location for each document
        $latestLocations = DocumentLocation::inLocation($locationCode)
            ->latest()
            ->limit($limit)
            ->get()
            ->groupBy(function ($location) {
                return $location->document_type . '_' . $location->document_id;
            })
            ->map(function ($locations) {
                return $locations->first(); // Get the latest location for each document
            });

        $documents = collect();

        foreach ($latestLocations as $location) {
            $document = $this->getDocument($location->document_type, $location->document_id);
            if ($document && $document->cur_loc === $locationCode) {
                $documents->push([
                    'document' => $document,
                    'document_type' => $location->document_type,
                    'location_info' => $location,
                ]);
            }
        }

        return $documents;
    }

    /**
     * Get movement statistics
     */
    public function getMovementStatistics(int $days = 30)
    {
        $stats = DocumentLocation::recent($days)
            ->selectRaw('location_code, COUNT(*) as movements')
            ->groupBy('location_code')
            ->orderByDesc('movements')
            ->get();

        return $stats;
    }

    /**
     * Track bulk document movement (for distributions)
     */
    public function trackBulkMovement(
        array $documents,
        string $toLocation,
        string $reason,
        ?int $distributionId = null
    ): array {
        $results = [];

        DB::transaction(function () use ($documents, $toLocation, $reason, $distributionId, &$results) {
            foreach ($documents as $doc) {
                $documentType = $doc['type'];
                $documentId = $doc['id'];
                $fromLocation = $doc['current_location'] ?? $this->getCurrentLocation($documentType, $documentId);

                if ($fromLocation !== $toLocation) {
                    $results[] = $this->trackMovement(
                        $documentType,
                        $documentId,
                        $fromLocation,
                        $toLocation,
                        $reason,
                        $distributionId
                    );
                }
            }
        });

        return $results;
    }

    /**
     * Get department location summary
     */
    public function getDepartmentLocationSummary()
    {
        return Department::withCount([
            'documentsAtLocation as invoice_count' => function ($query) {
                $query->whereHas('locations', function ($subQuery) {
                    $subQuery->where('document_type', 'invoice');
                });
            },
            'documentsAtLocation as additional_doc_count' => function ($query) {
                $query->whereHas('locations', function ($subQuery) {
                    $subQuery->where('document_type', 'additional_document');
                });
            }
        ])->get();
    }

    /**
     * Search documents by location history
     */
    public function searchByLocationHistory(string $searchTerm, int $limit = 20)
    {
        $locations = DocumentLocation::whereHas('department', function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('location_code', 'like', "%{$searchTerm}%");
        })
            ->orWhere('reason', 'like', "%{$searchTerm}%")
            ->with(['department', 'movedBy', 'distribution'])
            ->latest()
            ->limit($limit)
            ->get();

        return $locations->map(function ($location) {
            $document = $this->getDocument($location->document_type, $location->document_id);
            return [
                'location' => $location,
                'document' => $document,
            ];
        });
    }

    /**
     * Get a document by type and ID
     */
    private function getDocument(string $documentType, int $documentId)
    {
        return match ($documentType) {
            'invoice' => Invoice::find($documentId),
            'additional_document' => AdditionalDocument::find($documentId),
            default => null,
        };
    }

    /**
     * Initialize tracking for existing documents
     */
    public function initializeTracking(): array
    {
        $results = ['invoices' => 0, 'additional_documents' => 0];

        // Track existing invoices
        Invoice::whereNotNull('cur_loc')->chunk(100, function ($invoices) use (&$results) {
            foreach ($invoices as $invoice) {
                if (!DocumentLocation::forDocument('invoice', $invoice->id)->exists()) {
                    DocumentLocation::trackMovement(
                        'invoice',
                        $invoice->id,
                        $invoice->cur_loc,
                        null, // System initialization
                        null,
                        'Initial location tracking setup'
                    );
                    $results['invoices']++;
                }
            }
        });

        // Track existing additional documents
        AdditionalDocument::whereNotNull('cur_loc')->chunk(100, function ($documents) use (&$results) {
            foreach ($documents as $document) {
                if (!DocumentLocation::forDocument('additional_document', $document->id)->exists()) {
                    DocumentLocation::trackMovement(
                        'additional_document',
                        $document->id,
                        $document->cur_loc,
                        null, // System initialization
                        null,
                        'Initial location tracking setup'
                    );
                    $results['additional_documents']++;
                }
            }
        });

        return $results;
    }
}
