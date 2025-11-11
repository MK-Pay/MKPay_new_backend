<?php

namespace App\Services;

use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * Create a new transaction.
     *
     * @param  array  $data  Transaction data
     * @return Transaction
     *
     * @throws \Exception
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Get pending status
            $pendingStatus = TransactionStatus::where('code', 'pending')->first();

            // Create transaction
            $transaction = Transaction::create([
                'uuid' => Str::uuid(),
                'transaction_status_id' => $pendingStatus->id,
                'type' => $data['type'],
                'origin_wallet_id' => $data['origin_wallet_id'] ?? null,
                'destination_wallet_id' => $data['destination_wallet_id'] ?? null,
                'amount' => $data['amount'],
                'fee' => $data['fee'] ?? 0,
                'net_amount' => $data['amount'] - ($data['fee'] ?? 0),
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'reference' => $data['reference'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
            ]);

            // If type is 'hold', hold the amount in origin wallet
            if ($data['type'] === 'hold' && isset($data['origin_wallet_id'])) {
                $originWallet = Wallet::find($data['origin_wallet_id']);
                $this->walletService->hold(
                    $originWallet,
                    $data['amount'],
                    $data['description'] ?? 'Transaction hold'
                );
            }

            return $transaction;
        });
    }

    /**
     * Update transaction status.
     *
     * @param  Transaction  $transaction  The transaction
     * @param  string  $statusCode  New status code
     * @param  string|null  $reason  Reason for status change
     * @return Transaction
     *
     * @throws \Exception
     */
    public function updateStatus(Transaction $transaction, string $statusCode, ?string $reason = null): Transaction
    {
        // Check if transaction is already final
        if ($transaction->is_final) {
            throw new \Exception('Cannot update status of a final transaction.');
        }

        return DB::transaction(function () use ($transaction, $statusCode, $reason) {
            // Get new status
            $newStatus = TransactionStatus::where('code', $statusCode)->first();

            if (! $newStatus) {
                throw new \Exception("Invalid status code: {$statusCode}");
            }

            // Update transaction status
            $transaction->update([
                'transaction_status_id' => $newStatus->id,
                'is_final' => $newStatus->is_final,
            ]);

            // Process based on new status
            switch ($statusCode) {
                case 'completed':
                    $this->processCompletion($transaction);

                    break;

                case 'failed':
                case 'cancelled':
                    $this->processFailureOrCancellation($transaction, $reason);

                    break;
            }

            // Refresh transaction
            $transaction->refresh();

            return $transaction;
        });
    }

    /**
     * Process transaction completion.
     * Executes wallet operations based on transaction type.
     *
     * @param  Transaction  $transaction  The transaction
     *
     * @throws \Exception
     */
    protected function processCompletion(Transaction $transaction): void
    {
        switch ($transaction->type) {
            case 'payment_in':
            case 'deposit':
                // Credit destination wallet
                if ($transaction->destination_wallet_id) {
                    $wallet = Wallet::find($transaction->destination_wallet_id);
                    $this->walletService->credit(
                        $wallet,
                        $transaction->net_amount,
                        $transaction->description ?? 'Transaction credit',
                        $transaction
                    );
                }

                break;

            case 'payment_out':
            case 'withdrawal':
                // Debit origin wallet
                if ($transaction->origin_wallet_id) {
                    $wallet = Wallet::find($transaction->origin_wallet_id);
                    $this->walletService->debit(
                        $wallet,
                        $transaction->amount,
                        $transaction->description ?? 'Transaction debit',
                        $transaction
                    );
                }

                break;

            case 'transfer':
                // Transfer between wallets
                if ($transaction->origin_wallet_id && $transaction->destination_wallet_id) {
                    $originWallet = Wallet::find($transaction->origin_wallet_id);
                    $destinationWallet = Wallet::find($transaction->destination_wallet_id);
                    $this->walletService->transfer(
                        $originWallet,
                        $destinationWallet,
                        $transaction->amount,
                        $transaction->description ?? 'Transfer',
                        $transaction
                    );
                }

                break;

            case 'hold':
                // Hold is already processed during creation
                break;

            case 'release':
                // Release held amount
                if ($transaction->origin_wallet_id) {
                    $wallet = Wallet::find($transaction->origin_wallet_id);
                    $this->walletService->releaseHold(
                        $wallet,
                        $transaction->amount,
                        $transaction->description ?? 'Release hold'
                    );
                }

                break;
        }
    }

    /**
     * Process transaction failure or cancellation.
     * Reverses any holds or operations.
     *
     * @param  Transaction  $transaction  The transaction
     * @param  string|null  $reason  Reason for failure/cancellation
     *
     * @throws \Exception
     */
    protected function processFailureOrCancellation(Transaction $transaction, ?string $reason = null): void
    {
        // If transaction had a hold, release it
        if ($transaction->type === 'hold' && $transaction->origin_wallet_id) {
            $wallet = Wallet::find($transaction->origin_wallet_id);
            $this->walletService->releaseHold(
                $wallet,
                $transaction->amount,
                $reason ?? 'Transaction failed/cancelled'
            );
        }
    }

    /**
     * Refund a transaction.
     * Creates a reverse transaction and processes it.
     *
     * @param  Transaction  $transaction  Original transaction
     * @param  float|null  $amount  Refund amount (null for full refund)
     * @param  string|null  $reason  Refund reason
     * @return Transaction The refund transaction
     *
     * @throws \Exception
     */
    public function refund(Transaction $transaction, ?float $amount = null, ?string $reason = null): Transaction
    {
        // Validate transaction can be refunded
        if (! $transaction->is_final) {
            throw new \Exception('Can only refund completed transactions.');
        }

        if ($transaction->transactionStatus->code !== 'completed') {
            throw new \Exception('Transaction must be completed to refund.');
        }

        $refundAmount = $amount ?? $transaction->net_amount;

        if ($refundAmount <= 0 || $refundAmount > $transaction->net_amount) {
            throw new \Exception('Invalid refund amount.');
        }

        return DB::transaction(function () use ($transaction, $refundAmount, $reason) {
            // Create refund transaction (reverse operation)
            $refundData = [
                'type' => $this->getReverseType($transaction->type),
                'origin_wallet_id' => $transaction->destination_wallet_id,
                'destination_wallet_id' => $transaction->origin_wallet_id,
                'amount' => $refundAmount,
                'fee' => 0,
                'description' => 'Refund: ' . ($reason ?? $transaction->description),
                'reference' => $transaction->uuid,
                'metadata' => [
                    'refund_of' => $transaction->uuid,
                    'refund_reason' => $reason,
                    'original_amount' => $transaction->net_amount,
                ],
            ];

            $refundTransaction = $this->create($refundData);

            // Immediately complete the refund
            $this->updateStatus($refundTransaction, 'completed', 'Refund processed');

            // Link original transaction to refund
            $transaction->update([
                'refunded_at' => now(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_transaction' => $refundTransaction->uuid,
                    'refund_amount' => $refundAmount,
                ]),
            ]);

            return $refundTransaction;
        });
    }

    /**
     * Cancel a pending transaction.
     *
     * @param  Transaction  $transaction  The transaction
     * @param  string  $reason  Cancellation reason
     * @return Transaction
     *
     * @throws \Exception
     */
    public function cancel(Transaction $transaction, string $reason): Transaction
    {
        if ($transaction->is_final) {
            throw new \Exception('Cannot cancel a final transaction.');
        }

        return $this->updateStatus($transaction, 'cancelled', $reason);
    }

    /**
     * Get reverse transaction type for refunds.
     *
     * @param  string  $type  Original type
     * @return string
     */
    protected function getReverseType(string $type): string
    {
        return match ($type) {
            'payment_in', 'deposit' => 'payment_out',
            'payment_out', 'withdrawal' => 'payment_in',
            'transfer' => 'transfer',
            default => 'payment_out',
        };
    }

    /**
     * Get transaction statistics for an account or wallet.
     *
     * @param  array  $filters  Filters (account_id, wallet_id, date_from, date_to)
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Transaction::query();

        if (isset($filters['account_id'])) {
            $query->whereHas('originWallet', function ($q) use ($filters): void {
                $q->where('account_id', $filters['account_id']);
            });
        }

        if (isset($filters['wallet_id'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('origin_wallet_id', $filters['wallet_id'])
                    ->orWhere('destination_wallet_id', $filters['wallet_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_fees' => $query->sum('fee'),
            'by_status' => $query->select('transaction_status_id')
                ->selectRaw('count(*) as count')
                ->selectRaw('sum(amount) as total')
                ->groupBy('transaction_status_id')
                ->get()
                ->map(fn ($item) => [
                    'status' => $item->transactionStatus->code,
                    'count' => $item->count,
                    'total' => $item->total,
                ]),
            'by_type' => $query->select('type')
                ->selectRaw('count(*) as count')
                ->selectRaw('sum(amount) as total')
                ->groupBy('type')
                ->get(),
        ];
    }
}
