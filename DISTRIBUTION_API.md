# Distribution API Documentation

## Overview

The Distribution API provides endpoints for managing document distributions between departments. It supports the complete distribution workflow from creation to completion, including document verification and transmittal advice generation.

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## Distribution Types API

### GET /api/distribution-types

Get all distribution types.

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Normal",
            "code": "N",
            "color": "#28a745",
            "priority": 1,
            "description": "Standard distribution"
        }
    ]
}
```

### POST /api/distribution-types

Create a new distribution type.

**Request Body:**

```json
{
    "name": "Urgent",
    "code": "U",
    "color": "#dc3545",
    "priority": 2,
    "description": "Urgent distribution"
}
```

### GET /api/distribution-types/{id}

Get a specific distribution type.

### PUT /api/distribution-types/{id}

Update a distribution type.

### DELETE /api/distribution-types/{id}

Delete a distribution type (only if not in use).

### POST /api/distribution-types/validate-code

Validate distribution type code uniqueness.

**Request Body:**

```json
{
    "code": "U",
    "type_id": 1 // Optional, for edit mode
}
```

## Distributions API

### GET /api/distributions

Get all distributions with optional filtering.

**Query Parameters:**

-   `per_page`: Number of items per page (default: 15)
-   `status`: Filter by status
-   `type_id`: Filter by distribution type
-   `origin_department_id`: Filter by origin department
-   `destination_department_id`: Filter by destination department
-   `created_by`: Filter by creator
-   `date_from`: Filter by creation date (from)
-   `date_to`: Filter by creation date (to)
-   `search`: Search in distribution number and notes

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "distribution_number": "25/000H-ACC/U/00001",
                "status": "draft",
                "type": {
                    "id": 1,
                    "name": "Urgent",
                    "code": "U"
                },
                "origin_department": {
                    "id": 1,
                    "name": "Accounting",
                    "location_code": "000H-ACC"
                },
                "destination_department": {
                    "id": 2,
                    "name": "Finance",
                    "location_code": "000H-FIN"
                },
                "creator": {
                    "id": 1,
                    "name": "John Doe"
                },
                "created_at": "2025-01-27T10:00:00.000000Z",
                "notes": "Urgent processing required"
            }
        ],
        "total": 1
    }
}
```

### POST /api/distributions

Create a new distribution.

**Request Body:**

```json
{
    "type_id": 1,
    "origin_department_id": 1,
    "destination_department_id": 2,
    "notes": "Urgent processing required",
    "documents": [
        {
            "type": "invoice",
            "id": 1
        },
        {
            "type": "additional_document",
            "id": 2
        }
    ]
}
```

### GET /api/distributions/{id}

Get a specific distribution with all relationships.

### PUT /api/distributions/{id}

Update a distribution (only in draft status).

**Request Body:**

```json
{
    "type_id": 2,
    "destination_department_id": 3,
    "notes": "Updated notes"
}
```

### DELETE /api/distributions/{id}

Delete a distribution (only in draft status).

## Distribution Workflow Endpoints

### POST /api/distributions/{id}/attach-documents

Attach documents to a distribution (only in draft status).

**Request Body:**

```json
{
    "documents": [
        {
            "type": "invoice",
            "id": 1
        },
        {
            "type": "additional_document",
            "id": 2
        }
    ]
}
```

### DELETE /api/distributions/{id}/detach-document/{documentType}/{documentId}

Detach a document from distribution (only in draft status).

**Parameters:**

-   `documentType`: "invoice" or "additional_document"
-   `documentId`: ID of the document to detach

### POST /api/distributions/{id}/verify-sender

Verify distribution by sender (moves from draft to verified_by_sender).

**Request Body:**

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1
        }
    ]
}
```

### POST /api/distributions/{id}/send

Send distribution (moves from verified_by_sender to sent).

### POST /api/distributions/{id}/receive

Receive distribution (moves from sent to received).

### POST /api/distributions/{id}/verify-receiver

Verify distribution by receiver (moves from received to verified_by_receiver).

**Request Body:**

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1,
            "status": "verified",
            "notes": "Document received in good condition"
        },
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "missing",
            "notes": "Document not found in envelope"
        }
    ],
    "verification_notes": "Overall verification notes for the distribution",
    "force_complete_with_discrepancies": false
}
```

**Request Fields:**

-   `document_verifications`: Array of document verification records
    -   `document_type`: "invoice" or "additional_document"
    -   `document_id`: ID of the document
    -   `status`: "verified", "missing", or "damaged"
    -   `notes`: Optional notes about the document verification
-   `verification_notes`: Optional overall notes for the distribution verification
-   `force_complete_with_discrepancies`: Boolean to force completion even with discrepancies

**Response (Success):**

