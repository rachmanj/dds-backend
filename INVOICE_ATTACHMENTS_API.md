# Invoice Attachments API Documentation

## Overview

The Invoice Attachments system allows users to upload, manage, and download file attachments for invoices. The system supports PDF files and images (JPG, JPEG, PNG, GIF) with configurable size limits and security features.

## Features

-   **File Upload**: Upload PDF and image files to invoices
-   **File Management**: View, download, update descriptions, and delete attachments
-   **Type Filtering**: Filter attachments by type (images, PDFs)
-   **Search**: Search attachments by description
-   **Storage Statistics**: Get detailed storage information
-   **Security**: Authenticated access with file validation
-   **Auto-cleanup**: Automatic cleanup of orphaned files

## Configuration

All attachment settings are managed in `config/attachments.php`:

```php
'max_file_size' => env('ATTACHMENT_MAX_FILE_SIZE', 10485760), // 10MB
'max_files_per_invoice' => env('ATTACHMENT_MAX_FILES_PER_INVOICE', 50),
'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', ...],
'storage_disk' => env('ATTACHMENT_STORAGE_DISK', 'attachments'),
```

## API Endpoints

### Base URL

All endpoints are prefixed with `/api` and require authentication via Sanctum.

### List Attachments

```
GET /invoices/{invoiceId}/attachments
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "invoice_id": 123,
            "file_name": "invoice_scan.pdf",
            "file_size": 2048576,
            "formatted_file_size": "2.0 MB",
            "mime_type": "application/pdf",
            "description": "Scanned invoice copy",
            "is_image": false,
            "is_pdf": true,
            "file_url": "/api/invoices/123/attachments/1",
            "uploader": {
                "id": 1,
                "name": "John Doe"
            },
            "created_at": "2025-05-30T10:30:00Z"
        }
    ],
    "stats": {
        "total_files": 3,
        "total_size": 7340032,
        "formatted_total_size": "7.0 MB",
        "file_types": { "pdf": 2, "jpg": 1 }
    }
}
```

### Upload Attachment

```
POST /invoices/{invoiceId}/attachments
Content-Type: multipart/form-data
```

**Parameters:**

-   `file` (required): The file to upload
-   `description` (optional): Description of the attachment

**Response:**

```json
{
    "success": true,
    "message": "Attachment uploaded successfully",
    "data": {
        "id": 1,
        "file_name": "receipt.jpg",
        "formatted_file_size": "1.5 MB",
        "description": "Payment receipt",
        "file_url": "/api/invoices/123/attachments/1"
    }
}
```

### View/Preview Attachment

```
GET /invoices/{invoiceId}/attachments/{attachmentId}
```

Returns the file content with appropriate headers. Images and PDFs are displayed inline, other files are downloaded.

### Download Attachment (Force Download)

```
GET /invoices/{invoiceId}/attachments/{attachmentId}/download
```

Forces download of the file regardless of type.

### Get Attachment Info

```
GET /invoices/{invoiceId}/attachments/{attachmentId}/info
```

Returns attachment metadata without file content.

### Update Attachment Description

```
PUT /invoices/{invoiceId}/attachments/{attachmentId}
Content-Type: application/json
```

**Parameters:**

```json
{
    "description": "Updated description"
}
```

### Delete Attachment

```
DELETE /invoices/{invoiceId}/attachments/{attachmentId}
```

Deletes both the database record and physical file.

### Filter by Type

```
GET /invoices/{invoiceId}/attachments/type/{type}
```

**Types:**

-   `images` - Only image files (JPG, JPEG, PNG, GIF)
-   `pdfs` - Only PDF files
-   `all` - All files

### Search Attachments

```
GET /invoices/{invoiceId}/attachments/search?q={search_term}
```

Searches attachment descriptions for the given term.

### Storage Statistics

```
GET /invoices/{invoiceId}/attachments-stats
```

**Response:**

