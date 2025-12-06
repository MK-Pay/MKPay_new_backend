<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AppController;
use App\Http\Controllers\Api\V1\AccountController;

Route::prefix('auth')->name('auth.')->group(__DIR__ . '/api-auth.php');

Route::middleware(['auth:sanctum'])->group(function (): void {
    // Account Routes (User authenticated)
    Route::match(['get', 'post'], '/accounts', [AccountController::class, 'index'])->name('accounts.index');

    // Routes requiring account context (X-Account-Uuid header)
    Route::middleware([
        'init.tenant',
        //
    ])->group(function (): void {
        Route::apiResource('apps', AppController::class)->parameters(['apps' => 'appId']);
        Route::post('/apps/{appId}/activate', [AppController::class, 'activate'])->name('apps.activate');
        Route::post('/apps/{appId}/deactivate', [AppController::class, 'deactivate'])->name('apps.deactivate');
        Route::get('/apps/{appId}/tokens', [AppController::class, 'tokens'])->name('apps.tokens.index');
        Route::post('/apps/{appId}/tokens', [AppController::class, 'createToken'])->name('apps.tokens.create');
        Route::put('/apps/{appId}/tokens/{tokenId}', [AppController::class, 'updateToken'])->name('apps.tokens.update');
        Route::delete('/apps/{appId}/tokens/{tokenId}', [AppController::class, 'revokeToken'])->name('apps.tokens.revoke');
    });
});
