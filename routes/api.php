<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // مسارات عامة (بدون توكن)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // مسارات محمية (تحتاج توكن)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // المجموعات
        Route::get('/groups', [GroupController::class, 'index']);
        Route::post('/groups', [GroupController::class, 'store']);
        Route::get('/groups/{group}', [GroupController::class, 'show']);
        Route::put('/groups/{group}', [GroupController::class, 'update']);
        Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

        // أعضاء المجموعة
        Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
        Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember']);

        // المصاريف
        Route::get('/groups/{group}/expenses', [ExpenseController::class, 'index']);
        Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store']);
        Route::get('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'show']);
        Route::put('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'destroy']);

        // الأرصدة
        Route::get('/groups/{group}/balances', [BalanceController::class, 'index']);
        Route::get('/groups/{group}/simplify', [BalanceController::class, 'simplify']);

        // التسويات
        Route::get('/groups/{group}/settlements', [SettlementController::class, 'index']);
        Route::post('/groups/{group}/settlements', [SettlementController::class, 'store']);
        Route::delete('/groups/{group}/settlements/{settlement}', [SettlementController::class, 'destroy']);
    });

});