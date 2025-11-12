<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * List transactions for the authenticated account
     *
     * GET /api/v1/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:payment_in,payment_out,transfer,hold,release,deposit,withdrawal'],
            'status' => ['nullable', 'string'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $account = $request->get('account');

        $query = Transaction::query()
            ->where(function ($q) use ($account): void {
                $q->whereHas('originWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                });
            })
            ->with(['transactionStatus', 'originWallet', 'destinationWallet', 'app']);

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['status'])) {
            $query->whereHas('transactionStatus', function ($q) use ($validated): void {
                $q->where('code', $validated['status']);
            });
        }

        if (isset($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $transactions = $query->latest()->paginate($perPage);

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
     * Get transaction details
     *
     * GET /api/v1/transactions/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $account = $request->get('account');

        $transaction = Transaction::where('uuid', $uuid)
            ->where(function ($q) use ($account): void {
                $q->whereHas('originWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                })->orWhereHas('destinationWallet', function ($wq) use ($account): void {
                    $wq->where('account_id', $account->id);
                });
            })
            ->with(['transactionStatus', 'originWallet', 'destinationWallet', 'app'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ], 200);
    }
}
