<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // User info
    Route::get('/user', [AuthController::class, 'me']);

    // Resources
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('departments', DepartmentController::class);
});
