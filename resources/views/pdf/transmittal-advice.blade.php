<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmittal Advice - {{ $distribution->distribution_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }

        .distribution-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .info-section {
            width: 48%;
        }

        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .documents-table th,
        .documents-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .documents-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .documents-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin: 40px 0 10px 0;
            height: 1px;
        }

        .qr-code {
            float: right;
            margin-left: 20px;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }

        .type-normal {
            background-color: #28a745;
        }

        .type-urgent {
            background-color: #dc3545;
        }

        .type-confidential {
            background-color: #6f42c1;
        }

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>TRANSMITTAL ADVICE</h1>
        <h2>Surat Pengantar Dokumen</h2>
    </div>

    <div class="distribution-info">
        <div class="info-section">
            <h3>Distribution Information</h3>
            <div class="info-row">
                <span class="info-label">Number:</span>
                <strong>{{ $distribution->distribution_number }}</strong>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                {{ $distribution->created_at->format('d F Y') }}
            </div>
            <div class="info-row">
                <span class="info-label">Type:</span>
                <span class="type-badge type-{{ strtolower($distribution->type->code) }}">
                    {{ $distribution->type->name }}
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                {{ ucfirst(str_replace('_', ' ', $distribution->status)) }}
            </div>
        </div>

        <div class="info-section">
            <h3>Routing Information</h3>
            <div class="info-row">
                <span class="info-label">From:</span>
                {{ $distribution->originDepartment->name }}
                ({{ $distribution->originDepartment->location_code }})
            </div>
            <div class="info-row">
                <span class="info-label">To:</span>
                {{ $distribution->destinationDepartment->name }}
                ({{ $distribution->destinationDepartment->location_code }})
            </div>
            <div class="info-row">
                <span class="info-label">Created by:</span>
                {{ $distribution->creator->name }}
            </div>
            @if ($distribution->notes)
                <div class="info-row">
                    <span class="info-label">Notes:</span>
                    {{ $distribution->notes }}
                </div>
            @endif
        </div>
    </div>

    <h3>Attached Documents</h3>
    <table class="documents-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Document Type</th>
                <th>Document Number</th>
                <th>Date</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $index => $document)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $document['type'] }}</td>
                    <td>{{ $document['number'] }}</td>
                    <td>{{ $document['date'] }}</td>
                    <td>{{ $document['description'] }}</td>
                    <td>
                        @if ($document['amount'])
                            {{ $document['currency'] }} {{ number_format($document['amount'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">No documents attached</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div style="margin-bottom: 20px;">
            <strong>Total Documents:</strong> {{ count($documents) }}
        </div>

        @if (!isset($preview_mode) || !$preview_mode)
            <div class="qr-code">
                <!-- QR Code would be generated here -->
                <div
                    style="width: 80px; height: 80px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                    QR Code
                </div>
            </div>
        @endif

        <div class="signature-section">
            <div class="signature-box">
                <div><strong>Sender</strong></div>
                <div class="signature-line"></div>
                <div>{{ $distribution->creator->name }}</div>
                <div>{{ $distribution->originDepartment->name }}</div>
                @if ($distribution->sender_verified_at)
                    <div style="font-size: 10px; color: #666;">
                        Verified: {{ $distribution->sender_verified_at->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>

            <div class="signature-box">
                <div><strong>Receiver</strong></div>
                <div class="signature-line"></div>
                <div>_____________________</div>
                <div>{{ $distribution->destinationDepartment->name }}</div>
                @if ($distribution->receiver_verified_at)
                    <div style="font-size: 10px; color: #666;">
                        Verified: {{ $distribution->receiver_verified_at->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #666;">
            Generated on {{ $generated_at->format('d F Y \a\t H:i:s') }}
            @if (isset($generated_by))
                by {{ $generated_by->name }}
            @endif
        </div>
    </div>
</body>

</html>
