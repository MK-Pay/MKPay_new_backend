<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'name' => $this->name,
            'phone' => $this->phone,
            'type' => [
                'name' => $this->accountType->name ?? null,
                'slug' => $this->accountType->slug ?? null,
            ],
            'status' => [
                'name' => $this->accountStatus->name ?? null,
                'slug' => $this->accountStatus->slug ?? null,
            ],
            'category' => $this->when($this->accountCategory, fn () => [
                'name' => $this->accountCategory->name,
                'slug' => $this->accountCategory->slug,
            ]),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Document info (masked for security)
            'document_type' => $this->cpf ? 'cpf' : ($this->cnpj ? 'cnpj' : null),
            'document_verified' => (bool) $this->verified_at,

            // Note: CPF/CNPJ values are NOT exposed for security reasons
            // Limits are NOT exposed for security reasons
        ];
    }
}
