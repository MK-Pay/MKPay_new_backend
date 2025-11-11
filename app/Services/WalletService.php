<?php

namespace App\Services;

use App\Models\Tenant\Transaction;
use App\Models\Tenant\Wallet;
use App\Models\Tenant\WalletBalance;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Credit (add) amount to wallet.
     *
     * @param  Wallet  $wallet  The wallet to credit
     * @param  float  $amount  Amount to add
     * @param  string  $description  Description of the operation
     * @param  Transaction|null  $transaction  Related transaction (optional)
     * @return WalletBalance The created balance record
     *
     * @throws \Exception
     */
    public function credit(Wallet $wallet, float $amount, string $description, ?Transaction $transaction = null): WalletBalance
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $transaction) {
            // Update wallet available balance
            $wallet->increment('available_balance', $amount);

            // Create balance history record
            return WalletBalance::create([
                'wallet_id' => $wallet->id,
                'transaction_id' => $transaction?->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $wallet->available_balance - $amount,
                'balance_after' => $wallet->available_balance,
                'description' => $description,
            ]);
        });
    }

    /**
     * Debit (subtract) amount from wallet.
     *
     * @param  Wallet  $wallet  The wallet to debit
     * @param  float  $amount  Amount to subtract
     * @param  string  $description  Description of the operation
     * @param  Transaction|null  $transaction  Related transaction (optional)
     * @return WalletBalance The created balance record
     *
     * @throws \Exception
     */
    public function debit(Wallet $wallet, float $amount, string $description, ?Transaction $transaction = null): WalletBalance
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive.');
        }

        if ($wallet->available_balance < $amount) {
            throw new \Exception('Insufficient balance. Available: ' . $wallet->available_balance . ', Required: ' . $amount);
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $transaction) {
            // Update wallet available balance
            $wallet->decrement('available_balance', $amount);

            // Create balance history record
            return WalletBalance::create([
                'wallet_id' => $wallet->id,
                'transaction_id' => $transaction?->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $wallet->available_balance + $amount,
                'balance_after' => $wallet->available_balance,
                'description' => $description,
            ]);
        });
    }

    /**
     * Hold (reserve) amount from available balance.
     * Moves amount from available_balance to held_balance.
     *
     * @param  Wallet  $wallet  The wallet to hold balance
     * @param  float  $amount  Amount to hold
     * @param  string  $reason  Reason for holding
     * @return WalletBalance The created balance record
     *
     * @throws \Exception
     */
    public function hold(Wallet $wallet, float $amount, string $reason): WalletBalance
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Hold amount must be positive.');
        }

        if ($wallet->available_balance < $amount) {
            throw new \Exception('Insufficient available balance to hold. Available: ' . $wallet->available_balance . ', Required: ' . $amount);
        }

        return DB::transaction(function () use ($wallet, $amount, $reason) {
            // Move from available to held
            $wallet->decrement('available_balance', $amount);
            $wallet->increment('held_balance', $amount);

            // Create balance history record
            return WalletBalance::create([
                'wallet_id' => $wallet->id,
                'transaction_id' => null,
                'type' => 'hold',
                'amount' => $amount,
                'balance_before' => $wallet->available_balance + $amount,
                'balance_after' => $wallet->available_balance,
                'description' => 'Hold: ' . $reason,
            ]);
        });
    }

    /**
     * Release held amount back to available balance.
     *
     * @param  Wallet  $wallet  The wallet to release hold
     * @param  float  $amount  Amount to release
     * @param  string  $reason  Reason for releasing
     * @return WalletBalance The created balance record
     *
     * @throws \Exception
     */
    public function releaseHold(Wallet $wallet, float $amount, string $reason): WalletBalance
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Release amount must be positive.');
        }

        if ($wallet->held_balance < $amount) {
            throw new \Exception('Insufficient held balance to release. Held: ' . $wallet->held_balance . ', Required: ' . $amount);
        }

        return DB::transaction(function () use ($wallet, $amount, $reason) {
            // Move from held to available
            $wallet->decrement('held_balance', $amount);
            $wallet->increment('available_balance', $amount);

            // Create balance history record
            return WalletBalance::create([
                'wallet_id' => $wallet->id,
                'transaction_id' => null,
                'type' => 'release',
                'amount' => $amount,
                'balance_before' => $wallet->available_balance - $amount,
                'balance_after' => $wallet->available_balance,
                'description' => 'Release hold: ' . $reason,
            ]);
        });
    }

    /**
     * Transfer amount from one wallet to another.
     *
     * @param  Wallet  $fromWallet  Source wallet
     * @param  Wallet  $toWallet  Destination wallet
     * @param  float  $amount  Amount to transfer
     * @param  string  $description  Description of transfer
     * @param  Transaction|null  $transaction  Related transaction (optional)
     * @return array Array with 'from' and 'to' WalletBalance records
     *
     * @throws \Exception
     */
    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, string $description, ?Transaction $transaction = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same wallet.');
        }

        if ($fromWallet->currency_id !== $toWallet->currency_id) {
            throw new \Exception('Currency mismatch. Cannot transfer between different currencies without exchange.');
        }

        if ($fromWallet->available_balance < $amount) {
            throw new \Exception('Insufficient balance in source wallet. Available: ' . $fromWallet->available_balance . ', Required: ' . $amount);
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description, $transaction) {
            // Debit from source wallet
            $fromBalance = $this->debit($fromWallet, $amount, 'Transfer out: ' . $description, $transaction);

            // Credit to destination wallet
            $toBalance = $this->credit($toWallet, $amount, 'Transfer in: ' . $description, $transaction);

            return [
                'from' => $fromBalance,
                'to' => $toBalance,
            ];
        });
    }

    /**
     * Adjust wallet balance manually (admin operation).
     * Used for corrections, refunds, or manual adjustments.
     *
     * @param  Wallet  $wallet  The wallet to adjust
     * @param  float  $amount  Amount to adjust (positive for credit, negative for debit)
     * @param  string  $reason  Reason for adjustment
     * @return WalletBalance The created balance record
     *
     * @throws \Exception
     */
    public function adjust(Wallet $wallet, float $amount, string $reason): WalletBalance
    {
        if ($amount == 0) {
            throw new \InvalidArgumentException('Adjustment amount cannot be zero.');
        }

        return DB::transaction(function () use ($wallet, $amount, $reason) {
            $balanceBefore = $wallet->available_balance;

            if ($amount > 0) {
                // Positive adjustment (credit)
                $wallet->increment('available_balance', $amount);
                $type = 'adjustment_credit';
            } else {
                // Negative adjustment (debit)
                $absAmount = abs($amount);

                if ($wallet->available_balance < $absAmount) {
                    throw new \Exception('Insufficient balance for negative adjustment. Available: ' . $wallet->available_balance . ', Required: ' . $absAmount);
                }
                $wallet->decrement('available_balance', $absAmount);
                $type = 'adjustment_debit';
            }

            // Create balance history record
            return WalletBalance::create([
                'wallet_id' => $wallet->id,
                'transaction_id' => null,
                'type' => $type,
                'amount' => abs($amount),
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->available_balance,
                'description' => 'Manual adjustment: ' . $reason,
            ]);
        });
    }

    /**
     * Get wallet balance history with filters.
     *
     * @param  Wallet  $wallet  The wallet
     * @param  array  $filters  Filters (type, date_from, date_to)
     * @param  int  $perPage  Results per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getBalanceHistory(Wallet $wallet, array $filters = [], int $perPage = 20)
    {
        $query = WalletBalance::where('wallet_id', $wallet->id)
            ->with(['transaction']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
