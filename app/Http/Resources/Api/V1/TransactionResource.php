<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'amount' => $this->amount,
            'fee' => $this->fee,
            'net_amount' => $this->net_amount,
            'currency' => $this->currency->code ?? null,
            'payment_method' => $this->payment_method,
            'external_id' => $this->external_id,
            'description' => $this->description,
            'type' => [
                'name' => $this->transactionType->name ?? null,
                'slug' => $this->transactionType->slug ?? null,
            ],
            'status' => [
                'name' => $this->transactionStatus->name ?? null,
                'slug' => $this->transactionStatus->slug ?? null,
                'is_final' => $this->transactionStatus->is_final ?? false,
            ],
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Metadata (sanitized)
            'metadata' => $this->when($this->metadata, function () {
                $metadata = $this->metadata ?? [];
                $sensitiveKeys = ['card_number', 'cvv', 'password', 'token', 'secret'];

                foreach ($sensitiveKeys as $key) {
                    unset($metadata[$key]);
                }

                return $metadata;
            }),
        ];
    }
}
