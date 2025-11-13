<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'account_uuid' => $this->account_uuid,
            'currency_id' => $this->currency_id,
            'balance_available' => $this->balance_available,
            'balance_held' => $this->balance_held,
            'total_credited' => $this->total_credited,
            'total_debited' => $this->total_debited,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'account' => $this->when($this->relationLoaded('account'), fn () => [
                'uuid' => $this->account->uuid,
                'name' => $this->account->name,
                'email' => $this->account->email,
            ]),
            'currency' => $this->when($this->relationLoaded('currency'), fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
                'name' => $this->currency->name,
            ]),

            // Counts
            'balances_count' => $this->whenCounted('walletBalances'),
            'transactions_in_count' => $this->whenCounted('transactionsIn'),
            'transactions_out_count' => $this->whenCounted('transactionsOut'),

            // Computed values
            'balance_total' => $this->balance_available + $this->balance_held,
        ];
    }
}
