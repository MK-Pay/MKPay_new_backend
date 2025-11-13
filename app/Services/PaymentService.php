<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\TransactionType;
use App\Models\Tenant\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected TransactionService $transactionService,
        protected WalletService $walletService
    ) {
    }

    /**
     * Create a payment transaction
     */
    public function createPayment(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Determine transaction type based on payment method
            $transactionType = $this->getTransactionTypeForPayment($data['payment_method']);

            // Get or create wallet
            $wallet = $this->getOrCreateWallet($data['account_uuid'], $data['currency_id']);

            // Prepare transaction data
            $transactionData = [
                'account_uuid' => $data['account_uuid'],
                'app_id' => $data['app_id'] ?? null,
                'transaction_type_id' => $transactionType->id,
                'wallet_to_uuid' => $wallet->uuid,
                'amount' => $data['amount'],
                'fee' => $data['fee'] ?? 0,
                'currency_id' => $data['currency_id'],
                'payment_method' => $data['payment_method'],
                'external_id' => $data['external_id'] ?? null,
                'description' => $data['description'] ?? 'Payment',
                'metadata' => $data['metadata'] ?? null,
            ];

            // Create transaction
            $transaction = $this->transactionService->create($transactionData);

            // Log payment creation
            Log::channel('financial')->info('Payment created', [
                'transaction_uuid' => $transaction->uuid,
                'amount' => $transaction->amount,
                'payment_method' => $transaction->payment_method,
            ]);

            return $transaction;
        });
    }

    /**
     * Process a payment (simulate payment gateway)
     */
    public function processPayment(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            // In a real implementation, this would call the payment gateway
            // For now, we simulate the processing

            // Update to processing status
            $processingStatus = TransactionStatus::where('slug', 'processing')->first();

            if ($processingStatus) {
                $this->transactionService->updateStatus($transaction, $processingStatus->slug);
            }

            // Simulate gateway response (in real world, this would be async via webhook)
            $success = $this->simulateGatewayResponse($transaction);

            if ($success) {
                $approvedStatus = TransactionStatus::where('slug', 'approved')->first();

                if ($approvedStatus) {
                    $this->transactionService->updateStatus($transaction, $approvedStatus->slug);
                }

                Log::channel('financial')->info('Payment processed successfully', [
                    'transaction_uuid' => $transaction->uuid,
                ]);
            } else {
                $failedStatus = TransactionStatus::where('slug', 'failed')->first();

                if ($failedStatus) {
                    $this->transactionService->updateStatus($transaction, $failedStatus->slug);
                }

                Log::channel('financial')->warning('Payment processing failed', [
                    'transaction_uuid' => $transaction->uuid,
                ]);
            }

            return $success;
        });
    }

    /**
     * Handle payment gateway callback/webhook
     */
    public function handleCallback(array $callbackData): ?Transaction
    {
        return DB::transaction(function () use ($callbackData) {
            // Find transaction by external_id or uuid
            $transaction = Transaction::where('external_id', $callbackData['external_id'] ?? null)
                ->orWhere('uuid', $callbackData['transaction_uuid'] ?? null)
                ->first();

            if (! $transaction) {
                Log::channel('integrations')->warning('Transaction not found in callback', $callbackData);

                return null;
            }

            // Map gateway status to internal status
            $newStatus = $this->mapGatewayStatusToInternal($callbackData['status'] ?? 'unknown');

            // Update transaction status
            $this->transactionService->updateStatus($transaction, $newStatus);

            // Update metadata with gateway response
            $transaction->update([
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'gateway_response' => $callbackData,
                    'callback_received_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::channel('integrations')->info('Payment callback processed', [
                'transaction_uuid' => $transaction->uuid,
                'status' => $newStatus,
            ]);

            return $transaction;
        });
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment(Transaction $transaction, string $reason): bool
    {
        return DB::transaction(function () use ($transaction, $reason) {
            // Check if transaction can be cancelled
            if ($transaction->transactionStatus->is_final) {
                throw new \Exception('Cannot cancel a finalized transaction');
            }

            // Cancel the transaction
            $this->transactionService->cancel($transaction, $reason);

            Log::channel('financial')->info('Payment cancelled', [
                'transaction_uuid' => $transaction->uuid,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Transaction $transaction, float $amount, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $amount, $reason) {
            // Validate amount
            if ($amount > $transaction->amount) {
                throw new \Exception('Refund amount cannot exceed original payment amount');
            }

            // Check if transaction can be refunded
            $approvedStatus = TransactionStatus::where('slug', 'approved')->first();

            if ($transaction->transaction_status_id !== $approvedStatus?->id) {
                throw new \Exception('Can only refund approved transactions');
            }

            // Process refund
            $refundTransaction = $this->transactionService->refund($transaction, $amount, $reason);

            Log::channel('financial')->info('Payment refunded', [
                'original_transaction_uuid' => $transaction->uuid,
                'refund_transaction_uuid' => $refundTransaction->uuid,
                'amount' => $amount,
            ]);

            return $refundTransaction;
        });
    }

    /**
     * Get transaction type for payment method
     */
    protected function getTransactionTypeForPayment(string $paymentMethod): TransactionType
    {
        // Map payment methods to transaction types
        $typeMap = [
            'pix' => 'payment_in',
            'credit_card' => 'payment_in',
            'debit_card' => 'payment_in',
            'bank_transfer' => 'payment_in',
            'boleto' => 'payment_in',
        ];

        $typeSlug = $typeMap[$paymentMethod] ?? 'payment_in';

        return TransactionType::where('slug', $typeSlug)->firstOrFail();
    }

    /**
     * Get or create wallet for account
     */
    protected function getOrCreateWallet(string $accountUuid, int $currencyId): Wallet
    {
        $wallet = Wallet::where('account_uuid', $accountUuid)
            ->where('currency_id', $currencyId)
            ->where('is_active', true)
            ->first();

        if (! $wallet) {
            $wallet = Wallet::create([
                'account_uuid' => $accountUuid,
                'currency_id' => $currencyId,
                'balance_available' => 0,
                'balance_held' => 0,
                'total_credited' => 0,
                'total_debited' => 0,
                'is_active' => true,
            ]);
        }

        return $wallet;
    }

    /**
     * Simulate gateway response (for development/testing)
     */
    protected function simulateGatewayResponse(Transaction $transaction): bool
    {
        // Simulate 95% success rate
        return rand(1, 100) <= 95;
    }

    /**
     * Map gateway status to internal status
     */
    protected function mapGatewayStatusToInternal(string $gatewayStatus): string
    {
        $statusMap = [
            'paid' => 'approved',
            'approved' => 'approved',
            'success' => 'approved',
            'pending' => 'pending',
            'processing' => 'processing',
            'failed' => 'failed',
            'error' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'expired' => 'expired',
        ];

        return $statusMap[$gatewayStatus] ?? 'pending';
    }

    /**
     * Get payment statistics for an account
     */
    public function getPaymentStatistics(string $accountUuid, ?string $period = null): array
    {
        $query = Transaction::where('account_uuid', $accountUuid)
            ->whereHas('transactionType', fn ($q) => $q->where('slug', 'like', 'payment%'));

        if ($period) {
            $query->where('created_at', '>=', match ($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            });
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_fees' => $query->sum('fee'),
            'net_amount' => $query->sum('net_amount'),
            'approved_count' => $query->clone()->whereHas('transactionStatus', fn ($q) => $q->where('slug', 'approved'))->count(),
            'pending_count' => $query->clone()->whereHas('transactionStatus', fn ($q) => $q->where('slug', 'pending'))->count(),
            'failed_count' => $query->clone()->whereHas('transactionStatus', fn ($q) => $q->where('slug', 'failed'))->count(),
        ];
    }
}
