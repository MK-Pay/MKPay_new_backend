<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: null,
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::prefix('admin')
                ->middleware('api')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::prefix('api')
                ->middleware('api')
                ->name('api.')
                ->group(function () {
                    Route::prefix('admin')
                        ->name('admin.')
                        ->group(base_path('routes/admin.php'));

                    Route::middleware('api')->group(base_path('routes/api.php'));

                    Route::prefix('v1')->name('v1.')
                        ->group(function () {
                            Route::middleware('api')->group(base_path('routes/integration-api.php'));
                            Route::middleware('api')->group(base_path('routes/api-routes/v1.php'));
                        });
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
