<?php

namespace App\Services;

use App\Repositories\DistributionRepository;
use App\Repositories\DistributionTypeRepository;
use App\Repositories\DepartmentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DistributionService
{
    protected DistributionRepository $distributionRepository;
    protected DistributionTypeRepository $distributionTypeRepository;
    protected DepartmentRepository $departmentRepository;
    protected DistributionNotificationService $notificationService;

    public function __construct(
        DistributionRepository $distributionRepository,
        DistributionTypeRepository $distributionTypeRepository,
        DepartmentRepository $departmentRepository,
        DistributionNotificationService $notificationService
    ) {
        $this->distributionRepository = $distributionRepository;
        $this->distributionTypeRepository = $distributionTypeRepository;
        $this->departmentRepository = $departmentRepository;
        $this->notificationService = $notificationService;
    }

    public function getAll(array $fields = ['*'], int $perPage = 15, array $filters = [])
    {
        return $this->distributionRepository->getAll($fields, $perPage, $filters);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return $this->distributionRepository->getById($id, $fields);
    }

    public function getByNumber(string $distributionNumber, array $fields = ['*'])
    {
        return $this->distributionRepository->getByNumber($distributionNumber, $fields);
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            // Set creator
            $data['created_by'] = Auth::id();
            $data['status'] = 'draft';

            // Validate document_type is provided
            if (empty($data['document_type'])) {
                throw new \InvalidArgumentException('Document type is required.');
            }

            // Get user's department location for filtering
            $user = Auth::user();
            $userDepartment = $this->departmentRepository->getById($user->department_id, ['location_code']);
            $userLocationCode = $userDepartment->location_code;

            // Process documents based on type and location
            $processedDocuments = $this->processDocumentsForDistribution(
                $data['documents'] ?? [],
                $data['document_type'],
                $userLocationCode
            );

            // Generate distribution number
            $originDepartment = $this->departmentRepository->getById($data['origin_department_id'], ['location_code']);
            $distributionType = $this->distributionTypeRepository->getById($data['type_id'], ['code']);

            $data['distribution_number'] = $this->generateDistributionNumber(
                $originDepartment->location_code,
                $distributionType->code
            );

            // Create distribution
            $distribution = $this->distributionRepository->create($data);

            // Attach documents if provided
            if (!empty($processedDocuments['documents'])) {
                $this->distributionRepository->attachDocuments($distribution->id, $processedDocuments['documents']);
            }

            // Add history entry
            $this->distributionRepository->addHistory(
                $distribution->id,
                'created',
                Auth::id(),
                'Distribution created'
            );

            DB::commit();

            // Send notification
            $this->notificationService->sendCreatedNotification($distribution);

            // Return distribution with warnings
            $distribution->warnings = $processedDocuments['warnings'] ?? [];

            return $distribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($id);

            // Only allow updates if distribution is in draft status
            if (!$distribution->isDraft()) {
                throw new \InvalidArgumentException('Can only update distributions in draft status.');
            }

            $updatedDistribution = $this->distributionRepository->update($id, $data);

            // Add history entry
            $this->distributionRepository->addHistory(
                $id,
                'updated',
                Auth::id(),
                'Distribution updated'
            );

            DB::commit();

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $distribution = $this->distributionRepository->getById($id);

            // Only allow deletion if distribution is in draft status
            if (!$distribution->isDraft()) {
                throw new \InvalidArgumentException('Can only delete distributions in draft status.');
            }

            return $this->distributionRepository->delete($id);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function attachDocuments(int $distributionId, array $documents)
    {
        try {
            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow document changes if distribution is in draft status
            if (!$distribution->isDraft()) {
                throw new \InvalidArgumentException('Can only modify documents in draft status.');
            }

            $updatedDistribution = $this->distributionRepository->attachDocuments($distributionId, $documents);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'documents_attached',
                Auth::id(),
                'Documents attached to distribution'
            );

            return $updatedDistribution;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function detachDocument(int $distributionId, string $documentType, int $documentId)
    {
        try {
            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow document changes if distribution is in draft status
            if (!$distribution->isDraft()) {
                throw new \InvalidArgumentException('Can only modify documents in draft status.');
            }

            $updatedDistribution = $this->distributionRepository->detachDocument($distributionId, $documentType, $documentId);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'document_detached',
                Auth::id(),
                "Document {$documentType}:{$documentId} detached from distribution"
            );

            return $updatedDistribution;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function verifySender(int $distributionId, array $documentVerifications = [], ?string $verificationNotes = null)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow sender verification if distribution is in draft status
            if (!$distribution->isDraft()) {
                throw new \InvalidArgumentException('Distribution must be in draft status for sender verification.');
            }

            // Update document verifications with enhanced status tracking
            foreach ($documentVerifications as $verification) {
                $updateData = [
                    'sender_verified' => true,
                    'sender_verification_status' => $verification['status'] ?? 'verified', // Default to 'verified' for backward compatibility
                    'sender_verification_notes' => $verification['notes'] ?? null
                ];

                $this->distributionRepository->updateDocumentVerification(
                    $distributionId,
                    $verification['document_type'],
                    $verification['document_id'],
                    $updateData
                );
            }

            // Update distribution status and verification timestamp
            $updatedDistribution = $this->distributionRepository->update($distributionId, [
                'status' => 'verified_by_sender',
                'sender_verified_at' => now(),
                'sender_verified_by' => Auth::id(),
                'sender_verification_notes' => $verificationNotes
            ]);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'verified_by_sender',
                Auth::id(),
                'Distribution verified by sender'
            );

            DB::commit();

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function send(int $distributionId)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow sending if distribution is verified by sender
            if (!$distribution->isVerifiedBySender()) {
                throw new \InvalidArgumentException('Distribution must be verified by sender before sending.');
            }

            // Update distribution status
            $updatedDistribution = $this->distributionRepository->update($distributionId, [
                'status' => 'sent',
                'sent_at' => now()
            ]);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'sent',
                Auth::id(),
                'Distribution sent to destination department'
            );

            DB::commit();

            // Send notification
            $this->notificationService->sendSentNotification($updatedDistribution);

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function receive(int $distributionId)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow receiving if distribution is sent
            if (!$distribution->isSent()) {
                throw new \InvalidArgumentException('Distribution must be sent before it can be received.');
            }

            // Get destination department location code
            $destinationDepartment = $this->departmentRepository->getById(
                $distribution->destination_department_id,
                ['location_code']
            );
            $newLocationCode = $destinationDepartment->location_code;

            // Update document locations based on distribution type
            $this->updateDocumentLocations($distribution, $newLocationCode);

            // Update distribution status
            $updatedDistribution = $this->distributionRepository->update($distributionId, [
                'status' => 'received',
                'received_at' => now()
            ]);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'received',
                Auth::id(),
                "Distribution received by destination department. Document locations updated to {$newLocationCode}"
            );

            DB::commit();

            // Send notification
            $this->notificationService->sendReceivedNotification($updatedDistribution);

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function verifyReceiver(int $distributionId, array $documentVerifications = [], ?string $verificationNotes = null, bool $forceCompleteWithDiscrepancies = false)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow receiver verification if distribution is received
            if (!$distribution->isReceived()) {
                throw new \InvalidArgumentException('Distribution must be received before receiver verification.');
            }

            $hasDiscrepancies = false;
            $discrepancyDetails = [];

            // Update document verifications with enhanced status tracking
            foreach ($documentVerifications as $verification) {
                $updateData = [
                    'receiver_verified' => true,
                    'receiver_verification_status' => $verification['status'],
                    'receiver_verification_notes' => $verification['notes'] ?? null
                ];

                // Track discrepancies
                if (in_array($verification['status'], ['missing', 'damaged'])) {
                    $hasDiscrepancies = true;
                    $discrepancyDetails[] = [
                        'document_type' => $verification['document_type'],
                        'document_id' => $verification['document_id'],
                        'status' => $verification['status'],
                        'notes' => $verification['notes'] ?? null
                    ];
                }

                $this->distributionRepository->updateDocumentVerification(
                    $distributionId,
                    $verification['document_type'],
                    $verification['document_id'],
                    $updateData
                );
            }

            // Check if we can proceed with discrepancies
            if ($hasDiscrepancies && !$forceCompleteWithDiscrepancies) {
                // Return distribution with discrepancy information for user confirmation
                $distribution = $this->distributionRepository->getById($distributionId);
                $distribution->discrepancy_details = $discrepancyDetails;

                DB::rollBack();

                // Create exception with additional data
                $exception = new \InvalidArgumentException(
                    'Documents have discrepancies. Please review and confirm to proceed. ' .
                        'Discrepancy details: ' . json_encode($discrepancyDetails)
                );
                throw $exception;
            }

            // Update distribution status and verification timestamp
            $updatedDistribution = $this->distributionRepository->update($distributionId, [
                'status' => 'verified_by_receiver',
                'receiver_verified_at' => now(),
                'receiver_verified_by' => Auth::id(),
                'receiver_verification_notes' => $verificationNotes,
                'has_discrepancies' => $hasDiscrepancies
            ]);

            // Add history entry with discrepancy information
            $historyMessage = 'Distribution verified by receiver';
            if ($hasDiscrepancies) {
                $historyMessage .= ' (with discrepancies)';
            }

            $this->distributionRepository->addHistory(
                $distributionId,
                'verified_by_receiver',
                Auth::id(),
                $historyMessage
            );

            // If there are discrepancies, add detailed history
            if ($hasDiscrepancies) {
                foreach ($discrepancyDetails as $discrepancy) {
                    $this->distributionRepository->addHistory(
                        $distributionId,
                        'document_discrepancy',
                        Auth::id(),
                        "Document {$discrepancy['document_type']}:{$discrepancy['document_id']} marked as {$discrepancy['status']}" .
                            ($discrepancy['notes'] ? " - {$discrepancy['notes']}" : '')
                    );
                }

                // Send discrepancy notification to sender/origin department
                $this->notificationService->sendDiscrepancyNotification($updatedDistribution, $discrepancyDetails);
            }

            DB::commit();

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function complete(int $distributionId)
    {
        try {
            DB::beginTransaction();

            $distribution = $this->distributionRepository->getById($distributionId);

            // Only allow completion if distribution is verified by receiver
            if (!$distribution->isVerifiedByReceiver()) {
                throw new \InvalidArgumentException('Distribution must be verified by receiver before completion.');
            }

            // Update distribution status
            $updatedDistribution = $this->distributionRepository->update($distributionId, [
                'status' => 'completed'
            ]);

            // Add history entry
            $this->distributionRepository->addHistory(
                $distributionId,
                'completed',
                Auth::id(),
                'Distribution completed'
            );

            DB::commit();

            // Send notification
            $this->notificationService->sendCompletedNotification($updatedDistribution);

            return $updatedDistribution;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getHistory(int $distributionId)
    {
        return $this->distributionRepository->getHistory($distributionId);
    }

    public function getByDepartment(int $departmentId, string $direction = 'both', array $fields = ['*'])
    {
        return $this->distributionRepository->getByDepartment($departmentId, $direction, $fields);
    }

    public function getByStatus(string $status, array $fields = ['*'])
    {
        return $this->distributionRepository->getByStatus($status, $fields);
    }

    public function getByUser(int $userId, string $role = 'all', array $fields = ['*'])
    {
        return $this->distributionRepository->getByUser($userId, $role, $fields);
    }

    public function validateDistributionNumber(string $distributionNumber, ?int $distributionId = null): bool
    {
        return $this->distributionRepository->validateDistributionNumber($distributionNumber, $distributionId);
    }

    /**
     * Process documents for distribution based on type and location
     */
    protected function processDocumentsForDistribution(array $documents, string $documentType, string $userLocationCode): array
    {
        $processedDocuments = [];
        $warnings = [];
        $autoIncludedDocuments = [];

        foreach ($documents as $document) {
            if ($documentType === 'invoice') {
                // Validate invoice exists and location
                $invoice = \App\Models\Invoice::find($document['id']);
                if (!$invoice) {
                    throw new \InvalidArgumentException("Invoice with ID {$document['id']} not found.");
                }

                // Check if invoice location matches user location
                if ($invoice->cur_loc !== $userLocationCode) {
                    throw new \InvalidArgumentException("Invoice {$invoice->invoice_number} is not in your location ({$userLocationCode}). Current location: {$invoice->cur_loc}");
                }

                // Add invoice to processed documents
                $processedDocuments[] = [
                    'type' => 'invoice',
                    'id' => $invoice->id
                ];

                // Auto-include attached additional documents
                $attachedDocs = $invoice->additionalDocuments;
                foreach ($attachedDocs as $attachedDoc) {
                    // Check location of attached document
                    if ($attachedDoc->cur_loc !== $userLocationCode) {
                        $warnings[] = [
                            'type' => 'location_mismatch',
                            'message' => "Additional document {$attachedDoc->document_number} attached to invoice {$invoice->invoice_number} has different location ({$attachedDoc->cur_loc}). It will not be included in the distribution.",
                            'document_type' => 'additional_document',
                            'document_id' => $attachedDoc->id,
                            'document_number' => $attachedDoc->document_number
                        ];
                    } else {
                        // Auto-include if location matches
                        $processedDocuments[] = [
                            'type' => 'additional_document',
                            'id' => $attachedDoc->id
                        ];
                        $autoIncludedDocuments[] = [
                            'type' => 'additional_document',
                            'id' => $attachedDoc->id,
                            'document_number' => $attachedDoc->document_number,
                            'auto_included' => true
                        ];
                    }
                }
            } elseif ($documentType === 'additional_document') {
                // Validate additional document exists and location
                $additionalDoc = \App\Models\AdditionalDocument::find($document['id']);
                if (!$additionalDoc) {
                    throw new \InvalidArgumentException("Additional document with ID {$document['id']} not found.");
                }

                // Check if document location matches user location
                if ($additionalDoc->cur_loc !== $userLocationCode) {
                    throw new \InvalidArgumentException("Additional document {$additionalDoc->document_number} is not in your location ({$userLocationCode}). Current location: {$additionalDoc->cur_loc}");
                }

                // Add document to processed documents
                $processedDocuments[] = [
                    'type' => 'additional_document',
                    'id' => $additionalDoc->id
                ];
            }
        }

        return [
            'documents' => $processedDocuments,
            'warnings' => $warnings,
            'auto_included' => $autoIncludedDocuments
        ];
    }

    protected function generateDistributionNumber(string $departmentLocationCode, string $typeCode): string
    {
        $year = date('y'); // 2-digit year
        $sequence = $this->distributionRepository->getNextSequenceNumber($departmentLocationCode, $typeCode, $year);

        return sprintf('%s/%s/%s/%05d', $year, $departmentLocationCode, $typeCode, $sequence);
    }

    protected function updateDocumentLocations($distribution, $newLocationCode)
    {
        if ($distribution->isInvoiceDistribution()) {
            // Update invoice locations
            foreach ($distribution->invoices as $invoice) {
                \App\Models\Invoice::where('id', $invoice->id)
                    ->update(['cur_loc' => $newLocationCode]);
            }

            // Update additional document locations (auto-included)
            foreach ($distribution->additionalDocuments as $additionalDoc) {
                \App\Models\AdditionalDocument::where('id', $additionalDoc->id)
                    ->update(['cur_loc' => $newLocationCode]);
            }
        } elseif ($distribution->isAdditionalDocumentDistribution()) {
            // Update additional document locations only
            foreach ($distribution->additionalDocuments as $additionalDoc) {
                \App\Models\AdditionalDocument::where('id', $additionalDoc->id)
                    ->update(['cur_loc' => $newLocationCode]);
            }
        }
    }
}
