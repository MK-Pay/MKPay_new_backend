<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Facades\Tenancy;

class InitializeTenantFromAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get account UUID from header (sent by frontend)
        $accountUuid = $request->header('X-Account-Uuid');
        $accountId = $request->header('X-Account-Id');

        // Try to find account by UUID first, then by ID
        $account = null;

        if ($accountUuid) {
            $account = Account::where('uuid', $accountUuid)->first();
        } elseif ($accountId) {
            $account = Account::find($accountId);
        }

        // If no account found via headers, check if request has authenticated_account (from AuthenticateIntegrationApi middleware)
        if (! $account && $request->has('authenticated_account')) {
            $account = $request->get('authenticated_account');
        }

        // If we found an account with tenant_id, initialize tenancy
        if ($account && $account->tenant_id) {
            try {
                Tenancy::initialize(
                    \App\Models\Tenant::find($account->tenant_id)
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize tenant context.',
                    'errors' => [
                        'tenant' => ['The specified tenant could not be initialized.'],
                    ],
                ], 500);
            }
        }

        // Attach account to request for use in controllers
        if ($account) {
            $request->merge([
                'account' => $account,
            ]);
        }

        return $next($request);
    }
}
