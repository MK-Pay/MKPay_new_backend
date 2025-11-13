<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
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
            'app_id' => $this->app_id,
            'account_uuid' => $this->account_uuid,
            'name' => $this->name,
            'description' => $this->description,
            'webhook_url' => $this->webhook_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'account' => $this->when($this->relationLoaded('account'), fn () => [
                'uuid' => $this->account->uuid,
                'name' => $this->account->name,
                'email' => $this->account->email,
            ]),

            // Counts
            'tokens_count' => $this->whenCounted('appSecretTokens'),
            'transactions_count' => $this->whenCounted('transactions'),

            // Nested collections (when loaded)
            'tokens' => AppSecretTokenResource::collection($this->whenLoaded('appSecretTokens')),
        ];
    }
}
