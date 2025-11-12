<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Integration API Routes (v1)
|--------------------------------------------------------------------------
|
| These routes require X-App-Id and X-App-Secret headers for authentication.
| All routes are protected by the 'auth.integration' middleware.
|
*/

Route::middleware(['auth.integration'])->group(function (): void {
    // Account Routes
    Route::get('/account', [AccountController::class, 'show'])->name('api.v1.account.show');
    Route::put('/account', [AccountController::class, 'update'])->name('api.v1.account.update');

    // Payment Routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('api.v1.payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('api.v1.payments.store');
    Route::get('/payments/{uuid}', [PaymentController::class, 'show'])->name('api.v1.payments.show');
    Route::post('/payments/{uuid}/cancel', [PaymentController::class, 'cancel'])->name('api.v1.payments.cancel');
    Route::post('/payments/{uuid}/refund', [PaymentController::class, 'refund'])->name('api.v1.payments.refund');

    // Transaction Routes
    Route::get('/transactions', [TransactionController::class, 'index'])->name('api.v1.transactions.index');
    Route::get('/transactions/{uuid}', [TransactionController::class, 'show'])->name('api.v1.transactions.show');

    // Wallet Routes
    Route::get('/wallets', [WalletController::class, 'index'])->name('api.v1.wallets.index');
    Route::get('/wallets/{uuid}', [WalletController::class, 'show'])->name('api.v1.wallets.show');
    Route::get('/wallets/{uuid}/balance', [WalletController::class, 'balance'])->name('api.v1.wallets.balance');
    Route::get('/wallets/{uuid}/statement', [WalletController::class, 'statement'])->name('api.v1.wallets.statement');

    // Webhook Routes
    Route::get('/webhooks', [WebhookController::class, 'index'])->name('api.v1.webhooks.index');
    Route::post('/webhooks', [WebhookController::class, 'store'])->name('api.v1.webhooks.store');
    Route::get('/webhooks/{id}', [WebhookController::class, 'show'])->name('api.v1.webhooks.show');
    Route::put('/webhooks/{id}', [WebhookController::class, 'update'])->name('api.v1.webhooks.update');
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy'])->name('api.v1.webhooks.destroy');
    Route::post('/webhooks/{id}/test', [WebhookController::class, 'test'])->name('api.v1.webhooks.test');
    Route::get('/webhooks/{id}/logs', [WebhookController::class, 'logs'])->name('api.v1.webhooks.logs');
});
