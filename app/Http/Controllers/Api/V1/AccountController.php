<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * List authenticated user's accounts
     *
     * GET /api/v1/accounts
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $accounts = $user->accounts()
            ->with(['accountType', 'accountCategory', 'accountStatus', 'users'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ], 200);
    }

    /**
     * Get authenticated account details
     *
     * GET /api/v1/account
     */
    public function show(Request $request): JsonResponse
    {
        $account = $request->get('account');

        $account->load(['accountType', 'accountCategory', 'accountStatus', 'wallets']);

        return response()->json([
            'success' => true,
            'data' => $account,
        ], 200);
    }

    /**
     * Update authenticated account details
     *
     * PUT /api/v1/account
     */
    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $account = $request->get('account');

        try {
            $account->update(array_filter($validated));

            return response()->json([
                'success' => true,
                'message' => 'Account updated successfully.',
                'data' => $account->fresh(['accountType', 'accountCategory', 'accountStatus']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update account.',
                'errors' => ['account' => [$e->getMessage()]],
            ], 422);
        }
    }
}
