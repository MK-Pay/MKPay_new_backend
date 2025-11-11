<?php

namespace App\Http\Middleware;

use App\Models\Tenant\App;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegrationApi
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get app_id and app_secret from headers
        $appId = $request->header('X-App-Id');
        $appSecret = $request->header('X-App-Secret');

        // Validate headers are present
        if (! $appId || ! $appSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication credentials are required.',
                'errors' => [
                    'authentication' => ['Missing X-App-Id or X-App-Secret headers.'],
                ],
            ], 401);
        }

        // Find app by app_id (UUID)
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application credentials.',
                'errors' => [
                    'authentication' => ['The provided App ID is invalid.'],
                ],
            ], 401);
        }

        // Check if app is active
        if (! $app->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Application is inactive.',
                'errors' => [
                    'authentication' => ['This application has been deactivated.'],
                ],
            ], 403);
        }

        // Find valid secret token
        $validToken = null;

        foreach ($app->secretTokens()->where('is_active', true)->get() as $token) {
            // Check if token is expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                continue;
            }

            // Verify token hash
            if (Hash::check($appSecret, $token->token_hash)) {
                $validToken = $token;

                break;
            }
        }

        if (! $validToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid application credentials.',
                'errors' => [
                    'authentication' => ['The provided App Secret is invalid or expired.'],
                ],
            ], 401);
        }

        // Update last_used_at for the token
        $validToken->update(['last_used_at' => now()]);

        // Attach app, token, and account to request
        $request->merge([
            'authenticated_app' => $app,
            'authenticated_token' => $validToken,
            'authenticated_account' => $app->account,
        ]);

        return $next($request);
    }
}
