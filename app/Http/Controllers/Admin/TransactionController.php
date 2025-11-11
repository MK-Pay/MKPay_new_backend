<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()
            ->with(['transactionStatus', 'originWallet', 'destinationWallet']);

        // Filter by status
        if ($request->has('status')) {
            $query->whereHas('transactionStatus', function ($q) use ($request): void {
                $q->where('code', $request->status);
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by account (via wallet)
        if ($request->has('account_uuid')) {
            $query->where(function ($q) use ($request): void {
                $q->whereHas('originWallet.account', function ($accountQuery) use ($request): void {
                    $accountQuery->where('uuid', $request->account_uuid);
                })->orWhereHas('destinationWallet.account', function ($accountQuery) use ($request): void {
                    $accountQuery->where('uuid', $request->account_uuid);
                });
            });
        }

        // Filter by wallet
        if ($request->has('wallet_uuid')) {
            $query->where(function ($q) use ($request): void {
                $q->whereHas('originWallet', function ($walletQuery) use ($request): void {
                    $walletQuery->where('uuid', $request->wallet_uuid);
                })->orWhereHas('destinationWallet', function ($walletQuery) use ($request): void {
                    $walletQuery->where('uuid', $request->wallet_uuid);
                });
            });
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by UUID or reference
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
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
     * Store a newly created transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:payment_in,payment_out,deposit,withdrawal,transfer,hold,release'],
            'origin_wallet_uuid' => ['nullable', 'string'],
            'destination_wallet_uuid' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Get wallet IDs from UUIDs
        $originWalletId = null;

        if (! empty($validated['origin_wallet_uuid'])) {
            $originWallet = \App\Models\Tenant\Wallet::where('uuid', $validated['origin_wallet_uuid'])->first();

            if (! $originWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Origin wallet not found.',
                    'errors' => ['origin_wallet_uuid' => ['The specified origin wallet does not exist.']],
                ], 422);
            }
            $originWalletId = $originWallet->id;
        }

        $destinationWalletId = null;

        if (! empty($validated['destination_wallet_uuid'])) {
            $destinationWallet = \App\Models\Tenant\Wallet::where('uuid', $validated['destination_wallet_uuid'])->first();

            if (! $destinationWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Destination wallet not found.',
                    'errors' => ['destination_wallet_uuid' => ['The specified destination wallet does not exist.']],
                ], 422);
            }
            $destinationWalletId = $destinationWallet->id;
        }

        try {
            $transaction = $this->transactionService->create([
                'type' => $validated['type'],
                'origin_wallet_id' => $originWalletId,
                'destination_wallet_id' => $destinationWalletId,
                'amount' => $validated['amount'],
                'fee' => $validated['fee'] ?? 0,
                'description' => $validated['description'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'external_reference' => $validated['external_reference'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
            ]);

            $transaction->load(['transactionStatus', 'originWallet', 'destinationWallet']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully.',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction.',
                'errors' => ['transaction' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)
            ->with(['transactionStatus', 'originWallet.account', 'destinationWallet.account'])
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 200);
    }

    /**
     * Update the specified transaction status.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,completed,failed,cancelled'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $transaction = $this->transactionService->updateStatus(
                $transaction,
                $validated['status'],
                $validated['reason'] ?? null
            );

            $transaction->load(['transactionStatus', 'originWallet', 'destinationWallet']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated successfully.',
                'data' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction status.',
                'errors' => ['status' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        if ($transaction->is_final) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete final transaction.',
                'errors' => ['transaction' => ['Final transactions cannot be deleted.']],
            ], 422);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ], 200);
    }

    /**
     * Approve a transaction.
     */
    public function approve(string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        try {
            $transaction = $this->transactionService->updateStatus($transaction, 'completed', 'Approved by admin');
            $transaction->load(['transactionStatus', 'originWallet', 'destinationWallet']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully.',
                'data' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction.',
                'errors' => ['approval' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Reject a transaction.
     */
    public function reject(Request $request, string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        try {
            $transaction = $this->transactionService->updateStatus($transaction, 'failed', $validated['reason']);
            $transaction->load(['transactionStatus', 'originWallet', 'destinationWallet']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejected successfully.',
                'data' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction.',
                'errors' => ['rejection' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * Refund a transaction.
     */
    public function refund(Request $request, string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
                'errors' => ['transaction' => ['The specified transaction does not exist.']],
            ], 404);
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:10'],
        ]);

        try {
            $refundTransaction = $this->transactionService->refund(
                $transaction,
                $validated['amount'] ?? null,
                $validated['reason']
            );

            $refundTransaction->load(['transactionStatus', 'originWallet', 'destinationWallet']);

            return response()->json([
                'success' => true,
                'message' => 'Transaction refunded successfully.',
                'data' => [
                    'original_transaction' => $transaction->fresh(),
                    'refund_transaction' => $refundTransaction,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refund transaction.',
                'errors' => ['refund' => [$e->getMessage()]],
            ], 422);
        }
    }
}
