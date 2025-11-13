<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
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
            'account_uuid' => $this->account_uuid,
            'url' => $this->url,
            'events' => $this->events,
            'is_active' => $this->is_active,
            'secret' => $this->when($request->user()?->isAdmin ?? false, $this->secret), // Only show to admins
            'last_triggered_at' => $this->last_triggered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'account' => $this->when($this->relationLoaded('account'), fn () => [
                'uuid' => $this->account->uuid,
                'name' => $this->account->name,
                'email' => $this->account->email,
            ]),

            // Counts
            'logs_count' => $this->whenCounted('webhookLogs'),
        ];
    }
}
