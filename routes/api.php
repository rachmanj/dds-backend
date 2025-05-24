<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AdditionalDocumentTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes 
Route::post('/login', [AuthController::class, 'login']);
Route::post('/token-login', [AuthController::class, 'tokenLogin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Resources
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('addoc-types', AdditionalDocumentTypeController::class);
    Route::apiResource('invoice-types', InvoiceTypeController::class);

    Route::get('/user', [AuthController::class, 'me']);

    // Authenticated user routes
    Route::get('/auth-user/roles', [UserRoleController::class, 'getAuthUserRoles']);
    Route::get('/auth-user/permissions', [UserRoleController::class, 'getAuthUserPermissions']);
});