```json
{
    "success": true,
    "message": "Distribution verified by receiver successfully",
    "data": {
        // Distribution data with verification details
    }
}
```

**Response (Discrepancies Found):**

```json
{
    "success": false,
    "message": "Distribution has discrepancies that require confirmation",
    "requires_confirmation": true,
    "discrepancy_details": [
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "missing",
            "notes": "Document not found in envelope"
        }
    ]
}
```

When discrepancies are found, the frontend should show the discrepancy details to the user and provide an option to proceed by setting `force_complete_with_discrepancies: true` in a subsequent request.

### POST /api/distributions/{id}/complete

Complete distribution (moves from verified_by_receiver to completed).

## Distribution Query Endpoints

### GET /api/distributions/{id}/history

Get distribution history timeline.

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "distribution_id": 1,
            "action": "created",
            "user_id": 1,
            "user": {
                "id": 1,
                "name": "John Doe"
            },
            "description": "Distribution created",
            "created_at": "2025-01-27T10:00:00.000000Z"
        }
    ]
}
```

### GET /api/distributions/{id}/transmittal

Get transmittal advice data for PDF generation.

**Response:**

```json
{
    "success": true,
    "data": {
        "distribution_number": "25/000H-ACC/U/00001",
        "distribution_type": "Urgent",
        "distribution_date": "27-Jan-2025",
        "origin_department": {
            "name": "Accounting",
            "location_code": "000H-ACC",
            "project": "Project A"
        },
        "destination_department": {
            "name": "Finance",
            "location_code": "000H-FIN",
            "project": "Project A"
        },
        "creator": {
            "name": "John Doe",
            "department": "Accounting"
        },
        "documents": [
            {
                "type": "Invoice",
                "number": "INV-001",
                "date": "26-Jan-2025",
                "description": "Invoice from Supplier A",
                "amount": 1000000,
                "currency": "IDR"
            }
        ],
        "total_documents": 1,
        "notes": "Urgent processing required",
        "qr_code_data": "{\"distribution_number\":\"25/000H-ACC/U/00001\",...}"
    }
}
```

### GET /api/distributions/{id}/transmittal-preview

Get HTML preview of transmittal advice.

**Response:**

```json
{
    "success": true,
    "data": {
        "html": "<html>...</html>"
    }
}
```

### GET /api/distributions/{id}/discrepancy-summary

Get distribution discrepancy summary.

**Response:**

```json
{
    "success": true,
    "data": {
        "has_discrepancies": true,
        "sender_discrepancies": [
            {
                "type": "missing",
                "document_type": "invoice",
                "document_id": 1,
                "notes": "Document not included in envelope"
            }
        ],
        "receiver_discrepancies": [
            {
                "type": "missing",
                "document_type": "invoice",
                "document_id": 2,
                "notes": "Document not found in envelope"
            }
        ],
        "total_documents": 5,
        "verified_documents": 3,
        "missing_documents": 2,
        "damaged_documents": 0
    }
}
```

### GET /api/distributions/by-department/{departmentId}

Get distributions by department.

**Query Parameters:**

-   `direction`: "origin", "destination", or "both" (default: "both")

### GET /api/distributions/by-status/{status}

Get distributions by status.

**Valid statuses:**

-   `draft`
-   `verified_by_sender`
-   `sent`
-   `received`
-   `verified_by_receiver`
-   `completed`

### GET /api/distributions/by-user/{userId}

Get distributions by user.

**Query Parameters:**

-   `role`: "creator", "sender_verifier", "receiver_verifier", or "all" (default: "all")

## Distribution Status Flow

```
draft → verified_by_sender → sent → received → verified_by_receiver → completed
```

### Status Descriptions:

-   **draft**: Initial state, can be edited and deleted
-   **verified_by_sender**: Sender has verified documents, ready to send
-   **sent**: Distribution has been sent to destination
-   **received**: Destination has acknowledged receipt
-   **verified_by_receiver**: Receiver has verified documents
-   **completed**: Distribution workflow completed

## Error Responses

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error message (in development)"
}
```

### Common HTTP Status Codes:

-   `200`: Success
-   `201`: Created
-   `404`: Not Found
-   `422`: Validation Error
-   `500`: Server Error

## Distribution Number Format

Distribution numbers follow the format: `YY/DEPT-CODE/TYPE-CODE/XXXXX`

Example: `25/000H-ACC/U/00001`

-   `25`: Year (2025)
-   `000H-ACC`: Department location code
-   `U`: Distribution type code
-   `00001`: Sequential number

## Document Verification & Discrepancy Handling

The system supports comprehensive document verification with discrepancy handling for real-world scenarios where documents may be missing or damaged during distribution.

### Document Verification Status Types

Each document can have one of three verification statuses:

