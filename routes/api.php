<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// مسارات عامة (بدون توكن)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// مسارات محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});