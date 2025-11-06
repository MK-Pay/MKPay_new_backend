<?php

namespace Database\Factories;

use App\Models\Tenant\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Currency>
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->currencyCode(),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', 'R$']),
            'decimal_places' => 2,
            'is_active' => true,
            'is_national' => false,
        ];
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the currency is the national currency.
     */
    public function national(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_national' => true,
        ]);
    }

    /**
     * Create a BRL currency.
     */
    public function brl(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'BRL',
            'name' => 'Brazilian Real',
            'symbol' => 'R$',
            'decimal_places' => 2,
            'is_national' => true,
        ]);
    }

    /**
     * Create a USD currency.
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'United States Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);
    }
}
