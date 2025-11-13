<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\System\HealthCheckController;
use Illuminate\Support\Facades\Mail;
use App\Mail\ErrorReportMail;
use Illuminate\Support\Facades\Log;

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

            Route::prefix('dev')
                ->middleware('api')
                ->name('dev.')
                ->group(base_path('routes/dev.php'));

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
        // Send email notifications for critical errors
        $exceptions->report(function (Throwable $e): void {
            // Only send emails in production or staging environments
            if (! in_array(config('app.env'), ['production', 'staging'])) {
                return;
            }

            // Only send emails for critical errors (500+ level)
            $criticalExceptions = [
                Symfony\Component\HttpKernel\Exception\HttpException::class,
                Illuminate\Database\QueryException::class,
                RuntimeException::class,
                LogicException::class,
                Error::class,
            ];

            $shouldSendEmail = false;

            foreach ($criticalExceptions as $exceptionClass) {
                if ($e instanceof $exceptionClass) {
                    $shouldSendEmail = true;

                    break;
                }
            }

            // Also send for any uncaught exceptions in production
            if (! $shouldSendEmail && config('app.env') === 'production') {
                $shouldSendEmail = true;
            }

            if ($shouldSendEmail) {
                try {
                    $context = [
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'user_id' => auth()->id(),
                        'tenant' => tenant('id') ?? 'central',
                    ];

                    // Add request data for non-GET requests (exclude sensitive data)
                    if (! request()->isMethod('GET')) {
                        $requestData = request()->except(['password', 'password_confirmation', 'token', 'secret']);

                        if (! empty($requestData)) {
                            $context['request_data'] = $requestData;
                        }
                    }

                    $errorEmail = config('mail.error_reporting.to', config('mail.from.address'));

                    Mail::to($errorEmail)->send(new ErrorReportMail($e, $context));

                    Log::info('Error report email sent', [
                        'exception' => get_class($e),
                        'to' => $errorEmail,
                    ]);
                } catch (Throwable $mailException) {
                    // Log the failure to send email, but don't throw exception
                    Log::error('Failed to send error report email', [
                        'exception' => get_class($mailException),
                        'message' => $mailException->getMessage(),
                    ]);
                }
            }
        });
    })->create();
