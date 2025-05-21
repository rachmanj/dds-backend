<?php

use Illuminate\Support\Facades\Route;

// Catch-all route for SPA
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
