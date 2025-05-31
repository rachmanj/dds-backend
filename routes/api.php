<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AdditionalDocumentTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AdditionalDocumentController;
use App\Http\Controllers\DistributionTypeController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\InvoiceAttachmentController;
use App\Http\Controllers\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes 
Route::post('/login', [AuthController::class, 'login']);
Route::post('/token-login', [AuthController::class, 'tokenLogin']);

// Protected routes
Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User permission routes
    Route::get('/auth/user-roles', [UserRoleController::class, 'getAuthUserRoles']);
    Route::get('/auth/user-permissions', [UserRoleController::class, 'getAuthUserPermissions']);

    // Validation routes
    Route::post('/invoices/validate-number', [InvoiceController::class, 'validateInvoiceNumber']);
    Route::post('/distribution-types/validate-code', [DistributionTypeController::class, 'validateCode']);

    // User Management
    Route::apiResource('users', UserController::class);
    Route::get('/users/{id}/roles', [UserController::class, 'getRoles']);
    Route::get('/users/{id}/permissions', [UserController::class, 'getPermissions']);
    Route::post('/users/{id}/assign-roles', [UserController::class, 'assignRoles']);
    Route::get('/available-roles', [UserController::class, 'getAvailableRoles']);

    // Role Management
    Route::apiResource('roles', RoleController::class);
    Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::post('/roles/{id}/assign-permissions', [RoleController::class, 'assignPermissions']);
    Route::get('/roles/all/list', [RoleController::class, 'getAllRoles']);
    Route::get('/available-permissions', [RoleController::class, 'getAvailablePermissions']);

    // Permission Management
    Route::apiResource('permissions', PermissionController::class);
    Route::get('/permissions/all/list', [PermissionController::class, 'getAllPermissions']);
    Route::get('/permissions/by-guard', [PermissionController::class, 'getPermissionsByGuard']);

    // Resources
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('addoc-types', AdditionalDocumentTypeController::class);
    Route::apiResource('invoice-types', InvoiceTypeController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('additional-documents', AdditionalDocumentController::class);
    Route::post('additional-documents/import', [AdditionalDocumentController::class, 'import']);

    // Distribution Management
    Route::apiResource('distribution-types', DistributionTypeController::class);
    Route::apiResource('distributions', DistributionController::class);

    // Distribution workflow routes
    Route::post('/distributions/{id}/attach-documents', [DistributionController::class, 'attachDocuments']);
    Route::delete('/distributions/{id}/detach-document/{documentType}/{documentId}', [DistributionController::class, 'detachDocument']);
    Route::post('/distributions/{id}/verify-sender', [DistributionController::class, 'verifySender']);
    Route::post('/distributions/{id}/send', [DistributionController::class, 'send']);
    Route::post('/distributions/{id}/receive', [DistributionController::class, 'receive']);
    Route::post('/distributions/{id}/verify-receiver', [DistributionController::class, 'verifyReceiver']);
    Route::post('/distributions/{id}/complete', [DistributionController::class, 'complete']);

    // Distribution query routes
    Route::get('/distributions/{id}/history', [DistributionController::class, 'history']);
    Route::get('/distributions/{id}/transmittal', [DistributionController::class, 'transmittal']);
    Route::get('/distributions/{id}/transmittal-preview', [DistributionController::class, 'transmittalPreview']);
    Route::get('/distributions/{id}/discrepancy-summary', [DistributionController::class, 'discrepancySummary']);
    Route::get('/distributions/by-department/{departmentId}', [DistributionController::class, 'byDepartment']);
    Route::get('/distributions/by-status/{status}', [DistributionController::class, 'byStatus']);
    Route::get('/distributions/by-user/{userId}', [DistributionController::class, 'byUser']);

    // Location-based document routes for distribution
    Route::get('/invoices-for-distribution', [InvoiceController::class, 'forDistribution']);
    Route::get('/additional-documents-for-distribution', [AdditionalDocumentController::class, 'forDistribution']);

    // Department-filtered invoice routes
    Route::get('/invoices-location-filtered', function (Request $request) {
        $request->merge(['filter_by_department' => true]);
        return app(InvoiceController::class)->index($request);
    });
    Route::get('/invoices-location-filtered/{id}', function (Request $request, $id) {
        $request->merge(['filter_by_department' => true]);
        return app(InvoiceController::class)->show($id, $request);
    });

    // Department-filtered additional document routes
    Route::get('/additional-documents-location-filtered', function (Request $request) {
        $request->merge(['filter_by_department' => true]);
        return app(AdditionalDocumentController::class)->index($request);
    });
    Route::get('/additional-documents-location-filtered/{id}', function (Request $request, $id) {
        $request->merge(['filter_by_department' => true]);
        return app(AdditionalDocumentController::class)->show($id, $request);
    });

    // Invoice-AdditionalDocument relationship routes
    Route::get('/invoices/{id}/additional-documents', [InvoiceController::class, 'getAdditionalDocuments']);
    Route::post('/invoices/{id}/additional-documents', [InvoiceController::class, 'attachAdditionalDocument']);
    Route::delete('/invoices/{id}/additional-documents/{documentId}', [InvoiceController::class, 'detachAdditionalDocument']);
    Route::put('/invoices/{id}/additional-documents', [InvoiceController::class, 'syncAdditionalDocuments']);

    // AdditionalDocument-Invoice relationship routes
    Route::get('/additional-documents/{id}/invoices', [AdditionalDocumentController::class, 'getInvoices']);
    Route::post('/additional-documents/{id}/invoices', [AdditionalDocumentController::class, 'attachInvoice']);
    Route::delete('/additional-documents/{id}/invoices/{invoiceId}', [AdditionalDocumentController::class, 'detachInvoice']);
    Route::put('/additional-documents/{id}/invoices', [AdditionalDocumentController::class, 'syncInvoices']);

    // Invoice Attachment routes
    Route::get('/invoices/{invoiceId}/attachments', [InvoiceAttachmentController::class, 'index']);
    Route::post('/invoices/{invoiceId}/attachments', [InvoiceAttachmentController::class, 'store']);
    Route::get('/invoices/{invoiceId}/attachments/search', [InvoiceAttachmentController::class, 'search']);
    Route::get('/invoices/{invoiceId}/attachments/type/{type}', [InvoiceAttachmentController::class, 'byType']);
    Route::get('/invoices/{invoiceId}/attachments/{attachmentId}', [InvoiceAttachmentController::class, 'show']);
    Route::get('/invoices/{invoiceId}/attachments/{attachmentId}/download', [InvoiceAttachmentController::class, 'download']);
    Route::get('/invoices/{invoiceId}/attachments/{attachmentId}/info', [InvoiceAttachmentController::class, 'info']);
    Route::put('/invoices/{invoiceId}/attachments/{attachmentId}', [InvoiceAttachmentController::class, 'update']);
    Route::delete('/invoices/{invoiceId}/attachments/{attachmentId}', [InvoiceAttachmentController::class, 'destroy']);
    Route::get('/invoices/{invoiceId}/attachments-stats', [InvoiceAttachmentController::class, 'stats']);

    // Reports routes - Read-only comprehensive reporting
    Route::prefix('reports')->group(function () {
        // Invoice Reports
        Route::get('/invoices', [ReportsController::class, 'invoicesReport']);
        Route::get('/invoices/{id}', [ReportsController::class, 'invoiceDetails']);

        // Additional Documents Reports
        Route::get('/additional-documents', [ReportsController::class, 'additionalDocumentsReport']);
        Route::get('/additional-documents/{id}', [ReportsController::class, 'additionalDocumentDetails']);

        // Distribution Reports
        Route::get('/distributions', [ReportsController::class, 'distributionsReport']);
        Route::get('/distributions/{id}', [ReportsController::class, 'distributionDetails']);
    });
});
