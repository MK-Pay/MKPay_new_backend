<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Apps\CreateTokenRequest;
use App\Http\Requests\Admin\Apps\StoreAppRequest;
use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\AppSecretToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppController extends Controller
{
    /**
     * Display a listing of apps.
     */
    public function index(Request $request): JsonResponse
    {
        $query = App::query()->with(['account']);

        // Filter by account
        if ($request->has('account_uuid')) {
            $query->whereHas('account', function ($q) use ($request): void {
                $q->where('uuid', $request->account_uuid);
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $apps = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $apps->items(),
            'meta' => [
                'current_page' => $apps->currentPage(),
                'last_page' => $apps->lastPage(),
                'per_page' => $apps->perPage(),
                'total' => $apps->total(),
            ],
        ], 200);
    }

    /**
     * List apps by account UUID.
     */
    public function byAccount(string $accountUuid): JsonResponse
    {
        $account = Account::where('uuid', $accountUuid)->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account' => ['The specified account does not exist.']],
            ], 404);
        }

        $apps = App::where('account_id', $account->id)
            ->with(['secretTokens'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $apps,
        ], 200);
    }

    /**
     * Store a newly created app.
     */
    public function store(StoreAppRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find account
        $account = Account::where('uuid', $validated['account_uuid'])->first();

        // Create app with UUID
        $app = App::create([
            'app_id' => (string) Str::uuid(),
            'account_id' => $account->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'settings' => $validated['settings'] ?? null,
            'is_active' => true,
        ]);

        $app->load(['account']);

        return response()->json([
            'success' => true,
            'message' => 'App created successfully.',
            'data' => $app,
        ], 201);
    }

    /**
     * Display the specified app.
     */
    public function show(string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)
            ->with(['account', 'secretTokens'])
            ->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $app,
        ], 200);
    }

    /**
     * Update the specified app.
     */
    public function update(Request $request, string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $app->update($validated);
        $app->load(['account', 'secretTokens']);

        return response()->json([
            'success' => true,
            'message' => 'App updated successfully.',
            'data' => $app,
        ], 200);
    }

    /**
     * Remove the specified app (soft delete).
     */
    public function destroy(string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $app->delete();

        return response()->json([
            'success' => true,
            'message' => 'App deleted successfully.',
        ], 200);
    }

    /**
     * Activate an app.
     */
    public function activate(string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $app->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'App activated successfully.',
            'data' => $app,
        ], 200);
    }

    /**
     * Deactivate an app.
     */
    public function deactivate(Request $request, string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $app->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'App deactivated successfully.',
            'data' => $app,
        ], 200);
    }

    /**
     * List all tokens for an app.
     */
    public function tokens(string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $tokens = AppSecretToken::where('app_id', $app->id)->get();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ], 200);
    }

    /**
     * Create a new app secret token.
     */
    public function createToken(CreateTokenRequest $request, string $appId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $validated = $request->validated();

        // Generate a random secure token
        $plainToken = Str::random(64);

        // Create token with hashed value
        $token = AppSecretToken::create([
            'app_id' => $app->id,
            'name' => $validated['name'],
            'token_hash' => Hash::make($plainToken),
            'permissions' => $validated['permissions'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token created successfully. Save this token securely, it will not be shown again.',
            'data' => [
                'token_id' => $token->id,
                'name' => $token->name,
                'token' => $plainToken, // Only shown once
                'permissions' => $token->permissions,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
            ],
        ], 201);
    }

    /**
     * Update an app secret token.
     */
    public function updateToken(Request $request, string $appId, int $tokenId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $token = AppSecretToken::where('id', $tokenId)
            ->where('app_id', $app->id)
            ->first();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found.',
                'errors' => ['token' => ['The specified token does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $token->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Token updated successfully.',
            'data' => $token,
        ], 200);
    }

    /**
     * Revoke (delete) an app secret token.
     */
    public function revokeToken(string $appId, int $tokenId): JsonResponse
    {
        $app = App::where('app_id', $appId)->first();

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'App not found.',
                'errors' => ['app' => ['The specified app does not exist.']],
            ], 404);
        }

        $token = AppSecretToken::where('id', $tokenId)
            ->where('app_id', $app->id)
            ->first();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found.',
                'errors' => ['token' => ['The specified token does not exist.']],
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully.',
        ], 200);
    }
}
