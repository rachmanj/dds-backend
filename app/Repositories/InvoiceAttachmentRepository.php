<?php

namespace App\Repositories;

use App\Models\InvoiceAttachment;

class InvoiceAttachmentRepository
{
    public function getByInvoiceId(int $invoiceId, array $fields = ['*'])
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader'])
            ->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getById(int $id, array $fields = ['*']): ?InvoiceAttachment
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader'])
            ->find($id);
    }

    public function create(array $data): InvoiceAttachment
    {
        $attachment = InvoiceAttachment::create($data);
        return $attachment->load(['uploader']);
    }

    public function update(int $id, array $data): InvoiceAttachment
    {
        $attachment = InvoiceAttachment::findOrFail($id);
        $attachment->update($data);
        return $attachment->load(['uploader']);
    }

    public function delete(int $id): bool
    {
        return InvoiceAttachment::findOrFail($id)->delete();
    }

    public function getAttachmentsByUser(int $userId, array $fields = ['*'], int $perPage = 15)
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader', 'invoice'])
            ->where('uploaded_by', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getTotalSizeByInvoice(int $invoiceId): int
    {
        return InvoiceAttachment::where('invoice_id', $invoiceId)
            ->sum('file_size');
    }

    public function getCountByInvoice(int $invoiceId): int
    {
        return InvoiceAttachment::where('invoice_id', $invoiceId)
            ->count();
    }

    public function getByMimeType(int $invoiceId, array $mimeTypes, array $fields = ['*'])
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader'])
            ->where('invoice_id', $invoiceId)
            ->whereIn('mime_type', $mimeTypes)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchByDescription(int $invoiceId, string $search, array $fields = ['*'])
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader'])
            ->where('invoice_id', $invoiceId)
            ->where('description', 'like', "%{$search}%")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecentAttachments(int $limit = 10, array $fields = ['*'])
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getAttachmentsByDateRange(int $invoiceId, string $startDate, string $endDate, array $fields = ['*'])
    {
        return InvoiceAttachment::select($fields)
            ->with(['uploader'])
            ->where('invoice_id', $invoiceId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTotalSizeByUser(int $userId): int
    {
        return InvoiceAttachment::where('uploaded_by', $userId)
            ->sum('file_size');
    }

    public function getFileTypeStats(int $invoiceId): array
    {
        return InvoiceAttachment::selectRaw('
                LOWER(SUBSTRING_INDEX(file_name, ".", -1)) as extension,
                COUNT(*) as count,
                SUM(file_size) as total_size
            ')
            ->where('invoice_id', $invoiceId)
            ->groupBy('extension')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }
}
