<?php

namespace Database\Factories;

use App\Models\Tenant\App;
use App\Models\Tenant\AppSecretToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AppSecretToken>
 */
class AppSecretTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AppSecretToken>
     */
    protected $model = AppSecretToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'name' => fake()->words(3, true),
            'token_hash' => Hash::make('test-token-' . fake()->uuid()),
            'permissions' => ['read', 'write'],
            'is_active' => true,
            'last_used_at' => null,
            'expires_at' => now()->addYear(),
        ];
    }

    /**
     * Indicate that the token is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now(),
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the token has no expiration.
     */
    public function noExpiration(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the token has read-only permissions.
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => ['read'],
        ]);
    }

    /**
     * Indicate that the token has full permissions.
     */
    public function fullPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => ['read', 'write', 'delete', 'admin'],
        ]);
    }
}
