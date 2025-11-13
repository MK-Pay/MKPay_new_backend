<?php

namespace Database\Factories;

use App\Models\AccountStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountStatus>
 */
class AccountStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AccountStatus>
     */
    protected $model = AccountStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'allows_transactions' => true,
            'allows_withdrawals' => true,
            'allows_transfers' => true,
            'holds_funds' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the account status is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account status holds funds.
     */
    public function holdsFunds(): static
    {
        return $this->state(fn (array $attributes) => [
            'holds_funds' => true,
            'allows_transactions' => false,
            'allows_withdrawals' => false,
            'allows_transfers' => false,
        ]);
    }

    /**
     * Indicate that the account status is restricted.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'allows_transactions' => false,
            'allows_withdrawals' => false,
            'allows_transfers' => false,
        ]);
    }
}
