<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API Rate Limit (60 requests per minute)
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Admin API Rate Limit (120 requests per minute)
        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers)));

        // Integration API Rate Limit (60 requests per minute per token)
        RateLimiter::for('integration', function (Request $request) {
            // Rate limit by app_id and token
            $app = $request->attributes->get('app');
            $token = $request->attributes->get('token');

            $identifier = $app?->app_id ?? $request->ip();

            if ($token) {
                $identifier .= ':' . $token->id;
            }

            return Limit::perMinute(60)
                ->by($identifier)
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers));
        });

        // High-rate endpoints (webhooks, callbacks) - 300 requests per minute
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));

        // Auth endpoints (login, register) - 5 attempts per minute
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in ' . ($headers['Retry-After'] ?? 60) . ' seconds.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers)));

        // Payment creation - 30 requests per minute
        RateLimiter::for('payments', function (Request $request) {
            $app = $request->attributes->get('app');
            $identifier = $app?->app_id ?? $request->ip();

            return Limit::perMinute(30)
                ->by($identifier)
                ->response(fn (Request $request, array $headers) => response()->json([
                    'success' => false,
                    'message' => 'Payment rate limit exceeded. Please try again later.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers));
        });

        // Global rate limit per IP (1000 requests per minute)
        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(1000)->by($request->ip()));
    }
}
