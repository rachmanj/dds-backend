<?php

namespace App\Repositories;

use App\Models\Distribution;
use App\Models\DistributionHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DistributionRepository
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAll(array $fields = ['*'], int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Distribution::select($fields)
            ->with([
                'type',
                'originDepartment',
                'destinationDepartment',
                'creator',
                'senderVerifier',
                'receiverVerifier'
            ]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        if (!empty($filters['origin_department_id'])) {
            $query->where('origin_department_id', $filters['origin_department_id']);
        }

        if (!empty($filters['destination_department_id'])) {
            $query->where('destination_department_id', $filters['destination_department_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('distribution_number', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('notes', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Filter by user's department (show distributions where user's department is origin or destination)
        if (!empty($filters['user_department_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('origin_department_id', $filters['user_department_id'])
                    ->orWhere('destination_department_id', $filters['user_department_id']);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getById(int $id, array $fields = ['*'])
    {
        return Distribution::select($fields)
            ->with([
                'type',
                'originDepartment',
                'destinationDepartment',
                'creator',
                'senderVerifier',
                'receiverVerifier',
                'documents.document',
                'invoices',
                'additionalDocuments',
                'histories.user'
            ])
            ->findOrFail($id);
    }

    public function getByNumber(string $distributionNumber, array $fields = ['*'])
    {
        return Distribution::select($fields)
            ->with([
                'type',
                'originDepartment',
                'destinationDepartment',
                'creator',
                'invoices',
                'additionalDocuments'
            ])
            ->where('distribution_number', $distributionNumber)
            ->first();
    }

    public function create(array $data)
    {
        $distribution = Distribution::create($data);
        return $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator'
        ]);
    }

    public function update(int $id, array $data)
    {
        $distribution = Distribution::findOrFail($id);
        $distribution->update($data);
        return $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'senderVerifier',
            'receiverVerifier'
        ]);
    }

    public function delete(int $id)
    {
        return Distribution::findOrFail($id)->delete();
    }

    public function attachDocuments(int $distributionId, array $documents)
    {
        $distribution = Distribution::findOrFail($distributionId);

        foreach ($documents as $document) {
            if ($document['type'] === 'invoice') {
                $distribution->invoices()->attach($document['id']);
            } elseif ($document['type'] === 'additional_document') {
                $distribution->additionalDocuments()->attach($document['id']);
            }
        }

        return $distribution->load(['invoices', 'additionalDocuments']);
    }

    public function detachDocument(int $distributionId, string $documentType, int $documentId)
    {
        $distribution = Distribution::findOrFail($distributionId);

        if ($documentType === 'invoice') {
            $distribution->invoices()->detach($documentId);
        } elseif ($documentType === 'additional_document') {
            $distribution->additionalDocuments()->detach($documentId);
        }

        return $distribution->load(['invoices', 'additionalDocuments']);
    }

    public function updateDocumentVerification(int $distributionId, string $documentType, int $documentId, array $verificationData)
    {
        $distribution = Distribution::findOrFail($distributionId);

        if ($documentType === 'invoice') {
            $distribution->invoices()->updateExistingPivot($documentId, $verificationData);
        } elseif ($documentType === 'additional_document') {
            $distribution->additionalDocuments()->updateExistingPivot($documentId, $verificationData);
        }

        return $distribution->load(['invoices', 'additionalDocuments']);
    }

    public function getByDepartment(int $departmentId, string $direction = 'both', array $fields = ['*']): Collection
    {
        $query = Distribution::select($fields)
            ->with(['type', 'originDepartment', 'destinationDepartment', 'creator']);

        if ($direction === 'origin') {
            $query->where('origin_department_id', $departmentId);
        } elseif ($direction === 'destination') {
            $query->where('destination_department_id', $departmentId);
        } else {
            $query->where(function ($q) use ($departmentId) {
                $q->where('origin_department_id', $departmentId)
                    ->orWhere('destination_department_id', $departmentId);
            });
        }

        return $query->latest()->get();
    }

    public function getByStatus(string $status, array $fields = ['*']): Collection
    {
        return Distribution::select($fields)
            ->with(['type', 'originDepartment', 'destinationDepartment', 'creator'])
            ->where('status', $status)
            ->latest()
            ->get();
    }

    public function getByUser(int $userId, string $role = 'all', array $fields = ['*']): Collection
    {
        $query = Distribution::select($fields)
            ->with(['type', 'originDepartment', 'destinationDepartment']);

        if ($role === 'creator') {
            $query->where('created_by', $userId);
        } elseif ($role === 'sender_verifier') {
            $query->where('sender_verified_by', $userId);
        } elseif ($role === 'receiver_verifier') {
            $query->where('receiver_verified_by', $userId);
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhere('sender_verified_by', $userId)
                    ->orWhere('receiver_verified_by', $userId);
            });
        }

        return $query->latest()->get();
    }

    public function validateDistributionNumber(string $distributionNumber, ?int $distributionId = null): bool
    {
        $query = Distribution::where('distribution_number', $distributionNumber);

        if ($distributionId) {
            $query->where('id', '!=', $distributionId);
        }

        return !$query->exists();
    }

    public function getNextSequenceNumber(string $departmentLocationCode, string $typeCode, int $year): int
    {
        $prefix = "{$year}/{$departmentLocationCode}/{$typeCode}/";

        $lastDistribution = Distribution::where('distribution_number', 'like', $prefix . '%')
            ->orderBy('distribution_number', 'desc')
            ->first();

        if (!$lastDistribution) {
            return 1;
        }

        // Extract sequence number from distribution number
        $parts = explode('/', $lastDistribution->distribution_number);
        $lastSequence = (int) end($parts);

        return $lastSequence + 1;
    }

    public function addHistory(int $distributionId, string $action, int $userId, ?string $notes = null, ?array $metadata = null)
    {
        return DistributionHistory::createEntry($distributionId, $action, $userId, $notes, $metadata);
    }

    public function getHistory(int $distributionId): Collection
    {
        return DistributionHistory::where('distribution_id', $distributionId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
