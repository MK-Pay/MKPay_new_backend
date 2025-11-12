<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\System\HealthCheckController;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/health',
        then: function (): void {
            HealthCheckController::routes();

            // Admin API Routes - /admin/*
            Route::prefix('admin')
                ->middleware('api')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Integration API v1 Routes - /api/v1/*
            Route::prefix('api/v1')
                ->middleware('api')
                ->name('api.v1.')
                ->group(function (): void {
                    // Integration API routes (payments, transactions, wallets, webhooks)
                    require base_path('routes/integration-api.php');

                    // API auth routes (if needed)
                    require base_path('routes/api-routes/v1.php');
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => App\Http\Middleware\EnsureEmailIsVerified::class,
            'auth.integration' => App\Http\Middleware\AuthenticateIntegrationApi::class,
            'permission' => App\Http\Middleware\CheckTokenPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
