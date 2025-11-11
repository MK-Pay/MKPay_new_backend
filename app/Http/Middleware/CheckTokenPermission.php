<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $permission Required permission for the route
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Get authenticated token from request (set by AuthenticateIntegrationApi middleware)
        $token = $request->input('authenticated_token');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'errors' => [
                    'authorization' => ['Authentication required.'],
                ],
            ], 401);
        }

        // Check if token has the required permission
        $tokenPermissions = $token->permissions ?? [];

        if (! in_array($permission, $tokenPermissions, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'errors' => [
                    'authorization' => ["This token does not have the '{$permission}' permission."],
                ],
            ], 403);
        }

        return $next($request);
    }
}