-   **`verified`** - Document is present and in correct condition
-   **`missing`** - Document is listed in transmittal but not found in physical envelope
-   **`damaged`** - Document is present but damaged, torn, or unreadable

### Discrepancy Handling Workflow

#### Scenario 1: Normal Verification (No Discrepancies)

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1,
            "status": "verified"
        },
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "verified"
        }
    ]
}
```

**Result:** Normal completion, distribution moves to `verified_by_receiver`

#### Scenario 2: Missing Documents

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1,
            "status": "verified"
        },
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "missing",
            "notes": "Document not found in envelope. Checked all compartments."
        }
    ],
    "force_complete_with_discrepancies": false
}
```

**Initial Response (422):**

```json
{
    "success": false,
    "message": "Distribution has discrepancies that require confirmation",
    "requires_confirmation": true,
    "discrepancy_details": [
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "missing",
            "notes": "Document not found in envelope. Checked all compartments."
        }
    ]
}
```

**User Confirmation Required:** Frontend shows discrepancy dialog, user confirms by sending same request with `force_complete_with_discrepancies: true`

#### Scenario 3: Damaged Documents

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1,
            "status": "verified"
        },
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "damaged",
            "notes": "Water damage on bottom half, numbers partially readable"
        }
    ],
    "verification_notes": "Envelope showed signs of water exposure",
    "force_complete_with_discrepancies": false
}
```

#### Scenario 4: Mixed Discrepancies

```json
{
    "document_verifications": [
        {
            "document_type": "invoice",
            "document_id": 1,
            "status": "verified"
        },
        {
            "document_type": "invoice",
            "document_id": 2,
            "status": "missing",
            "notes": "Not found in envelope"
        },
        {
            "document_type": "additional_document",
            "document_id": 3,
            "status": "damaged",
            "notes": "Torn in half, content illegible"
        }
    ],
    "verification_notes": "Multiple issues with this distribution",
    "force_complete_with_discrepancies": false
}
```

### What Happens with Discrepancies

1. **Detection**: System detects any document with status other than `verified`
2. **422 Response**: Returns error response with `requires_confirmation: true`
3. **User Review**: Frontend shows discrepancy summary to user
4. **User Decision**: User can either:
    - Go back and edit the verification
    - Proceed despite discrepancies by setting `force_complete_with_discrepancies: true`
5. **Audit Trail**: All discrepancies are recorded in distribution history
6. **Workflow Continuation**: Distribution can proceed to completion with `has_discrepancies: true` flag

### Database Impact

When discrepancies are confirmed:

-   `distributions.has_discrepancies` = `true`
-   `distributions.receiver_verification_notes` = user notes
-   `distribution_documents.receiver_verification_status` = individual document status
-   `distribution_documents.receiver_verification_notes` = individual document notes
-   History entry created with discrepancy details

### Frontend User Experience

The enhanced verification dialog provides:

1. **Document Status Selection** (for receivers):

    - ✅ Verified - Document received correctly
    - ❌ Missing - Document not found in envelope
    - ⚠️ Damaged - Document damaged or unreadable

2. **Required Notes** for non-verified documents:

    - Missing: "Describe where you looked, when noticed missing, etc."
    - Damaged: "Describe the damage, extent, readability, etc."

3. **Discrepancy Confirmation Dialog**:

    - Shows summary of all discrepancies
    - Requires explicit user confirmation
    - "Go Back to Edit" or "Proceed with Discrepancies"

4. **Validation Rules**:
    - Notes required for missing/damaged documents
    - Cannot submit without reviewing discrepancies
    - Clear visual indicators (icons, colors) for each status

This system ensures complete audit trails while allowing real-world workflows to continue despite documentation issues.

## Notifications

The system automatically sends notifications for:

-   Distribution created
-   Distribution sent
-   Distribution received
-   Distribution completed
-   **Distribution discrepancies found** (NEW)

### Discrepancy Notifications

When a receiver verifies documents and marks any as missing or damaged, the system automatically sends notifications to:

1. **Origin Department Users**: All users in the department that sent the distribution
2. **Distribution Creator**: The user who originally created the distribution (if not already included above)

**Notification Content Includes:**

-   Distribution number and details
-   Receiver department and verifier name
-   Count of missing vs damaged documents
-   Detailed list of discrepancies with notes
-   Receiver's overall verification notes
-   Verification timestamp

**Example Notification Log:**

```json
{
    "distribution_id": 123,
    "distribution_number": "25/000H-ACC/U/00001",
    "verified_by": "Jane Doe",
    "missing_documents": 1,
    "damaged_documents": 1,
    "total_discrepancies": 2,
    "receiver_notes": "Multiple issues found during verification"
}
```

This ensures the sending department is immediately aware of any issues and can take corrective action for future distributions.

Notifications are currently logged and can be extended to send emails.
