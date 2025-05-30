<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceAttachmentRequest;
use App\Http\Resources\InvoiceAttachmentResource;
use App\Services\InvoiceAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class InvoiceAttachmentController extends Controller
{
    protected InvoiceAttachmentService $attachmentService;

    public function __construct(InvoiceAttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Display a listing of attachments for a specific invoice.
     */
    public function index(int $invoiceId): JsonResponse
    {
        try {
            $attachments = $this->attachmentService->getInvoiceAttachments($invoiceId);

            return response()->json([
                'success' => true,
                'data' => InvoiceAttachmentResource::collection($attachments),
                'stats' => $this->attachmentService->getInvoiceStorageStats($invoiceId)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attachments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly uploaded attachment.
     */
    public function store(int $invoiceId, InvoiceAttachmentRequest $request): JsonResponse
    {
        try {
            $attachment = $this->attachmentService->uploadAttachment(
                $invoiceId,
                $request->file('file'),
                $request->input('description')
            );

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully',
                'data' => new InvoiceAttachmentResource($attachment)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified attachment (download/view).
     */
    public function show(int $invoiceId, int $attachmentId): Response
    {
        try {
            // Check if user can access this attachment
            if (!$this->attachmentService->canUserAccessAttachment($attachmentId, Auth::id())) {
                abort(403, 'Access denied');
            }

            $fileData = $this->attachmentService->getFileContent($attachmentId);
            $attachment = $fileData['attachment'];
            $content = $fileData['content'];

            // Determine if we should display inline or force download
            $isInline = $attachment->is_image || $attachment->is_pdf;
            $disposition = $isInline ? 'inline' : 'attachment';

            return response($content)
                ->header('Content-Type', $attachment->mime_type)
                ->header('Content-Disposition', $disposition . '; filename="' . $attachment->file_name . '"')
                ->header('Content-Length', $attachment->file_size);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Download the specified attachment (force download).
     */
    public function download(int $invoiceId, int $attachmentId): Response
    {
        try {
            // Check if user can access this attachment
            if (!$this->attachmentService->canUserAccessAttachment($attachmentId, Auth::id())) {
                abort(403, 'Access denied');
            }

            $fileData = $this->attachmentService->getFileContent($attachmentId);
            $attachment = $fileData['attachment'];
            $content = $fileData['content'];

            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $attachment->file_name . '"')
                ->header('Content-Length', $attachment->file_size);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Update the specified attachment (description only).
     */
    public function update(int $invoiceId, int $attachmentId, InvoiceAttachmentRequest $request): JsonResponse
    {
        try {
            $attachment = $this->attachmentService->updateAttachment($attachmentId, [
                'description' => $request->input('description')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment updated successfully',
                'data' => new InvoiceAttachmentResource($attachment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(int $invoiceId, int $attachmentId): JsonResponse
    {
        try {
            $deleted = $this->attachmentService->deleteAttachment($attachmentId);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachment information without downloading the file.
     */
    public function info(int $invoiceId, int $attachmentId): JsonResponse
    {
        try {
            $attachment = $this->attachmentService->getAttachment($attachmentId);

            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new InvoiceAttachmentResource($attachment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attachment information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics for an invoice.
     */
    public function stats(int $invoiceId): JsonResponse
    {
        try {
            $stats = $this->attachmentService->getInvoiceStorageStats($invoiceId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve storage statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachments filtered by type (images, pdfs, all).
     */
    public function byType(int $invoiceId, string $type): JsonResponse
    {
        try {
            $allowedTypes = ['images', 'pdfs', 'all'];

            if (!in_array($type, $allowedTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type. Allowed types: ' . implode(', ', $allowedTypes)
                ], 400);
            }

            $attachments = $this->attachmentService->getAttachmentsByType($invoiceId, $type);

            return response()->json([
                'success' => true,
                'data' => InvoiceAttachmentResource::collection($attachments),
                'type' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attachments by type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search attachments by description.
     */
    public function search(int $invoiceId, Request $request): JsonResponse
    {
        try {
            $search = $request->query('q', '');

            if (empty($search)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }

            $attachments = $this->attachmentService->searchAttachments($invoiceId, $search);

            return response()->json([
                'success' => true,
                'data' => InvoiceAttachmentResource::collection($attachments),
                'search_query' => $search
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search attachments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
