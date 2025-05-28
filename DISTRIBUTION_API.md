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
            "document_id": 1
        }
    ]
}
```

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

## Document Verification

Documents can be individually verified by both sender and receiver. The verification status is tracked in the `distribution_documents` pivot table with `sender_verified` and `receiver_verified` flags.

## Notifications

The system automatically sends notifications for:

-   Distribution created
-   Distribution sent
-   Distribution received
-   Distribution completed

Notifications are currently logged and can be extended to send emails.
