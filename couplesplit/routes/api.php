<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/dashboard/settle', [DashboardController::class, 'settle']);

    // Despesas
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);

    // Pagamentos
    Route::post('/payments', [PaymentController::class, 'store']);
});
