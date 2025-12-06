<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CurrentUserController extends Controller
{
    /**
     * Get current authenticated user information with optional related data.
     *
     * Query parameters:
     * - info: Comma-separated list of additional info to include (permissions, roles, accounts)
     *
     * Example: /api/v1/user/current?info=permissions,roles,accounts
     */
    public function __invoke(Request $request): JsonResponse
    {
        $allowedToReturn = [
            'permissions', // name
            'roles', // name
            'accounts', // name, tenant_id, email and uuid
        ];

        // Parse requested info from query parameter
        $requestedInfo = explode(',', $request->input('info', ''));
        $toReturn = array_filter(
            $allowedToReturn,
            fn ($item) => in_array($item, $requestedInfo)
        );

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Build cache key based on user ID and requested info
        $cacheKey = 'user_info:' . $user->id . ':' . implode('-', $toReturn);

        // Cache the response for 30 seconds
        $toResponse = Cache::remember($cacheKey, 30, function () use ($user, $toReturn) {
            $response = [
                'user' => $user->only(['name', 'email']),
            ];

            // Add permissions if requested
            if (in_array('permissions', $toReturn)) {
                // Spatie permissions are cached internally via getPermissionNames()
                $response['permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
            }

            // Add roles if requested
            if (in_array('roles', $toReturn)) {
                // Spatie roles are cached internally via getRoleNames()
                $response['roles'] = $user->getRoleNames()->toArray();
            }

            // Add accounts if requested
            if (in_array('accounts', $toReturn)) {
                $response['accounts'] = $user->accounts()
                    ->select(['accounts.id', 'accounts.name', 'accounts.tenant_id', 'accounts.email', 'accounts.uuid'])
                    ->whereNotNull('accounts.tenant_id')
                    ->get()
                    ->map(fn ($account) => [
                        'name' => $account->name,
                        'tenant_id' => $account->tenant_id,
                        'email' => $account->email,
                        'uuid' => $account->uuid,
                    ])
                    ->toArray();
            }

            // Static checks placeholder for future implementation
            $response['static_check'] = [];

            return $response;
        });

        return response()->json($toResponse);
    }
}
