<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'app_id' => $this->app_id,
            'transaction_type_id' => $this->transaction_type_id,
            'transaction_status_id' => $this->transaction_status_id,
            'wallet_from_uuid' => $this->wallet_from_uuid,
            'wallet_to_uuid' => $this->wallet_to_uuid,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'net_amount' => $this->net_amount,
            'currency_id' => $this->currency_id,
            'payment_method' => $this->payment_method,
            'external_id' => $this->external_id,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'account' => $this->when($this->relationLoaded('account'), fn () => [
                'uuid' => $this->account->uuid,
                'name' => $this->account->name,
                'email' => $this->account->email,
            ]),
            'app' => $this->when($this->relationLoaded('app'), fn () => [
                'app_id' => $this->app->app_id,
                'name' => $this->app->name,
            ]),
            'transaction_type' => $this->when($this->relationLoaded('transactionType'), fn () => [
                'id' => $this->transactionType->id,
                'name' => $this->transactionType->name,
                'slug' => $this->transactionType->slug,
            ]),
            'transaction_status' => $this->when($this->relationLoaded('transactionStatus'), fn () => [
                'id' => $this->transactionStatus->id,
                'name' => $this->transactionStatus->name,
                'slug' => $this->transactionStatus->slug,
                'is_final' => $this->transactionStatus->is_final,
            ]),
            'wallet_from' => $this->when($this->relationLoaded('walletFrom'), fn () => [
                'uuid' => $this->walletFrom->uuid,
                'balance_available' => $this->walletFrom->balance_available,
            ]),
            'wallet_to' => $this->when($this->relationLoaded('walletTo'), fn () => [
                'uuid' => $this->walletTo->uuid,
                'balance_available' => $this->walletTo->balance_available,
            ]),
            'currency' => $this->when($this->relationLoaded('currency'), fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ]),
        ];
    }
}
