<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'name' => $this->name,
            'cpf' => $this->cpf,
            'cnpj' => $this->cnpj,
            'phone' => $this->phone,
            'usage_types' => $this->usage_types,
            'hourly_transaction_limit' => $this->hourly_transaction_limit,
            'daily_transaction_limit' => $this->daily_transaction_limit,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'account_type' => [
                'id' => $this->accountType->id,
                'name' => $this->accountType->name,
                'slug' => $this->accountType->slug,
            ],
            'account_status' => [
                'id' => $this->accountStatus->id,
                'name' => $this->accountStatus->name,
                'slug' => $this->accountStatus->slug,
            ],
            'account_category' => $this->when($this->accountCategory, fn () => [
                'id' => $this->accountCategory->id,
                'name' => $this->accountCategory->name,
                'slug' => $this->accountCategory->slug,
            ]),

            // Counts
            'apps_count' => $this->whenCounted('apps'),
            'wallets_count' => $this->whenCounted('wallets'),
            'transactions_count' => $this->whenCounted('transactions'),
            'webhooks_count' => $this->whenCounted('webhooks'),

            // Nested collections (when loaded)
            'apps' => AppResource::collection($this->whenLoaded('apps')),
            'wallets' => WalletResource::collection($this->whenLoaded('wallets')),
            'webhooks' => WebhookResource::collection($this->whenLoaded('webhooks')),
        ];
    }
}
