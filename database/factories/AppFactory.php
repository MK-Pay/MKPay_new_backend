<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Tenant\App;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<App>
     */
    protected $model = App::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'settings' => [
                'webhook_enabled' => false,
                'auto_approve_transactions' => true,
            ],
        ];
    }

    /**
     * Indicate that the app is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the app has no settings.
     */
    public function noSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => null,
        ]);
    }

    /**
     * Indicate that the app has webhook enabled.
     */
    public function webhookEnabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], [
                'webhook_enabled' => true,
            ]),
        ]);
    }
}
