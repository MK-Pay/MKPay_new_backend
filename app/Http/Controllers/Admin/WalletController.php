<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Account;
use App\Models\Tenant\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * Display a listing of wallets.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Wallet::query()->with(['account', 'currency']);

        // Filter by account
        if ($request->has('account_uuid')) {
            $query->whereHas('account', function ($q) use ($request): void {
                $q->where('uuid', $request->account_uuid);
            });
        }

        // Filter by currency
        if ($request->has('currency')) {
            $query->whereHas('currency', function ($q) use ($request): void {
                $q->where('code', $request->currency);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($accountQuery) use ($search): void {
                        $accountQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 15);
        $wallets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $wallets->items(),
            'meta' => [
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
                'per_page' => $wallets->perPage(),
                'total' => $wallets->total(),
            ],
        ], 200);
    }

    /**
     * List wallets by account UUID.
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

        $wallets = Wallet::where('account_id', $account->id)
            ->with(['currency'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wallets,
        ], 200);
    }

    /**
     * Display the specified wallet.
     */
    public function show(string $uuid): JsonResponse
    {
        $wallet = Wallet::where('uuid', $uuid)
            ->with(['account', 'currency', 'app'])
            ->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
                'errors' => ['wallet' => ['The specified wallet does not exist.']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $wallet,
        ], 200);
    }

    /**
     * Get wallet balance history.
     */
    public function balances(Request $request, string $uuid): JsonResponse
    {
        $wallet = Wallet::where('uuid', $uuid)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
                'errors' => ['wallet' => ['The specified wallet does not exist.']],
            ], 404);
        }

        $filters = [
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = $request->input('per_page', 20);
        $balances = $this->walletService->getBalanceHistory($wallet, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $balances->items(),
            'meta' => [
                'current_page' => $balances->currentPage(),
                'last_page' => $balances->lastPage(),
                'per_page' => $balances->perPage(),
                'total' => $balances->total(),
            ],
        ], 200);
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(Request $request, string $uuid): JsonResponse
    {
        $wallet = Wallet::where('uuid', $uuid)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
                'errors' => ['wallet' => ['The specified wallet does not exist.']],
            ], 404);
        }

        $query = $wallet->transactions()
            ->with(['transactionStatus', 'originWallet', 'destinationWallet']);

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Type filter
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->has('status')) {
            $query->whereHas('transactionStatus', function ($q) use ($request): void {
                $q->where('code', $request->status);
            });
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    /**
     * Manually adjust wallet balance (admin operation).
     */
    public function adjust(Request $request, string $uuid): JsonResponse
    {
        $wallet = Wallet::where('uuid', $uuid)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
                'errors' => ['wallet' => ['The specified wallet does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'min:10'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $balanceRecord = $this->walletService->adjust(
                $wallet,
                $validated['amount'],
                $validated['reason']
            );

            // Refresh wallet to get updated balances
            $wallet->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Wallet balance adjusted successfully.',
                'data' => [
                    'wallet' => $wallet,
                    'balance_record' => $balanceRecord,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust wallet balance.',
                'errors' => ['adjustment' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Store a newly created wallet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_uuid' => ['required', 'string'],
            'currency_code' => ['required', 'string'],
            'app_id' => ['nullable', 'string'],
        ]);

        $account = Account::where('uuid', $validated['account_uuid'])->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
                'errors' => ['account_uuid' => ['The specified account does not exist.']],
            ], 422);
        }

        // Get currency
        $currency = \App\Models\Tenant\Currency::where('code', $validated['currency_code'])->first();

        if (! $currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found.',
                'errors' => ['currency_code' => ['The specified currency does not exist.']],
            ], 422);
        }

        // Check if wallet already exists for this account + currency
        $existingWallet = Wallet::where('account_id', $account->id)
            ->where('currency_id', $currency->id)
            ->first();

        if ($existingWallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet already exists for this account and currency.',
                'errors' => ['wallet' => ['A wallet with this currency already exists for the account.']],
            ], 422);
        }

        // Get app if provided
        $app = null;

        if (! empty($validated['app_id'])) {
            $app = \App\Models\Tenant\App::where('app_id', $validated['app_id'])->first();
        }

        $wallet = Wallet::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'account_id' => $account->id,
            'app_id' => $app?->id,
            'currency_id' => $currency->id,
            'available_balance' => 0,
            'held_balance' => 0,
        ]);

        $wallet->load(['account', 'currency', 'app']);

        return response()->json([
            'success' => true,
            'message' => 'Wallet created successfully.',
            'data' => $wallet,
        ], 201);
    }

    /**
     * Remove the specified wallet (soft delete).
     */
    public function destroy(string $uuid): JsonResponse
    {
        $wallet = Wallet::where('uuid', $uuid)->first();

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found.',
                'errors' => ['wallet' => ['The specified wallet does not exist.']],
            ], 404);
        }

        // Check if wallet has balance
        if ($wallet->available_balance > 0 || $wallet->held_balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete wallet with non-zero balance.',
                'errors' => ['wallet' => ['Wallet must have zero balance before deletion.']],
            ], 422);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully.',
        ], 200);
    }
}
