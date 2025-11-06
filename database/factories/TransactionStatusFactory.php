<?php

namespace Database\Factories;

use App\Models\Tenant\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionStatus>
 */
class TransactionStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TransactionStatus>
     */
    protected $model = TransactionStatus::class;

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
            'is_final' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the transaction status is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the transaction status is final.
     */
    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_final' => true,
        ]);
    }

    /**
     * Create a pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PENDING',
            'name' => 'Pending',
            'is_final' => false,
        ]);
    }

    /**
     * Create a completed status.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'COMPLETED',
            'name' => 'Completed',
            'is_final' => true,
        ]);
    }

    /**
     * Create a failed status.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'FAILED',
            'name' => 'Failed',
            'is_final' => true,
        ]);
    }
}
