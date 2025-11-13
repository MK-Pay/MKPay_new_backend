<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSecretTokenResource extends JsonResource
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
            'name' => $this->name,
            'permissions' => $this->permissions,
            'is_active' => $this->is_active,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Note: token_hash is never exposed for security reasons
            // Plain text token is only shown once during creation

            // Relationships
            'app' => $this->when($this->relationLoaded('app'), fn () => [
                'app_id' => $this->app->app_id,
                'name' => $this->app->name,
            ]),
        ];
    }
}
