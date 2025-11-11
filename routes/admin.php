<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Admin API. These routes require Sanctum
| authentication and appropriate role/permission checks.
|
*/

// Authentication routes (no auth required)
Route::post('/login', [AuthController::class, 'login'])->name('admin.auth.login');

// Protected admin routes
Route::middleware(['auth:sanctum'])->group(function (): void {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.auth.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('admin.auth.user');

    // Account management routes
    // Route::apiResource('accounts', AccountController::class)->names('admin.accounts');

    // App management routes
    // Route::apiResource('apps', AppController::class)->names('admin.apps');

    // Wallet management routes
    // Route::apiResource('wallets', WalletController::class)->names('admin.wallets');

    // Transaction management routes
    // Route::apiResource('transactions', TransactionController::class)->names('admin.transactions');
});
