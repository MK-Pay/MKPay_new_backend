<?php

namespace Database\Factories;

use App\Models\Tenant\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Wallet>
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'app_id' => App::factory(),
            'currency_id' => Currency::factory(),
            'is_main' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the wallet is the main wallet.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the wallet has no app.
     */
    public function noApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'app_id' => null,
        ]);
    }
}
