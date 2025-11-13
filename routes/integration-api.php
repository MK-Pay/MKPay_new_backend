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

Route::middleware(['auth.integration', 'init.tenant'])->group(function (): void {
    // Account Routes (Integration API - app authentication)
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');

    // Payment Routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{uuid}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{uuid}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('/payments/{uuid}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    // Transaction Routes
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{uuid}', [TransactionController::class, 'show'])->name('transactions.show');

    // Wallet Routes
    Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::get('/wallets/{uuid}', [WalletController::class, 'show'])->name('wallets.show');
    Route::get('/wallets/{uuid}/balance', [WalletController::class, 'balance'])->name('wallets.balance');
    Route::get('/wallets/{uuid}/statement', [WalletController::class, 'statement'])->name('wallets.statement');

    // Webhook Routes
    Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::get('/webhooks/{id}', [WebhookController::class, 'show'])->name('webhooks.show');
    Route::put('/webhooks/{id}', [WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{id}/test', [WebhookController::class, 'test'])->name('webhooks.test');
    Route::get('/webhooks/{id}/logs', [WebhookController::class, 'logs'])->name('webhooks.logs');
});