```json
{
    "success": true,
    "data": {
        "total_files": 5,
        "total_size": 15728640,
        "formatted_total_size": "15.0 MB",
        "file_types": {
            "pdf": 3,
            "jpg": 1,
            "png": 1
        }
    }
}
```

## File Storage Structure

Files are organized in a hierarchical structure:

```
storage/app/private/attachments/
└── invoices/
    ├── 123/
    │   └── attachments/
    │       ├── 1638360000_invoice-scan.pdf
    │       └── 1638360120_receipt-photo.jpg
    └── 124/
        └── attachments/
            └── 1638360180_contract.pdf
```

## Validation Rules

### File Upload Validation

-   **Size**: Maximum 10MB (configurable)
-   **Types**: PDF, JPG, JPEG, PNG, GIF only
-   **Content**: MIME type validation
-   **Security**: File header validation

### Description Validation

-   **Upload**: Optional, max 255 characters
-   **Update**: Required, max 255 characters

## Error Handling

All endpoints return standardized error responses:

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error message (in debug mode)"
}
```

### Common HTTP Status Codes

-   `200` - Success
-   `201` - Created (file upload)
-   `400` - Bad Request (validation errors)
-   `403` - Forbidden (access denied)
-   `404` - Not Found (file or invoice not found)
-   `500` - Internal Server Error

## Security Features

### Authentication

-   All endpoints require Sanctum authentication
-   User access control (expandable for role-based permissions)

### File Security

-   Files stored outside web root (`storage/app/private/`)
-   Access only through authenticated API endpoints
-   MIME type validation prevents malicious file uploads
-   File header validation

### Access Control

```php
// Check if user can access attachment
if (!$this->attachmentService->canUserAccessAttachment($attachmentId, Auth::id())) {
    abort(403, 'Access denied');
}
```

## Maintenance Commands

### Cleanup Orphaned Files

```bash
# Dry run - show what would be deleted
php artisan attachments:cleanup --dry-run

# Actually delete orphaned files older than 30 days
php artisan attachments:cleanup

# Delete orphaned files older than 7 days
php artisan attachments:cleanup --days=7
```

## Integration with Invoices

Attachments are automatically included when loading invoices:

```php
// Invoice resources include attachments
$invoice = Invoice::with(['attachments.uploader'])->find(1);

// Attachments are included in InvoiceResource
return new InvoiceResource($invoice);
```

## Environment Variables

Configure the attachment system using environment variables:

```env
# File size limit in bytes (default: 10MB)
ATTACHMENT_MAX_FILE_SIZE=10485760

# Maximum files per invoice (default: 50)
ATTACHMENT_MAX_FILES_PER_INVOICE=50

# Storage disk (default: attachments)
ATTACHMENT_STORAGE_DISK=attachments

# Virus scanning (default: false)
ATTACHMENT_VIRUS_SCAN=false
```

## Performance Considerations

### Storage

-   Files stored on local disk by default
-   Can be configured for cloud storage (S3, etc.)
-   Automatic directory cleanup for deleted invoices

### Database

-   Optimized queries with eager loading
-   Indexed foreign keys for performance
-   Pagination support for large attachment lists

### Caching

-   File metadata cached in database
-   Configuration cached for better performance

## Best Practices

### File Organization

-   Use descriptive file names
-   Provide meaningful descriptions
-   Regular cleanup of old/orphaned files

### Security

-   Validate all file uploads
-   Monitor file upload patterns
-   Regular security audits

### Performance

-   Monitor storage usage
-   Implement file size limits appropriate for your use case
-   Use cloud storage for large deployments

## Troubleshooting

### Common Issues

1. **File Upload Fails**

    - Check file size limits
    - Verify file type is allowed
    - Ensure storage directory is writable

2. **Files Not Found**

    - Run orphaned file cleanup
    - Check storage disk configuration
    - Verify file paths in database

3. **Performance Issues**
    - Check for large number of attachments
    - Monitor storage disk space
    - Consider implementing file pagination
