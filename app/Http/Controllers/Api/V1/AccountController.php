<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
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
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $account = $request->get('account');

        if (isset($validated['email']) && $validated['email'] !== $account->email) {
            $exists = \App\Models\Account::where('email', $validated['email'])
                ->where('id', '!=', $account->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already in use.',
                    'errors' => ['email' => ['Email is already registered to another account.']],
                ], 422);
            }
        }

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
