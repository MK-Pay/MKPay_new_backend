<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AppController;
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
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Protected admin routes
Route::middleware(['auth:sanctum'])->group(function (): void {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

    // Account management routes
    Route::apiResource('accounts', AccountController::class)->parameters(['accounts' => 'uuid']);
    Route::post('/accounts/{uuid}/suspend', [AccountController::class, 'suspend'])->name('accounts.suspend');
    Route::post('/accounts/{uuid}/activate', [AccountController::class, 'activate'])->name('accounts.activate');
    Route::post('/accounts/{uuid}/verify', [AccountController::class, 'verify'])->name('accounts.verify');
    Route::get('/accounts/{uuid}/activity', [AccountController::class, 'activity'])->name('accounts.activity');

    // App management routes
    Route::get('/accounts/{accountUuid}/apps', [AppController::class, 'byAccount'])->name('accounts.apps');
    Route::apiResource('apps', AppController::class)->parameters(['apps' => 'appId']);
    Route::post('/apps/{appId}/activate', [AppController::class, 'activate'])->name('apps.activate');
    Route::post('/apps/{appId}/deactivate', [AppController::class, 'deactivate'])->name('apps.deactivate');
    Route::get('/apps/{appId}/tokens', [AppController::class, 'tokens'])->name('apps.tokens.index');
    Route::post('/apps/{appId}/tokens', [AppController::class, 'createToken'])->name('apps.tokens.create');
    Route::put('/apps/{appId}/tokens/{tokenId}', [AppController::class, 'updateToken'])->name('apps.tokens.update');
    Route::delete('/apps/{appId}/tokens/{tokenId}', [AppController::class, 'revokeToken'])->name('apps.tokens.revoke');

    // Wallet management routes (to be implemented)
    // Route::apiResource('wallets', WalletController::class)->parameters(['wallets' => 'uuid']);

    // Transaction management routes (to be implemented)
    // Route::apiResource('transactions', TransactionController::class)->parameters(['transactions' => 'uuid']);
});
