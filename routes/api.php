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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DocumentTrackingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\FileManagementController;
use App\Http\Controllers\UserPreferencesController;
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

    // Notification routes - Phase 1 Real-time Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/types', [NotificationController::class, 'getTypes']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::post('/bulk-action', [NotificationController::class, 'bulkAction']);
        Route::post('/test', [NotificationController::class, 'test']);
    });

    // Document Tracking routes - Phase 2 Enhanced Document Tracking
    Route::prefix('tracking')->group(function () {
        Route::get('/{documentType}/{documentId}/history', [DocumentTrackingController::class, 'getHistory']);
        Route::get('/{documentType}/{documentId}/timeline', [DocumentTrackingController::class, 'getTimeline']);
        Route::get('/{documentType}/{documentId}/location', [DocumentTrackingController::class, 'getCurrentLocation']);
        Route::get('/location/{locationCode}/documents', [DocumentTrackingController::class, 'getLocationDocuments']);
        Route::post('/move', [DocumentTrackingController::class, 'trackMovement']);
        Route::get('/statistics', [DocumentTrackingController::class, 'getStatistics']);
        Route::get('/departments/summary', [DocumentTrackingController::class, 'getDepartmentSummary']);
        Route::get('/search', [DocumentTrackingController::class, 'search']);
        Route::post('/initialize', [DocumentTrackingController::class, 'initializeTracking']);
    });

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

    // Analytics routes - Phase 3 Analytics Dashboard
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/performance', [AnalyticsController::class, 'performance']);
        Route::get('/departments/{departmentId}/metrics', [AnalyticsController::class, 'departmentMetrics']);
        Route::get('/users/{userId}/activity', [AnalyticsController::class, 'userActivity']);
        Route::get('/weekly', [AnalyticsController::class, 'weeklyAnalytics']);
        Route::get('/realtime', [AnalyticsController::class, 'realtime']);
        Route::get('/workflow-metrics', [AnalyticsController::class, 'workflowMetrics']);
        Route::get('/usage-patterns', [AnalyticsController::class, 'usagePatterns']);
        Route::get('/performance-comparison', [AnalyticsController::class, 'performanceComparison']);
        Route::get('/system-health', [AnalyticsController::class, 'systemHealth']);
        Route::get('/performance-alerts', [AnalyticsController::class, 'performanceAlerts']);
        Route::post('/export-report', [AnalyticsController::class, 'exportReport']);

        // Admin-only routes
        Route::middleware('role:super-admin')->group(function () {
            Route::post('/collect', [AnalyticsController::class, 'collectAnalytics']);
            Route::post('/cleanup', [AnalyticsController::class, 'cleanupOldData']);
        });
    });

    // File Management routes - Phase 5 File Management & Watermarking
    Route::prefix('file-management')->group(function () {
        // Enhanced file upload and processing
        Route::post('/invoices/{invoiceId}/upload', [FileManagementController::class, 'upload']);
        Route::get('/attachments/{attachmentId}/processing-status', [FileManagementController::class, 'getProcessingStatus']);
        Route::get('/invoices/{invoiceId}/attachments/{attachmentId}/download', [FileManagementController::class, 'download']);

        // Watermarking operations
        Route::post('/attachments/{attachmentId}/watermark', [FileManagementController::class, 'applyWatermark']);
        Route::delete('/attachments/{attachmentId}/watermark', [FileManagementController::class, 'removeWatermark']);
        Route::get('/attachments/{attachmentId}/watermark', [FileManagementController::class, 'getWatermarkDetails']);

        // Processing jobs management
        Route::get('/processing-jobs', [FileManagementController::class, 'getProcessingJobs']);
        Route::post('/processing-jobs/{jobId}/retry', [FileManagementController::class, 'retryProcessingJob']);

        // File statistics and management
        Route::get('/statistics', [FileManagementController::class, 'getFileStatistics']);
    });

    // User Preferences routes - Phase 6 User Experience Enhancements
    Route::prefix('preferences')->group(function () {
        Route::get('/', [UserPreferencesController::class, 'show']);
        Route::put('/', [UserPreferencesController::class, 'update']);
        Route::put('/theme', [UserPreferencesController::class, 'updateTheme']);
        Route::put('/dashboard-layout', [UserPreferencesController::class, 'updateDashboardLayout']);
        Route::put('/notifications', [UserPreferencesController::class, 'updateNotificationSettings']);
        Route::post('/reset', [UserPreferencesController::class, 'resetToDefaults']);
    });
});
