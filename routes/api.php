<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AdditionalDocumentTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AdditionalDocumentController;
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

    // Resources
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('addoc-types', AdditionalDocumentTypeController::class);
    Route::apiResource('invoice-types', InvoiceTypeController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('additional-documents', AdditionalDocumentController::class);

    // Authenticated user routes
    Route::get('/auth-user/roles', [UserRoleController::class, 'getAuthUserRoles']);
    Route::get('/auth-user/permissions', [UserRoleController::class, 'getAuthUserPermissions']);
});
