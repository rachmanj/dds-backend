<?php

namespace App\Services;

use App\Models\Distribution;
use Illuminate\Support\Facades\View;

class TransmittalAdviceService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function generatePdf(Distribution $distribution)
    {
        // Load distribution with all necessary relationships
        $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'invoices',
            'additionalDocuments'
        ]);

        // Prepare data for PDF
        $data = [
            'distribution' => $distribution,
            'documents' => $distribution->getAllDocuments(),
            'generated_at' => now(),
            'generated_by' => auth()->user()
        ];

        // Generate HTML content
        $html = View::make('pdf.transmittal-advice', $data)->render();

        // For now, return HTML content
        // In a real implementation, you would use a PDF library like DomPDF or wkhtmltopdf
        return $html;
    }

    public function generatePreview(Distribution $distribution)
    {
        // Load distribution with all necessary relationships
        $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'invoices',
            'additionalDocuments'
        ]);

        // Prepare data for preview
        $data = [
            'distribution' => $distribution,
            'documents' => $distribution->getAllDocuments(),
            'generated_at' => now(),
            'generated_by' => auth()->user(),
            'preview_mode' => true
        ];

        // Generate HTML content for preview
        return View::make('pdf.transmittal-advice', $data)->render();
    }

    public function getTransmittalData(Distribution $distribution): array
    {
        $distribution->load([
            'type',
            'originDepartment',
            'destinationDepartment',
            'creator',
            'invoices',
            'additionalDocuments'
        ]);

        $documents = $distribution->getAllDocuments();

        return [
            'distribution_number' => $distribution->distribution_number,
            'distribution_type' => $distribution->type->name,
            'distribution_date' => $distribution->created_at->format('d-M-Y'),
            'origin_department' => [
                'name' => $distribution->originDepartment->name,
                'location_code' => $distribution->originDepartment->location_code,
                'project' => $distribution->originDepartment->project
            ],
            'destination_department' => [
                'name' => $distribution->destinationDepartment->name,
                'location_code' => $distribution->destinationDepartment->location_code,
                'project' => $distribution->destinationDepartment->project
            ],
            'creator' => [
                'name' => $distribution->creator->name,
                'department' => $distribution->creator->department ? $distribution->creator->department->name : 'N/A'
            ],
            'documents' => $documents->map(function ($document) {
                if ($document->document_type === 'invoice') {
                    return [
                        'type' => 'Invoice',
                        'number' => $document->invoice_number,
                        'date' => $document->invoice_date ? $document->invoice_date->format('d-M-Y') : 'N/A',
                        'description' => "Invoice from " . ($document->supplier->name ?? 'N/A'),
                        'amount' => $document->amount ?? 0,
                        'currency' => $document->currency ?? 'IDR'
                    ];
                } else {
                    return [
                        'type' => $document->type->type_name ?? 'Additional Document',
                        'number' => $document->document_number,
                        'date' => $document->document_date ? $document->document_date->format('d-M-Y') : 'N/A',
                        'description' => $document->remarks ?? 'Additional Document',
                        'amount' => null,
                        'currency' => null
                    ];
                }
            })->toArray(),
            'total_documents' => $documents->count(),
            'notes' => $distribution->notes,
            'qr_code_data' => $this->generateQrCodeData($distribution)
        ];
    }

    protected function generateQrCodeData(Distribution $distribution): string
    {
        // Generate QR code data for verification
        return json_encode([
            'distribution_number' => $distribution->distribution_number,
            'created_at' => $distribution->created_at->toISOString(),
            'origin' => $distribution->originDepartment->location_code,
            'destination' => $distribution->destinationDepartment->location_code,
            'verification_url' => url("/api/distributions/{$distribution->id}/verify")
        ]);
    }
}
