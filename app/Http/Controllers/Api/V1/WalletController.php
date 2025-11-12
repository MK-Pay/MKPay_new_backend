<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * List wallets for the authenticated account
     *
     * GET /api/v1/wallets
     */
    public function index(Request $request): JsonResponse
    {
        $account = $request->get('account');

        $wallets = Wallet::where('account_id', $account->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wallets,
        ], 200);
    }

    /**
     * Get wallet details
     *
     * GET /api/v1/wallets/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $account = $request->get('account');

        $wallet = Wallet::where('uuid', $uuid)
            ->where('account_id', $account->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $wallet,
        ], 200);
    }

    /**
     * Get wallet balance
     *
     * GET /api/v1/wallets/{uuid}/balance
     */
    public function balance(Request $request, string $uuid): JsonResponse
    {
        $account = $request->get('account');

        $wallet = Wallet::where('uuid', $uuid)
            ->where('account_id', $account->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_uuid' => $wallet->uuid,
                'currency' => $wallet->currency,
                'available_balance' => $wallet->available_balance,
                'held_balance' => $wallet->held_balance,
                'total_balance' => $wallet->available_balance + $wallet->held_balance,
                'updated_at' => $wallet->updated_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Get wallet statement (balance history)
     *
     * GET /api/v1/wallets/{uuid}/statement
     */
    public function statement(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'type' => ['nullable', 'string', 'in:credit,debit,adjustment,hold,release_hold'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $account = $request->get('account');

        $wallet = Wallet::where('uuid', $uuid)
            ->where('account_id', $account->id)
            ->firstOrFail();

        $query = WalletBalance::where('wallet_id', $wallet->id)
            ->with('transaction.transactionStatus');

        if (isset($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $statement = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => [
                    'uuid' => $wallet->uuid,
                    'currency' => $wallet->currency,
                    'current_balance' => $wallet->available_balance,
                ],
                'statement' => $statement->items(),
            ],
            'meta' => [
                'current_page' => $statement->currentPage(),
                'last_page' => $statement->lastPage(),
                'per_page' => $statement->perPage(),
                'total' => $statement->total(),
            ],
        ], 200);
    }
}
