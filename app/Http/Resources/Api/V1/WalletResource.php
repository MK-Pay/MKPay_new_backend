<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource is for Integration API - only public, non-sensitive data.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'currency' => [
                'code' => $this->currency->code ?? null,
                'symbol' => $this->currency->symbol ?? null,
                'name' => $this->currency->name ?? null,
            ],
            'balance_available' => $this->balance_available,
            'balance_held' => $this->balance_held,
            'balance_total' => $this->balance_available + $this->balance_held,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Statistics (when requested)
            'statistics' => $this->when($this->relationLoaded('walletBalances'), fn () => [
                'total_credited' => $this->total_credited,
                'total_debited' => $this->total_debited,
            ]),
        ];
    }
}
