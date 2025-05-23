<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AdditionalDocumentTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/token-login', [AuthController::class, 'tokenLogin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // User info
    Route::get('/user', [AuthController::class, 'me']);

    // Resources
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('addoc-types', AdditionalDocumentTypeController::class);
    Route::apiResource('invoice-types', InvoiceTypeController::class);
});
